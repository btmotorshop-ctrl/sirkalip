<?php
$heroBanners = db()->query("SELECT * FROM banners WHERE position = 'home_hero' AND is_active = 1 AND image IS NOT NULL AND image <> '' AND (starts_at IS NULL OR starts_at <= NOW()) AND (ends_at IS NULL OR ends_at >= NOW()) ORDER BY sort_order, id")->fetchAll();
if ($heroBanners): ?>
<section class="container-x pt-4 md:pt-6" data-hero-slider>
  <div class="relative overflow-hidden rounded-2xl bg-ink shadow-lg">
    <?php foreach ($heroBanners as $i => $bn): $alt = tr_field($bn, 'title') ?: 'SIR KALIP'; $href = $bn['link'] ?: url('/kategori/balya-makinesi'); ?>
      <a href="<?= e($href) ?>" data-hero-slide class="<?= $i ? 'absolute inset-0 opacity-0' : 'relative' ?> block transition-opacity duration-700" aria-label="<?= e($alt) ?>">
        <picture>
          <?php if ($bn['mobile_image']): ?><source media="(max-width: 767px)" srcset="<?= e($bn['mobile_image']) ?>"><?php endif; ?>
          <img src="<?= e(media_url($bn['image'])) ?>" alt="<?= e($alt) ?>" width="2089" height="753" class="block h-auto w-full" <?= $i ? 'loading="lazy"' : 'fetchpriority="high"' ?>>
        </picture>
      </a>
    <?php endforeach; ?>
    <?php if (count($heroBanners) > 1): ?>
      <button type="button" data-hero-prev style="z-index:20" class="absolute left-3 top-1/2 hidden -translate-y-1/2 rounded-full bg-white/80 p-2 shadow hover:bg-white md:block" aria-label="Önceki"><?= icon('chevron-left', 'h-5 w-5') ?></button>
      <button type="button" data-hero-next style="z-index:20" class="absolute right-3 top-1/2 hidden -translate-y-1/2 rounded-full bg-white/80 p-2 shadow hover:bg-white md:block" aria-label="Sonraki"><?= icon('chevron-right', 'h-5 w-5') ?></button>
      <div class="absolute inset-x-0 bottom-3 flex justify-center gap-2" style="z-index:20"><?php foreach ($heroBanners as $i => $bn): ?><button type="button" data-hero-dot="<?= $i ?>" class="h-2.5 rounded-full bg-white/60 transition-all <?= $i ? 'w-2.5' : 'w-7 bg-white' ?>" aria-label="Banner <?= $i + 1 ?>"></button><?php endforeach; ?></div>
    <?php endif; ?>
  </div>
</section>
<?php return; endif; ?>
<section class="relative overflow-hidden bg-ink">
  <div class="absolute inset-0 bg-[radial-gradient(ellipse_at_78%_30%,#ffb347_0%,#f97316_18%,#7c2d12_45%,#1f2530_75%)]"></div>
  <div class="absolute inset-x-0 bottom-0 h-1/3 bg-[repeating-linear-gradient(100deg,#8a5a1c_0_6px,#6b4414_6px_12px)] opacity-80"></div>
  <div class="absolute inset-0 bg-gradient-to-r from-black/70 via-black/25 to-transparent"></div>
  <img src="<?= asset('img/hero-parts.webp') ?>" alt="<?= L('Balya makinesi yedek parçaları', 'Baler spare parts') ?>" width="1400" height="1000" class="pointer-events-none absolute bottom-[6%] right-[3%] hidden h-[88%] w-auto max-w-[52%] object-contain drop-shadow-[0_25px_35px_rgba(0,0,0,.5)] md:block" fetchpriority="high">
  <img src="<?= asset('img/hero-parts.webp') ?>" alt="" class="pointer-events-none absolute -right-16 bottom-0 h-[70%] w-auto opacity-35 md:hidden">
  <div class="container-x relative flex min-h-[300px] flex-col justify-center py-12 md:min-h-[420px]">
    <h1 class="break-words text-[30px] font-black leading-[1.08] text-white sm:text-4xl md:text-6xl">
      <?php $ds = locale() === 'tr' ? setting('design', []) : []; ?><?= e(($ds['heroTitle1'] ?? '') ?: t('hero.title1')) ?><br><span class="text-accent"><?= e(($ds['heroTitle2'] ?? '') ?: t('hero.title2')) ?></span>
    </h1>
    <p class="mt-5 max-w-lg text-[15px] leading-relaxed text-white/90 md:text-lg"><?= e(($ds['heroText'] ?? '') ?: t('hero.text')) ?></p>
    <a href="<?= url('/kategori/balya-makinesi') ?>" class="btn-primary mt-7 h-12 w-fit px-7 text-[15px]"><?= e(t('hero.cta')) ?> <?= icon('arrow-right') ?></a>
  </div>
</section>
