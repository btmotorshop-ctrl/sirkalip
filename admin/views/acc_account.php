<?php
/** Hesap detayı – BizimHesap "ngnaccount" */
$title = $a['name'];
admin_view('layout_top', compact('title'));
$kinds = acc_account_kinds(); $colors = acc_account_colors();
$cur = $a['currency']; $cs = acc_account_cur($cur);
$num = function ($v) { return number_format((float)$v, 2, ',', '.'); };
$today = date('Y-m-d');
$act = aurl('acc/account-move');
$hdr = function ($t) { return '<div class="df-mh">' . e($t) . '<button type="button" class="pm-x" data-close aria-label="Kapat">×</button></div>'; };
$bg = ($colors[$a['color'] ?? ''] ?? $colors[''])[1];
?>
<div class="hs-top">
  <div class="hs-head" style="background:linear-gradient(135deg,#6a8fd0,#4a6fb4)">
    <div class="hs-cur"><?= e($cs === 'TL' ? '₺' : $cs) ?></div>
    <div><div class="hs-nm"><?= e($a['name']) ?></div><div class="hs-bl">Bakiye : <?= $num($bal) ?> <?= e($cs) ?></div>
      <?php if (!empty($a['account_no'])): ?><div class="hs-no"><?= e($kinds[$a['kind']][0] ?? '') ?> · No: <?= e($a['account_no']) ?></div><?php endif; ?></div>
  </div>
  <div class="hs-acts">
    <button type="button" class="hs-act bl" data-m="upd"><?= icon('pencil', '') ?> Güncelle</button>
    <button type="button" class="hs-act gn" data-m="in"><?= icon('plus', '') ?> Hesaba Para Girişi Yap</button>
    <button type="button" class="hs-act rd" data-m="out"><?= icon('minus', '') ?> Hesaptan Para Çıkışı Yap</button>
    <button type="button" class="hs-act or" data-m="tr"><?= icon('refresh-ccw', '') ?> Hesaplar Arası Transfer</button>
    <a class="hs-act tl" href="<?= aurl('acc/accounts') ?>"><?= icon('chevron-left', '') ?> Hesaplarım</a>
  </div>
</div>

<div class="st-panel hs-tbl">
  <div class="st-h"><span>HESAP HAREKETLERİ</span><input type="search" id="hsFind" class="hs-find" placeholder="Bul..." autocomplete="off"></div>
  <div class="st-b" style="padding:14px 16px">
    <div class="st-wrap">
    <table class="st-t hs-t" id="hsT" style="min-width:900px">
      <thead><tr><th>Tarih</th><th>İşlem</th><th>Kullanıcı</th><th>Hesap</th><th>Açıklama</th><th class="r">Borç</th><th class="r">Alacak</th><th class="r">Bakiye</th><th></th></tr></thead>
      <tbody>
      <?php foreach ($moves as $m): ?>
        <tr>
          <td><?= acc_date($m['d']) ?></td><td><?= e($m['label']) ?></td><td><?= e($m['user']) ?></td>
          <td><?php if ($m['pid']): ?><a href="<?= aurl('acc/party/' . $m['pid']) ?>"><?= e($m['who']) ?></a><?php else: echo e($m['who']); endif; ?></td>
          <td><?= e($m['note']) ?></td>
          <td class="r"><?= $m['in'] ? $num($m['in']) : '' ?></td><td class="r"><?= $m['out'] ? $num($m['out']) : '' ?></td>
          <td class="r"><b><?= $num($m['bal']) ?></b></td>
          <td class="r"><?php if ($m['cm']): ?>
            <form method="post" action="<?= aurl('acc/account-move-delete') ?>" data-confirm="Bu hesap hareketi silinsin mi?"><?= csrf_field() ?><input type="hidden" name="id" value="<?= (int)$m['cm'] ?>"><button class="hs-del" type="submit" title="Sil"><?= icon('x', '') ?> Sil</button></form>
          <?php elseif ($m['pid']): ?><a class="hs-del" href="<?= aurl('acc/payments/' . $m['pid']) ?>">Cari hareketleri</a><?php endif; ?></td>
        </tr>
      <?php endforeach; ?>
      <?php if (abs((float)$a['opening_balance']) > 0.004): ?>
        <tr class="hs-open"><td></td><td>Açılış Bakiyesi</td><td></td><td></td><td></td><td class="r"></td><td class="r"></td><td class="r"><b><?= $num($a['opening_balance']) ?></b></td><td></td></tr>
      <?php endif; ?>
      <?php if (!$moves && abs((float)$a['opening_balance']) <= 0.004): ?><tr><td colspan="9" class="st-empty">Bu hesapta henüz hareket yok.</td></tr><?php endif; ?>
      </tbody>
    </table>
    </div>
  </div>
