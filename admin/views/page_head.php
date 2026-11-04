<div class="mb-5 flex flex-wrap items-center justify-between gap-3">
  <div>
    <div class="text-xs text-muted"><a href="<?= aurl(acc_shell() ? 'acc' : '') ?>" class="hover:text-brand"><?= acc_shell() ? 'Ön Muhasebe' : 'Anasayfa' ?></a><?php foreach ($crumbs ?? [] as [$cl, $ch]): if (acc_shell() && $cl === 'Ön Muhasebe') continue; ?> › <?= $ch ? '<a href="' . e($ch) . '" class="hover:text-brand">' . e($cl) . '</a>' : e($cl) ?><?php endforeach; ?></div>
    <h1 class="mt-1 text-xl font-extrabold"><?= e($title) ?></h1>
  </div>
  <div class="flex flex-wrap gap-2"><?= $actions ?? '' ?></div>
</div>
