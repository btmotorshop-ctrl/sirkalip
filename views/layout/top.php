<?php
$categories = get_categories();
$brands = get_brands();
$nav = [
  ['/kategori/balya-makinesi', t('nav.baler'), 'cat'],
  ['/markalar', t('nav.brands'), null],
  ['/kampanyalar', t('nav.campaigns'), null],
  ['/yeni-urunler', t('nav.newProducts'), null],
  ['/teknik-bilgiler', t('nav.tech'), null],
  ['/hakkimizda', t('nav.about'), null],
  ['/iletisim', t('nav.contact'), null],
];
if (empty($product)) track_visit();
$pageTitle = !empty($title) ? $title . ' | SIR KALIP' : 'SIR KALIP | ' . L('Balya Makinesi Yedek Parça', 'Baler Spare Parts');
?><!doctype html>
<html lang="<?= locale() ?>" dir="<?= is_rtl() ? 'rtl' : 'ltr' ?>">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title><?= e($pageTitle) ?></title>
  <meta name="description" content="<?= e($meta ?? L('Gallignani, CLAAS, Welger, John Deere, New Holland, BCS ve Paksan balya makineleri için bağlama ünitesi (knotter), mekik, yıldız, bıçak kolu ve dişli yedek parçaları. Hızlı kargo, güvenli ödeme.', 'Baler spare parts: pickup, knotter, needles, bearings, chains and gears. Fast shipping, secure payment.')) ?>">
  <meta property="og:title" content="<?= e($pageTitle) ?>"><meta property="og:type" content="<?= !empty($product) ? 'product' : 'website' ?>">
  <link rel="canonical" href="<?= e(url($GLOBALS['CURRENT_PATH'] ?? '/')) ?>">
  <?php foreach (array_keys(locales()) as $__l): ?><link rel="alternate" hreflang="<?= $__l ?>" href="<?= e(url($GLOBALS['CURRENT_PATH'] ?? '/', $__l)) ?>"><?php endforeach; ?>
  <link rel="preconnect" href="https://fonts.googleapis.com"><link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Barlow+Condensed:ital,wght@0,600;0,700;0,800;1,700;1,800&display=swap">
  <link rel="stylesheet" href="<?= asset('css/style.css') ?>">
  <link rel="icon" href="<?= asset('img/favicon.png') ?>" type="image/png">
  <script>window.SK = { base: <?= json_encode(base_path()) ?>, lang: <?= json_encode(locale()) ?>, url: <?= json_encode(url('/')) ?>, cartUrl: <?= json_encode(url('/sepet')) ?>, checkoutUrl: <?= json_encode(url('/odeme')) ?>, searchUrl: <?= json_encode(url('/arama')) ?> };</script>
  <?php $scr = setting('scripts', []); echo $scr['head'] ?? ''; ?>
</head>
<body>
<?php if (!empty($minimal)): ?>
<header class="border-b border-line bg-white"><div class="container-x flex h-16 items-center justify-between"><?php view('partials/logo', ['tagline' => true]); ?><span class="flex items-center gap-2 text-sm font-semibold text-ok"><?= icon('lock', 'h-4 w-4') ?> <?= L('Güvenli Ödeme', 'Secure Checkout') ?></span></div></header>
<main>
<?php return; endif; ?>

<!-- Üst bar -->
<?php $tg = setting('general', []); $tgTel = preg_replace('/[^\d+]/', '', $tg['phone'] ?? ''); ?>
<div class="sticky top-0 z-40 shadow-sm" data-sticky-head>
<div class="bg-ink text-[12px] text-white/90">
  <div class="container-x flex h-9 items-center justify-between gap-4">
    <div class="flex min-w-0 items-center gap-4 sm:gap-5">
      <?php if (!empty($tg['phone'])): ?><a href="tel:<?= e($tgTel) ?>" class="flex shrink-0 items-center gap-1.5 font-semibold hover:text-white"><?= icon('phone', 'h-3.5 w-3.5 text-accent') ?><?= e($tg['phone']) ?></a><?php endif; ?>
      <?php if (!empty($tg['email'])): ?><a href="mailto:<?= e($tg['email']) ?>" class="flex min-w-0 items-center gap-1.5 font-semibold hover:text-white"><?= icon('mail', 'h-3.5 w-3.5 shrink-0 text-accent') ?><span class="truncate"><?= e($tg['email']) ?></span></a><?php endif; ?>
    </div>
    <div class="hidden items-center gap-6 lg:flex">
      <?php foreach ([['truck', 'fastShipping'], ['lock', 'securePay'], ['boxes', 'products'], ['headset', 'support']] as [$ic, $k]): ?>
        <span class="flex items-center gap-1.5"><?= icon($ic, 'h-4 w-4') ?><?= e(t("topbar.$k")) ?></span>
      <?php endforeach; ?>
    </div>
    <div class="hidden items-center gap-3 md:flex">
      <?php view('partials/locale_switch'); ?>
      <span class="h-4 w-px bg-white/20"></span>
      <a href="#" aria-label="Facebook"><?= social('facebook') ?></a>
      <a href="#" aria-label="Instagram"><?= social('instagram') ?></a>
      <a href="#" aria-label="YouTube"><?= social('youtube') ?></a>
      <a href="<?= e($tg['tiktok'] ?? 'https://www.tiktok.com/@sirkalip') ?>" target="_blank" rel="noopener" aria-label="TikTok" class="hover:text-white"><?= social('tiktok') ?></a>
    </div>
  </div>
