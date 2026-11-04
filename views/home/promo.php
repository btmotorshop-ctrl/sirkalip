<?php $wa = setting('whatsapp', ['number' => '']); ?>
<section class="container-x mt-12 grid gap-4 md:grid-cols-3">
  <div class="rounded-xl bg-gradient-to-br from-brand to-brand-dark p-6 text-white">
    <div class="flex items-center gap-3"><span class="rounded-full bg-white/15 p-3"><?= icon('percent', 'h-6 w-6') ?></span><h3 class="text-xl font-extrabold"><?= e(t('promo.c1t')) ?></h3></div>
    <p class="mt-2 text-sm text-white/85"><?= e(t('promo.c1d')) ?></p>
    <a href="<?= url('/kampanyalar') ?>" class="btn-outline mt-5 h-10 px-5 text-sm"><?= e(t('promo.c1b')) ?> <?= icon('arrow-right', 'h-4 w-4') ?></a>
  </div>
  <div class="rounded-xl bg-ink p-6 text-white">
    <div class="flex items-center gap-3"><span class="rounded-full bg-white/10 p-3"><?= icon('wrench', 'h-6 w-6') ?></span><h3 class="text-xl font-extrabold"><?= e(t('promo.c2t')) ?></h3></div>
    <p class="mt-2 text-sm text-white/80"><?= e(t('promo.c2d')) ?></p>
    <a href="<?= url('/teknik-bilgiler') ?>" class="btn-outline mt-5 h-10 px-5 text-sm"><?= e(t('promo.c2b')) ?> <?= icon('arrow-right', 'h-4 w-4') ?></a>
  </div>
  <div class="rounded-xl bg-gradient-to-br from-green-600 to-green-800 p-6 text-white">
    <div class="flex items-center gap-3"><span class="rounded-full bg-white/15 p-3"><?= social('whatsapp', 'h-6 w-6') ?></span><h3 class="text-lg font-extrabold leading-tight"><?= e(t('promo.c3t')) ?></h3></div>
    <p class="mt-2 text-sm text-white/85"><?= e(t('promo.c3d')) ?></p>
    <a href="https://wa.me/<?= e($wa['number'] ?? '') ?>" target="_blank" rel="noopener" class="btn mt-5 h-10 bg-white px-5 text-sm text-green-700 hover:bg-green-50"><?= e(t('promo.c3b')) ?> <?= icon('arrow-right', 'h-4 w-4') ?></a>
  </div>
</section>
