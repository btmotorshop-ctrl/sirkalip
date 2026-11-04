<?php
$ids = fav_ids();
$rows = $ids ? db()->query('SELECT p.*, c.slug category_slug FROM products p JOIN categories c ON c.id = p.category_id WHERE p.id IN (' . implode(',', $ids) . ') AND p.is_active = 1 AND p.deleted_at IS NULL')->fetchAll() : [];
view('layout/top', ['title' => L('Favorilerim', 'My Favorites')]);
echo breadcrumb([[L('Favorilerim', 'My Favorites'), null]]);
?>
<section class="container-x">
  <h1 class="mb-6 text-2xl font-extrabold md:text-3xl"><?= L('Favorilerim', 'My Favorites') ?> <span class="text-base font-semibold text-muted">(<?= count($rows) ?>)</span></h1>
  <?php if (!customer() && $rows): ?><div class="mb-4 rounded-lg bg-soft px-4 py-3 text-sm"><?= L('Favorileriniz bu cihazda saklanıyor.', 'Favorites are stored on this device.') ?> <a href="<?= url('/giris') ?>?r=<?= rawurlencode(url('/favoriler')) ?>" class="font-bold text-brand"><?= L('Giriş yaparak', 'Log in') ?></a> <?= L('hesabınıza kaydedin.', 'to save them to your account.') ?></div><?php endif; ?>
  <?php if ($rows): ?><div class="grid grid-cols-2 gap-3 sm:grid-cols-3 lg:grid-cols-5"><?php foreach ($rows as $p) view('partials/product_card', ['p' => $p, 'favs' => $ids]); ?></div>
  <?php else: ?><div class="rounded-xl border border-dashed border-line p-12 text-center"><?= icon('heart', 'mx-auto h-12 w-12 text-muted') ?><h2 class="mt-4 text-xl font-bold"><?= L('Favori listeniz boş', 'No favorites yet') ?></h2><p class="mt-1 text-sm text-muted"><?= L('Ürünlerdeki kalp simgesine tıklayarak favorilerinize ekleyebilirsiniz.', 'Tap the heart icon on products to add them here.') ?></p><a href="<?= url('/kategori/balya-makinesi') ?>" class="btn-primary mt-6 h-11 px-6"><?= L('Ürünleri Keşfet', 'Explore Products') ?></a></div><?php endif; ?>
</section>
<?php view('layout/bottom');
