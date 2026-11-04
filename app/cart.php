<?php
/* Sepet (oturumda) + fiyat/kampanya hesaplama */
function cart_items(): array
{
    $cart = $_SESSION['cart'] ?? [];
    if (!$cart) return [];
    $codes = array_keys($cart);
    $st = db()->prepare('SELECT p.*, c.slug category_slug FROM products p JOIN categories c ON c.id = p.category_id WHERE p.code IN (' . implode(',', array_fill(0, count($codes), '?')) . ') AND p.is_active = 1 AND p.deleted_at IS NULL');
    $st->execute($codes);
    $items = [];
    foreach ($st->fetchAll() as $p) {
        $qty = min((int)$cart[$p['code']], max(0, (int)$p['stock']));
        if ($qty <= 0) { unset($_SESSION['cart'][$p['code']]); continue; }
        [$price] = product_prices($p);
        $items[] = ['p' => $p, 'qty' => $qty, 'price' => $price, 'total' => $price * $qty];
    }
    return $items;
}

function cart_set(string $code, int $qty): void
{
    if ($qty <= 0) { unset($_SESSION['cart'][$code]); return; }
    $st = db()->prepare('SELECT stock FROM products WHERE code = ? AND is_active = 1 AND deleted_at IS NULL');
    $st->execute([$code]);
    $stock = $st->fetchColumn();
    if ($stock === false || (int)$stock <= 0) { unset($_SESSION['cart'][$code]); return; }
    $_SESSION['cart'][$code] = min($qty, (int)$stock);
}

function active_campaigns(): array
{
    static $c = null;
    if ($c === null) $c = db()->query('SELECT * FROM campaigns WHERE is_active = 1 AND (starts_at IS NULL OR starts_at <= NOW()) AND (ends_at IS NULL OR ends_at >= NOW())')->fetchAll();
    return $c;
}

function find_coupon(string $code): ?array
{
    $st = db()->prepare('SELECT * FROM coupons WHERE code = ? AND is_active = 1 AND (starts_at IS NULL OR starts_at <= NOW()) AND (ends_at IS NULL OR ends_at >= NOW()) AND (usage_limit IS NULL OR used_count < usage_limit)');
    $st->execute([trim($code)]);
    return $st->fetch() ?: null;
}

/** Sepet toplamları. $method: card|transfer|cod */
function cart_totals(?string $method = null): array
{
    $items = cart_items();
    $sub = array_sum(array_column($items, 'total'));
    $lines = []; $discount = 0.0; $freeShip = false;
    $in = function ($c, $p) {
        $ids = json_decode((string)$c['scope_ids'], true) ?: [];
        switch ($c['scope']) {
            case 'categories': return in_array((int)$p['category_id'], $ids, true);
            case 'brands': return in_array((int)$p['brand_id'], $ids, true);
            case 'products': return in_array((int)$p['id'], $ids, true);
            default: return true;
        }
    };
    foreach (active_campaigns() as $c) {
        $rules = json_decode((string)$c['rules'], true) ?: [];
        $val = (float)$c['discount_value']; $d = 0.0;
        switch ($c['kind']) {
            case 'product':
                foreach ($items as $it) if ($in($c, $it['p'])) $d += $c['discount_type'] === 'percent' ? $it['total'] * $val / 100 : min($it['total'], $val * $it['qty']);
                break;
            case 'cart_total':
                if ($sub >= (float)($rules['min_total'] ?? 0)) $d = $c['discount_type'] === 'percent' ? $sub * $val / 100 : $val;
                break;
            case 'quantity_range':
                foreach ($items as $it) if ($in($c, $it['p']) && $it['qty'] >= (int)($rules['min_qty'] ?? 2) && $it['qty'] <= (int)($rules['max_qty'] ?? 9999)) $d += $c['discount_type'] === 'percent' ? $it['total'] * $val / 100 : $val * $it['qty'];
                break;
            case 'payment_type':
                if ($method && $method === ($rules['method'] ?? 'transfer')) $d = $c['discount_type'] === 'percent' ? $sub * $val / 100 : $val;
                break;
            case 'shipping':
                if ($sub >= (float)($rules['min_total'] ?? 0)) $freeShip = true;
                break;
            case 'buy_x_pay_y':
                $x = (int)($rules['x'] ?? 3); $y = (int)($rules['y'] ?? 2);
                foreach ($items as $it) if ($x > $y && $in($c, $it['p'])) $d += intdiv($it['qty'], $x) * ($x - $y) * $it['price'];
                break;
        }
        if ($d > 0) { $d = round(min($d, $sub - $discount), 2); $discount += $d; $lines[] = [$c['name'], $d]; }
    }
    // Kupon
    $coupon = null;
    if (!empty($_SESSION['coupon']) && ($coupon = find_coupon($_SESSION['coupon']))) {
        if ($sub >= (float)($coupon['min_cart_total'] ?? 0)) {
            $d = $coupon['discount_type'] === 'percent' ? $sub * (float)$coupon['discount_value'] / 100 : (float)$coupon['discount_value'];
            $d = round(min($d, $sub - $discount), 2);
            if ($d > 0) { $discount += $d; $lines[] = [L('Kupon', 'Coupon') . ' (' . $coupon['code'] . ')', $d]; }
        } else $coupon['_min_not_met'] = true;
    }
    // Havale indirimi (ayar)
    $pay = setting('payment', []);
    if ($method === 'transfer' && !empty($pay['transferDiscount'])) {
        $d = round(($sub - $discount) * (float)$pay['transferDiscount'] / 100, 2);
        if ($d > 0) { $discount += $d; $lines[] = [L('Havale/EFT indirimi', 'Bank transfer discount') . ' (%' . (float)$pay['transferDiscount'] . ')', $d]; }
    }
    // KDV (fiyatlar KDV hariç girildiyse sepette ayrı satır olarak eklenir; indirimler orantılı düşülür)
    $vatBy = [];
    if (prices_ex_vat() && $sub > 0) {
        $ratio = max(0, $sub - $discount) / $sub;
        foreach ($items as $it) { $r = (int)$it['p']['vat_rate']; if ($r > 0) $vatBy[$r] = ($vatBy[$r] ?? 0) + $it['total'] * $ratio * $r / 100; }
        ksort($vatBy);
        foreach ($vatBy as $r => $v) $vatBy[$r] = round($v, 2);
    }
    $vat = round(array_sum($vatBy), 2);
    $goods = max(0, $sub - $discount) + $vat; // müşterinin ödeyeceği ürün tutarı
    $ship = setting('shipping', []);
    $limit = (float)($ship['freeShippingLimit'] ?? 0);
    $fee = (float)($ship['defaultFee'] ?? 0);
    if ($freeShip || ($limit > 0 && $goods >= $limit) || !$items) $fee = 0.0;
    foreach ($items as $it) if (!empty($it['p']['free_shipping'])) { $fee = 0.0; break; }
    $codFee = $method === 'cod' ? (float)($pay['codFee'] ?? 0) : 0.0;
    $total = $goods + $fee + $codFee;
    return ['items' => $items, 'subtotal' => $sub, 'discount' => round($discount, 2), 'lines' => $lines, 'shipping' => $fee, 'cod_fee' => $codFee,
        'vat' => $vat, 'vat_lines' => $vatBy, 'ex_vat' => prices_ex_vat(),
        'total' => round($total, 2), 'free_limit' => $limit, 'remaining_free' => $limit > 0 ? max(0, $limit - $goods) : 0, 'coupon' => $coupon, 'count' => array_sum(array_column($items, 'qty'))];
}
