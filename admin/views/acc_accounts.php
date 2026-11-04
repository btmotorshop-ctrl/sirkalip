<?php
/** Hesaplarım – BizimHesap "Nakit Yönetimi › Hesaplarım" */
$title = 'Hesaplarım';
admin_view('layout_top', compact('title'));
$colors = acc_account_colors();
$cols = [['cash', 'pos', 'card'], ['bank', 'partner', 'credit']];
?>
<div class="hs-bar">
  <div class="hs-dd" id="hsAddDd">
    <button type="button" class="hs-btn gr" id="hsAddBtn"><?= icon('plus', '') ?> Yeni Hesap Ekle <span class="hs-car">▾</span></button>
    <div class="hs-menu">
      <?php foreach (['cash' => 'Kasa Ekle', 'bank' => 'Banka Hesabı Ekle', 'pos' => 'POS Hesabı Ekle', 'partner' => 'Ortaklar Hesabı Ekle', 'credit' => 'Veresiye Hesabı Ekle', 'card' => 'Kredi Kartı Ekle'] as $k => $l): ?>
        <a href="#" data-new="<?= $k ?>"><?= e($l) ?></a>
      <?php endforeach; ?>
    </div>
  </div>
  <button type="button" class="hs-btn tl" data-soon title="Banka entegrasyonu bu sürümde yok">
    <?= icon('plus', '') ?> Yeni Banka Entegrasyonu Ekle <span class="hs-new">yakında</span></button>
</div>

<div class="hs-grid">
  <?php foreach ($cols as $col): ?>
  <div class="hs-col">
    <?php foreach ($col as $gk): [$one, $gl, $help] = $kinds[$gk]; $list = array_values(array_filter($rows, function ($r) use ($gk) { return $r['kind'] === $gk; })); ?>
      <div class="hs-card">
        <div class="hs-h"><span><?= e(mb_strtoupper($gl, 'UTF-8')) ?></span><?php if ($gk !== 'cash' && $gk !== 'bank'): ?><span class="hs-q" title="<?= e($help) ?>">?</span><?php endif; ?></div>
        <div class="hs-b">
          <?php foreach ($list as $r): $c = $colors[$r['color'] ?? ''] ?? $colors['']; ?>
            <a class="hs-chip<?= $r['is_active'] ? '' : ' off' ?>" style="background:<?= e($c[1]) ?>" href="<?= aurl('acc/account/' . $r['id']) ?>">
              <span class="n"><?= e($r['name']) ?></span>
              <b class="<?= $r['balance'] < 0 ? 'neg' : '' ?>"><?= e(acc_account_cur($r['currency'])) ?> <?= e(number_format($r['balance'], 2, ',', '.')) ?></b>
            </a>
          <?php endforeach; ?>
          <?php if (!$list): ?><span class="hs-none">Henüz tanımlı hesap yok.</span><?php endif; ?>
        </div>
      </div>
    <?php endforeach; ?>
  </div>
  <?php endforeach; ?>
</div>

<!-- Yeni hesap -->
<div class="df-modal" id="hsNew" hidden><div class="df-mbox pm-box">
  <div class="df-mh"><span id="hsNewT">Banka Hesabı</span><button type="button" class="pm-x" data-close aria-label="Kapat">×</button></div>
  <form method="post" action="<?= aurl('acc/account-save') ?>"><?= csrf_field() ?><input type="hidden" name="kind" id="hsKind" value="bank">
    <div class="pm-b">
      <div class="pm-r"><label>Tanım</label><input name="name" class="df-i" maxlength="120" required autocomplete="off"></div>
      <div class="pm-r"><label>Etiket Rengi</label><select name="color" class="df-i hs-color"><?php foreach ($colors as $k => $c): ?><option value="<?= e($k) ?>" data-bg="<?= e($c[1]) ?>"><?= e($c[0]) ?></option><?php endforeach; ?></select></div>
      <div class="pm-r"><label>Para Birimi</label><select name="currency" class="df-i"><?php foreach (acc_party_currencies() as $k => $l): ?><option value="<?= e($k) ?>"><?= e($l) ?></option><?php endforeach; ?></select></div>
      <div class="pm-r"><label>Hesap No</label><input name="account_no" class="df-i" maxlength="60" placeholder="isteğe bağlı" autocomplete="off"></div>
      <div class="pm-r"><label>Güncel Bakiye</label><input name="balance" class="df-i" inputmode="decimal" value="0" autocomplete="off"></div>
      <div class="pm-r" id="hsLimR" hidden><label>Harcama Limiti</label><input name="credit_limit" class="df-i" inputmode="decimal" autocomplete="off"></div>
    </div>
    <div class="df-mf"><button class="pm-save gr" type="submit"><?= icon('check', '') ?> Kaydet</button></div>
  </form>
</div></div>

<script>
(function(){
  var $=function(s,r){return (r||document).querySelector(s)},$$=function(s,r){return [].slice.call((r||document).querySelectorAll(s))};
  var dd=$('#hsAddDd');
  $('#hsAddBtn').addEventListener('click',function(e){e.stopPropagation();dd.classList.toggle('open')});
  document.addEventListener('click',function(){dd.classList.remove('open')});
  var names={cash:'Kasa',bank:'Banka Hesabı',pos:'POS Hesabı',partner:'Ortaklar Hesabı',credit:'Veresiye Hesabı',card:'Kredi Kartı'};
  var m=$('#hsNew');
  $$('[data-new]').forEach(function(a){a.addEventListener('click',function(e){e.preventDefault();dd.classList.remove('open');
    $('#hsKind').value=a.dataset.new;$('#hsNewT').textContent=names[a.dataset.new];$('#hsLimR').hidden=a.dataset.new!=='card';
    m.hidden=false;setTimeout(function(){$('input[name=name]',m).focus()},50)})});
  m.addEventListener('mousedown',function(e){if(e.target===m)m.hidden=true});
  $$('[data-close]',m).forEach(function(x){x.addEventListener('click',function(){m.hidden=true})});
  document.addEventListener('keydown',function(e){if(e.key==='Escape')m.hidden=true});
  $$('.hs-color').forEach(function(s){function p(){s.style.background=s.selectedOptions[0].dataset.bg}s.addEventListener('change',p);p()});
  $$('[data-soon]').forEach(function(b){b.addEventListener('click',function(){alert('Banka entegrasyonu bu sürümde henüz yok. Hesap bakiyelerini hareketlerle takip edebilirsiniz.')})});
})();
</script>
<?php admin_view('layout_bottom'); ?>
