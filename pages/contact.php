<?php
$g = setting('general', []); $wa = setting('whatsapp', []);
$errors = []; $d = $_POST; $sent = false;
if (is_post()) {
    if (!csrf_ok()) $errors['_'] = L('Oturum süresi doldu.', 'Session expired.');
    $spam = !empty($d['website']);
    foreach (['name' => L('Ad soyad', 'Name'), 'email' => 'E-posta', 'message' => L('Mesaj', 'Message')] as $k => $l) if (trim($d[$k] ?? '') === '') $errors[$k] = $l . ' ' . L('zorunludur', 'is required');
    if (!empty($d['email']) && !filter_var($d['email'], FILTER_VALIDATE_EMAIL)) $errors['email'] = L('Geçerli bir e-posta girin', 'Invalid email');
    if (!$errors) {
        $isSpam = ($spam || contact_is_spam(trim($d['name']), trim($d['email']), trim($d['message']))) ? 1 : 0;
        db()->prepare('INSERT INTO contact_messages (name, email, phone, subject, message, is_spam) VALUES (?,?,?,?,?,?)')->execute([trim($d['name']), trim($d['email']), trim($d['phone'] ?? ''), trim($d['subject'] ?? ''), trim($d['message']), $isSpam]);
        flash_msg(L('Mesajınız alındı. En kısa sürede size dönüş yapacağız.', 'Message received. We will get back to you soon.'));
        go(url('/iletisim'));
    }
}
view('layout/top', ['title' => L('İletişim', 'Contact')]);
echo breadcrumb([[L('İletişim', 'Contact'), null]]);
$tel = preg_replace('/[^\d+]/', '', $g['phone'] ?? '');
?>
<section class="container-x">
  <div class="mb-8 max-w-2xl"><h1 class="text-3xl font-extrabold"><?= L('Bize Ulaşın', 'Contact Us') ?></h1><p class="mt-2 text-muted"><?= L('Doğru parçayı bulmak, sipariş ve teknik destek için ekibimiz size yardımcı olmaya hazır.', 'Our team is ready to help with parts, orders and technical support.') ?></p></div>
  <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
    <a href="tel:<?= e($tel) ?>" class="rounded-xl border border-line p-5 hover:border-brand"><?= icon('phone', 'h-7 w-7 text-brand') ?><div class="mt-3 text-sm text-muted"><?= L('Telefon', 'Phone') ?></div><div class="font-bold"><?= e($g['phone'] ?? '') ?></div></a>
    <a href="<?= e(wa_link($wa['message'] ?? '')) ?>" target="_blank" rel="noopener" class="rounded-xl border border-line p-5 hover:border-wa"><?= social('whatsapp', 'h-7 w-7 text-wa') ?><div class="mt-3 text-sm text-muted">WhatsApp</div><div class="font-bold"><?= L('Hemen yazın', 'Chat now') ?></div></a>
    <a href="mailto:<?= e($g['email'] ?? '') ?>" class="rounded-xl border border-line p-5 hover:border-brand"><?= icon('mail', 'h-7 w-7 text-brand') ?><div class="mt-3 text-sm text-muted">E-posta</div><div class="font-bold break-all"><?= e($g['email'] ?? '') ?></div></a>
    <div class="rounded-xl border border-line p-5"><?= icon('headset', 'h-7 w-7 text-brand') ?><div class="mt-3 text-sm text-muted"><?= L('Çalışma Saatleri', 'Working Hours') ?></div><div class="text-sm font-bold"><?= e($g['workingHours'] ?? L('Hafta içi 08:30 - 18:00', 'Weekdays 08:30 - 18:00')) ?></div></div>
  </div>
  <div class="mt-8 grid gap-6 lg:grid-cols-2">
    <form method="post" class="rounded-xl border border-line p-6">
      <?= csrf_input() ?><input type="text" name="website" class="hidden" tabindex="-1" autocomplete="off">
      <h2 class="text-xl font-extrabold"><?= L('Mesaj Gönderin', 'Send a Message') ?></h2>
      <?php if (!empty($errors['_'])): ?><div class="mt-3 rounded bg-red-50 px-3 py-2 text-sm text-red-700"><?= e($errors['_']) ?></div><?php endif; ?>
      <div class="mt-4 grid gap-4 sm:grid-cols-2">
        <?php view('partials/field', ['name' => 'name', 'label' => L('Ad Soyad', 'Name') . ' *', 'value' => $d['name'] ?? (customer() ? customer()['first_name'] . ' ' . customer()['last_name'] : ''), 'error' => $errors['name'] ?? null]); ?>
        <?php view('partials/field', ['name' => 'email', 'label' => L('E-posta', 'Email') . ' *', 'type' => 'email', 'value' => $d['email'] ?? (customer()['email'] ?? ''), 'error' => $errors['email'] ?? null]); ?>
        <?php view('partials/field', ['name' => 'phone', 'label' => L('Telefon', 'Phone'), 'type' => 'tel', 'value' => $d['phone'] ?? '']); ?>
        <label class="block"><span class="mb-1 block text-xs font-semibold text-muted"><?= L('Konu', 'Subject') ?></span><select name="subject" class="h-11 w-full rounded-md border border-line bg-white px-3 text-sm"><?php foreach ([L('Parça sorgulama', 'Part inquiry'), L('Sipariş hakkında', 'About an order'), L('İade / değişim', 'Return / exchange'), L('Bayilik', 'Dealership'), L('Diğer', 'Other')] as $s) echo '<option' . (($d['subject'] ?? '') === $s ? ' selected' : '') . '>' . e($s) . '</option>'; ?></select></label>
        <div class="sm:col-span-2"><?php view('partials/field', ['name' => 'message', 'label' => L('Mesajınız', 'Message') . ' *', 'type' => 'textarea', 'rows' => 5, 'value' => $d['message'] ?? '', 'error' => $errors['message'] ?? null, 'attrs' => 'placeholder="' . e(L('Makine marka/model ve parça bilgisini yazarsanız daha hızlı yardımcı olabiliriz.', 'Include machine brand/model and part details.')) . '"']); ?></div>
      </div>
      <button class="btn-primary mt-5 h-12 px-8"><?= L('Gönder', 'Send') ?> <?= icon('arrow-right', 'h-4 w-4') ?></button>
    </form>
    <div class="overflow-hidden rounded-xl border border-line">
      <?php $mq = !empty($g['mapLat']) ? $g['mapLat'] . ',' . $g['mapLng'] : ($g['address'] ?? 'Türkiye'); $mapLink = $g['mapUrl'] ?? ('https://www.google.com/maps/search/?api=1&query=' . rawurlencode($mq)); ?>
      <iframe title="<?= L('Konumumuz', 'Our location') ?>" class="h-72 w-full lg:h-[360px]" loading="lazy" referrerpolicy="no-referrer-when-downgrade" src="https://maps.google.com/maps?q=<?= rawurlencode($mq) ?>&z=17&hl=<?= locale() ?>&output=embed"></iframe>
      <div class="flex flex-wrap items-center gap-3 p-5 text-sm">
        <?= icon('map-pin', 'h-5 w-5 shrink-0 text-brand') ?>
        <div class="min-w-0 flex-1"><b><?= e(($g['companyTitle'] ?? '') ?: 'SIR KALIP') ?></b><div class="text-muted"><?= nl2br(e($g['address'] ?? '')) ?></div></div>
        <a href="<?= e($mapLink) ?>" target="_blank" rel="noopener" class="btn-primary h-10 px-4 text-sm"><?= icon('map-pin', 'h-4 w-4') ?> <?= L('Yol Tarifi Al', 'Get Directions') ?></a>
      </div>
    </div>
  </div>
</section>
<?php view('layout/bottom');
