<?php
$st = db()->prepare('SELECT * FROM orders WHERE order_no = ?'); $st->execute([$params[1]]); $o = $st->fetch();
$c = customer();
if (!$o || (($_SESSION['last_order'] ?? '') !== $o['order_no'] && (!$c || (int)$o['user_id'] !== (int)$c['id']))) go(url('/'));
$items = db()->query('SELECT * FROM order_items WHERE order_id = ' . (int)$o['id'])->fetchAll();
$banks = db()->query('SELECT * FROM bank_accounts WHERE is_active = 1')->fetchAll();
view('layout/top', ['title' => L('Siparişiniz Alındı', 'Order Received')]);
?>
<section class="container-x max-w-3xl py-10">
  <div class="rounded-2xl border border-line p-6 text-center md:p-10">
    <div class="mx-auto flex h-16 w-16 items-center justify-center rounded-full bg-green-50 text-ok"><?= icon('check', 'h-9 w-9', 3) ?></div>
    <h1 class="mt-4 text-2xl font-extrabold md:text-3xl"><?= $o['payment_method'] === 'card' && $o['payment_status'] !== 'paid' ? L('Siparişiniz oluşturuldu', 'Your order has been created') : L('Siparişiniz alındı!', 'Your order has been received!') ?></h1>
    <p class="mt-2 text-muted"><?= L('Teşekkür ederiz, ', 'Thank you, ') ?><?= e($o['customer_name']) ?>. <?= L('Sipariş numaranız:', 'Your order number:') ?></p>
    <div class="mt-3 inline-block rounded-lg bg-soft px-5 py-2 font-mono text-2xl font-black tracking-wider"><?= e($o['order_no']) ?></div>
    <?php if ($o['payment_method'] === 'transfer'): ?>
      <div class="mx-auto mt-6 max-w-lg rounded-xl border border-amber-200 bg-amber-50 p-5 text-left text-sm">
        <b><?= L('Havale / EFT ile ödeme', 'Bank transfer payment') ?></b>
        <p class="mt-1"><?= LV('Lütfen <b>{total}</b> tutarı aşağıdaki hesaba gönderin ve açıklamaya <b>{no}</b> yazın. Ödemeniz onaylanınca siparişiniz kargoya hazırlanır.', 'Please transfer <b>{total}</b> and write <b>{no}</b> in the description.', ['total' => money($o['total']), 'no' => e($o['order_no'])]) ?></p>
        <?php foreach ($banks as $b): ?><div class="mt-3 rounded bg-white p-3"><b><?= e($b['bank_name']) ?></b> · <?= e($b['account_holder']) ?><div class="mt-1 flex items-center gap-2 font-mono"><span data-copy-src><?= e($b['iban']) ?></span><button type="button" data-copy class="rounded border border-line px-2 py-0.5 font-sans text-xs font-semibold"><?= L('Kopyala', 'Copy') ?></button></div></div><?php endforeach; ?>
      </div>
    <?php elseif ($o['payment_method'] === 'cod'): ?>
      <p class="mt-6 text-sm"><?= L('Ödemenizi ürünü teslim alırken yapabilirsiniz.', 'You will pay on delivery.') ?></p>
    <?php elseif ($o['payment_status'] === 'paid'): ?>
      <div class="mx-auto mt-6 flex max-w-lg items-center gap-3 rounded-xl bg-green-50 p-4 text-left text-sm text-green-800"><?= icon('credit-card', 'h-6 w-6 shrink-0') ?><div><b><?= L('Ödemeniz alındı.', 'Payment received.') ?></b> <?= L('Siparişiniz hazırlanmaya başlandı.', 'Your order is being prepared.') ?></div></div>
    <?php else: ?>
      <div class="mx-auto mt-6 max-w-lg rounded-xl border <?= $o['payment_status'] === 'failed' ? 'border-red-200 bg-red-50 text-red-800' : 'border-amber-200 bg-amber-50 text-amber-800' ?> p-4 text-left text-sm">
        <b><?= $o['payment_status'] === 'failed' ? L('Ödeme tamamlanamadı.', 'Payment failed.') : L('Ödeme bekleniyor.', 'Awaiting payment.') ?></b>
        <?php if (!empty($_GET['m'])): ?><div class="mt-1"><?= e($_GET['m']) ?></div><?php endif; ?>
        <div class="mt-3"><a href="<?= url('/odeme/kart/' . $o['order_no']) ?>" class="btn-primary h-10 px-5 text-sm"><?= icon('credit-card', 'h-4 w-4') ?> <?= L('Kartla Öde', 'Pay by Card') ?></a></div>
      </div>
    <?php endif; ?>
    <div class="mx-auto mt-6 max-w-lg text-left">
      <?php foreach ($items as $it): ?><div class="flex justify-between border-b border-line py-2 text-sm"><span><?= (int)$it['quantity'] ?> × <?= e($it['name']) ?></span><b><?= money($it['total']) ?></b></div><?php endforeach; ?>
      <?php if ((float)($o['vat_total'] ?? 0) > 0): ?><div class="flex justify-between pt-2 text-sm"><span><?= L('Ara toplam (KDV hariç)', 'Subtotal (excl. VAT)') ?></span><b><?= money($o['subtotal'] - $o['discount']) ?></b></div><div class="flex justify-between py-1 text-sm"><span><?= L('KDV', 'VAT') ?></span><b><?= money($o['vat_total']) ?></b></div><?php endif; ?>
      <?php if ((float)$o['shipping_fee'] > 0): ?><div class="flex justify-between py-1 text-sm"><span><?= L('Kargo', 'Shipping') ?></span><b><?= money($o['shipping_fee']) ?></b></div><?php endif; ?>
      <div class="flex justify-between py-3 text-lg font-extrabold"><span><?= L('Toplam', 'Total') ?></span><span class="text-brand"><?= money($o['total']) ?></span></div>
    </div>
    <p class="text-sm text-muted"><?= L('Sipariş detaylarını', 'Order details were sent to') ?> <b><?= e($o['email']) ?></b> <?= L('adresine de ileteceğiz.', '.') ?></p>
    <div class="mt-6 flex flex-wrap justify-center gap-3">
      <?php if ($c): ?><a href="<?= url('/hesabim/siparis/' . $o['order_no']) ?>" class="btn h-11 border border-line px-5"><?= L('Siparişimi Görüntüle', 'View Order') ?></a><?php else: ?><a href="<?= url('/siparis-takip') ?>?no=<?= e($o['order_no']) ?>" class="btn h-11 border border-line px-5"><?= L('Sipariş Takibi', 'Track Order') ?></a><?php endif; ?>
      <a href="<?= url('/') ?>" class="btn-primary h-11 px-5"><?= L('Alışverişe Devam Et', 'Continue Shopping') ?></a>
    </div>
  </div>
</section>
<?php view('layout/bottom');