</div>

<!-- Güncelle -->
<div class="df-modal" id="hsUpd" hidden><div class="df-mbox pm-box">
  <?= $hdr($kinds[$a['kind']][0] ?? 'Hesap') ?>
  <form method="post" action="<?= aurl('acc/account-save') ?>"><?= csrf_field() ?><input type="hidden" name="id" value="<?= (int)$a['id'] ?>">
    <div class="pm-b">
      <div class="pm-r"><label>Tanım</label><input name="name" class="df-i" maxlength="120" required value="<?= e($a['name']) ?>"></div>
      <div class="pm-r"><label>Etiket Rengi</label><select name="color" class="df-i hs-color"><?php foreach ($colors as $k => $c): ?><option value="<?= e($k) ?>" data-bg="<?= e($c[1]) ?>"<?= (string)($a['color'] ?? '') === (string)$k ? ' selected' : '' ?>><?= e($c[0]) ?></option><?php endforeach; ?></select></div>
      <div class="pm-r"><label>Para Birimi</label><input class="df-i" value="<?= e($cs) ?>" disabled></div>
      <div class="pm-r"><label>Hesap No</label><input name="account_no" class="df-i" maxlength="60" placeholder="isteğe bağlı" value="<?= e((string)($a['account_no'] ?? '')) ?>"></div>
      <div class="pm-r"><label>Güncel Bakiye</label><input name="balance" class="df-i" inputmode="decimal" value="<?= e($num($bal)) ?>" autocomplete="off"></div>
      <?php if ($a['kind'] === 'card'): ?><div class="pm-r"><label>Harcama Limiti</label><input name="credit_limit" class="df-i" inputmode="decimal" value="<?= $a['credit_limit'] !== null ? e($num($a['credit_limit'])) : '' ?>"></div><?php endif; ?>
      <div class="pm-r"><label>Durum</label><label class="hs-chk"><input type="checkbox" name="inactive" value="1"<?= $a['is_active'] ? '' : ' checked' ?>> Pasif (listede soluk gösterilir, ödeme ekranlarında çıkmaz)</label></div>
    </div>
    <div class="df-mf"><button class="pm-save gr" type="submit"><?= icon('check', '') ?> Kaydet</button></div>
  </form>
</div></div>

<!-- Para girişi / çıkışı -->
<?php foreach (['in' => 'Hesaba Para Girişi', 'out' => 'Hesaptan Para Çıkışı'] as $t => $tl): ?>
<div class="df-modal" id="hsM<?= $t ?>" hidden><div class="df-mbox pm-box">
  <?= $hdr($tl . ' – ' . $a['name']) ?>
  <form method="post" action="<?= $act ?>"><?= csrf_field() ?><input type="hidden" name="account_id" value="<?= (int)$a['id'] ?>"><input type="hidden" name="type" value="<?= $t ?>">
    <div class="pm-b">
      <div class="pm-r"><label>İşlem Tarihi</label><input type="date" name="date" class="df-i" value="<?= $today ?>"></div>
      <div class="pm-r"><label>Tutar (<?= e($cs) ?>)</label><input name="amount" class="df-i" inputmode="decimal" autocomplete="off" required></div>
      <div class="pm-r top"><label>Açıklama</label><textarea name="note" rows="3" maxlength="250" class="df-i"></textarea></div>
    </div>
    <div class="df-mf"><button type="button" class="pm-save or" data-close><?= icon('x', '') ?> Vazgeç</button><button class="pm-save <?= $t === 'in' ? 'gr' : 'rd' ?>" type="submit"><?= icon('check', '') ?> Kaydet</button></div>
  </form>
