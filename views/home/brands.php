<?php
$pop = array_values(array_filter($brands, function ($b) { return !isset($b['is_popular']) || (int)$b['is_popular'] === 1; }));
if (!$pop) return; ?>
<section class="mt-14">
  <div class="container-x mb-5 flex items-end justify-between gap-4">
    <div>
      <h2 class="text-xl font-extrabold md:text-2xl"><?= e(t('brands.title')) ?></h2>
      <p class="mt-1 text-sm text-muted"><?= L('Bu markaların makinelerine uyumlu yedek parçalar', 'Compatible spare parts for these brands') ?></p>
    </div>
    <a href="<?= url('/markalar') ?>" class="flex shrink-0 items-center gap-1 text-sm font-semibold text-brand"><?= L('Tüm Markalar', 'All Brands') ?> <?= icon('arrow-right', 'h-4 w-4') ?></a>
  </div>
  <div class="brand-marquee group relative overflow-hidden py-2" data-marquee>
    <div class="brand-track flex w-max gap-4 group-hover:[animation-play-state:paused]" style="--speed: <?= max(20, count($pop) * 4) ?>s">
      <?php for ($loop = 0; $loop < 2; $loop++): foreach ($pop as $b): ?>
        <a href="<?= url('/marka/' . $b['slug']) ?>" <?= $loop ? 'aria-hidden="true" tabindex="-1"' : '' ?> title="<?= e($b['name']) ?>"
           class="brand-card">
          <span class="brand-name"><?= e($b['name']) ?></span>
          <span class="brand-meta"><?= count($b['models'] ?? []) ? count($b['models']) . ' ' . L('model', 'models') : L('Yedek parça', 'Spare parts') ?></span>
        </a>
      <?php endforeach; endfor; ?>
    </div>
  </div>
</section>
