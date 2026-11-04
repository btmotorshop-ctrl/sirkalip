<?php
$u = require_customer();
$sec = $params[1] ?? '';
$sl = ['pending' => [L('Onay Bekliyor', 'Pending'), 'bg-amber-50 text-amber-700'], 'preparing' => [L('Hazırlanıyor', 'Preparing'), 'bg-blue-50 text-blue-700'], 'shipped' => [L('Kargoda', 'Shipped'), 'bg-blue-50 text-blue-700'], 'delivered' => [L('Teslim Edildi', 'Delivered'), 'bg-green-50 text-green-700'], 'cancelled' => [L('İptal Edildi', 'Cancelled'), 'bg-red-50 text-red-700'], 'returned' => [L('İade Edildi', 'Returned'), 'bg-soft text-muted']];
$uid = (int)$u['id'];
$msg = null; $errors = [];
if (is_post() && csrf_ok()) {
    switch ($_POST['action'] ?? '') {
        case 'profile':
            db()->prepare('UPDATE users SET first_name=?, last_name=?, phone=?, company_name=?, tax_office=?, tax_number=?, email_opt_in=?, sms_opt_in=? WHERE id=?')
              ->execute([trim($_POST['first_name']), trim($_POST['last_name']), trim($_POST['phone']), trim($_POST['company_name'] ?? '') ?: null, trim($_POST['tax_office'] ?? '') ?: null, trim($_POST['tax_number'] ?? '') ?: null, !empty($_POST['email_opt_in']) ? 1 : 0, !empty($_POST['sms_opt_in']) ? 1 : 0, $uid]);
            flash_msg(L('Bilgileriniz güncellendi.', 'Profile updated.')); go(url('/hesabim/bilgilerim'));
        case 'password':
            if (!password_verify($_POST['current'] ?? '', $u['password_hash'])) $errors['current'] = L('Mevcut şifre hatalı', 'Current password is wrong');
            elseif (strlen($_POST['new'] ?? '') < 6) $errors['new'] = L('Yeni şifre en az 6 karakter olmalı', 'Min 6 characters');
            else { db()->prepare('UPDATE users SET password_hash = ? WHERE id = ?')->execute([password_hash($_POST['new'], PASSWORD_DEFAULT), $uid]); flash_msg(L('Şifreniz değiştirildi.', 'Password changed.')); go(url('/hesabim/bilgilerim')); }
            $sec = 'bilgilerim'; break;
        case 'address_save':
            $f = ['title', 'full_name', 'phone', 'city', 'district', 'address', 'postal_code'];
            $vals = array_map(function ($k) { return trim($_POST[$k] ?? ''); }, $f);
            if (in_array('', array_slice($vals, 0, 6), true)) { flash_msg(L('Lütfen tüm zorunlu alanları doldurun.', 'Please fill all required fields.'), 'err'); go(url('/hesabim/adreslerim')); }
            if (!empty($_POST['id'])) db()->prepare('UPDATE addresses SET title=?, full_name=?, phone=?, city=?, district=?, address=?, postal_code=? WHERE id=? AND user_id=?')->execute(array_merge($vals, [(int)$_POST['id'], $uid]));
            else db()->prepare('INSERT INTO addresses (title, full_name, phone, city, district, address, postal_code, user_id) VALUES (?,?,?,?,?,?,?,?)')->execute(array_merge($vals, [$uid]));
            flash_msg(L('Adres kaydedildi.', 'Address saved.')); go(url('/hesabim/adreslerim'));
        case 'address_delete':
            db()->prepare('DELETE FROM addresses WHERE id = ? AND user_id = ?')->execute([(int)$_POST['id'], $uid]);
            flash_msg(L('Adres silindi.', 'Address deleted.')); go(url('/hesabim/adreslerim'));
        case 'return':
            $st = db()->prepare('SELECT id FROM orders WHERE order_no = ? AND user_id = ?'); $st->execute([$_POST['order_no'], $uid]);
            if ($oid = $st->fetchColumn()) {
                db()->prepare('INSERT INTO return_requests (order_id, order_item_id, quantity, reason) VALUES (?,?,?,?)')->execute([$oid, (int)$_POST['item_id'] ?: null, max(1, (int)$_POST['qty']), trim($_POST['reason'])]);
                flash_msg(L('İade talebiniz alındı. En kısa sürede sizinle iletişime geçeceğiz.', 'Return request received.'));
            }
            go(url('/hesabim/siparis/' . $_POST['order_no']));
    }
}
$menu = ['' => [L('Hesap Özeti', 'Overview'), 'user'], 'siparislerim' => [L('Siparişlerim', 'My Orders'), 'clipboard-list'], 'adreslerim' => [L('Adreslerim', 'My Addresses'), 'map-pin'], 'favoriler' => [L('Favorilerim', 'Favorites'), 'heart'], 'bilgilerim' => [L('Üyelik Bilgilerim', 'Account Details'), 'settings']];
$orders = db()->query("SELECT o.*, (SELECT SUM(quantity) FROM order_items i WHERE i.order_id = o.id) qty FROM orders o WHERE user_id = $uid AND deleted_at IS NULL ORDER BY id DESC")->fetchAll();
view('layout/top', ['title' => L('Hesabım', 'My Account')]);
echo breadcrumb([[L('Hesabım', 'My Account'), url('/hesabim')]] + ($sec ? [1 => [$menu[$sec][0] ?? L('Sipariş Detayı', 'Order Detail'), null]] : []));
?>
<section class="container-x">
  <div class="grid grid-cols-1 gap-6 lg:grid-cols-[250px_minmax(0,1fr)]">
    <aside class="h-fit rounded-xl border border-line p-3">
      <div class="flex items-center gap-3 border-b border-line p-3"><span class="flex h-11 w-11 items-center justify-center rounded-full bg-brand text-lg font-black text-white"><?= e(tr_upper(mb_substr($u['first_name'], 0, 1))) ?></span><div class="min-w-0"><div class="truncate font-bold"><?= e($u['first_name'] . ' ' . $u['last_name']) ?></div><div class="truncate text-xs text-muted"><?= e($u['email']) ?></div></div></div>
      <nav class="mt-2 space-y-0.5 text-sm font-semibold">
        <?php foreach ($menu as $k => [$l, $ic]): $h = $k === 'favoriler' ? url('/favoriler') : url('/hesabim' . ($k ? '/' . $k : '')); ?>
          <a href="<?= $h ?>" class="flex items-center gap-3 rounded-md px-3 py-2.5 <?= $sec === $k || ($sec === 'siparis' && $k === 'siparislerim') ? 'bg-brand-50 text-brand' : 'hover:bg-soft' ?>"><?= icon($ic, 'h-4 w-4') ?><?= e($l) ?></a>
        <?php endforeach; ?>
        <a href="<?= url('/cikis') ?>" class="flex items-center gap-3 rounded-md px-3 py-2.5 text-muted hover:bg-soft"><?= icon('log-out', 'h-4 w-4') ?><?= L('Çıkış Yap', 'Log Out') ?></a>
      </nav>
    </aside>
    <div class="min-w-0">
