<?php
$brands = get_brands();
view('layout/top', ['title' => L('Markalar', 'Brands')]);
echo breadcrumb([[L('Markalar', 'Brands'), null]]);
?>
<section class="container-x">
  <h1 class="mb-2 text-3xl font-extrabold"><?= L('Markalar', 'Brands') ?></h1>
  <p class="mb-6 text-muted"><?= L('Makinenizin markasını seçin, uyumlu parçaları görüntüleyin.', 'Choose your machine brand to see compatible parts.') ?></p>
  <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3">
    <?php foreach ($brands as $b): ?>
      <div class="brand-tile rounded-xl border border-line p-5 transition hover:border-brand hover:shadow-md">
        <a href="<?= url('/marka/' . $b['slug']) ?>" class="brand-title"><?= e($b['name']) ?></a>
        <?php if ($b['models']): ?><div class="mt-3 flex flex-wrap gap-1.5"><?php foreach ($b['models'] as $m): ?><a href="<?= url('/parca-bul') ?>?marka_makine=<?= e($b['slug']) ?>&model=<?= rawurlencode($m) ?>" class="rounded-full bg-soft px-2.5 py-1 text-xs font-semibold hover:bg-brand-50 hover:text-brand"><?= e($m) ?></a><?php endforeach; ?></div><?php endif; ?>
        <a href="<?= url('/marka/' . $b['slug']) ?>" class="mt-4 inline-flex items-center gap-1 text-sm font-bold text-brand"><?= L('Parçaları gör', 'View parts') ?> <?= icon('arrow-right', 'h-4 w-4') ?></a>
      </div>
    <?php endforeach; ?>
  </div>
</section>
<?php view('layout/bottom');