</div>

<header class="border-b border-line bg-white">
  <div class="container-x flex h-16 items-center gap-4 md:h-20 md:gap-8">
    <button class="md:hidden" data-open="drawer" aria-label="Menü"><?= icon('menu', 'h-6 w-6') ?></button>
    <?php view('partials/logo', ['tagline' => true]); ?>
    <form action="<?= url('/arama') ?>" class="relative hidden flex-1 md:flex" data-search>
      <input name="q" value="<?= e($_GET['q'] ?? '') ?>" autocomplete="off" data-search-input placeholder="<?= e(t('header.search')) ?>" class="h-11 w-full rounded-l-md border border-r-0 border-line bg-soft px-4 text-sm outline-none focus:border-brand">
      <button class="flex h-11 w-14 items-center justify-center rounded-r-md bg-brand text-white hover:bg-brand-dark" aria-label="Ara"><?= icon('search') ?></button>
      <div data-search-results class="absolute inset-x-0 top-full z-50 mt-1 hidden overflow-hidden rounded-lg border border-line bg-white shadow-xl"></div>
    </form>
    <div class="ml-auto flex items-center gap-5">
      <?php $cu = customer(); ?>
      <div class="group relative hidden md:block">
        <a href="<?= url($cu ? '/hesabim' : '/giris') ?>" class="flex items-center gap-2 text-sm font-semibold"><?= icon('user') ?><span class="max-w-28 truncate"><?= $cu ? e($cu['first_name']) : e(t('header.account')) ?></span></a>
        <div class="invisible absolute right-0 top-full z-50 w-56 pt-3 opacity-0 transition group-hover:visible group-hover:opacity-100">
          <div class="rounded-lg border border-line bg-white p-2 text-sm shadow-xl">
            <?php if ($cu): ?>
              <?php foreach ([['/hesabim', L('Hesabım', 'My Account')], ['/hesabim/siparislerim', L('Siparişlerim', 'My Orders')], ['/hesabim/adreslerim', L('Adreslerim', 'Addresses')], ['/favoriler', L('Favorilerim', 'Favorites')]] as [$h, $l]): ?><a href="<?= url($h) ?>" class="block rounded px-3 py-2 hover:bg-soft"><?= e($l) ?></a><?php endforeach; ?>
              <a href="<?= url('/cikis') ?>" class="block rounded px-3 py-2 text-brand hover:bg-soft"><?= L('Çıkış Yap', 'Log Out') ?></a>
            <?php else: ?>
              <a href="<?= url('/giris') ?>" class="btn-primary h-10 w-full text-sm"><?= L('Giriş Yap', 'Log In') ?></a>
              <a href="<?= url('/uye-ol') ?>" class="btn mt-2 h-10 w-full border border-line text-sm"><?= L('Üye Ol', 'Sign Up') ?></a>
              <a href="<?= url('/siparis-takip') ?>" class="mt-2 block rounded px-3 py-2 text-center text-xs font-semibold text-muted hover:text-brand"><?= L('Sipariş Takibi', 'Track Order') ?></a>
            <?php endif; ?>
          </div>
        </div>
      </div>
      <a href="<?= url('/favoriler') ?>" class="relative hidden md:block" aria-label="<?= L('Favorilerim', 'Favorites') ?>"><?= icon('heart', 'h-6 w-6') ?><span data-fav-count class="absolute -right-2 -top-2 flex h-5 min-w-5 items-center justify-center rounded-full bg-ink px-1 text-[11px] font-bold text-white <?= count(fav_ids()) ? '' : 'hidden' ?>"><?= count(fav_ids()) ?></span></a>
      <a href="<?= url('/sepet') ?>" data-open-cart class="relative flex items-center gap-2 text-sm font-semibold">
        <?= icon('shopping-cart', 'h-6 w-6') ?>
        <span class="hidden md:inline"><?= e(t('header.cart')) ?></span>
        <span data-cart-count class="absolute -top-2 left-4 flex h-5 min-w-5 items-center justify-center rounded-full bg-brand px-1 text-[11px] font-bold text-white"><?= cart_count() ?></span>
      </a>
    </div>
  </div>

  <form action="<?= url('/arama') ?>" class="container-x relative flex pb-3 md:hidden" data-search>
    <input name="q" value="<?= e($_GET['q'] ?? '') ?>" autocomplete="off" data-search-input placeholder="<?= e(t('header.search')) ?>" class="h-10 w-full rounded-l-md border border-r-0 border-line bg-soft px-3 text-sm outline-none">
    <button class="flex h-10 w-12 items-center justify-center rounded-r-md bg-brand text-white" aria-label="Ara"><?= icon('search', 'h-4 w-4') ?></button>
    <div data-search-results class="absolute inset-x-4 top-full z-50 hidden overflow-hidden rounded-lg border border-line bg-white shadow-xl"></div>
  </form>

  <nav class="relative hidden border-t border-line md:block" data-mega-nav>
    <div class="container-x flex h-12 items-center gap-7">
      <button data-mega-trigger class="flex h-10 w-56 items-center justify-between rounded-md bg-brand px-4 text-sm font-semibold text-white">
        <span class="flex items-center gap-2"><?= icon('menu', 'h-4 w-4') ?><?= e(t('header.allCategories')) ?></span><?= icon('arrow-right', 'h-4 w-4') ?>
      </button>
      <?php foreach ($nav as [$href, $label, $drop]): ?>
        <a href="<?= url($href) ?>" <?= $drop === 'cat' ? 'data-mega-trigger' : 'data-mega-close' ?> class="flex items-center gap-1 text-[13.5px] font-semibold text-ink hover:text-brand">
          <?= e($label) ?><?= $drop ? icon('chevron-down', 'h-3.5 w-3.5') : '' ?>
        </a>
      <?php endforeach; ?>
    </div>
    <div data-mega class="absolute inset-x-0 top-full hidden border-t border-line bg-white shadow-xl">
      <div class="container-x grid grid-cols-4 gap-x-8 gap-y-1 py-6">
        <?php foreach ($categories as $c): ?>
          <a href="<?= url('/kategori/' . $c['slug']) ?>" class="rounded px-2 py-2 hover:bg-soft">
            <div class="text-sm font-semibold"><?= e(tr_field($c, 'name')) ?></div>
            <div class="text-xs text-muted"><?= e(tr_field($c, 'desc')) ?></div>
          </a>
        <?php endforeach; ?>
        <div class="col-span-4 mt-3 flex flex-wrap gap-2 border-t border-line pt-4">
          <?php foreach ($brands as $b): ?>
            <a href="<?= url('/marka/' . $b['slug']) ?>" class="rounded-full border border-line px-3 py-1 text-xs font-semibold hover:border-brand hover:text-brand"><?= e($b['name']) ?></a>
          <?php endforeach; ?>
        </div>
      </div>
    </div>
  </nav>
