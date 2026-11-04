<?php
$title = $id ? 'Ürün Düzenle: ' . ($p['name_tr'] ?? '') : 'Yeni Ürün Ekle';
admin_view('layout_top', compact('title'));
admin_view('page_head', ['title' => $title, 'crumbs' => [['Ürün Yönetimi', aurl('products')], [$id ? '#' . $id : 'Yeni', null]],
  'actions' => $id ? '<a href="' . e(url('/urun/' . $p['slug'])) . '" target="_blank" class="abtn">' . icon('eye', 'h-4 w-4') . ' Sitede Gör</a>' : '']);
$v = function ($k) use ($p) { return e($p[$k] ?? ''); };
$inp = function ($k, $label, $type = 'text', $extra = '') use ($p, $errors) {
    return '<label class="block"><span class="alabel">' . $label . '</span><input type="' . $type . '" name="' . $k . '" value="' . e($p[$k] ?? '') . '" ' . ($type === 'number' ? 'step="any"' : '') . ' ' . $extra . ' class="ainput">'
        . (!empty($errors[$k]) ? '<span class="mt-1 block text-xs font-semibold text-brand">' . e($errors[$k]) . '</span>' : '') . '</label>';
};
$select = function ($k, $label, $opts, $empty = true) use ($p, $errors) {
    $h = '<label class="block"><span class="alabel">' . $label . '</span><select name="' . $k . '" class="ainput">' . ($empty ? '<option value="">— Seçiniz —</option>' : '');
    foreach ($opts as $ok => $ol) $h .= '<option value="' . e($ok) . '"' . ((string)($p[$k] ?? '') === (string)$ok ? ' selected' : '') . '>' . e($ol) . '</option>';
    return $h . '</select>' . (!empty($errors[$k]) ? '<span class="mt-1 block text-xs font-semibold text-brand">' . e($errors[$k]) . '</span>' : '') . '</label>';
};
$chk = function ($k, $label) use ($p) { return '<label class="flex items-center gap-2 text-sm font-semibold"><input type="checkbox" name="' . $k . '" value="1" ' . (!empty($p[$k]) ? 'checked' : '') . ' class="h-4 w-4 accent-[#e30613]"> ' . $label . '</label>'; };
$brandOpts = []; foreach ($brands as $b) $brandOpts[$b['id']] = $b['name'];
$refKinds = ['asem' => 'ASEM Kodu', 'original' => 'Orijinal Kod', 'manufacturer' => 'Üretici Kodu', 'equivalent' => 'Muadil Kod'];
$tabs = ['basic' => 'Temel Bilgiler', 'price' => 'Fiyat ve Stok', 'images' => 'Fotoğraflar', 'specs' => 'Teknik Bilgiler', 'compat' => 'Uyumlu Modeller', 'refs' => 'Referans Numaraları', 'relations' => 'Etiket & İlişkili', 'seo' => 'SEO'];
?>
<form method="post" enctype="multipart/form-data" data-tabs>
  <?= csrf_field() ?>
  <?php if (!empty($errors)): ?><div class="mb-4 rounded bg-red-50 px-4 py-3 text-sm font-semibold text-red-700"><?= e($errors['_'] ?? 'Lütfen işaretli alanları kontrol edin.') ?></div><?php endif; ?>
  <div class="acard">
    <div class="no-scrollbar flex overflow-x-auto border-b border-line px-2">
      <?php $i = 0; foreach ($tabs as $k => $l): $i++; ?><button type="button" data-tab="<?= $k ?>" class="atab <?= $k === 'basic' ? 'is-active' : '' ?>"><span class="mr-1.5 inline-flex h-5 w-5 items-center justify-center rounded-full bg-soft text-[11px]"><?= $i ?></span><?= $l ?></button><?php endforeach; ?>
    </div>
    <div class="p-6">
      <!-- 1 Temel -->
      <div data-pane="basic" class="grid gap-4 md:grid-cols-2">
        <?= $inp('code', 'Ürün Kodu <span class="text-brand">*</span>') ?>
        <?= $inp('barcode', 'Barkod') ?>
        <?= $inp('name_tr', 'Ürün Adı (TR) <span class="text-brand">*</span>') ?>
        <?= $select('category_id', 'Kategori <span class="text-brand">*</span>', $cats) ?>
        <?= $select('brand_id', 'Marka', $brandOpts) ?>
        <?= $select('supplier_id', 'Tedarikçi', $suppliers) ?>
        <?= $inp('gtip', 'GTİP Kodu') ?>
        <label class="block md:col-span-2"><span class="alabel">Kısa Açıklama (TR)</span><textarea name="short_desc_tr" rows="2" class="ainput h-auto py-2"><?= $v('short_desc_tr') ?></textarea></label>
        <label class="block md:col-span-2"><span class="alabel">Ürün Açıklaması (TR)</span><textarea data-rte name="desc_tr" rows="7" class="ainput h-auto py-2"><?= $v('desc_tr') ?></textarea></label>
        <!-- Diğer diller -->
        <div class="md:col-span-2 rounded-lg border border-line" data-ltabs>
          <div class="flex flex-wrap gap-1 border-b border-line bg-soft p-2">
            <?php $__first = true; foreach (extra_locales() as $__l): if ($__l !== 'en' && !loc_ready()) continue; ?>
              <button type="button" data-ltab="<?= $__l ?>" class="rounded px-3 py-1.5 text-xs font-bold <?= $__first ? 'bg-white text-brand shadow-sm' : 'text-muted' ?>"><?= e(locales()[$__l]) ?></button>
            <?php $__first = false; endforeach; ?>
            <span class="ml-auto self-center text-[11px] text-muted">Boş bırakılan diller, çeviri anahtarı tanımlıysa kaydedince Türkçeden otomatik çevrilir; elle yazdığınız çeviriler korunur.</span>
          </div>
          <?php $__first = true; foreach (extra_locales() as $__l): if ($__l !== 'en' && !loc_ready()) continue; $__rtl = $__l === 'ar'; ?>
            <div data-lpane="<?= $__l ?>" class="grid gap-4 p-4 <?= $__first ? '' : 'hidden' ?>" <?= $__rtl ? 'dir="rtl"' : '' ?>>
              <?= $inp('name_' . $__l, 'Ürün Adı (' . strtoupper($__l) . ')') ?>
              <label class="block"><span class="alabel">Kısa Açıklama (<?= strtoupper($__l) ?>)</span><textarea name="short_desc_<?= $__l ?>" rows="2" class="ainput h-auto py-2"><?= $v('short_desc_' . $__l) ?></textarea></label>
              <label class="block"><span class="alabel">Ürün Açıklaması (<?= strtoupper($__l) ?>)</span><textarea data-rte name="desc_<?= $__l ?>" rows="7" class="ainput h-auto py-2"><?= $v('desc_' . $__l) ?></textarea></label>
            </div>
          <?php $__first = false; endforeach; ?>
        </div>
        <script>
        document.querySelectorAll('[data-ltabs]').forEach(function (box) {
          var tabs = box.querySelectorAll('[data-ltab]'), panes = box.querySelectorAll('[data-lpane]');
          tabs.forEach(function (t) { t.onclick = function () {
            tabs.forEach(function (x) { var on = x === t; x.classList.toggle('bg-white', on); x.classList.toggle('text-brand', on); x.classList.toggle('shadow-sm', on); x.classList.toggle('text-muted', !on); });
            panes.forEach(function (p) { p.classList.toggle('hidden', p.dataset.lpane !== t.dataset.ltab); });
          }; });
        });
        </script>
        <div class="flex flex-wrap gap-6 md:col-span-2"><?= $chk('is_active', 'Aktif olarak yayınla') ?><?= $chk('is_featured', 'Vitrinde / Çok satanlarda göster') ?><?= $chk('is_new', 'Yeni ürün') ?><?= $chk('free_shipping', 'Ücretsiz kargo') ?></div>
      </div>
      <!-- 2 Fiyat & Stok -->
      <div data-pane="price" class="grid hidden gap-4 md:grid-cols-3">
        <?= $inp('price', 'Satış Fiyatı (KDV dahil) <span class="text-brand">*</span>', 'number') ?>
        <?= $inp('discount_price', 'İndirimli Fiyat', 'number') ?>
        <?= $inp('market_price', 'Piyasa Fiyatı (üstü çizili)', 'number') ?>
        <?= $inp('purchase_price', 'Alış Fiyatı (kârlılık raporu için)', 'number') ?>
        <?= $select('currency', 'Para Birimi', ['TRY' => 'TL', 'USD' => 'USD', 'EUR' => 'EUR'], false) ?>
        <?= $select('vat_rate', 'KDV Oranı', ['20' => '%20', '10' => '%10', '1' => '%1', '0' => '%0'], false) ?>
        <?= $inp('stock', 'Stok Adedi', 'number') ?>
        <?= $inp('critical_stock', 'Kritik Stok Uyarı Seviyesi', 'number') ?>
        <?= $inp('desi', 'Desi', 'number') ?>
        <?= $inp('estimated_ship_days', 'Tahmini Kargoya Veriliş (gün)', 'number') ?>
        <?= $inp('sort_order', 'Sıra', 'number') ?>
      </div>
      <!-- 3 Fotoğraflar -->
      <div data-pane="images" class="hidden">
        <label class="block rounded-lg border-2 border-dashed border-line p-6 text-center hover:border-brand">
          <?= icon('upload', 'mx-auto h-8 w-8 text-muted') ?>
          <div class="mt-2 text-sm font-semibold">Fotoğraf / video seçin (birden fazla seçilebilir)</div>
          <div class="text-xs text-muted">JPG, PNG, WEBP, MP4 — Kaydet'e bastığınızda yüklenir</div>
          <input type="file" name="upload[]" multiple accept="image/*,video/mp4" class="mt-3 text-xs">
        </label>
        <div class="mt-5 text-sm font-bold">Mevcut görseller / URL ile ekle</div>
        <div class="mt-2 space-y-2">
          <?php foreach ($images as $i => $im): ?>
            <div data-row class="flex items-center gap-2">
              <div class="h-12 w-12 shrink-0 overflow-hidden rounded border border-line"><?php if ($im['kind'] !== 'video'): ?><img src="<?= e(media_url($im['url'])) ?>" class="h-full w-full object-cover" alt=""><?php endif; ?></div>
              <input name="img_url[]" value="<?= e($im['url']) ?>" class="ainput flex-1">
              <select name="img_kind[]" class="ainput w-36"><?php foreach (['photo' => 'Fotoğraf', 'drawing' => 'Teknik Çizim', 'video' => 'Video'] as $k => $l) echo '<option value="' . $k . '"' . ($im['kind'] === $k ? ' selected' : '') . ">$l</option>"; ?></select>
              <label class="flex items-center gap-1 text-xs font-semibold whitespace-nowrap"><input type="radio" name="img_main" value="<?= $i ?>" <?= $im['is_main'] ? 'checked' : '' ?>> Ana görsel</label>
              <button type="button" data-remove-row class="aicon hover:text-brand"><?= icon('trash2', 'h-4 w-4') ?></button>
            </div>
          <?php endforeach; ?>
          <template id="tpl-img"><div data-row class="flex items-center gap-2"><input name="img_url[]" placeholder="https://... görsel adresi" class="ainput flex-1"><select name="img_kind[]" class="ainput w-36"><option value="photo">Fotoğraf</option><option value="drawing">Teknik Çizim</option><option value="video">Video</option></select><button type="button" data-remove-row class="aicon hover:text-brand"><?= icon('trash2', 'h-4 w-4') ?></button></div></template>
        </div>
        <button type="button" data-add-row="tpl-img" class="abtn mt-3 text-xs"><?= icon('plus', 'h-4 w-4') ?> URL ile görsel ekle</button>
      </div>
      <!-- 4 Teknik -->
      <div data-pane="specs" class="hidden">
        <div class="mb-3 text-sm text-muted">Özellik başlıkları <a href="<?= aurl('r/spec_keys') ?>" class="font-semibold text-brand">Teknik Detaylar</a> sayfasından yönetilir.</div>
        <div class="grid gap-4 md:grid-cols-3">
          <?php foreach ($specKeys as $sk): ?>
            <label class="block"><span class="alabel"><?= e($sk['name_tr']) ?><?= $sk['unit'] ? ' (' . e($sk['unit']) . ')' : '' ?></span><input name="spec[<?= $sk['id'] ?>]" value="<?= e($specs[$sk['id']] ?? '') ?>" class="ainput"></label>
          <?php endforeach; ?>
        </div>
      </div>
      <!-- 5 Uyumlu modeller -->
      <div data-pane="compat" class="hidden space-y-4">
        <div class="text-sm text-muted">Bu parçanın uyumlu olduğu makine modellerini işaretleyin. Modeller <a href="<?= aurl('r/machine_models') ?>" class="font-semibold text-brand">Makine Modelleri</a> sayfasından eklenir.</div>
        <?php foreach ($brands as $b): $ms = array_filter($models, function ($m) use ($b) { return (int)$m['brand_id'] === (int)$b['id']; }); if (!$ms) continue; ?>
          <div class="rounded-lg border border-line p-4">
            <div class="mb-2 flex items-center justify-between"><b><?= e($b['name']) ?></b><button type="button" data-toggle-group="b<?= $b['id'] ?>" class="text-xs font-semibold text-brand">Tümünü seç / kaldır</button></div>
            <div class="flex flex-wrap gap-x-5 gap-y-2">
              <?php foreach ($ms as $m): ?><label class="flex items-center gap-1.5 text-sm"><input type="checkbox" data-group="b<?= $b['id'] ?>" name="compat[]" value="<?= $m['id'] ?>" <?= in_array($m['id'], $compat) ? 'checked' : '' ?> class="accent-[#e30613]"> <?= e($m['name']) ?></label><?php endforeach; ?>
            </div>
          </div>
        <?php endforeach; ?>
      </div>
      <!-- 6 Referanslar -->
      <div data-pane="refs" class="hidden">
        <div class="mb-3 text-sm text-muted">Referans kodları sitede aramada da kullanılır.</div>
        <div class="space-y-2">
          <?php foreach ($refs as $rf): ?>
            <div data-row class="flex gap-2"><select name="ref_kind[]" class="ainput w-48"><?php foreach ($refKinds as $k => $l) echo '<option value="' . $k . '"' . ($rf['kind'] === $k ? ' selected' : '') . ">$l</option>"; ?></select><input name="ref_code[]" value="<?= e($rf['code']) ?>" class="ainput flex-1"><button type="button" data-remove-row class="aicon hover:text-brand"><?= icon('trash2', 'h-4 w-4') ?></button></div>
          <?php endforeach; ?>
          <template id="tpl-ref"><div data-row class="flex gap-2"><select name="ref_kind[]" class="ainput w-48"><?php foreach ($refKinds as $k => $l) echo "<option value=\"$k\">$l</option>"; ?></select><input name="ref_code[]" class="ainput flex-1"><button type="button" data-remove-row class="aicon hover:text-brand"><?= icon('trash2', 'h-4 w-4') ?></button></div></template>
        </div>
        <button type="button" data-add-row="tpl-ref" class="abtn mt-3 text-xs"><?= icon('plus', 'h-4 w-4') ?> Kod ekle</button>
      </div>
      <!-- 7 Etiket & ilişkili -->
      <div data-pane="relations" class="grid hidden gap-6 md:grid-cols-2">
        <div><div class="alabel">Etiketler</div><div class="flex flex-wrap gap-3"><?php foreach ($allTags as $tid => $tn): ?><label class="flex items-center gap-1.5 text-sm"><input type="checkbox" name="tags[]" value="<?= $tid ?>" <?= in_array($tid, $tags) ? 'checked' : '' ?>> <?= e($tn) ?></label><?php endforeach; ?><?= $allTags ? '' : '<span class="text-sm text-muted">Henüz etiket yok. <a class="text-brand" href="' . aurl('r/tags/new') . '">Etiket ekle</a></span>' ?></div>
          <div class="mt-4"><?= $select('icon', 'Görsel yoksa kullanılacak simge', icon_options()) ?></div></div>
        <label class="block"><span class="alabel">Bu Ürünle Birlikte Alınanlar (Ctrl ile çoklu seçim)</span>
          <select name="related[]" multiple size="10" class="ainput h-auto py-1"><?php foreach ($allProducts as $pid => $pn) if ($pid != $id) echo '<option value="' . $pid . '"' . (in_array($pid, $related) ? ' selected' : '') . '>' . e($pn) . '</option>'; ?></select></label>
      </div>
      <!-- 8 SEO -->
      <div data-pane="seo" class="grid hidden gap-4">
        <?= $inp('slug', 'SEO URL (boş bırakılırsa otomatik)') ?>
        <?= $inp('seo_title', 'SEO Başlık (Title)') ?>
        <label class="block"><span class="alabel">SEO Açıklama (Meta Description)</span><textarea name="seo_description" rows="3" class="ainput h-auto py-2"><?= $v('seo_description') ?></textarea></label>
      </div>
    </div>
    <div class="flex flex-wrap gap-2 border-t border-line p-4">
      <button class="abtn-primary"><?= icon('check', 'h-4 w-4') ?> Kaydet</button>
      <button name="_stay" value="1" class="abtn">Kaydet ve Düzenlemeye Devam Et</button>
      <a href="<?= aurl('products') ?>" class="abtn">Vazgeç</a>
    </div>
  </div>
</form>
<?php if ($id): ?>
  <form method="post" action="<?= aurl("products/$id/delete") ?>" class="mt-3" data-confirm="Ürün geri dönüşüm kutusuna taşınsın mı?"><?= csrf_field() ?><button class="text-xs font-semibold text-brand hover:underline">Bu ürünü sil</button></form>
<?php endif; ?>
<?php admin_view('layout_bottom'); ?>
