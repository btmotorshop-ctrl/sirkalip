</main>
<?php if (!empty($minimal)): ?>
<footer class="mt-10 border-t border-line py-6 text-center text-xs text-muted">© <?= date('Y') ?> SIR KALIP · <a href="<?= url('/sozlesmeler/mesafeli-satis') ?>" class="hover:text-brand"><?= L('Mesafeli Satış Sözleşmesi', 'Distance Sales Agreement') ?></a> · <a href="<?= url('/sozlesmeler/kvkk') ?>" class="hover:text-brand">KVKK</a></footer>
<script src="<?= asset('js/app.js') ?>"></script></body></html>
<?php return; endif; ?>
<?php
$wa = setting('whatsapp', ['number' => '908503027675']);
$gen = setting('general', []);
$cols = [
  [t('footer.corporate'), [['/hakkimizda', t('footer.aboutUs')], ['/teknik-bilgiler', t('footer.tech')], ['/markalar', L('Markalar', 'Brands')], ['/kategoriler', L('Kategoriler', 'Categories')], ['/iletisim', t('footer.contactUs')]]],
  [t('footer.help'), [['/siparis-takip', t('footer.orderTrack')], ['/iade', t('footer.returns')], ['/kargo', t('footer.shipping')], ['/sss', t('footer.faq')], ['/sozlesmeler/mesafeli-satis', t('footer.distance')], ['/sozlesmeler/kvkk', t('footer.privacy')], ['/sozlesmeler/cerez', t('footer.cookies')]]],
];
?>
<section class="container-x mt-16">
  <div class="flex flex-col items-center gap-4 rounded-2xl bg-soft p-6 md:flex-row md:p-8">
    <div class="flex-1 text-center md:text-left"><h3 class="text-xl font-extrabold"><?= L('Kampanyalardan ilk siz haberdar olun', 'Be the first to hear about deals') ?></h3><p class="mt-1 text-sm text-muted"><?= L('Sezon öncesi fırsatlar ve yeni ürünler e-posta kutunuzda.', 'Pre-season deals and new products in your inbox.') ?></p></div>
    <form method="post" action="<?= url('/iletisim') ?>" class="flex w-full max-w-md gap-2" data-newsletter><?= csrf_input() ?><input type="hidden" name="name" value="Bülten"><input type="hidden" name="subject" value="Bülten kaydı"><input type="hidden" name="message" value="E-posta bültenine kayıt"><input type="email" name="email" required placeholder="<?= L('E-posta adresiniz', 'Your email') ?>" class="h-12 flex-1 rounded-md border border-line bg-white px-4 text-sm"><button class="btn-primary h-12 px-5"><?= L('Abone Ol', 'Subscribe') ?></button></form>
  </div>
