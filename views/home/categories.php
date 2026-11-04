<section class="container-x mt-12">
  <div class="mb-5 flex items-center justify-between">
    <h2 class="text-xl font-extrabold md:text-2xl"><?= e(t('categories.title')) ?></h2>
    <a href="<?= url('/kategoriler') ?>" class="flex items-center gap-1 text-sm font-semibold text-brand"><?= e(t('categories.all')) ?> <?= icon('arrow-right', 'h-4 w-4') ?></a>
  </div>
  <div class="grid grid-cols-2 gap-3 sm:grid-cols-3 lg:grid-cols-6">
    <?php foreach ($categories as $c): ?>
      <a href="<?= url('/kategori/' . $c['slug']) ?>" class="group rounded-lg border border-line bg-white p-3 transition hover:border-brand hover:shadow-md">
        <?= $c['image'] ? '<img src="' . e(media_url($c['image'])) . '" alt="" class="aspect-square w-full rounded-md bg-white object-contain transition duration-300 group-hover:scale-[1.05]" loading="lazy">' : part_art($c['icon'], 'transition group-hover:scale-[1.03]') ?>
        <div class="mt-3 text-sm font-bold"><?= e(tr_field($c, 'name')) ?></div>
        <div class="text-xs text-muted"><?= e(tr_field($c, 'desc')) ?></div>
      </a>
    <?php endforeach; ?>
  </div>
</section>