</header>
</div>

<!-- Mobil çekmece menü -->
<div data-drawer class="fixed inset-0 z-50 hidden md:hidden">
  <div class="absolute inset-0 bg-black/40" data-close="drawer"></div>
  <div class="absolute inset-y-0 left-0 w-80 max-w-[85%] overflow-y-auto bg-white p-4">
    <div class="mb-4 flex items-center justify-between"><?php view('partials/logo'); ?><button data-close="drawer" aria-label="Kapat"><?= icon('x') ?></button></div>
    <div class="mb-2 text-xs font-bold uppercase text-muted"><?= e(t('header.allCategories')) ?></div>
    <?php foreach ($categories as $c): ?>
      <a href="<?= url('/kategori/' . $c['slug']) ?>" class="block border-b border-line py-2.5 text-sm font-medium"><?= e(tr_field($c, 'name')) ?></a>
    <?php endforeach; ?>
    <div class="mt-4 space-y-1">
      <?php foreach (array_slice($nav, 1) as [$href, $label]): ?>
        <a href="<?= url($href) ?>" class="block py-2 text-sm font-semibold"><?= e($label) ?></a>
      <?php endforeach; ?>
    </div>
    <div class="mt-4 border-t border-line pt-4 space-y-1">
      <?php if (customer()): ?><a href="<?= url('/hesabim') ?>" class="block py-2 text-sm font-semibold"><?= L('Hesabım', 'My Account') ?></a><a href="<?= url('/cikis') ?>" class="block py-2 text-sm font-semibold text-brand"><?= L('Çıkış Yap', 'Log Out') ?></a>
      <?php else: ?><a href="<?= url('/giris') ?>" class="btn-primary h-10 w-full text-sm"><?= L('Giriş Yap / Üye Ol', 'Log In / Sign Up') ?></a><?php endif; ?>
      <a href="<?= url('/siparis-takip') ?>" class="block py-2 text-sm font-semibold"><?= L('Sipariş Takibi', 'Order Tracking') ?></a>
      <a href="<?= url('/sss') ?>" class="block py-2 text-sm font-semibold"><?= L('Sıkça Sorulan Sorular', 'FAQ') ?></a>
    </div>
    <div class="mt-4"><?php view('partials/locale_switch', ['dark' => true]); ?></div>
  </div>
</div>

<main>
<?php if ($fm = flash_msg()): ?>
  <div class="container-x pt-4"><div data-flash class="flex items-center gap-2 rounded-lg px-4 py-3 text-sm font-semibold <?= $fm[1] === 'err' ? 'bg-red-50 text-red-700' : 'bg-green-50 text-green-700' ?>"><?= icon($fm[1] === 'err' ? 'x' : 'check', 'h-4 w-4') ?><?= e($fm[0]) ?></div></div>
<?php endif; ?>
