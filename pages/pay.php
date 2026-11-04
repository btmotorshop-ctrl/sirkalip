<?php
/* Kart ile ödeme sayfası + sağlayıcı dönüşleri */
$act = $params[1];
$find = function ($no) { $st = db()->prepare('SELECT * FROM orders WHERE order_no = ? AND deleted_at IS NULL'); $st->execute([$no]); return $st->fetch(); };

if ($act === 'iyzico-donus') { // iyzico, kart işleminden sonra buraya POST eder
    $o = $find($_GET['no'] ?? '');
    $token = $_POST['token'] ?? '';
    if ($o && $token) {
        $r = iyzico_retrieve($token, $o['order_no']);
        $okAmount = isset($r['paidPrice']) && abs((float)$r['paidPrice'] - (float)$o['total']) < 0.05;
        if (($r['status'] ?? '') === 'success' && ($r['paymentStatus'] ?? '') === 'SUCCESS' && ($r['basketId'] ?? '') === $o['order_no'] && $okAmount) {
            mark_order_paid($o, 'iyzico', $r['paymentId'] ?? null, $r);
            go(url('/siparis-tamamlandi/' . $o['order_no']) . '?odeme=ok');
        }
        mark_order_failed($o, 'iyzico', $r);
        go(url('/siparis-tamamlandi/' . $o['order_no']) . '?odeme=hata&m=' . rawurlencode($r['errorMessage'] ?? ''));
    }
    go(url('/'));
}

if ($act === 'paytr-bildirim') { // PayTR sunucudan sunucuya bildirim (PayTR panelinde "Bildirim URL" olarak girilmeli)
    $pos = pos_config();
    $hash = base64_encode(hash_hmac('sha256', ($_POST['merchant_oid'] ?? '') . ($pos['paytrSalt'] ?? '') . ($_POST['status'] ?? '') . ($_POST['total_amount'] ?? ''), $pos['paytrKey'] ?? '', true));
    if (!hash_equals($hash, (string)($_POST['hash'] ?? ''))) { http_response_code(400); exit('PAYTR notification failed: bad hash'); }
    if ($o = $find($_POST['merchant_oid'])) {
        if ($_POST['status'] === 'success') mark_order_paid($o, 'paytr', $_POST['merchant_oid'], $_POST);
        else mark_order_failed($o, 'paytr', $_POST);
    }
    exit('OK');
}

// /odeme/kart/{no}
$o = $find($params[2] ?? '');
$c = customer();
if (!$o || $o['payment_method'] !== 'card' || (($_SESSION['last_order'] ?? '') !== $o['order_no'] && (!$c || (int)$o['user_id'] !== (int)$c['id']))) go(url('/'));
if ($o['payment_status'] === 'paid') go(url('/siparis-tamamlandi/' . $o['order_no']));
$pos = pos_config(); $err = null; $html = null;
if (!$pos['ready']) $err = L('Kartla ödeme şu an kullanılamıyor. Lütfen bizimle iletişime geçin.', 'Card payment is not available right now.');
elseif ($pos['provider'] === 'iyzico') {
    $r = iyzico_init($o);
    if (($r['status'] ?? '') === 'success') { payment_log((int)$o['id'], 'iyzico', (float)$o['total'], 'started', $r['token'] ?? null, ['token' => $r['token'] ?? null]); $html = '<div id="iyzipay-checkout-form" class="responsive"></div>' . ($r['checkoutFormContent'] ?? ''); }
    else $err = $r['errorMessage'] ?? L('Ödeme başlatılamadı.', 'Payment could not be started.');
} else {
    $r = paytr_token($o);
    if (($r['status'] ?? '') === 'success') { payment_log((int)$o['id'], 'paytr', (float)$o['total'], 'started', null, ['token' => 'ok']); $html = '<script src="https://www.paytr.com/js/iframeResizer.min.js"></script><iframe src="https://www.paytr.com/odeme/guvenli/' . e($r['token']) . '" id="paytriframe" frameborder="0" scrolling="no" style="width:100%;min-height:600px"></iframe><script>iFrameResize({},"#paytriframe");</script>'; }
    else $err = $r['reason'] ?? ($r['errorMessage'] ?? L('Ödeme başlatılamadı.', 'Payment could not be started.'));
}
view('layout/top', ['title' => L('Kartla Ödeme', 'Card Payment'), 'minimal' => true]);
?>
<section class="container-x max-w-3xl py-8">
  <div class="mb-6 flex items-center justify-center gap-2 text-xs font-semibold sm:gap-4 sm:text-sm">
    <span class="flex items-center gap-2 text-ok"><span class="flex h-7 w-7 items-center justify-center rounded-full bg-ok text-white"><?= icon('check', 'h-4 w-4') ?></span><?= L('Sepet', 'Cart') ?></span><span class="h-px w-8 bg-line sm:w-16"></span>
    <span class="flex items-center gap-2 text-ok"><span class="flex h-7 w-7 items-center justify-center rounded-full bg-ok text-white"><?= icon('check', 'h-4 w-4') ?></span><?= L('Teslimat', 'Delivery') ?></span><span class="h-px w-8 bg-line sm:w-16"></span>
    <span class="flex items-center gap-2 text-brand"><span class="flex h-7 w-7 items-center justify-center rounded-full bg-brand text-white">3</span><?= L('Kartla Ödeme', 'Card Payment') ?></span>
  </div>
  <div class="rounded-2xl border border-line p-5 md:p-8">
    <div class="mb-5 flex flex-wrap items-center justify-between gap-3 border-b border-line pb-4">
      <div><div class="text-xs text-muted"><?= L('Sipariş No', 'Order No') ?></div><div class="font-mono text-lg font-black"><?= e($o['order_no']) ?></div></div>
      <div class="text-right"><div class="text-xs text-muted"><?= L('Ödenecek Tutar', 'Amount') ?></div><div class="text-2xl font-black text-brand"><?= money($o['total']) ?></div></div>
    </div>
    <?php if ($err): ?>
      <div class="rounded-lg bg-red-50 p-4 text-sm text-red-700"><b><?= L('Ödeme sayfası açılamadı', 'Payment page could not be opened') ?>:</b> <?= e($err) ?></div>
      <div class="mt-4 flex flex-wrap gap-3"><a href="<?= e(wa_link(LV('Merhaba, {no} numaralı siparişimin ödemesini yapamadım.', 'Hello, I could not pay order {no}', ['no' => $o['order_no']]))) ?>" target="_blank" class="btn h-11 bg-wa px-5 text-white"><?= social('whatsapp', 'h-5 w-5') ?> <?= L('Destek Al', 'Get Help') ?></a><a href="<?= url('/siparis-tamamlandi/' . $o['order_no']) ?>" class="btn h-11 border border-line px-5"><?= L('Siparişe Dön', 'Back to Order') ?></a></div>
    <?php else: ?>
      <p class="mb-4 flex items-center gap-2 text-sm text-muted"><?= icon('lock', 'h-4 w-4 text-ok') ?><?= LV('Kart bilgileriniz {p} güvenli ödeme altyapısında işlenir, sitemizde saklanmaz. 3D Secure ile korunur.', 'Your card is processed securely by the payment provider with 3D Secure; we never store it.', ['p' => ($pos['provider'] === 'iyzico' ? 'iyzico' : 'PayTR')]) ?></p>
      <?= $html ?>
    <?php endif; ?>
  </div>
</section>
<?php view('layout/bottom', ['minimal' => true]);