<?php if ($sec === ''): ?>
      <h1 class="text-2xl font-extrabold"><?= L('Merhaba', 'Hello') ?>, <?= e($u['first_name']) ?> 👋</h1>
      <div class="mt-5 grid gap-4 sm:grid-cols-3">
        <a href="<?= url('/hesabim/siparislerim') ?>" class="rounded-xl border border-line p-5 hover:border-brand"><?= icon('clipboard-list', 'h-6 w-6 text-brand') ?><div class="mt-2 text-2xl font-black"><?= count($orders) ?></div><div class="text-sm text-muted"><?= L('Sipariş', 'Orders') ?></div></a>
        <a href="<?= url('/favoriler') ?>" class="rounded-xl border border-line p-5 hover:border-brand"><?= icon('heart', 'h-6 w-6 text-brand') ?><div class="mt-2 text-2xl font-black"><?= count(fav_ids()) ?></div><div class="text-sm text-muted"><?= L('Favori Ürün', 'Favorites') ?></div></a>
        <a href="<?= url('/hesabim/adreslerim') ?>" class="rounded-xl border border-line p-5 hover:border-brand"><?= icon('map-pin', 'h-6 w-6 text-brand') ?><div class="mt-2 text-2xl font-black"><?= (int)db()->query("SELECT COUNT(*) FROM addresses WHERE user_id = $uid")->fetchColumn() ?></div><div class="text-sm text-muted"><?= L('Kayıtlı Adres', 'Addresses') ?></div></a>
      </div>
      <h2 class="mb-3 mt-8 text-lg font-extrabold"><?= L('Son Siparişlerim', 'Recent Orders') ?></h2>