</section>
<footer class="mt-10 bg-ink pb-20 text-white/80 md:pb-0">
  <div class="container-x grid gap-10 py-12 md:grid-cols-4">
    <div>
      <?php view('partials/logo', ['light' => true]); ?>
      <p class="mt-4 text-sm leading-relaxed"><?= e(setting('content', [])['footerAbout'] ?? t('footer.about')) ?></p>
      <div class="mt-4 flex gap-3">
        <?php foreach (['facebook', 'instagram', 'youtube', 'tiktok', 'whatsapp'] as $s): $sh = $s === 'whatsapp' ? wa_link() : ($gen[$s] ?? ($s === 'tiktok' ? 'https://www.tiktok.com/@sirkalip' : '')); if (!$sh) continue; ?>
          <a href="<?= e($sh) ?>" target="_blank" rel="noopener" class="rounded-full bg-white/10 p-2 hover:bg-brand" aria-label="<?= $s ?>"><?= social($s) ?></a>
        <?php endforeach; ?>
      </div>
    </div>
    <?php foreach ($cols as [$title, $links]): ?>
      <div>
        <h4 class="mb-4 font-bold text-white"><?= e($title) ?></h4>
        <ul class="space-y-2 text-sm">
          <?php foreach ($links as [$h, $l]): ?><li><a href="<?= url($h) ?>" class="hover:text-white"><?= e($l) ?></a></li><?php endforeach; ?>
        </ul>
      </div>
    <?php endforeach; ?>
    <div>
      <h4 class="mb-4 font-bold text-white"><?= e(t('footer.contact')) ?></h4>
      <ul class="space-y-3 text-sm">
        <li><a href="tel:<?= e(preg_replace('/[^\d+]/', '', $gen['phone'] ?? '')) ?>" class="flex gap-2 hover:text-white"><?= icon('phone', 'h-4 w-4 shrink-0') ?><?= e($gen['phone'] ?? '') ?></a></li>
        <li><a href="mailto:<?= e($gen['email'] ?? '') ?>" class="flex gap-2 hover:text-white"><?= icon('mail', 'h-4 w-4 shrink-0') ?><?= e($gen['email'] ?? '') ?></a></li>
        <?php if (!empty($gen['workingHours'])): ?><li class="flex gap-2"><?= icon('headset', 'h-4 w-4 shrink-0') ?><?= e($gen['workingHours']) ?></li><?php endif; ?>
        <li><a href="<?= e($gen['mapUrl'] ?? url('/iletisim')) ?>" target="_blank" rel="noopener" class="flex gap-2 hover:text-white"><?= icon('map-pin', 'h-4 w-4 shrink-0') ?><?= e($gen['address'] ?? 'Türkiye') ?></a></li>
      </ul>
      <div class="mt-5 flex gap-2 text-[11px] font-bold">
        <?php foreach (['VISA', 'Mastercard', 'TROY', 'iyzico'] as $pm): ?><span class="rounded bg-white px-2 py-1 text-ink"><?= $pm ?></span><?php endforeach; ?>
      </div>
    </div>
  </div>
  <?php $mq = !empty($gen['mapLat']) ? $gen['mapLat'] . ',' . $gen['mapLng'] : ($gen['address'] ?? ''); if ($mq): ?>
  <div class="container-x pb-10">
    <div class="relative overflow-hidden rounded-2xl ring-1 ring-white/10">
      <iframe title="<?= L('SIR KALIP konumu', 'SIR KALIP location') ?>" src="https://maps.google.com/maps?q=<?= rawurlencode($mq) ?>&z=16&hl=<?= locale() ?>&output=embed" class="block h-72 w-full md:h-80" loading="lazy" referrerpolicy="no-referrer-when-downgrade"></iframe>
      <div class="pointer-events-none absolute inset-x-3 bottom-3 md:inset-x-auto md:bottom-auto md:left-6 md:top-6 md:w-80">
        <div class="pointer-events-auto rounded-xl bg-white p-4 text-ink shadow-2xl md:p-5">
          <div class="flex items-center gap-2 text-sm font-extrabold"><?= icon('map-pin', 'h-5 w-5 text-brand') ?><?= L('Mağazamız', 'Our Store') ?></div>
          <p class="mt-1.5 text-sm text-muted"><?= e($gen['address'] ?? '') ?></p>
          <?php if (!empty($gen['workingHours'])): ?><p class="mt-1 text-xs text-muted"><?= e($gen['workingHours']) ?></p><?php endif; ?>
          <div class="mt-3 grid grid-cols-2 gap-2">
            <a href="<?= e($gen['mapUrl'] ?? ('https://www.google.com/maps/search/?api=1&query=' . rawurlencode($mq))) ?>" target="_blank" rel="noopener" class="btn-primary h-10 text-xs"><?= icon('map-pin', 'h-4 w-4') ?> <?= L('Yol Tarifi Al', 'Directions') ?></a>
            <a href="tel:<?= e(preg_replace('/[^\d+]/', '', $gen['phone'] ?? '')) ?>" class="btn h-10 border border-line text-xs hover:border-brand hover:text-brand"><?= icon('phone', 'h-4 w-4') ?> <?= L('Hemen Ara', 'Call Now') ?></a>
          </div>
        </div>
      </div>
    </div>
  </div>
  <?php endif; ?>
  <div class="border-t border-white/10 py-4 text-center text-xs">© <?= date('Y') ?> <?= e(($gen['companyTitle'] ?? '') ?: 'SIR KALIP') ?>. <?= e(t('footer.rights')) ?><?= !empty($gen['etbis']) ? ' · ETBİS: ' . e($gen['etbis']) : '' ?></div>
