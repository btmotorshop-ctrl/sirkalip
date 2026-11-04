<?php
$o = null; $err = null;
$no = strtoupper(trim($_REQUEST['no'] ?? '')); $email = trim($_REQUEST['email'] ?? '');
if ($no !== '' && $email !== '') {
    $st = db()->prepare('SELECT * FROM orders WHERE order_no = ? AND (email = ? OR REPLACE(phone, " ", "") = ?) AND deleted_at IS NULL'); $st->execute([$no, $email, preg_replace('/\s/', '', $email)]); $o = $st->fetch();
    if (!$o) $err = L('Sipariş bulunamadı. Bilgileri kontrol edin.', 'Order not found.');
} elseif ($no !== '' && ($_SESSION['last_order'] ?? '') === $no) { $st = db()->prepare('SELECT * FROM orders WHERE order_no = ?'); $st->execute([$no]); $o = $st->fetch(); }
$sl = ['pending' => L('Onay Bekliyor', 'Pending'), 'preparing' => L('Hazırlanıyor', 'Preparing'), 'shipped' => L('Kargoda', 'Shipped'), 'delivered' => L('Teslim Edildi', 'Delivered'), 'cancelled' => L('İptal Edildi', 'Cancelled'), 'returned' => L('İade Edildi', 'Returned')];
view('layout/top', ['title' => L('Sipariş Takibi', 'Order Tracking')]);
echo breadcrumb([[L('Sipariş Takibi', 'Order Tracking'), null]]);
?>
<section class="container-x max-w-2xl">
  <h1 class="text-3xl font-extrabold"><?= L('Sipariş Takibi', 'Order Tracking') ?></h1>
  <form class="mt-5 grid gap-3 rounded-xl border border-line p-5 sm:grid-cols-[1fr_1fr_auto]">
    <input name="no" value="<?= e($no) ?>" required placeholder="<?= L('Sipariş No (SK...)', 'Order No') ?>" class="h-11 rounded-md border border-line px-3 text-sm uppercase">
    <input name="email" value="<?= e($email) ?>" required placeholder="<?= L('E-posta veya telefon', 'Email or phone') ?>" class="h-11 rounded-md border border-line px-3 text-sm">
    <button class="btn-primary h-11 px-5"><?= L('Sorgula', 'Track') ?></button>
  </form>
  <?php if ($err): ?><div class="mt-4 rounded-lg bg-red-50 px-4 py-3 text-sm text-red-700"><?= e($err) ?></div><?php endif; ?>
  <?php if ($o): $ships = db()->query('SELECT s.*, c.name company, c.tracking_url FROM shipments s LEFT JOIN shipping_companies c ON c.id = s.company_id WHERE order_id = ' . (int)$o['id'])->fetchAll(); ?>
    <div class="mt-5 rounded-xl border border-line p-5">
      <div class="flex flex-wrap items-center justify-between gap-2"><div><div class="text-xs text-muted"><?= L('Sipariş', 'Order') ?></div><div class="font-mono text-lg font-black"><?= e($o['order_no']) ?></div></div><span class="rounded-full bg-brand-50 px-3 py-1 text-sm font-bold text-brand"><?= $sl[$o['status']] ?></span></div>
      <div class="mt-3 text-sm text-muted"><?= date('d.m.Y H:i', strtotime($o['created_at'])) ?> · <?= money($o['total']) ?></div>
      <?php foreach ($ships as $s): ?><div class="mt-3 rounded-lg bg-blue-50 p-3 text-sm"><b><?= e($s['company']) ?></b> · <?= e($s['tracking_no']) ?><?php if ($s['tracking_url']): ?> · <a class="font-bold text-brand underline" target="_blank" href="<?= e(str_replace('{no}', $s['tracking_no'], $s['tracking_url'])) ?>"><?= L('Kargo takibi', 'Track shipment') ?></a><?php endif; ?></div><?php endforeach; ?>
    </div>
  <?php endif; ?>
</section>
<?php view('layout/bottom');
