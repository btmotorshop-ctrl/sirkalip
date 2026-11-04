<?php
$u = customer();
$pay = setting('payment', []);
$pos = setting('pos', []);
$cardReady = !empty($pay['card']) && pos_config()['ready'];
$methods = [];
if ($cardReady) $methods['card'] = L('Kredi / Banka Kartı', 'Credit / Debit Card');
if (!empty($pay['transfer'])) $methods['transfer'] = L('Havale / EFT', 'Bank Transfer');
if (!empty($pay['cod'])) $methods['cod'] = L('Kapıda Ödeme', 'Cash on Delivery');
if (!$methods) $methods['transfer'] = L('Havale / EFT', 'Bank Transfer');
$method = $_POST['payment_method'] ?? array_key_first($methods);
if (!isset($methods[$method])) $method = array_key_first($methods);
$t = cart_totals($method);
if (!$t['items']) go(url('/sepet'));
$addresses = $u ? db()->query('SELECT * FROM addresses WHERE user_id = ' . (int)$u['id'] . ' ORDER BY id DESC')->fetchAll() : [];
$d = $_POST + ($addresses ? ['full_name' => $addresses[0]['full_name'], 'phone' => $addresses[0]['phone'], 'city' => $addresses[0]['city'], 'district' => $addresses[0]['district'], 'address' => $addresses[0]['address']] : [])
    + ($u ? ['full_name' => $u['first_name'] . ' ' . $u['last_name'], 'email' => $u['email'], 'phone' => $u['phone']] : []);
$errors = [];
$minOrder = (float)($pay['minOrder'] ?? 0);

