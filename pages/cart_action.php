<?php
$act = $params[1];
if ($act === 'ozet') { view('partials/mini_cart'); exit; }
$json = strpos($_SERVER['HTTP_ACCEPT'] ?? '', 'application/json') !== false || !empty($_SERVER['HTTP_X_REQUESTED_WITH']);
$msg = null; $ok = true;
switch ($act) {
    case 'ekle':
        $code = (string)($_POST['code'] ?? ''); $qty = max(1, (int)($_POST['qty'] ?? 1));
        $before = $_SESSION['cart'][$code] ?? 0;
        cart_set($code, $before + $qty);
        $ok = isset($_SESSION['cart'][$code]);
        $msg = $ok ? (($_SESSION['cart'][$code] - $before) < $qty ? L('Stok sınırına ulaşıldı, mevcut stok kadar eklendi.', 'Stock limit reached.') : L('Ürün sepete eklendi', 'Added to cart')) : L('Ürün stokta yok', 'Out of stock');
        break;
    case 'guncelle':
        foreach ((array)($_POST['qty'] ?? []) as $code => $q) cart_set((string)$code, (int)$q);
        break;
    case 'sil':
        unset($_SESSION['cart'][(string)($_POST['code'] ?? '')]);
        break;
    case 'kupon':
        $c = find_coupon((string)($_POST['coupon'] ?? ''));
        if ($c) { $_SESSION['coupon'] = $c['code']; $msg = L('Kupon uygulandı', 'Coupon applied'); }
        else { $ok = false; $msg = L('Kupon kodu geçersiz veya süresi dolmuş', 'Invalid or expired coupon'); }
        break;
    case 'kupon-kaldir':
        unset($_SESSION['coupon']);
        break;
}
if ($json) {
    header('Content-Type: application/json; charset=utf-8');
    $t = cart_totals();
    echo json_encode(['ok' => $ok, 'msg' => $msg, 'count' => $t['count'], 'total' => money($t['total']), 'qty' => (int)($_SESSION['cart'][$_POST['code'] ?? ''] ?? 0)], JSON_UNESCAPED_UNICODE);
    exit;
}
if ($msg) flash_msg($msg, $ok ? 'ok' : 'err');
go(!empty($_POST['back']) ? $_POST['back'] : url('/sepet'));
