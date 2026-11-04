<?php
$cats = db()->query('SELECT c.*, (SELECT COUNT(*) FROM products p WHERE p.category_id = c.id AND p.is_active = 1 AND p.deleted_at IS NULL) n FROM categories c WHERE c.is_active = 1 AND c.parent_id IS NULL ORDER BY sort_order')->fetchAll();
view('layout/top', ['title' => L('Tüm Kategoriler', 'All Categories')]);
echo breadcrumb([[L('Tüm Kategoriler', 'All Categories'), null]]);
?>
<section class="container-x">
  <h1 class="mb-6 text-3xl font-extrabold"><?= L('Balya Makinesi Yedek Parça Grupları', 'Baler Spare Part Groups') ?></h1>
  <div class="grid grid-cols-2 gap-4 sm:grid-cols-3 lg:grid-cols-4">
    <?php foreach ($cats as $c): ?>
      <a href="<?= url('/kategori/' . $c['slug']) ?>" class="group flex items-center gap-4 rounded-xl border border-line bg-white p-4 transition hover:border-brand hover:shadow-md">
        <div class="w-20 shrink-0"><?= $c['image'] ? '<img src="' . e(media_url($c['image'])) . '" alt="" class="aspect-square w-full object-contain">' : part_art($c['icon']) ?></div>
        <div class="min-w-0"><div class="font-bold group-hover:text-brand"><?= e(tr_field($c, 'name')) ?></div><div class="text-xs text-muted"><?= e(tr_field($c, 'desc')) ?></div><div class="mt-1 text-xs font-semibold text-brand"><?= (int)$c['n'] ?> <?= L('ürün', 'products') ?></div></div>
      </a>
    <?php endforeach; ?>
  </div>
</section>
<?php view('layout/bottom');
