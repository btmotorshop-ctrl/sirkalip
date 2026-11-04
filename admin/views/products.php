<?php
$title = $deleted ? 'Geri Dönüşüm Kutusu (Silinen Ürünler)' : 'Ürün Yönetimi';
admin_view('layout_top', compact('title'));
$qs = $_GET; unset($qs['page']);
$pages = max(1, (int)ceil($total / $per));
$sel = function ($name, $opts, $label) { $v = $_GET[$name] ?? ''; $h = '<label class="w-44"><span class="alabel">' . $label . '</span><select name="' . $name . '" class="ainput"><option value="">Tümü</option>'; foreach ($opts as $k => $l) $h .= '<option value="' . e($k) . '"' . ((string)$v === (string)$k ? ' selected' : '') . '>' . e($l) . '</option>'; return $h . '</select></label>'; };
admin_view('page_head', ['title' => $title, 'crumbs' => [['Ürünler', null], [$title, null]],
  'actions' => '<a href="' . aurl('products/bulk') . '" class="abtn">' . icon('upload', 'h-4 w-4') . ' Toplu İşlemler</a><a href="' . aurl('products/bulk?do=export') . '" class="abtn">' . icon('download', 'h-4 w-4') . ' Dışa Aktar</a><a href="' . aurl('products/new') . '" class="abtn-primary">' . icon('plus', 'h-4 w-4') . ' Yeni Ürün Ekle</a>']);