</div></div>
<?php endforeach; ?>

<!-- Transfer -->
<div class="df-modal" id="hsMtr" hidden><div class="df-mbox pm-box">
  <?= $hdr('Hesaplar Arası Transfer') ?>
  <form method="post" action="<?= $act ?>"><?= csrf_field() ?><input type="hidden" name="account_id" value="<?= (int)$a['id'] ?>"><input type="hidden" name="type" value="transfer">
    <div class="pm-b">
      <div class="pm-r"><label>Kaynak Hesap</label><input class="df-i" value="<?= e($a['name'] . ' (' . $cs . ')') ?>" disabled></div>
      <div class="pm-r"><label>Hedef Hesap</label><select name="to_id" id="hsTo" class="df-i" required><option value="">— Seçiniz —</option>
        <?php foreach ($others as $o): ?><option value="<?= (int)$o['id'] ?>" data-cur="<?= e($o['currency']) ?>"><?= e($o['name'] . ' (' . acc_account_cur($o['currency']) . ')') ?></option><?php endforeach; ?></select></div>
      <div class="pm-r"><label>İşlem Tarihi</label><input type="date" name="date" class="df-i" value="<?= $today ?>"></div>
      <div class="pm-r"><label>Tutar (<?= e($cs) ?>)</label><input name="amount" class="df-i" inputmode="decimal" autocomplete="off" required></div>
      <div class="pm-r" id="hsToAmR" hidden><label>Hedefe Geçen Tutar</label><div><input name="to_amount" class="df-i" inputmode="decimal" autocomplete="off"><small class="pm-hint" id="hsToAmH"></small></div></div>
      <div class="pm-r top"><label>Açıklama</label><textarea name="note" rows="3" maxlength="250" class="df-i"></textarea></div>
    </div>
    <div class="df-mf"><button type="button" class="pm-save or" data-close><?= icon('x', '') ?> Vazgeç</button><button class="pm-save gr" type="submit"><?= icon('check', '') ?> Aktar</button></div>
  </form>
</div></div>

<script>
(function(){
  var $=function(s,r){return (r||document).querySelector(s)},$$=function(s,r){return [].slice.call((r||document).querySelectorAll(s))};
  var map={upd:'#hsUpd',in:'#hsMin',out:'#hsMout',tr:'#hsMtr'},mods=$$('#hsUpd,#hsMin,#hsMout,#hsMtr');
  $$('[data-m]').forEach(function(b){b.addEventListener('click',function(){var m=$(map[b.dataset.m]);m.hidden=false;var i=$('input:not([type=hidden]):not([disabled]),textarea',m);if(i)setTimeout(function(){i.focus()},50)})});
  mods.forEach(function(m){m.addEventListener('mousedown',function(e){if(e.target===m)m.hidden=true});$$('[data-close]',m).forEach(function(x){x.addEventListener('click',function(){m.hidden=true})})});
  document.addEventListener('keydown',function(e){if(e.key==='Escape')mods.forEach(function(m){m.hidden=true})});
  $$('.hs-color').forEach(function(s){function p(){s.style.background=s.selectedOptions[0].dataset.bg}s.addEventListener('change',p);p()});
  var me='<?= e($cur) ?>',to=$('#hsTo');
  to.addEventListener('change',function(){var o=to.selectedOptions[0],c=o&&o.dataset.cur;var diff=c&&c!==me;$('#hsToAmR').hidden=!diff;if(diff)$('#hsToAmH').textContent='Hedef hesaba geçecek tutar ('+(c==='TRY'?'TL':c)+')'});
  var f=$('#hsFind');f.addEventListener('input',function(){var q=f.value.toLocaleLowerCase('tr');$$('#hsT tbody tr').forEach(function(r){r.style.display=r.textContent.toLocaleLowerCase('tr').indexOf(q)>-1?'':'none'})});
})();
</script>
<?php admin_view('layout_bottom'); ?>
