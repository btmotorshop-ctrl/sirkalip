<?php
// Kategori Yönetimi – yeni liste görünümü
$title = $r['title'];
admin_view('layout_top', compact('title'));
admin_view('page_head', ['title' => $title, 'crumbs' => [[$title, null]],
  'actions' => '<a href="' . aurl("r/$key/new") . '" class="abtn-primary">' . icon('plus', 'h-4 w-4') . ' Yeni Kategori</a>']);

$all = db()->query("SELECT c.*, (SELECT COUNT(*) FROM products p WHERE p.category_id = c.id AND p.deleted_at IS NULL) AS pcount
                    FROM categories c ORDER BY c.sort_order, c.id")->fetchAll();
$byParent = []; $names = [];
foreach ($all as $c) { $byParent[(int)($c['parent_id'] ?? 0)][] = $c; $names[(int)$c['id']] = $c['name_tr']; }
$total = count($all);
$active = count(array_filter($all, function ($c) { return !empty($c['is_active']); }));
$products = array_sum(array_column($all, 'pcount'));

$renderRow = function (array $c, int $depth) use ($key, $names, &$renderRow, $byParent) {
    $id = (int)$c['id']; $kids = $byParent[$id] ?? [];
    $img = $c['image'] ? media_url($c['image']) : null;
    $initial = mb_strtoupper(mb_substr(trim($c['name_tr']), 0, 1));
    ?>
    <div class="cat-row <?= $depth ? 'is-child' : '' ?> <?= empty($c['is_active']) ? 'is-off' : '' ?>" data-name="<?= e(mb_strtolower($c['name_tr'] . ' ' . ($c['name_en'] ?? '') . ' ' . $c['slug'])) ?>">
      <div class="cat-thumb"><?php if ($img): ?><img src="<?= e($img) ?>" alt="" loading="lazy"><?php else: ?><span><?= e($initial) ?></span><?php endif; ?></div>
      <div class="cat-main">
        <a href="<?= aurl("r/$key/$id") ?>" class="cat-name"><?= e($c['name_tr']) ?></a>
        <div class="cat-sub">
          <?php if (!empty($c['name_en'])): ?><span><?= e($c['name_en']) ?></span><?php endif; ?>
          <span class="cat-slug">/<?= e($c['slug']) ?></span>
          <?php if ($depth && !empty($c['parent_id'])): ?><span class="cat-parent">↳ <?= e($names[(int)$c['parent_id']] ?? '') ?></span><?php endif; ?>
        </div>
      </div>
      <div class="cat-count" title="Bu kategorideki aktif ürün sayısı"><b><?= (int)$c['pcount'] ?></b><small>ürün</small></div>
      <div class="cat-order" title="Sıra"><small>Sıra</small><b><?= (int)$c['sort_order'] ?></b></div>
      <div class="cat-state"><?= !empty($c['is_active']) ? '<span class="cat-badge ok">Aktif</span>' : '<span class="cat-badge off">Pasif</span>' ?></div>
      <div class="cat-actions">
        <a href="<?= aurl("r/$key/$id") ?>" class="aicon" title="Düzenle"><?= icon('pencil', 'h-4 w-4') ?></a>
        <?php if ((int)$c['pcount'] > 0 || $kids): ?>
          <span class="aicon is-disabled" title="<?= $kids ? 'Alt kategorisi var, önce onları silin veya taşıyın' : 'İçinde ' . (int)$c['pcount'] . ' ürün var, önce ürünleri başka kategoriye taşıyın' ?>"><?= icon('trash2', 'h-4 w-4') ?></span>
        <?php else: ?>
          <form method="post" action="<?= aurl("r/$key/$id/delete") ?>" class="inline" data-confirm="“<?= e($c['name_tr']) ?>” kategorisini silmek istediğinize emin misiniz?"><?= csrf_field() ?><button class="aicon hover:text-brand" title="Sil"><?= icon('trash2', 'h-4 w-4') ?></button></form>
        <?php endif; ?>
      </div>
    </div>
    <?php foreach ($kids as $k) $renderRow($k, $depth + 1);
};
?>
<style>
.cat-stats{display:flex;flex-wrap:wrap;gap:12px;margin-bottom:16px}
.cat-stat{background:#fff;border:1px solid #e5e7eb;border-radius:10px;padding:12px 18px;min-width:130px}
.cat-stat b{display:block;font-size:22px;line-height:1.1;color:#1b2a4e}
.cat-stat span{font-size:12px;color:#6b7280}
.cat-bar{display:flex;gap:12px;align-items:center;padding:14px 16px;border-bottom:1px solid #e5e7eb}
.cat-bar input{flex:1;max-width:360px}
.cat-hint{margin-left:auto;font-size:12px;color:#6b7280}
.cat-row{display:grid;grid-template-columns:56px minmax(0,1fr) 70px 60px 80px 84px;gap:14px;align-items:center;padding:12px 16px;border-bottom:1px solid #f0f1f4;transition:background .15s}
.cat-row:last-child{border-bottom:0}
.cat-row:hover{background:#f8fafc}
.cat-row.is-child{padding-left:48px;background:#fcfcfd}
.cat-row.is-off .cat-thumb,.cat-row.is-off .cat-main{opacity:.55}
.cat-thumb{width:56px;height:56px;border-radius:10px;background:#f3f4f6;border:1px solid #eceef2;display:flex;align-items:center;justify-content:center;overflow:hidden}
.cat-thumb img{max-width:100%;max-height:100%;object-fit:contain;padding:3px}
.cat-thumb span{font-weight:800;font-size:20px;color:#9aa3b2}
.cat-name{font-weight:700;font-size:14px;color:#111827;display:block;overflow:hidden;text-overflow:ellipsis;white-space:nowrap}
.cat-name:hover{color:#e30613}
.cat-sub{display:flex;flex-wrap:wrap;gap:4px 12px;font-size:12px;color:#6b7280;margin-top:3px}
.cat-slug{font-family:ui-monospace,Consolas,monospace;color:#9aa3b2;overflow:hidden;text-overflow:ellipsis;max-width:320px;white-space:nowrap}
.cat-count,.cat-order{text-align:center;line-height:1.1}
.cat-count b,.cat-order b{display:block;font-size:16px;color:#1b2a4e}
.cat-count small,.cat-order small{font-size:10.5px;color:#9aa3b2;text-transform:uppercase;letter-spacing:.04em}
.cat-order small{display:block;margin-bottom:2px}
.cat-badge{display:inline-block;padding:3px 10px;border-radius:999px;font-size:11.5px;font-weight:700}
.cat-badge.ok{background:#dcfce7;color:#166534}
.cat-badge.off{background:#f3f4f6;color:#6b7280}
.cat-actions{display:flex;justify-content:flex-end;gap:2px}
.aicon.is-disabled{opacity:.3;cursor:not-allowed}
.cat-empty{padding:48px 16px;text-align:center;color:#6b7280}
@media (max-width:760px){
 .cat-row{grid-template-columns:48px minmax(0,1fr) auto;row-gap:8px}
 .cat-row.is-child{padding-left:28px}
 .cat-thumb{width:48px;height:48px}
 .cat-count,.cat-order{display:none}
 .cat-state{grid-column:2}
 .cat-actions{grid-column:3;grid-row:1}
}
</style>

<div class="cat-stats">
  <div class="cat-stat"><b><?= $total ?></b><span>Toplam kategori</span></div>
  <div class="cat-stat"><b><?= $active ?></b><span>Aktif</span></div>
  <div class="cat-stat"><b><?= $total - $active ?></b><span>Pasif</span></div>
  <div class="cat-stat"><b><?= (int)$products ?></b><span>Kategorideki ürün</span></div>
</div>

<div class="acard overflow-hidden">
  <div class="cat-bar">
    <input id="catSearch" class="ainput" type="search" placeholder="Kategori ara… (ad, İngilizce ad veya URL)" autocomplete="off">
    <div class="cat-hint">Sıralama, “Sıra” numarasına göredir; küçük numara önce görünür.</div>
  </div>
  <div id="catList">
    <?php foreach ($byParent[0] ?? [] as $c) $renderRow($c, 0);
    // Üst kategorisi silinmiş (yetim) kayıtlar da görünsün
    foreach ($all as $c) if (!empty($c['parent_id']) && !isset($names[(int)$c['parent_id']])) $renderRow($c, 0); ?>
    <?php if (!$all): ?><div class="cat-empty">Henüz kategori yok. Sağ üstteki “Yeni Kategori” ile ekleyebilirsiniz.</div><?php endif; ?>
  </div>
  <div id="catNone" class="cat-empty" style="display:none">Aramanıza uygun kategori bulunamadı.</div>
</div>
<script>
(function(){
  var q=document.getElementById('catSearch'),rows=[].slice.call(document.querySelectorAll('#catList .cat-row')),none=document.getElementById('catNone');
  q.addEventListener('input',function(){
    var s=q.value.trim().toLocaleLowerCase('tr'),n=0;
    rows.forEach(function(r){var ok=!s||r.getAttribute('data-name').indexOf(s)>-1;r.style.display=ok?'':'none';if(ok)n++;});
    none.style.display=n?'none':'';
  });
})();
</script>
<?php admin_view('layout_bottom'); ?>