<?php endif; ?>
<?php if ($sec === '' || $sec === 'siparislerim'): ?>
      <?php if ($sec): ?><h1 class="mb-5 text-2xl font-extrabold"><?= L('Siparişlerim', 'My Orders') ?></h1><?php endif; ?>
      <?php if (!$orders): ?><div class="rounded-xl border border-dashed border-line p-8 text-center text-sm text-muted"><?= L('Henüz siparişiniz yok.', 'No orders yet.') ?> <a href="<?= url('/kategori/balya-makinesi') ?>" class="font-bold text-brand"><?= L('Alışverişe başlayın', 'Start shopping') ?></a></div><?php endif; ?>
      <div class="space-y-3">
        <?php foreach ($sec === '' ? array_slice($orders, 0, 5) : $orders as $o): ?>
          <a href="<?= url('/hesabim/siparis/' . $o['order_no']) ?>" class="flex flex-wrap items-center gap-4 rounded-xl border border-line p-4 hover:border-brand">
            <div><div class="text-xs text-muted"><?= L('Sipariş No', 'Order No') ?></div><div class="font-mono font-bold"><?= e($o['order_no']) ?></div></div>
            <div><div class="text-xs text-muted"><?= L('Tarih', 'Date') ?></div><div class="text-sm font-semibold"><?= date('d.m.Y', strtotime($o['created_at'])) ?></div></div>
            <div><div class="text-xs text-muted"><?= L('Ürün', 'Items') ?></div><div class="text-sm font-semibold"><?= (int)$o['qty'] ?></div></div>
            <div><div class="text-xs text-muted"><?= L('Tutar', 'Total') ?></div><div class="text-sm font-bold text-brand"><?= money($o['total']) ?></div></div>
            <span class="ml-auto rounded-full px-3 py-1 text-xs font-bold <?= $sl[$o['status']][1] ?>"><?= $sl[$o['status']][0] ?></span>
            <?= icon('chevron-right', 'h-5 w-5 text-muted') ?>
          </a>
        <?php endforeach; ?>
      </div>
