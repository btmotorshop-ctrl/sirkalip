<?php
/** Tahsilat / Ödeme (Nakit - Kredi Kartı - Banka) – BizimHesap tarzı */
$title = $isCust ? 'Tahsilat / Ödeme' : 'Ödeme / Tahsilat';
admin_view('layout_top', compact('title'));
$kindName = $isCust ? 'Müşteri' : 'Tedarikçi';
$back = aurl('acc/party/' . (int)$p['id']);
$ops = $isCust ? ['in' => 'Müşteriden Tahsilat', 'out' => 'Müşteriye Ödeme'] : ['out' => 'Tedarikçiye Ödeme', 'in' => 'Tedarikçiden Tahsilat'];
$curSym = ['TRY' => 'TL'];
$accMap = [];
foreach ($accounts as $a) $accMap[(int)$a['id']] = ['cur' => $a['currency'], 'sym' => $curSym[$a['currency']] ?? $a['currency'], 'rate' => $a['currency'] === 'TRY' ? 1 : round($a['xrate'], 4)];
$ac0 = (int)($f['account_id'] ?? 0);
?>
<form method="post" id="tpF" action="<?= aurl('acc/payment') ?>">
  <?= csrf_field() ?><input type="hidden" name="party_id" value="<?= (int)$p['id'] ?>">
  <div class="tp-bar">
    <button class="tp-b gr" type="submit"><?= icon('check', '') ?> Kaydet</button>
    <a class="tp-b tl" href="<?= e($back) ?>"><?= icon('user', '') ?> <?= $kindName ?> Sayfası</a>
  </div>
  <?php if ($err): ?><div class="tp-err"><?= e($err) ?></div><?php endif; ?>
  <div class="tp-grid">
    <div class="tp-card">
      <div class="tp-h">GİRİŞ</div>
      <div class="tp-body">
        <div class="tp-r"><label class="req">İşlem</label>
          <select name="type" class="df-i"><?php foreach ($ops as $k => $v): ?><option value="<?= $k ?>" <?= $f['type'] === $k ? 'selected' : '' ?>><?= e($v) ?></option><?php endforeach; ?></select></div>
        <div class="tp-r"><label>Tarih</label><input type="date" name="date" class="df-i" value="<?= e($f['date']) ?>"></div>
        <div class="tp-r top"><label>Açıklama</label><textarea name="note" rows="3" maxlength="250" class="df-i" placeholder="isteğe bağlı..."><?= e($f['note']) ?></textarea></div>
        <div class="tp-r"><label>Kasa/Hesap</label>
          <select name="account_id" id="tpAcc" class="df-i"><option value="">Hesap seçin</option>
            <?php foreach ($accounts as $a): ?><option value="<?= (int)$a['id'] ?>" <?= $ac0 === (int)$a['id'] ? 'selected' : '' ?>><?= e($a['name']) ?> (<?= e(number_format($a['bal'], 2, ',', '.')) ?> <?= e($curSym[$a['currency']] ?? $a['currency']) ?>)</option><?php endforeach; ?></select></div>
        <div class="tp-r"><label id="tpAmL">Tutar (TL)</label><input name="amount" id="tpAm" class="df-i" inputmode="decimal" autocomplete="off" value="<?= e($f['amount']) ?>"></div>
        <div class="tp-r fx" id="tpFx" hidden>
          <label></label>
          <div class="tp-two"><div><small>Döviz Kuru</small><input name="rate" id="tpRate" class="df-i" inputmode="decimal" autocomplete="off" value="<?= e($f['rate']) ?>"></div>
            <div><small><b>Kaydedilecek Tutar (TL)</b></small><input name="effective" id="tpEff" class="df-i" inputmode="decimal" autocomplete="off" value="<?= e($f['effective']) ?>"></div></div>
        </div>
      </div>
    </div>
    <div class="tp-card g">
      <div class="tp-h"><?= e($p['name']) ?></div>
      <div class="tp-body info">
        <div class="tp-i"><span>Açık Hesap Bakiyesi</span><b class="<?= $bal > 0.004 ? 'neg' : '' ?>"><?= e(number_format($bal, 2, ',', '.')) ?></b></div>
        <div class="tp-i"><span>Çek/Senet Bakiyesi</span><b>0,00</b></div>
        <div class="tp-i"><span>Banka Bilgileri</span><b class="soft"><?= !empty($p['bank_info']) ? nl2br(e($p['bank_info'])) : 'kayıtlı değil' ?></b></div>
      </div>
    </div>
  </div>
</form>
<script>
(function(){
  var acc=<?= json_encode($accMap, JSON_UNESCAPED_UNICODE) ?>,sel=document.getElementById('tpAcc'),am=document.getElementById('tpAm'),rt=document.getElementById('tpRate'),ef=document.getElementById('tpEff'),
      fx=document.getElementById('tpFx'),aml=document.getElementById('tpAmL'),f=document.getElementById('tpF');
  function num(s){s=String(s||'').replace(/\s/g,'');if(s.indexOf(',')>-1)s=s.replace(/\./g,'').replace(',','.');var n=parseFloat(s);return isNaN(n)?0:n}
  function fmt(n,d){return n.toLocaleString('tr-TR',{minimumFractionDigits:d,maximumFractionDigits:d})}
  function cur(){return acc[sel.value]||null}
  function onAcc(){var a=cur();aml.textContent='Tutar ('+(a?a.sym:'TL')+')';
    var foreign=a&&a.cur!=='TRY';fx.hidden=!foreign;
    if(foreign){if(!rt.value||!num(rt.value))rt.value=fmt(a.rate,4);calc()}}
  function calc(){var a=num(am.value),r=num(rt.value);ef.value=a&&r?fmt(a*r,2):'0,00'}
  sel.addEventListener('change',function(){rt.value='';onAcc()});
  am.addEventListener('input',calc);rt.addEventListener('input',calc);
  ef.addEventListener('input',function(){var a=num(am.value),e=num(ef.value);if(a>0&&e>0)rt.value=fmt(e/a,4)});
  f.addEventListener('submit',function(){var b=f.querySelector('button[type=submit]');setTimeout(function(){b.disabled=true},0)});
  onAcc();if(!rt.value&&cur()&&cur().cur!=='TRY')rt.value=fmt(cur().rate,4);
})();
</script>
<?php admin_view('layout_bottom'); ?>
