<?php
/**
 * Kredi kartı ödemesi – iyzico Ödeme Formu ve PayTR iFrame API.
 * Kart bilgileri SİTEMİZE GİRİLMEZ; ödeme sağlayıcının güvenli sayfasında alınır (PCI-DSS).
 */
function pos_config(): array
{
    $pos = setting('pos', []);
    $p = $pos['provider'] ?? 'none';
    $ready = ($p === 'iyzico' && !empty($pos['iyzicoApiKey']) && !empty($pos['iyzicoSecret']))
          || ($p === 'paytr' && !empty($pos['paytrMerchantId']) && !empty($pos['paytrKey']) && !empty($pos['paytrSalt']));
    return $pos + ['provider' => $p, 'ready' => $ready];
}

function site_origin(): string
{
    $https = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') || (($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https');
    return ($https ? 'https' : 'http') . '://' . ($_SERVER['HTTP_HOST'] ?? 'localhost');
}

function client_ip(): string
{
    $ip = $_SERVER['HTTP_CF_CONNECTING_IP'] ?? ($_SERVER['HTTP_X_FORWARDED_FOR'] ?? ($_SERVER['REMOTE_ADDR'] ?? '127.0.0.1'));
    return trim(explode(',', $ip)[0]);
}

function payment_log(int $orderId, string $provider, float $amount, string $status, ?string $tx, $raw): void
{
    db()->prepare('INSERT INTO payments (order_id, provider, amount, status, transaction_id, raw) VALUES (?,?,?,?,?,?)')
        ->execute([$orderId, $provider, $amount, $status, $tx, json_encode($raw, JSON_UNESCAPED_UNICODE)]);
}

function http_post(string $url, $body, array $headers = []): array
{
    $ch = curl_init($url);
    curl_setopt_array($ch, [CURLOPT_POST => true, CURLOPT_POSTFIELDS => $body, CURLOPT_RETURNTRANSFER => true, CURLOPT_TIMEOUT => 30, CURLOPT_HTTPHEADER => $headers]);
    $res = curl_exec($ch); $err = curl_error($ch); curl_close($ch);
    if ($res === false) return ['status' => 'failure', 'errorMessage' => 'Bağlantı hatası: ' . $err];
    $j = json_decode($res, true);
    return is_array($j) ? $j : ['status' => 'failure', 'errorMessage' => 'Geçersiz yanıt', 'raw' => mb_substr($res, 0, 500)];
}