<?php elseif ($sec === 'siparis'):
      $st = db()->prepare('SELECT * FROM orders WHERE order_no = ? AND user_id = ?'); $st->execute([$params[2] ?? '', $uid]); $o = $st->fetch();
      if (!$o) { echo '<p>' . L('Sipariş bulunamadı.', 'Order not found.') . '</p>'; } else {
      $items = db()->query('SELECT * FROM order_items WHERE order_id = ' . (int)$o['id'])->fetchAll();
      $ships = db()->query('SELECT s.*, c.name company, c.tracking_url FROM shipments s LEFT JOIN shipping_companies c ON c.id = s.company_id WHERE order_id = ' . (int)$o['id'])->fetchAll();
      $addr = json_decode($o['shipping_address'], true) ?: [];
      $steps = ['pending', 'preparing', 'shipped', 'delivered']; $cur = array_search($o['status'], $steps, true); ?>
      <div class="flex flex-wrap items-center justify-between gap-3"><h1 class="text-2xl font-extrabold"><?= L('Sipariş', 'Order') ?> <span class="font-mono"><?= e($o['order_no']) ?></span></h1><span class="rounded-full px-3 py-1 text-sm font-bold <?= $sl[$o['status']][1] ?>"><?= $sl[$o['status']][0] ?></span></div>
      <?php if ($cur !== false): ?>
        <div class="mt-6 grid grid-cols-4 gap-1 text-center text-xs font-semibold">
          <?php foreach ($steps as $i => $s): ?><div><div class="h-2 rounded-full <?= $i <= $cur ? 'bg-brand' : 'bg-soft' ?>"></div><div class="mt-2 <?= $i <= $cur ? 'text-brand' : 'text-muted' ?>"><?= $sl[$s][0] ?></div></div><?php endforeach; ?>
        </div>
      <?php endif; ?>
      <?php foreach ($ships as $s): ?><div class="mt-4 rounded-lg bg-blue-50 p-3 text-sm"><?= icon('truck', 'mr-1 inline h-4 w-4') ?><b><?= e($s['company']) ?></b> · <?= L('Takip No', 'Tracking') ?>: <b><?= e($s['tracking_no']) ?></b><?php if ($s['tracking_url']): ?> · <a class="font-bold text-brand underline" target="_blank" href="<?= e(str_replace('{no}', $s['tracking_no'], $s['tracking_url'])) ?>"><?= L('Kargom nerede?', 'Track') ?></a><?php endif; ?></div><?php endforeach; ?>
      <div class="mt-6 overflow-hidden rounded-xl border border-line">
        <?php foreach ($items as $it): ?><div class="flex items-center justify-between gap-3 border-b border-line p-4 text-sm last:border-0"><div><div class="text-xs text-muted"><?= e($it['code']) ?></div><b><?= e($it['name']) ?></b><div class="text-xs text-muted"><?= (int)$it['quantity'] ?> × <?= money($it['unit_price']) ?></div></div><b><?= money($it['total']) ?></b></div><?php endforeach; ?>
        <div class="space-y-1 bg-soft p-4 text-sm"><div class="flex justify-between"><span><?= L('Ara toplam', 'Subtotal') ?></span><span><?= money($o['subtotal']) ?></span></div><?php if ($o['discount'] > 0): ?><div class="flex justify-between text-ok"><span><?= L('İndirim', 'Discount') ?></span><span>-<?= money($o['discount']) ?></span></div><?php endif; ?><?php if ((float)($o['vat_total'] ?? 0) > 0): ?><div class="flex justify-between"><span><?= L('KDV', 'VAT') ?></span><span><?= money($o['vat_total']) ?></span></div><?php endif; ?><div class="flex justify-between"><span><?= L('Kargo', 'Shipping') ?></span><span><?= money($o['shipping_fee']) ?></span></div><div class="flex justify-between text-base font-extrabold"><span><?= L('Toplam', 'Total') ?></span><span class="text-brand"><?= money($o['total']) ?></span></div></div>
      </div>
      <div class="mt-4 grid gap-4 sm:grid-cols-2">
        <div class="rounded-xl border border-line p-4 text-sm"><b><?= L('Teslimat Adresi', 'Shipping Address') ?></b><div class="mt-1 text-muted"><?= e($addr['fullName'] ?? '') ?><br><?= e($addr['address'] ?? '') ?><br><?= e(($addr['district'] ?? '') . ' / ' . ($addr['city'] ?? '')) ?><br><?= e($addr['phone'] ?? '') ?></div></div>
        <div class="rounded-xl border border-line p-4 text-sm"><b><?= L('Ödeme', 'Payment') ?></b><div class="mt-1 text-muted"><?= ['card' => L('Kredi Kartı', 'Card'), 'transfer' => L('Havale / EFT', 'Bank Transfer'), 'cod' => L('Kapıda Ödeme', 'Cash on Delivery')][$o['payment_method']] ?> · <?= ['waiting' => L('Ödeme bekleniyor', 'Awaiting payment'), 'paid' => L('Ödendi', 'Paid'), 'failed' => L('Başarısız', 'Failed'), 'refunded' => L('İade edildi', 'Refunded')][$o['payment_status']] ?></div></div>
      </div>
      <?php if ($o['status'] === 'delivered'): ?>
        <details class="mt-4 rounded-xl border border-line p-4"><summary class="cursor-pointer text-sm font-bold"><?= L('İade Talebi Oluştur', 'Request a Return') ?></summary>
          <form method="post" class="mt-3 grid gap-3 sm:grid-cols-3"><?= csrf_input() ?><input type="hidden" name="action" value="return"><input type="hidden" name="order_no" value="<?= e($o['order_no']) ?>">
            <select name="item_id" class="h-11 rounded-md border border-line px-3 text-sm sm:col-span-2"><?php foreach ($items as $it) echo '<option value="' . (int)$it['id'] . '">' . e($it['name']) . '</option>'; ?></select>
            <input name="qty" type="number" min="1" value="1" class="h-11 rounded-md border border-line px-3 text-sm">
            <textarea name="reason" required placeholder="<?= L('İade nedeni', 'Reason') ?>" class="rounded-md border border-line p-3 text-sm sm:col-span-3"></textarea>
            <button class="btn-primary h-11 sm:col-span-3"><?= L('Talebi Gönder', 'Submit') ?></button></form></details>
      <?php endif; ?>
      <a href="<?= url('/hesabim/siparislerim') ?>" class="mt-5 inline-flex items-center gap-1 text-sm font-semibold text-muted hover:text-brand"><?= icon('chevron-left', 'h-4 w-4') ?> <?= L('Siparişlerime dön', 'Back to orders') ?></a>
<?php } elseif ($sec === 'adreslerim'):
      $addrs = db()->query("SELECT * FROM addresses WHERE user_id = $uid ORDER BY id DESC")->fetchAll(); $cities = require ROOT . '/app/cities.php'; $edit = null;
      foreach ($addrs as $a) if ((int)($_GET['duzenle'] ?? 0) === (int)$a['id']) $edit = $a; ?>
      <h1 class="mb-5 text-2xl font-extrabold"><?= L('Adreslerim', 'My Addresses') ?></h1>
      <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
        <?php foreach ($addrs as $a): ?>
          <div class="rounded-xl border border-line p-4 text-sm"><div class="flex justify-between"><b><?= e($a['title']) ?></b><span class="flex gap-3"><a href="?duzenle=<?= $a['id'] ?>" class="text-xs font-semibold text-brand"><?= L('Düzenle', 'Edit') ?></a><form method="post" onsubmit="return confirm('<?= L('Silinsin mi?', 'Delete?') ?>')"><?= csrf_input() ?><input type="hidden" name="action" value="address_delete"><input type="hidden" name="id" value="<?= $a['id'] ?>"><button class="text-xs font-semibold text-muted"><?= L('Sil', 'Delete') ?></button></form></span></div>
            <div class="mt-1 text-muted"><?= e($a['full_name']) ?> · <?= e($a['phone']) ?><br><?= e($a['address']) ?><br><?= e($a['district'] . ' / ' . $a['city']) ?></div></div>
        <?php endforeach; ?>
      </div>
      <form method="post" class="mt-6 grid gap-4 rounded-xl border border-line p-5 sm:grid-cols-2"><?= csrf_input() ?><input type="hidden" name="action" value="address_save"><input type="hidden" name="id" value="<?= (int)($edit['id'] ?? 0) ?>">
        <div class="font-bold sm:col-span-2"><?= $edit ? L('Adresi Düzenle', 'Edit Address') : L('Yeni Adres Ekle', 'Add New Address') ?></div>
        <?php view('partials/field', ['name' => 'title', 'label' => L('Adres Başlığı (Ev, İş, Çiftlik...)', 'Title') . ' *', 'value' => $edit['title'] ?? '']); ?>
        <?php view('partials/field', ['name' => 'full_name', 'label' => L('Ad Soyad', 'Full Name') . ' *', 'value' => $edit['full_name'] ?? $u['first_name'] . ' ' . $u['last_name']]); ?>
        <?php view('partials/field', ['name' => 'phone', 'label' => L('Telefon', 'Phone') . ' *', 'value' => $edit['phone'] ?? $u['phone']]); ?>
        <label class="block"><span class="mb-1 block text-xs font-semibold text-muted"><?= L('İl', 'City') ?> *</span><select name="city" class="h-11 w-full rounded-md border border-line bg-white px-3 text-sm"><?php foreach ($cities as $c) echo '<option' . (($edit['city'] ?? '') === $c ? ' selected' : '') . '>' . e($c) . '</option>'; ?></select></label>
        <?php view('partials/field', ['name' => 'district', 'label' => L('İlçe', 'District') . ' *', 'value' => $edit['district'] ?? '']); ?>
        <?php view('partials/field', ['name' => 'postal_code', 'label' => L('Posta Kodu', 'Postal Code'), 'value' => $edit['postal_code'] ?? '']); ?>
        <div class="sm:col-span-2"><?php view('partials/field', ['name' => 'address', 'label' => L('Açık Adres', 'Address') . ' *', 'type' => 'textarea', 'rows' => 2, 'value' => $edit['address'] ?? '']); ?></div>
        <div class="sm:col-span-2"><button class="btn-primary h-11 px-6"><?= L('Kaydet', 'Save') ?></button></div>
      </form>
