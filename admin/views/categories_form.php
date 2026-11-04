<?php
// Kategori Yönetimi – yeni form görünümü (tüm alanlar korunur)
$isNew = $id === null;
$title = ($isNew ? 'Yeni Kategori' : 'Kategoriyi Düzenle');
admin_view('layout_top', compact('title'));
admin_view('page_head', ['title' => $isNew ? 'Yeni Kategori' : ($row['name_tr'] ?? 'Kategoriyi Düzenle'),
  'crumbs' => [[$r['title'], aurl("r/$key")], [$isNew ? 'Yeni' : 'Düzenle', null]]]);

$F = $r['fields'];
$field = function (string $f, ?array $spec = null) use ($F, $row, $errors) {
    admin_view('field', ['f' => $f, 'spec' => $spec ?? $F[$f], 'val' => $row[$f] ?? '', 'err' => $errors[$f] ?? null]);
};
// Kendisini üst kategori olarak seçemesin
$parentSpec = $F['parent_id'];
$parentSpec[2] = array_diff_key(field_options($F['parent_id']), $isNew ? [] : [(int)$id => 1]);
?>
<style>
.cf{display:grid;grid-template-columns:minmax(0,1fr) 340px;gap:20px;align-items:start;max-width:1180px}
.cf-card{background:#fff;border:1px solid #e5e7eb;border-radius:12px;padding:20px;margin-bottom:16px}
.cf-card h2{font-size:13px;font-weight:800;letter-spacing:.04em;text-transform:uppercase;color:#1b2a4e;margin:0 0 4px}
.cf-card p.cf-help{font-size:12px;color:#6b7280;margin:0 0 14px}
.cf-grid2{display:grid;grid-template-columns:1fr 1fr;gap:14px}
.cf-grid2 .full{grid-column:1/-1}
.cf-side{position:sticky;top:12px}
.cf-save{display:flex;gap:8px}
.cf-save .abtn-primary{flex:1;justify-content:center}
.cf details summary{cursor:pointer;font-size:13px;font-weight:700;color:#1b2a4e;list-style:none}
.cf details summary::-webkit-details-marker{display:none}
.cf details summary::after{content:"+";float:right;font-weight:800}
.cf details[open] summary::after{content:"−"}
.cf details .cf-inner{margin-top:14px}
.cf-err{margin-bottom:14px;border-radius:8px;background:#fef2f2;color:#b91c1c;padding:12px 16px;font-size:13px;font-weight:600}
@media (max-width:980px){.cf{grid-template-columns:1fr}.cf-side{position:static}.cf-grid2{grid-template-columns:1fr}}
</style>
<form method="post" enctype="multipart/form-data" class="cf">
  <?= csrf_field() ?>
  <div>
    <?php if (!empty($errors['_'])): ?><div class="cf-err"><?= e($errors['_']) ?></div><?php endif; ?>

    <div class="cf-card">
      <h2>Genel bilgiler</h2>
      <p class="cf-help">Kategorinin sitede görünen adı ve kısa açıklaması.</p>
      <div class="cf-grid2">
        <div><?php $field('name_tr'); ?></div>
        <div><?php $field('name_en'); ?></div>
        <div><?php $field('slug'); ?></div>
        <div><?php $field('parent_id', $parentSpec); ?></div>
        <div><?php $field('desc_tr'); ?></div>
        <div><?php $field('desc_en'); ?></div>
      </div>
    </div>

    <div class="cf-card">
      <details <?= !empty($row['seo_title']) || !empty($row['seo_description']) || isset($errors['seo_title']) ? 'open' : '' ?>>
        <summary>Arama motoru (SEO) ayarları <span style="font-weight:500;color:#6b7280">— isteğe bağlı</span></summary>
        <div class="cf-inner" style="display:grid;gap:14px">
          <?php $field('seo_title'); $field('seo_description'); ?>
        </div>
      </details>
    </div>
  </div>

  <aside class="cf-side">
    <div class="cf-card">
      <h2>Kategori görseli</h2>
      <?php $field('image'); ?>
    </div>
    <div class="cf-card">
      <h2>Yayın ve sıralama</h2>
      <p class="cf-help">Pasif kategoriler sitede gizlenir.</p>
      <?php $field('is_active'); ?>
      <div style="margin-top:10px"><?php $field('sort_order'); ?></div>
      <div style="margin-top:10px"><?php $field('icon'); ?></div>
    </div>
    <div class="cf-card">
      <div class="cf-save">
        <button class="abtn-primary"><?= icon('check', 'h-4 w-4') ?> Kaydet</button>
        <a href="<?= aurl("r/$key") ?>" class="abtn">Vazgeç</a>
      </div>
    </div>
  </aside>
</form>
<?php admin_view('layout_bottom'); ?>