/* ───── iyzico ───── */
function iyzico_request(string $path, array $body): array
{
    $pos = pos_config();
    $base = !empty($pos['iyzicoSandbox']) ? 'https://sandbox-api.iyzipay.com' : 'https://api.iyzipay.com';
    $json = json_encode($body, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    $rnd = (string)(int)(microtime(true) * 1000) . random_int(100000, 999999);
    $sig = hash_hmac('sha256', $rnd . $path . $json, $pos['iyzicoSecret']);
    $auth = 'IYZWSv2 ' . base64_encode('apiKey:' . $pos['iyzicoApiKey'] . '&randomKey:' . $rnd . '&signature:' . $sig);
    return http_post($base . $path, $json, ['Content-Type: application/json', 'Accept: application/json', 'Authorization: ' . $auth, 'x-iyzi-rnd: ' . $rnd]);
}

function iyzico_price(float $v): string { $s = rtrim(rtrim(number_format($v, 2, '.', ''), '0'), '.'); return strpos($s, '.') === false ? $s . '.0' : $s; }

function iyzico_init(array $o): array
{
    $pos = pos_config();
    $addr = json_decode($o['shipping_address'], true) ?: []; $bill = json_decode($o['billing_address'], true) ?: $addr;
    $parts = preg_split('/\s+/', trim($o['customer_name'])); $surname = count($parts) > 1 ? array_pop($parts) : '-'; $name = implode(' ', $parts) ?: $o['customer_name'];
    $max = (int)($pos['maxInstallment'] ?? 9);
    $inst = array_values(array_filter([1, 2, 3, 6, 9, 12], function ($i) use ($max) { return $i <= max(1, $max); }));
    $price = iyzico_price((float)$o['total']);
    $body = [
        'locale' => locale() === 'tr' ? 'tr' : 'en', 'conversationId' => $o['order_no'], 'price' => $price, 'paidPrice' => $price, 'currency' => 'TRY',
        'basketId' => $o['order_no'], 'paymentGroup' => 'PRODUCT', 'enabledInstallments' => $inst,
        'callbackUrl' => site_origin() . url('/odeme/iyzico-donus') . '?no=' . rawurlencode($o['order_no']),
        'buyer' => ['id' => 'U' . ($o['user_id'] ?: $o['id']), 'name' => $name, 'surname' => $surname, 'gsmNumber' => '+90' . substr(preg_replace('/\D/', '', $o['phone']), -10),
            'email' => $o['email'], 'identityNumber' => preg_match('/^\d{11}$/', $bill['tcNo'] ?? '') ? $bill['tcNo'] : '11111111111',
            'registrationAddress' => $addr['address'] ?? '-', 'ip' => client_ip(), 'city' => $addr['city'] ?? 'Istanbul', 'country' => 'Turkey'],
        'shippingAddress' => ['contactName' => $o['customer_name'], 'city' => $addr['city'] ?? '-', 'country' => 'Turkey', 'address' => trim(($addr['address'] ?? '') . ' ' . ($addr['district'] ?? ''))],
        'billingAddress' => ['contactName' => $bill['companyName'] ?? $o['customer_name'], 'city' => $bill['city'] ?? ($addr['city'] ?? '-'), 'country' => 'Turkey', 'address' => trim(($bill['address'] ?? '') . ' ' . ($bill['district'] ?? ''))],
        'basketItems' => [['id' => $o['order_no'], 'name' => 'Sipariş ' . $o['order_no'], 'category1' => 'Yedek Parça', 'itemType' => 'PHYSICAL', 'price' => $price]],
    ];
    return iyzico_request('/payment/iyzipos/checkoutform/initialize/auth/ecom', $body);
}

function iyzico_retrieve(string $token, string $orderNo): array
{
    return iyzico_request('/payment/iyzipos/checkoutform/auth/ecom/detail', ['locale' => 'tr', 'conversationId' => $orderNo, 'token' => $token]);
}

/* ───── PayTR ───── */
function paytr_token(array $o): array
{
    $pos = pos_config();
    $items = db()->query('SELECT name, unit_price, quantity FROM order_items WHERE order_id = ' . (int)$o['id'])->fetchAll();
    $gross = function ($i) use ($o) { return round((float)$i['unit_price'] * ((float)($o['vat_total'] ?? 0) > 0 ? 1 + (int)$i['vat_rate'] / 100 : 1), 2); };
    $basket = array_map(function ($i) use ($gross) { return [$i['name'], number_format($gross($i), 2, '.', ''), (int)$i['quantity']]; }, $items);
    $extra = round((float)$o['total'] - array_sum(array_map(function ($i) use ($gross) { return $gross($i) * $i['quantity']; }, $items)), 2);
    if (abs($extra) >= 0.01) $basket[] = [$extra > 0 ? 'Kargo / Hizmet' : 'İndirim / Yuvarlama', number_format($extra, 2, '.', ''), 1];
    $userBasket = base64_encode(json_encode($basket, JSON_UNESCAPED_UNICODE));
    $mid = $pos['paytrMerchantId']; $ip = client_ip(); $oid = $o['order_no']; $email = $o['email'];
    $amount = (int)round((float)$o['total'] * 100); $noInst = 0; $maxInst = (int)($pos['maxInstallment'] ?? 0); $cur = 'TL'; $test = !empty($pos['paytrTest']) ? 1 : 0;
    $hash = base64_encode(hash_hmac('sha256', $mid . $ip . $oid . $email . $amount . $userBasket . $noInst . $maxInst . $cur . $test . $pos['paytrSalt'], $pos['paytrKey'], true));
    $addr = json_decode($o['shipping_address'], true) ?: [];
    $back = site_origin() . url('/siparis-tamamlandi/' . $oid);
    return http_post('https://www.paytr.com/odeme/api/get-token', [
        'merchant_id' => $mid, 'user_ip' => $ip, 'merchant_oid' => $oid, 'email' => $email, 'payment_amount' => $amount, 'paytr_token' => $hash,
        'user_basket' => $userBasket, 'debug_on' => $test, 'no_installment' => $noInst, 'max_installment' => $maxInst, 'user_name' => $o['customer_name'],
        'user_address' => trim(($addr['address'] ?? '') . ' ' . ($addr['district'] ?? '') . ' ' . ($addr['city'] ?? '')), 'user_phone' => $o['phone'],
        'merchant_ok_url' => $back . '?odeme=kontrol', 'merchant_fail_url' => $back . '?odeme=hata', 'timeout_limit' => 30, 'currency' => $cur, 'test_mode' => $test, 'lang' => locale() === 'tr' ? 'tr' : 'en',
    ]);
}

/** Ödeme başarılı: siparişi ödendi yap */
function mark_order_paid(array $o, string $provider, ?string $tx, $raw): void
{
    if ($o['payment_status'] === 'paid') return;
    db()->prepare("UPDATE orders SET payment_status = 'paid', status = IF(status = 'pending', 'preparing', status) WHERE id = ?")->execute([$o['id']]);
    payment_log((int)$o['id'], $provider, (float)$o['total'], 'success', $tx, $raw);
}
function mark_order_failed(array $o, string $provider, $raw): void
{
    if ($o['payment_status'] === 'paid') return;
    db()->prepare("UPDATE orders SET payment_status = 'failed' WHERE id = ?")->execute([$o['id']]);
    payment_log((int)$o['id'], $provider, (float)$o['total'], 'failed', null, $raw);
}