<?php elseif ($sec === 'bilgilerim'): ?>
      <h1 class="mb-5 text-2xl font-extrabold"><?= L('Üyelik Bilgilerim', 'Account Details') ?></h1>
      <form method="post" class="grid gap-4 rounded-xl border border-line p-5 sm:grid-cols-2"><?= csrf_input() ?><input type="hidden" name="action" value="profile">
        <?php view('partials/field', ['name' => 'first_name', 'label' => L('Ad', 'First Name'), 'value' => $u['first_name'], 'attrs' => 'required']); ?>
        <?php view('partials/field', ['name' => 'last_name', 'label' => L('Soyad', 'Last Name'), 'value' => $u['last_name'], 'attrs' => 'required']); ?>
        <label class="block"><span class="mb-1 block text-xs font-semibold text-muted">E-posta</span><input value="<?= e($u['email']) ?>" disabled class="h-11 w-full rounded-md border border-line bg-soft px-3 text-sm"></label>
        <?php view('partials/field', ['name' => 'phone', 'label' => L('Telefon', 'Phone'), 'value' => $u['phone']]); ?>
        <?php view('partials/field', ['name' => 'company_name', 'label' => L('Firma Adı', 'Company'), 'value' => $u['company_name']]); ?>
        <?php view('partials/field', ['name' => 'tax_office', 'label' => L('Vergi Dairesi', 'Tax Office'), 'value' => $u['tax_office']]); ?>
        <?php view('partials/field', ['name' => 'tax_number', 'label' => L('Vergi No / TC', 'Tax No'), 'value' => $u['tax_number']]); ?>
        <div class="flex flex-col gap-2 text-sm sm:col-span-2"><label class="flex items-center gap-2"><input type="checkbox" name="email_opt_in" value="1" <?= $u['email_opt_in'] ? 'checked' : '' ?> class="h-4 w-4 accent-[#e30613]"> <?= L('Kampanya e-postaları almak istiyorum', 'Receive campaign emails') ?></label><label class="flex items-center gap-2"><input type="checkbox" name="sms_opt_in" value="1" <?= $u['sms_opt_in'] ? 'checked' : '' ?> class="h-4 w-4 accent-[#e30613]"> <?= L('SMS almak istiyorum', 'Receive SMS') ?></label></div>
        <div class="sm:col-span-2"><button class="btn-primary h-11 px-6"><?= L('Bilgileri Güncelle', 'Update') ?></button></div>
      </form>
      <form method="post" class="mt-6 grid gap-4 rounded-xl border border-line p-5 sm:grid-cols-2"><?= csrf_input() ?><input type="hidden" name="action" value="password">
        <div class="font-bold sm:col-span-2"><?= L('Şifre Değiştir', 'Change Password') ?></div>
        <?php view('partials/field', ['name' => 'current', 'label' => L('Mevcut Şifre', 'Current'), 'type' => 'password', 'error' => $errors['current'] ?? null]); ?>
        <?php view('partials/field', ['name' => 'new', 'label' => L('Yeni Şifre', 'New'), 'type' => 'password', 'error' => $errors['new'] ?? null]); ?>
        <div class="sm:col-span-2"><button class="btn h-11 border border-line px-6 hover:border-brand"><?= L('Şifreyi Değiştir', 'Change') ?></button></div>
      </form>
<?php endif; ?>
    </div>
  </div>
</section>
<?php view('layout/bottom');
