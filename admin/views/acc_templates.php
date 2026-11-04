<?php
// Ön Muhasebe › Ayarlar › Teklif ve Özel Şablonlar (liste)
$title = 'Teklif ve Özel Şablonlar';
admin_view('layout_top', compact('title'));
admin_view('page_head', ['title' => $title, 'crumbs' => [['Ön Muhasebe', aurl('acc')], ['Ayarlar', null], [$title, null]],
  'actions' => '<a href="' . aurl('acc/template/new') . '" class="abtn-primary">' . icon('plus', 'h-4 w-4') . ' Yeni Şablon Ekle</a>']);
$badge = ['proposal' => ['Teklif', '#2563eb'], 'sale' => ['Satış', '#16a34a'], 'purchase' => ['Alış', '#d97706']];
?>
<style>
.tl-wrap{max-width:980px}
.tl-hint{margin:0 0 14px;color:#6b7280;font-size:13px}
.tl-row{display:flex;align-items:center;gap:14px;padding:14px 18px;border-bottom:1px solid #eef0f5}
.tl-row:last-child{border-bottom:0}
.tl-row:hover{background:#f8fafd}
.tl-ic{width:42px;height:42px;border-radius:10px;background:#eef2ff;color:#4338ca;display:flex;align-items:center;justify-content:center;flex:none}
.tl-main{flex:1;min-width:0}
.tl-main a{font-weight:700;font-size:14.5px;color:#111827;text-decoration:none}
.tl-main a:hover{color:#e30613}
.tl-main small{display:block;color:#6b7280;font-size:12px;margin-top:2px}
.tl-badge{font-size:11.5px;font-weight:700;color:#fff;border-radius:999px;padding:3px 11px}
.tl-act{display:flex;gap:2px}
</style>
<div class="tl-wrap">
  <p class="tl-hint">Teklif, satış ve alış belgelerinin yazdırma / PDF görünümünü buradan düzenleyin. Belge sayfasındaki <b>Şablonlu Yazdır</b> düğmesi bu şablonları kullanır.</p>
  <div class="acard overflow-hidden">
    <?php foreach ($rows as $t): [$bl, $bc] = $badge[$t['kind']] ?? ['—', '#6b7280']; ?>
      <div class="tl-row">
        <div class="tl-ic"><?= icon('file-text', 'h-5 w-5') ?></div>
        <div class="tl-main"><a href="<?= aurl('acc/template/' . $t['id']) ?>"><?= e($t['name']) ?></a>
          <small><?= e(ACCT_LANGS[$t['lang']] ?? $t['lang']) ?> · <?= e(ACCT_SIZES[$t['page_size']] ?? '') ?><?= $t['title'] !== '' ? ' · Başlık: ' . e($t['title']) : '' ?></small></div>
        <span class="tl-badge" style="background:<?= $bc ?>"><?= e($bl) ?></span>
        <div class="tl-act">
          <a href="<?= aurl('acc/template/' . $t['id']) ?>" class="aicon" title="Düzenle"><?= icon('pencil', 'h-4 w-4') ?></a>
          <form method="post" action="<?= aurl('acc/template/' . $t['id'] . '/copy') ?>" class="inline"><?= csrf_field() ?><button class="aicon" title="Kopyala"><?= icon('file-spreadsheet', 'h-4 w-4') ?></button></form>
          <form method="post" action="<?= aurl('acc/template/' . $t['id'] . '/delete') ?>" class="inline" data-confirm="“<?= e($t['name']) ?>” şablonu silinsin mi?"><?= csrf_field() ?><button class="aicon" title="Sil"><?= icon('trash2', 'h-4 w-4') ?></button></form>
        </div>
      </div>
    <?php endforeach; ?>
    <?php if (!$rows): ?><div style="padding:40px;text-align:center;color:#6b7280">Henüz şablon yok. “Yeni Şablon Ekle” ile başlayın.</div><?php endif; ?>
  </div>
</div>
<?php admin_view('layout_bottom'); ?>