</footer>

<!-- Mobil alt menü -->
<nav class="fixed inset-x-0 bottom-0 z-40 grid grid-cols-5 border-t border-line bg-white md:hidden">
  <?php foreach ([['/', 'home', 'home'], ['/kategoriler', 'layout-grid', 'categories'], ['/sepet', 'shopping-cart', 'cart'], ['/favoriler', 'heart', 'favorites'], ['/hesabim', 'user', 'account']] as [$h, $ic, $k]): ?>
    <a href="<?= url($h === '/hesabim' && !customer() ? '/giris' : $h) ?>" <?= $k === 'cart' ? 'data-open-cart' : '' ?> class="relative flex flex-col items-center gap-0.5 py-2 text-[11px] font-semibold <?= ($GLOBALS['CURRENT_PATH'] ?? '') === $h ? 'text-brand' : 'text-ink' ?>"><?= icon($ic) ?><?= e(t("mobile.$k")) ?><?php if ($k === 'cart'): ?><span data-cart-count class="absolute right-[22%] top-1 flex h-4 min-w-4 items-center justify-center rounded-full bg-brand px-1 text-[10px] font-bold text-white"><?= cart_count() ?></span><?php endif; ?></a>
  <?php endforeach; ?>
</nav>

<a href="https://wa.me/<?= e($wa['number'] ?? '') ?>" target="_blank" rel="noopener" aria-label="WhatsApp" class="fixed bottom-20 right-4 z-40 rounded-full bg-wa p-3.5 text-white shadow-lg transition hover:scale-105 md:bottom-6"><?= social('whatsapp', 'h-7 w-7') ?></a>

<!-- Yandan açılan sepet -->
<div data-cart-drawer class="fixed inset-0 z-[70] hidden" aria-modal="true" role="dialog">
  <div data-close-cart class="absolute inset-0 bg-black/40 opacity-0 transition-opacity duration-300"></div>
  <aside data-cart-panel class="absolute inset-y-0 right-0 flex w-full max-w-[420px] translate-x-full flex-col bg-white shadow-2xl transition-transform duration-300 ease-out"></aside>
</div>
<div data-toast class="fixed left-1/2 top-24 z-[60] hidden w-[calc(100%-2rem)] max-w-sm -translate-x-1/2 rounded-xl bg-ink p-4 text-sm text-white shadow-2xl">
  <div class="flex items-center gap-2 font-semibold"><span data-toast-icon><?= icon('check', 'h-5 w-5 text-green-400') ?></span><span data-toast-msg></span></div>
  <div data-toast-actions class="mt-3 grid grid-cols-2 gap-2"><a href="<?= url('/sepet') ?>" class="btn h-9 border border-white/30 text-xs text-white"><?= L('Sepete Git', 'View Cart') ?></a><a href="<?= url('/odeme') ?>" class="btn-primary h-9 text-xs"><?= L('Alışverişi Tamamla', 'Checkout') ?></a></div>
</div>
<?php $ck = setting('cookies', ['enabled' => true]); if (($ck['enabled'] ?? true) !== false): ?>
<div data-cookie class="fixed inset-x-3 bottom-20 z-50 hidden rounded-xl border border-line bg-white p-4 shadow-2xl md:bottom-4 md:left-4 md:right-auto md:max-w-md">
  <p class="text-sm"><?= e($ck['text'] ?? L('Sitemizde deneyiminizi iyileştirmek için çerezler kullanıyoruz.', 'We use cookies to improve your experience.')) ?> <a href="<?= url('/sozlesmeler/cerez') ?>" class="font-semibold text-brand underline"><?= L('Detaylı bilgi', 'Learn more') ?></a></p>
  <div class="mt-3 flex gap-2"><button type="button" data-cookie-ok="all" class="btn-primary h-9 px-4 text-sm"><?= L('Kabul Et', 'Accept') ?></button><button type="button" data-cookie-ok="necessary" class="btn h-9 border border-line px-4 text-sm"><?= L('Sadece Zorunlu', 'Necessary only') ?></button></div>
</div>
<?php endif; ?>
<script src="<?= asset('js/app.js') ?>"></script>
<?= setting('scripts', [])['body'] ?? '' ?>
</body>
</html>