if (is_post() && ($_POST['step'] ?? '') === 'place') {
    if (!csrf_ok()) $errors['_'] = L('Oturum süresi doldu, tekrar deneyin.', 'Session expired, try again.');
    $req = ['full_name' => L('Ad soyad', 'Full name'), 'email' => L('E-posta', 'Email'), 'phone' => L('Telefon', 'Phone'), 'city' => L('İl', 'City'), 'district' => L('İlçe', 'District'), 'address' => L('Adres', 'Address')];
    foreach ($req as $k => $l) if (trim($_POST[$k] ?? '') === '') $errors[$k] = $l . ' ' . L('zorunludur', 'is required');
    if (!empty($_POST['email']) && !filter_var($_POST['email'], FILTER_VALIDATE_EMAIL)) $errors['email'] = L('Geçerli bir e-posta girin', 'Enter a valid email');
    if (!empty($_POST['phone']) && strlen(preg_replace('/\D/', '', $_POST['phone'])) < 10) $errors['phone'] = L('Geçerli bir telefon girin', 'Enter a valid phone');
    if (!empty($_POST['corporate']) && (trim($_POST['company_name'] ?? '') === '' || trim($_POST['tax_number'] ?? '') === '')) $errors['company_name'] = L('Firma adı ve vergi numarası zorunludur', 'Company name and tax number required');
    if (empty($_POST['agree'])) $errors['agree'] = L('Sözleşmeleri onaylamanız gerekiyor', 'You must accept the agreements');
    if ($minOrder > 0 && $t['subtotal'] < $minOrder) $errors['_'] = L('Minimum sipariş tutarı: ', 'Minimum order: ') . money($minOrder);
    if (!empty($_POST['create_account']) && !$u) {
        if (strlen($_POST['password'] ?? '') < 6) $errors['password'] = L('Şifre en az 6 karakter olmalı', 'Password must be at least 6 characters');
        $ex = db()->prepare('SELECT id FROM users WHERE email = ?'); $ex->execute([trim($_POST['email'] ?? '')]);
        if ($ex->fetchColumn()) $errors['password'] = L('Bu e-posta ile kayıtlı bir hesap var. Giriş yapın.', 'An account with this email exists. Please log in.');
    }
    if (!$errors) {
        $pdo = db();
        try {
            $pdo->beginTransaction();
            // stok kontrolü (kilitleyerek)
            foreach ($t['items'] as $it) {
                $s = $pdo->prepare('SELECT stock FROM products WHERE id = ? FOR UPDATE'); $s->execute([$it['p']['id']]);
                if ((int)$s->fetchColumn() < $it['qty']) throw new RuntimeException(tr_field($it['p'], 'name') . ': ' . L('yeterli stok yok', 'insufficient stock'));
            }
            $uid = $u['id'] ?? null;
            if (!$uid && !empty($_POST['create_account'])) {
                $parts = preg_split('/\s+/', trim($_POST['full_name'])); $ln = count($parts) > 1 ? array_pop($parts) : ''; $fn = implode(' ', $parts);
                $pdo->prepare("INSERT INTO users (role, first_name, last_name, email, phone, password_hash) VALUES ('customer',?,?,?,?,?)")->execute([$fn, $ln, trim($_POST['email']), trim($_POST['phone']), password_hash($_POST['password'], PASSWORD_DEFAULT)]);
                $uid = (int)$pdo->lastInsertId();
            }
            $addr = ['fullName' => trim($_POST['full_name']), 'phone' => trim($_POST['phone']), 'city' => $_POST['city'], 'district' => trim($_POST['district']), 'address' => trim($_POST['address']), 'postalCode' => trim($_POST['postal_code'] ?? '')];
            $bill = $addr + (!empty($_POST['corporate']) ? ['companyName' => trim($_POST['company_name']), 'taxOffice' => trim($_POST['tax_office'] ?? ''), 'taxNumber' => trim($_POST['tax_number'])] : ['tcNo' => trim($_POST['tc_no'] ?? '')]);
            if ($uid && !empty($_POST['save_address'])) {
                $pdo->prepare('INSERT INTO addresses (user_id, title, full_name, phone, city, district, address, postal_code) VALUES (?,?,?,?,?,?,?,?)')->execute([$uid, L('Teslimat Adresi', 'Delivery'), $addr['fullName'], $addr['phone'], $addr['city'], $addr['district'], $addr['address'], $addr['postalCode']]);
            }
            do { $no = 'SK' . date('ymd') . random_int(1000, 9999); $c = $pdo->prepare('SELECT 1 FROM orders WHERE order_no = ?'); $c->execute([$no]); } while ($c->fetchColumn());
            $pdo->prepare('INSERT INTO orders (order_no, user_id, status, payment_status, payment_method, source, customer_name, email, phone, shipping_address, billing_address, subtotal, discount, shipping_fee, vat_total, total, coupon_code, note) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)')
                ->execute([$no, $uid, 'pending', 'waiting', $method, preg_match('/Mobile|Android|iPhone/i', $_SERVER['HTTP_USER_AGENT'] ?? '') ? 'mobile' : 'web', $addr['fullName'], trim($_POST['email']), $addr['phone'],
                    json_encode($addr, JSON_UNESCAPED_UNICODE), json_encode($bill, JSON_UNESCAPED_UNICODE), $t['subtotal'], $t['discount'], $t['shipping'] + $t['cod_fee'], $t['vat'] ?? 0, $t['total'], !empty($t['coupon']) && empty($t['coupon']['_min_not_met']) ? $t['coupon']['code'] : null, trim($_POST['note'] ?? '') ?: null]);
            $oid = (int)$pdo->lastInsertId();
            foreach ($t['items'] as $it) {
                $p = $it['p'];
                $pdo->prepare('INSERT INTO order_items (order_id, product_id, code, name, unit_price, quantity, vat_rate, total) VALUES (?,?,?,?,?,?,?,?)')->execute([$oid, $p['id'], $p['code'], $p['name_tr'], $it['price'], $it['qty'], $p['vat_rate'], $it['total']]);
                $pdo->prepare('UPDATE products SET stock = stock - ?, sales_count = sales_count + ? WHERE id = ?')->execute([$it['qty'], $it['qty'], $p['id']]);
                $pdo->prepare("INSERT INTO stock_movements (product_id, kind, quantity, note, order_id) VALUES (?, 'order', ?, ?, ?)")->execute([$p['id'], $it['qty'], 'Sipariş ' . $no, $oid]);
            }
            if (!empty($t['coupon']) && empty($t['coupon']['_min_not_met'])) $pdo->prepare('UPDATE coupons SET used_count = used_count + 1 WHERE code = ?')->execute([$t['coupon']['code']]);
            $pdo->commit();
            if ($uid && !$u) { $s = $pdo->prepare('SELECT * FROM users WHERE id = ?'); $s->execute([$uid]); customer_login($s->fetch()); }
            unset($_SESSION['cart'], $_SESSION['coupon']);
            $_SESSION['last_order'] = $no;
            go($method === 'card' ? url('/odeme/kart/' . $no) : url('/siparis-tamamlandi/' . $no));
        } catch (Throwable $e) {
            if ($pdo->inTransaction()) $pdo->rollBack();
            $errors['_'] = $e instanceof RuntimeException ? $e->getMessage() : L('Sipariş oluşturulamadı: ', 'Order failed: ') . $e->getMessage();
        }
    }
}
$cities = require ROOT . '/app/cities.php';
view('layout/top', ['title' => L('Ödeme', 'Checkout'), 'minimal' => true]);
view('checkout', compact('t', 'd', 'errors', 'methods', 'method', 'u', 'addresses', 'cities', 'pay'));
view('layout/bottom', ['minimal' => true]);
