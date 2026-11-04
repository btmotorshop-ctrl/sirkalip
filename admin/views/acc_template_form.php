<?php
// Şablon düzenleyici: solda canlı önizleme, sağda ayarlar
$title = $id ? 'Şablonu Düzenle' : 'Yeni Şablon';
admin_view('layout_top', compact('title'));
admin_view('page_head', ['title' => $title, 'crumbs' => [['Ön Muhasebe', aurl('acc')], ['Ayarlar', null], ['Teklif ve Özel Şablonlar', aurl('acc/templates')], [$id ? $t['name'] : 'Yeni', null]]]);
$o = acct_opts($t);
$sel = function (string $name, array $choices, string $cur) {
    $h = '<select name="' . e($name) . '" class="ainput">';
    foreach ($choices as $k => $v) $h .= '<option value="' . e($k) . '"' . ((string)$k === $cur ? ' selected' : '') . '>' . e($v) . '</option>';
    return $h . '</select>';
};
?>
<style>
.te{display:grid;grid-template-columns:minmax(0,1fr) 360px;gap:20px;align-items:start}
.te-prev{background:#e5e8ee;border-radius:14px;padding:22px 14px;overflow:auto;min-height:480px}
.te-side{position:sticky;top:12px;max-height:calc(100vh - 24px);overflow:auto;padding-right:2px}
.te-box{background:#fff;border:1px solid #e5e7eb;border-radius:12px;padding:16px;margin-bottom:14px}
.te-box h3{margin:-16px -16px 14px;padding:10px 16px;background:#64748b;color:#fff;border-radius:12px 12px 0 0;font-size:12.5px;font-weight:700;letter-spacing:.03em}
.te-box label{display:block;font-size:12px;font-weight:600;color:#475068;margin:0 0 10px}
.te-box .ainput,.te-box textarea{width:100%;margin-top:4px}
.te-box textarea{border:1px solid #d9dee8;border-radius:9px;padding:8px 10px;font-size:13px;min-height:74px;resize:vertical}
.te-err{color:#dc2626;font-size:12px;margin-top:-6px;margin-bottom:8px}
.te-bar{display:flex;gap:8px;margin-bottom:14px;flex-wrap:wrap}
@media (max-width:1100px){.te{grid-template-columns:1fr}.te-side{position:static;max-height:none}}
<?= acct_css() ?>
</style>
<form method="post" class="te" id="teForm">
  <?= csrf_field() ?>
  <div>
    <div class="te-bar">
      <button class="abtn-primary"><?= icon('check', 'h-4 w-4') ?> Kaydet</button>
      <a href="<?= aurl('acc/templates') ?>" class="abtn">Geri Dön</a>
      <span style="margin-left:auto;font-size:12px;color:#6b7280;align-self:center">Önizleme örnek verilerle hazırlanır ve sağdaki ayarlara göre anında güncellenir.</span>
    </div>
    <div class="te-prev"><div id="tePaper"><?= $paper ?></div></div>
  </div>
  <aside class="te-side">
    <div class="te-box">
      <label>Şablon Adı<input name="name" class="ainput" value="<?= e($t['name']) ?>" required></label>
      <?php if (!empty($errors['name'])): ?><div class="te-err"><?= e($errors['name']) ?></div><?php endif; ?>
      <label>Şablon Tipi<?= $sel('kind', ACCT_KINDS, $t['kind']) ?></label>
      <label>Dil<?= $sel('lang', ACCT_LANGS, $t['lang']) ?></label>
      <label>Başlık<input name="title" class="ainput" value="<?= e($t['title']) ?>"></label>
      <label>Sayfa Boyu<?= $sel('page_size', ACCT_SIZES, $t['page_size']) ?></label>
    </div>
    <div class="te-box">
      <h3>Metin Alanları</h3>
      <label>Firma adı<input name="head_name" class="ainput" value="<?= e($t['head_name']) ?>"></label>
      <label>Firma bilgileri (adres, telefon, vergi…)<textarea name="head_info"><?= e($t['head_info']) ?></textarea></label>
      <label>Giriş metni<textarea name="intro"><?= e($t['intro']) ?></textarea></label>
      <label>Alt bilgi (banka / IBAN, şartlar…)<textarea name="footer" style="min-height:96px"><?= e($t['footer']) ?></textarea></label>
    </div>
    <?php foreach (acct_meta() as $group => $items): ?>
      <div class="te-box"><h3><?= e($group) ?></h3>
        <?php foreach ($items as $k => [$label, $choices]): ?>
          <label><?= e($label) ?><?= $sel("opt[$k]", $choices, $o[$k]) ?></label>
        <?php endforeach; ?>
      </div>
    <?php endforeach; ?>
  </aside>
</form>
<script>
(function(){
  var f=document.getElementById('teForm'),paper=document.getElementById('tePaper'),tm=null,url=<?= json_encode(aurl('acc/template-preview')) ?>;
  function refresh(){
    var fd=new FormData(f);
    fetch(url,{method:'POST',body:fd,credentials:'same-origin'}).then(function(r){return r.text()}).then(function(h){paper.innerHTML=h}).catch(function(){});
  }
  function later(){clearTimeout(tm);tm=setTimeout(refresh,250)}
  f.addEventListener('input',later);f.addEventListener('change',later);
})();
</script>
<?php admin_view('layout_bottom'); ?>
