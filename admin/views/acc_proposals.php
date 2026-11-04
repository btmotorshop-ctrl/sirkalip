<?php
$title = 'Teklifler';
admin_view('layout_top', compact('title'));
$stTxt = function ($s) { return $s === 'converted' ? ['Satışa çevrildi', 'cv'] : ($s === 'cancelled' ? ['İptal', 'off'] : ['Açık', '']); };
?>
<div class="tk-bar">
  <button type="button" class="pl-btn rd" id="tkReg"><?= icon('users', 'h-4 w-4') ?> Kayıtlı Müşteriye Teklif Hazırla</button>
  <button type="button" class="pl-btn bl" id="tkNew"><?= icon('plus', 'h-4 w-4') ?> Yeni Müşteriye Teklif Hazırla</button>
</div>

<div class="pl-card">
  <form class="tk-tools" method="get" id="tkF">
    <select name="period" class="pl-sel" onchange="this.form.submit()" style="min-width:150px">
      <option value="month" <?= $period === 'month' ? 'selected' : '' ?>>Son Bir Ay</option>
      <option value="year" <?= $period === 'year' ? 'selected' : '' ?>>Son Bir Yıl</option>
      <option value="all" <?= $period === 'all' ? 'selected' : '' ?>>Tümü</option>
    </select>
    <label class="tk-sw"><input type="checkbox" name="cancelled" value="1" <?= $showCancel ? 'checked' : '' ?> onchange="this.form.submit()"><i></i> İptalleri de göster</label>
    <span class="pl-search"><span>Ara:</span>
      <select id="tkBy" class="pl-sel" style="min-width:160px"><option value="name">Müşteri ismi</option><option value="no">Belge no</option></select>
      <input id="tkQ" type="search" placeholder="arama... (en az 2 karakter)" autocomplete="off"></span>
  </form>
  <div class="pl-scroll">
    <table class="pl-table" id="tkT">
      <thead><tr><th>Tarih</th><th>İsim / Ünvan</th><th>Teklif No</th><th>Geçerlilik</th><th>Durum</th><th class="r">Tutar</th></tr></thead>
      <tbody>
      <?php foreach ($rows as $r): [$stl, $stc] = $stTxt($r['status']); ?>
        <tr class="tk-r" data-href="<?= e(aurl('acc/doc/proposal/' . $r['id'])) ?>" data-name="<?= e(mb_strtolower($r['party_name'] ?: '', 'UTF-8')) ?>" data-no="<?= e(mb_strtolower($r['doc_no'] ?: '', 'UTF-8')) ?>">
          <td><span class="tk-dot <?= $stc ?>"><?= $r['status'] === 'cancelled' ? '×' : '✓' ?></span><?= acc_date($r['doc_date']) ?></td>
          <td><?= e($r['party_name'] ?: '—') ?></td>
          <td><?= e($r['doc_no']) ?></td>
          <td><?= acc_date($r['due_date']) ?></td>
          <td><span class="tk-state <?= $stc ?>"><?= $stl ?></span></td>
          <td class="r"><?= e(acc_money($r['total'], $r['currency'])) ?></td>
        </tr>
      <?php endforeach; ?>
      <?php if (!$rows): ?><tr><td colspan="6" style="text-align:center;padding:48px;color:#8b97a6;cursor:default">Henüz teklif yok. Yukarıdaki düğmelerden teklif hazırlayabilirsiniz.</td></tr><?php endif; ?>
      </tbody>
    </table>
  </div>
</div>

<!-- Kayıtlı müşteri seçimi -->
<div class="df-modal" id="tkM1" hidden><div class="df-mbox" style="width:560px">
  <div class="df-mh">Kayıtlı Müşteriye Teklif Hazırla</div>
  <div class="df-mb">
    <div class="df-mrow"><label style="flex:1">Müşteri<input list="tkList" id="tkPick" class="df-i" style="width:100%" placeholder="müşteri adını yazmaya başlayın..." autocomplete="off"></label></div>
    <datalist id="tkList"><?php foreach ($customers as $cid => $cn): ?><option value="<?= e($cn) ?>" data-id="<?= (int)$cid ?>"></option><?php endforeach; ?></datalist>
    <div class="df-rm" id="tkErr" style="color:#b3261e;font-weight:600" hidden>Listeden bir müşteri seçin.</div>
  </div>
  <div class="df-mf"><button type="button" class="pl-btn gr" id="tkGo">Devam Et</button><button type="button" class="df-link" data-close>Vazgeç</button></div>
  <button type="button" class="df-x" data-close>×</button>
</div></div>

<!-- Yeni müşteri -->
<div class="df-modal" id="tkM2" hidden><div class="df-mbox" style="width:560px">
  <div class="df-mh">Yeni Müşteriye Teklif Hazırla</div>
  <form method="post" action="<?= aurl('acc/party-quick') ?>">
    <?= csrf_field() ?>
    <div class="df-mb">
      <div class="df-mrow"><label style="flex:1">Müşteri adı / ünvanı<input name="name" class="df-i" style="width:100%" required></label></div>
      <div class="df-mrow"><label style="flex:1">Telefon<input name="phone" class="df-i" style="width:100%" inputmode="tel"></label></div>
      <p style="font-size:12.5px;color:#7d5a0b;margin:0 0 12px">Müşteri kaydı açılır, ayrıntılarını daha sonra Müşteriler bölümünden tamamlayabilirsiniz.</p>
    </div>
    <div class="df-mf"><button type="submit" class="pl-btn gr">Devam Et</button><button type="button" class="df-link" data-close>Vazgeç</button></div>
  </form>
  <button type="button" class="df-x" data-close>×</button>
</div></div>

<script>
(function(){
  var $=function(s,r){return (r||document).querySelector(s)},$$=function(s,r){return [].slice.call((r||document).querySelectorAll(s))};
  $$('#tkT tr.tk-r').forEach(function(r){r.addEventListener('click',function(){location.href=r.dataset.href})});
  var by=$('#tkBy'),q=$('#tkQ');
  function filt(){var s=q.value.trim().toLowerCase();$$('#tkT tr.tk-r').forEach(function(r){r.hidden=s.length>=2&&r.dataset[by.value].indexOf(s)<0})}
  q.addEventListener('input',filt);by.addEventListener('change',filt);
  function modal(el,on){el.hidden=!on}
  $('#tkReg').onclick=function(){modal($('#tkM1'),true);$('#tkErr').hidden=true;setTimeout(function(){$('#tkPick').focus()},50)};
  $('#tkNew').onclick=function(){modal($('#tkM2'),true);setTimeout(function(){$('#tkM2 input[name=name]').focus()},50)};
  $$('.df-modal').forEach(function(m){m.addEventListener('mousedown',function(e){if(e.target===m)m.hidden=true});$$('[data-close]',m).forEach(function(b){b.onclick=function(){m.hidden=true}})});
  function go(){var v=$('#tkPick').value.trim(),id=0;$$('#tkList option').forEach(function(o){if(o.value===v)id=o.dataset.id});
    if(!id){$('#tkErr').hidden=false;return}location.href='<?= aurl('acc/doc/proposal/new') ?>?party_id='+id}
  $('#tkGo').onclick=go;$('#tkPick').addEventListener('keydown',function(e){if(e.key==='Enter'){e.preventDefault();go()}});
  document.addEventListener('keydown',function(e){if(e.key==='Escape')$$('.df-modal').forEach(function(m){m.hidden=true})});
})();
</script>
<?php admin_view('layout_bottom'); ?>
