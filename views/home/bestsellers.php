<?php $tabs = array_values(array_filter($categories, function ($c) use ($products) { foreach ($products as $p) if ($p['category_slug'] === $c['slug']) return true; return false; })); ?>
<section class="container-x mt-12" data-tabs>
  <div class="mb-5 flex flex-col gap-3 md:flex-row md:items-center">
    <h2 class="shrink-0 text-xl font-extrabold md:text-2xl"><?= e(t('best.title')) ?></h2>
    <div class="no-scrollbar flex flex-1 gap-1 overflow-x-auto md:ml-6">
      <button type="button" data-tab="" class="tab-btn is-active shrink-0 rounded-md px-3 py-1.5 text-[13px] font-semibold"><?= e(t('best.all')) ?></button>
      <?php foreach ($tabs as $c): ?>
        <button type="button" data-tab="<?= e($c['slug']) ?>" class="tab-btn shrink-0 rounded-md px-3 py-1.5 text-[13px] font-semibold"><?= e(tr_field($c, 'name')) ?></button>
      <?php endforeach; ?>
    </div>
    <div class="hidden gap-2 md:flex">
      <button type="button" data-scroll="-1" class="rounded-full border border-line p-2 hover:border-brand" aria-label="Geri"><?= icon('chevron-left', 'h-4 w-4') ?></button>
      <button type="button" data-scroll="1" class="rounded-full border border-line p-2 hover:border-brand" aria-label="İleri"><?= icon('chevron-right', 'h-4 w-4') ?></button>
    </div>
  </div>
  <div data-row class="no-scrollbar flex snap-x gap-3 overflow-x-auto pb-2">
    <?php foreach ($products as $p): ?>
      <div data-cat="<?= e($p['category_slug']) ?>" class="w-[46%] shrink-0 snap-start sm:w-[31%] lg:w-[calc((100%-60px)/6)]"><?php view('partials/product_card', ['p' => $p]); ?></div>
    <?php endforeach; ?>
  </div>
</section>