?>
<div class="acard">
  <form class="flex flex-wrap items-end gap-3 border-b border-line p-4">
    <?php if ($deleted): ?><input type="hidden" name="deleted" value="1"><?php endif; ?>
    <label class="w-64"><span class="alabel">Ürün adı / kodu / barkod / referans no</span><input name="q" value="<?= e($_GET['q'] ?? '') ?>" class="ainput"></label>
    <?= $sel('category_id', $cats, 'Kategori') ?><?= $sel('brand_id', $brands, 'Marka') ?><?= $sel('supplier_id', $suppliers, 'Tedarikçi') ?>
    <?= $sel('active', ['1' => 'Aktif', '0' => 'Pasif'], 'Durum') ?>
    <?= $sel('stock', ['in' => 'Stokta', 'out' => 'Stokta yok', 'critical' => 'Kritik stok'], 'Stok') ?>
    <?= $sel('missing', ['desc' => 'Açıklamasız', 'image' => 'Resimsiz', 'seo' => 'SEO bilgisi eksik'], 'Eksik Bilgi') ?>
    <label class="w-24"><span class="alabel">Min ₺</span><input name="price_min" value="<?= e($_GET['price_min'] ?? '') ?>" class="ainput"></label>
    <label class="w-24"><span class="alabel">Max ₺</span><input name="price_max" value="<?= e($_GET['price_max'] ?? '') ?>" class="ainput"></label>
    <label class="flex items-center gap-1 pb-2 text-xs font-semibold"><input type="checkbox" name="featured" value="1" <?= !empty($_GET['featured']) ? 'checked' : '' ?>> Vitrin</label>
    <label class="flex items-center gap-1 pb-2 text-xs font-semibold"><input type="checkbox" name="discount" value="1" <?= !empty($_GET['discount']) ? 'checked' : '' ?>> İndirimli</label>
    <?= $sel('sort', ['new' => 'En yeni', 'name' => 'Ada göre', 'price' => 'Fiyat (artan)', 'price_desc' => 'Fiyat (azalan)', 'stock' => 'Stok (az→çok)', 'sales' => 'Çok satan'], 'Sırala') ?>
    <button class="abtn"><?= icon('filter', 'h-4 w-4') ?> Filtrele</button>
    <a href="<?= aurl('products' . ($deleted ? '?deleted=1' : '')) ?>" class="pb-2 text-xs font-semibold text-muted hover:text-brand">Temizle</a>
  </form>
  <form method="post">
    <?= csrf_field() ?>
    <div class="flex flex-wrap items-center gap-2 border-b border-line bg-soft/60 px-4 py-2 text-xs">
      <b>Seçilenlere:</b>
      <select name="bulk" class="ainput h-8 w-56 text-xs">
        <?php if ($deleted): ?><option value="restore">Geri yükle</option><option value="purge">Kalıcı olarak sil</option>
        <?php else: ?><option value="activate">Aktif yap</option><option value="deactivate">Pasif yap</option><option value="feature">Vitrine ekle</option><option value="unfeature">Vitrinden çıkar</option><option value="delete">Sil (geri dönüşüm kutusu)</option><?php endif; ?>
      </select>
      <button class="abtn h-8 text-xs">Uygula</button>
      <span class="ml-auto text-muted">Toplam <b><?= $total ?></b> ürün</span>
    </div>
    <div class="overflow-x-auto">
      <table class="atable">
        <thead><tr><th class="w-8"><input type="checkbox" data-check-all></th><th class="w-14">Görsel</th><th>Ürün Kodu</th><th>Ürün Adı</th><th>Kategori</th><th>Marka</th><th class="text-right">Fiyat</th><th class="text-right">Stok</th><th>Durum</th><th class="text-right">İşlemler</th></tr></thead>
        <tbody>
        <?php foreach ($rows as $p): [$price, $old] = product_prices($p); ?>
          <tr>
            <td><input type="checkbox" name="ids[]" value="<?= $p['id'] ?>" data-check></td>
            <td><div class="h-11 w-11 overflow-hidden rounded border border-line"><?= $p['img'] ? '<img src="' . e(media_url($p['img'])) . '" class="h-full w-full object-cover" alt="">' : part_art($p['icon']) ?></div></td>
            <td class="font-bold"><?= e($p['code']) ?></td>
            <td><a href="<?= aurl('products/' . $p['id']) ?>" class="font-semibold hover:text-brand"><?= e($p['name_tr']) ?></a>
              <div class="mt-0.5 flex gap-1"><?= $p['is_featured'] ? '<span class="badge-info">Vitrin</span>' : '' ?><?= $p['is_new'] ? '<span class="badge-info">Yeni</span>' : '' ?><?= $p['discount_price'] ? '<span class="badge-err">İndirimli</span>' : '' ?></div></td>
            <td><?= e($p['cat']) ?></td>
            <td><?= e($p['brand']) ?></td>
            <td class="whitespace-nowrap text-right"><b><?= money($price) ?></b><?= $old ? '<div class="text-xs text-muted line-through">' . money($old) . '</div>' : '' ?></td>
            <td class="text-right"><span class="<?= $p['stock'] <= 0 ? 'badge-err' : ($p['stock'] <= $p['critical_stock'] ? 'badge-warn' : 'badge-ok') ?>"><?= (int)$p['stock'] ?></span></td>
            <td><?= $p['is_active'] ? '<span class="badge-ok">Aktif</span>' : '<span class="badge-muted">Pasif</span>' ?></td>
            <td class="whitespace-nowrap text-right">
              <a href="<?= e(url('/urun/' . $p['slug'])) ?>" target="_blank" class="aicon" title="Sitede gör"><?= icon('eye', 'h-4 w-4') ?></a>
              <a href="<?= aurl('products/' . $p['id']) ?>" class="aicon" title="Düzenle"><?= icon('pencil', 'h-4 w-4') ?></a>
            </td>
          </tr>
        <?php endforeach; ?>
        <?php if (!$rows): ?><tr><td colspan="10" class="py-12 text-center text-muted">Ürün bulunamadı.</td></tr><?php endif; ?>
        </tbody>
      </table>
    </div>
  </form>
  <div class="flex items-center justify-between border-t border-line p-3 text-xs">
    <form class="flex items-center gap-2"><?php foreach ($qs as $k => $v) if ($k !== 'per') echo '<input type="hidden" name="' . e($k) . '" value="' . e($v) . '">'; ?>Sayfa başına
      <select name="per" onchange="this.form.submit()" class="ainput h-8 w-20 text-xs"><?php foreach ([25, 50, 100] as $n) echo "<option" . ($per === $n ? ' selected' : '') . ">$n</option>"; ?></select></form>
    <div class="flex gap-1"><?php for ($i = 1; $i <= $pages; $i++): ?><a href="?<?= e(http_build_query($qs + ['page' => $i])) ?>" class="apage <?= $i === $page ? 'is-active' : '' ?>"><?= $i ?></a><?php endfor; ?></div>
  </div>
</div>
<?php admin_view('layout_bottom'); ?>
