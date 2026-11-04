<?php
$qs = $_GET; unset($qs['sayfa']);
$link = function (array $over = [], array $remove = []) use ($qs) { $q = array_merge($qs, $over); foreach ($remove as $r) unset($q[$r]); return '?' . http_build_query($q); };
$isFinder = strpos($GLOBALS['CURRENT_PATH'], '/parca-bul') === 0;
$activeFilters = [];
foreach ((array)($_GET['marka'] ?? []) as $b) if (!$isFinder && $b) $activeFilters[] = [$b, $link(['marka' => array_values(array_diff((array)$_GET['marka'], [$b]))])];
if (!empty($_GET['stok'])) $activeFilters[] = [L('Stoktakiler', 'In stock'), $link([], ['stok'])];
if (($_GET['min'] ?? '') !== '' || ($_GET['max'] ?? '') !== '') $activeFilters[] = [money($_GET['min'] ?: 0) . ' – ' . (($_GET['max'] ?? '') !== '' ? money($_GET['max']) : '∞'), $link([], ['min', 'max'])];
$favs = fav_ids();
?>
<?= breadcrumb($crumbs) ?>
<section class="container-x">
  <div class="mb-5 flex flex-col gap-1">
    <h1 class="text-2xl font-extrabold md:text-3xl"><?= e($title) ?></h1>
    <?php if ($intro): ?><p class="text-sm text-muted"><?= e($intro) ?></p><?php endif; ?>
  </div>

  <?php if ($isFinder): $brands = get_brands(); $mb = $_GET['marka_makine'] ?? ($_GET['marka'] ?? ''); if (is_array($mb)) $mb = ''; ?>
    <form class="mb-6 grid gap-3 rounded-xl bg-soft p-4 md:grid-cols-4" data-finder>
      <select name="marka_makine" data-finder-brand class="h-11 rounded-md border border-line bg-white px-3 text-sm font-medium">
        <?php foreach ($brands as $b): ?><option value="<?= e($b['slug']) ?>" data-models="<?= e(json_encode($b['models'], JSON_UNESCAPED_UNICODE)) ?>" <?= $mb === $b['slug'] ? 'selected' : '' ?>><?= e($b['name']) ?></option><?php endforeach; ?>
      </select>
      <select name="model" data-finder-model class="h-11 rounded-md border border-line bg-white px-3 text-sm font-medium">
        <option value=""><?= e(t('finder.all')) ?></option>
        <?php foreach ((function () use ($brands, $mb) { foreach ($brands as $b) if ($b['slug'] === $mb) return $b['models']; return $brands[0]['models'] ?? []; })() as $m): ?><option <?= ($_GET['model'] ?? '') === $m ? 'selected' : '' ?>><?= e($m) ?></option><?php endforeach; ?>
      </select>
      <select name="grup" class="h-11 rounded-md border border-line bg-white px-3 text-sm font-medium">
        <option value=""><?= e(t('finder.all')) ?></option>
        <?php foreach ($allCats as $c): ?><option value="<?= e($c['slug']) ?>" <?= ($_GET['grup'] ?? '') === $c['slug'] ? 'selected' : '' ?>><?= e(tr_field($c, 'name')) ?></option><?php endforeach; ?>
      </select>
      <button class="btn-primary h-11"><?= e(t('finder.submit')) ?></button>
    </form>
  <?php endif; ?>

  <div class="flex gap-6">
    <!-- Filtreler -->
    <aside data-filters class="fixed inset-0 z-50 hidden overflow-y-auto bg-white p-5 lg:static lg:z-auto lg:block lg:w-64 lg:shrink-0 lg:overflow-visible lg:bg-transparent lg:p-0">
      <div class="mb-4 flex items-center justify-between lg:hidden"><b class="text-lg"><?= L('Filtrele', 'Filter') ?></b><button type="button" data-close-filters><?= icon('x') ?></button></div>
      <form data-filter-form class="space-y-5">
        <?php foreach (['q', 'marka_makine', 'model', 'grup', 'sirala'] as $keep) if (isset($_GET[$keep]) && !is_array($_GET[$keep])) echo '<input type="hidden" name="' . $keep . '" value="' . e($_GET[$keep]) . '">'; ?>
        <div class="rounded-lg border border-line p-4">
          <div class="mb-3 text-sm font-bold"><?= L('Kategoriler', 'Categories') ?></div>
          <ul class="space-y-1.5 text-sm">
            <?php foreach ($allCats as $c): $on = $category && (int)$category['id'] === (int)$c['id']; ?>
              <li><a href="<?= url('/kategori/' . $c['slug']) ?>" class="flex items-center justify-between rounded px-1 py-0.5 <?= $on ? 'font-bold text-brand' : 'hover:text-brand' ?>"><?= e(tr_field($c, 'name')) ?><?= $on ? icon('check', 'h-4 w-4') : '' ?></a></li>
            <?php endforeach; ?>
          </ul>
        </div>
        <?php if (!$isFinder && $res['brand_facets']): ?>
        <div class="rounded-lg border border-line p-4">
          <div class="mb-3 text-sm font-bold"><?= L('Marka', 'Brand') ?></div>
          <?php foreach ($res['brand_facets'] as $bf): ?>
            <label class="flex cursor-pointer items-center gap-2 py-1 text-sm"><input type="checkbox" name="marka[]" value="<?= e($bf['slug']) ?>" <?= in_array($bf['slug'], (array)($_GET['marka'] ?? []), true) ? 'checked' : '' ?> class="h-4 w-4 accent-[#e30613]" data-autosubmit> <?= e($bf['name']) ?> <span class="ml-auto text-xs text-muted"><?= (int)$bf['n'] ?></span></label>
          <?php endforeach; ?>
        </div>
        <?php endif; ?>
        <div class="rounded-lg border border-line p-4">
          <div class="mb-3 text-sm font-bold"><?= L('Fiyat Aralığı', 'Price Range') ?></div>
          <div class="flex items-center gap-2">
            <input name="min" value="<?= e($_GET['min'] ?? '') ?>" inputmode="numeric" placeholder="<?= e((string)floor((float)($res['range']['mn'] ?? 0))) ?>" class="h-9 w-full rounded border border-line px-2 text-sm">
            <span>–</span>
            <input name="max" value="<?= e($_GET['max'] ?? '') ?>" inputmode="numeric" placeholder="<?= e((string)ceil((float)($res['range']['mx'] ?? 0))) ?>" class="h-9 w-full rounded border border-line px-2 text-sm">
          </div>
        </div>
        <?php foreach ($specs as $sid => $sp): ?>
          <div class="rounded-lg border border-line p-4">
            <div class="mb-3 text-sm font-bold"><?= e(tr_field($sp['key'], 'name')) ?></div>
            <div class="max-h-44 space-y-1 overflow-y-auto">
            <?php foreach ($sp['values'] as [$val, $n]): ?>
              <label class="flex cursor-pointer items-center gap-2 py-0.5 text-sm"><input type="checkbox" name="ozellik[<?= $sid ?>][]" value="<?= e($val) ?>" <?= in_array($val, (array)($_GET['ozellik'][$sid] ?? []), true) ? 'checked' : '' ?> class="h-4 w-4 accent-[#e30613]" data-autosubmit> <?= e($val) ?> <span class="ml-auto text-xs text-muted"><?= (int)$n ?></span></label>
            <?php endforeach; ?>
            </div>
          </div>
        <?php endforeach; ?>
        <label class="flex cursor-pointer items-center gap-2 rounded-lg border border-line p-4 text-sm font-semibold"><input type="checkbox" name="stok" value="1" <?= !empty($_GET['stok']) ? 'checked' : '' ?> class="h-4 w-4 accent-[#e30613]" data-autosubmit> <?= L('Sadece stoktakiler', 'In stock only') ?></label>
        <button class="btn-primary h-11 w-full"><?= L('Filtreleri Uygula', 'Apply Filters') ?></button>
      </form>
    </aside>

    <!-- Ürünler -->
    <div class="min-w-0 flex-1">
      <div class="mb-4 flex flex-wrap items-center gap-3">
        <button type="button" data-open-filters class="flex h-10 items-center gap-2 rounded-md border border-line px-4 text-sm font-semibold lg:hidden"><?= icon('filter', 'h-4 w-4') ?> <?= L('Filtrele', 'Filter') ?></button>
        <div class="text-sm text-muted"><b class="text-ink"><?= (int)$res['total'] ?></b> <?= L('ürün bulundu', 'products found') ?></div>
        <form class="ml-auto" data-sort-form>
          <?php foreach ($qs as $k => $v) if ($k !== 'sirala') { if (is_array($v)) { foreach ($v as $kk => $vv) { if (is_array($vv)) { foreach ($vv as $vvv) echo '<input type="hidden" name="' . e($k) . '[' . e($kk) . '][]" value="' . e($vvv) . '">'; } else echo '<input type="hidden" name="' . e($k) . '[]" value="' . e($vv) . '">'; } } else echo '<input type="hidden" name="' . e($k) . '" value="' . e($v) . '">'; } ?>
          <select name="sirala" onchange="this.form.submit()" class="h-10 rounded-md border border-line bg-white px-3 text-sm font-semibold">
            <?php foreach (['' => L('Önerilen sıralama', 'Recommended'), 'best' => L('Çok satanlar', 'Best sellers'), 'price' => L('Fiyat: Düşükten yükseğe', 'Price: low to high'), 'price_desc' => L('Fiyat: Yüksekten düşüğe', 'Price: high to low'), 'new' => L('En yeniler', 'Newest'), 'name' => L('İsme göre (A-Z)', 'Name (A-Z)')] as $k => $l): ?><option value="<?= $k ?>" <?= ($_GET['sirala'] ?? '') === $k ? 'selected' : '' ?>><?= e($l) ?></option><?php endforeach; ?>
          </select>
        </form>
      </div>
      <?php if ($activeFilters): ?>
        <div class="mb-4 flex flex-wrap gap-2"><?php foreach ($activeFilters as [$l, $h]): ?><a href="<?= e($h) ?>" class="flex items-center gap-1 rounded-full bg-brand-50 px-3 py-1 text-xs font-semibold text-brand"><?= e($l) ?> <?= icon('x', 'h-3 w-3') ?></a><?php endforeach; ?>
          <a href="<?= e('?' . http_build_query(array_intersect_key($_GET, array_flip(['q', 'marka_makine', 'model', 'grup'])))) ?>" class="px-2 py-1 text-xs font-semibold text-muted hover:text-brand"><?= L('Tümünü temizle', 'Clear all') ?></a></div>
      <?php endif; ?>

      <?php if ($res['rows']): ?>
        <div class="grid grid-cols-2 gap-3 sm:grid-cols-3 xl:grid-cols-4">
          <?php foreach ($res['rows'] as $p) view('partials/product_card', ['p' => $p, 'favs' => $favs]); ?>
        </div>
        <?php if ($res['pages'] > 1): ?>
          <nav class="mt-8 flex justify-center gap-1">
            <?php for ($i = 1; $i <= $res['pages']; $i++): ?><a href="<?= e($link(['sayfa' => $i])) ?>" class="flex h-10 min-w-10 items-center justify-center rounded-md border px-3 text-sm font-semibold <?= $i == $res['page'] ? 'border-brand bg-brand text-white' : 'border-line hover:border-brand' ?>"><?= $i ?></a><?php endfor; ?>
          </nav>
        <?php endif; ?>
      <?php else: ?>
        <div class="rounded-xl border border-dashed border-line p-10 text-center">
          <?= icon('search', 'mx-auto h-10 w-10 text-muted') ?>
          <h2 class="mt-3 text-lg font-bold"><?= L('Aradığınız kriterlere uygun ürün bulunamadı', 'No products match your criteria') ?></h2>
          <p class="mt-1 text-sm text-muted"><?= L('Filtreleri azaltmayı deneyin ya da parçanın fotoğrafını bize gönderin, sizin için bulalım.', 'Try removing filters, or send us a photo of the part and we will find it for you.') ?></p>
          <div class="mt-5 flex flex-wrap justify-center gap-3">
            <a href="<?= e(wa_link(L('Merhaba, bir parça arıyorum: ', 'Hello, I am looking for a part: ') . ($f['q'] ?? ''))) ?>" target="_blank" rel="noopener" class="btn h-11 bg-wa px-5 text-white"><?= social('whatsapp', 'h-5 w-5') ?> <?= L('WhatsApp ile Sor', 'Ask on WhatsApp') ?></a>
            <a href="<?= url('/kategoriler') ?>" class="btn h-11 border border-line px-5"><?= L('Tüm Kategoriler', 'All Categories') ?></a>
          </div>
        </div>
      <?php endif; ?>
    </div>
  </div>
</section>
