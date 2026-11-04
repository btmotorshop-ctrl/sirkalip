<?php
$kindName = $isCust ? 'Müşteri' : 'Tedarikçi';
$title = $p['name'];
admin_view('layout_top', compact('title'));
$tl = function ($v, $cur = 'TRY') { return acc_money($v, $cur); };
$qs = '?party_id=' . (int)$p['id'];
$edit = aurl('acc/party-edit/' . $p['kind'] . '/' . $p['id']);
$ekstre = aurl('acc/party/' . $p['id'] . '/ekstre');
$mainNew = aurl('acc/doc/' . $mainKind . '/new' . $qs);
$otherNew = aurl('acc/doc/' . $otherKind . '/new' . $qs);
$payNew = aurl('acc/payment' . $qs);
$allPays = aurl('acc/payments/' . (int)$p['id']);
$sub = trim(($p['contact_person'] ?: '') . ($p['contact_person'] && $p['phone'] ? '   ' : ''));
$addr = trim((string)($p['address'] ?: ''));
$tax = trim(($p['tax_office'] ?: '') . ($p['tax_office'] && $p['tax_no'] ? ' / ' : '') . ($p['tax_no'] ?: ''));
$ic = function ($d) { return '<svg viewBox="0 0 24 24" fill="currentColor"><path d="' . $d . '"/></svg>'; };
?>
<div class="pv-top">
  <div class="pv-card">
    <div class="pv-av"><?php if (!empty($p['image'])): ?><img src="<?= e(media_url($p['image'])) ?>" alt=""><?php else: ?><svg viewBox="0 0 24 24" fill="currentColor"><path d="M12 12a4.5 4.5 0 1 0 0-9 4.5 4.5 0 0 0 0 9zm0 2c-4 0-8 2-8 5v2h16v-2c0-3-4-5-8-5z"/></svg><?php endif; ?></div>
    <div class="pv-info">
      <h1><?= e($p['name']) ?></h1>
      <?php if (!$p['is_active']): ?><span class="pv-pasif">PASİF</span><?php endif; ?>
      <?php if ($class): ?><span class="pv-class"><?= e($class) ?></span><?php endif; ?>
      <?php if ($p['contact_person'] || $p['phone']): ?><div class="pv-l"><?= icon('user', '') ?><?= e($p['contact_person'] ?: '') ?> <?php if ($p['phone']): ?><a href="tel:<?= e(preg_replace('/\s+/', '', $p['phone'])) ?>"><?= icon('phone', '') ?><?= e($p['phone']) ?></a><?php endif; ?><?php if ($p['phone2']): ?> <a href="tel:<?= e(preg_replace('/\s+/', '', $p['phone2'])) ?>"><?= e($p['phone2']) ?></a><?php endif; ?></div><?php endif; ?>
      <?php if ($p['email']): ?><div class="pv-l"><?= icon('mail', '') ?><a href="mailto:<?= e(explode(',', $p['email'])[0]) ?>"><?= e($p['email']) ?></a></div><?php endif; ?>
      <?php if ($addr): ?><div class="pv-l"><?= icon('home', '') ?><?= e($addr) ?></div><?php endif; ?>
      <?php if ($tax): ?><div class="pv-l"><span class="pv-v">V</span><?= e($tax) ?></div><?php endif; ?>
    </div>
  </div>
  <div class="pv-note" id="pvNote" title="Not eklemek / düzenlemek için tıklayın">
    <div class="pv-note-t" id="pvNoteT"><?= $p['note'] ? nl2br(e($p['note'])) : '<span>Bu ' . strtolower($kindName) . ' ile ilgili not kaydetmek için tıklayın.</span>' ?></div>
    <textarea id="pvNoteA" hidden maxlength="2000"><?= e($p['note'] ?? '') ?></textarea>
  </div>
</div>

<div class="pv-stats">
  <div class="pv-st a"><?= icon('banknote', '') ?><div><span>Açık Bakiye</span><strong data-count="<?= round($bal, 2) ?>">TL <?= number_format($bal, 2, ',', '.') ?></strong></div></div>
  <div class="pv-st b"><?= icon('tags', '') ?><div><span>Çek Bakiyesi</span><strong>TL 0,00</strong></div></div>
  <div class="pv-st b"><?= icon('tags', '') ?><div><span>Senet Bakiyesi</span><strong>TL 0,00</strong></div></div>
  <div class="pv-st c"><?= icon('trending-up', '') ?><div><span><?= $isCust ? 'Cirosu' : 'Alışları' ?></span><strong data-count="<?= round($turnover, 2) ?>">TL <?= number_format($turnover, 2, ',', '.') ?></strong></div></div>
</div>

<div class="pv-actions">
  <a class="pv-b nv" href="<?= $mainNew ?>"><?= icon('shopping-cart', '') ?> <?= $isCust ? 'Satış Yap' : 'Alış Yap' ?></a>
  <?php if ($isCust): ?><a class="pv-b or" href="<?= aurl('acc/doc/proposal/new' . $qs) ?>"><?= icon('file-text', '') ?> Teklif Hazırla</a><?php endif; ?>
  <div class="pv-dd"><button type="button" class="pv-b gr"><?= icon('banknote', '') ?> <?= $isCust ? 'Tahsilat/Ödeme' : 'Ödeme/Tahsilat' ?> <i>▾</i></button>
    <div class="pv-menu">
      <a href="<?= $payNew ?>">Nakit - Kredi Kartı - Banka</a>
      <a href="#" data-pm="pos">Temassız Kredi Kartı</a>
      <?php foreach (['Çek', $isCust ? 'Müşteriden Senet Al' : 'Tedarikçiden Senet Al', $isCust ? 'Müşteriye Senet Ver' : 'Tedarikçiye Senet Ver'] as $x): ?><a href="#" data-soon="<?= e($x) ?> bir sonraki adımlarda eklenecek."><?= e($x) ?> <small>yakında</small></a><?php endforeach; ?>
      <a href="#" data-pm="bal">Bakiye düzelt</a>
      <a href="#" data-pm="rec">Borç-Alacak Fişleri</a>
      <a href="#" data-pm="vir">Cari Virman</a>
    </div></div>
  <?php if ($isCust): ?><button type="button" class="pv-b tl" data-soon="Tahsilat isteme bir sonraki adımlarda eklenecek."><?= icon('credit-card', '') ?> Tahsilat İste</button><?php endif; ?>
  <div class="pv-dd"><button type="button" class="pv-b wh"><?= icon('file-spreadsheet', '') ?> Hesap Ekstresi <i>▾</i></button>
    <div class="pv-menu">
      <a href="<?= $ekstre ?>">Ekstre</a>
      <a href="<?= aurl('acc/party/' . $p['id'] . '/detay') ?>">Detaylı Ekstre</a>
      <a href="#" data-soon="Ekstre linki bir sonraki adımlarda eklenecek.">Ekstre Linki <small>yakında</small></a>
      <a href="#" data-soon="Mutabakat mektubu bir sonraki adımlarda eklenecek.">Mutabakat Mektubu <small>yakında</small></a>
    </div></div>
  <button type="button" class="pv-b pu" data-soon="SMS gönderimi bir sonraki adımlarda eklenecek."><?= icon('phone', '') ?> SMS Gönder</button>
  <?php if ($isCust): ?><button type="button" class="pv-b dr" id="pvAras"><?= icon('truck', '') ?> Kargo Siparişi Oluştur</button><?php endif; ?>
  <div class="pv-dd"><button type="button" class="pv-b rd"><?= icon('settings', '') ?> Diğer İşlemler <i>▾</i></button>
    <div class="pv-menu">
      <a href="<?= $edit ?>"><?= $kindName ?> Bilgilerini Güncelle</a>
      <a href="<?= $otherNew ?>"><?= $isCust ? 'Alış Yap' : 'Satış Yap' ?></a>
      <?php foreach (['Etiket Yazdır', 'Döküman Yükle', 'E-Posta Gönderi Durumu'] as $x): ?><a href="#" data-soon="<?= e($x) ?> bir sonraki adımlarda eklenecek."><?= e($x) ?> <small>yakında</small></a><?php endforeach; ?>
      <a href="#" class="danger" id="pvDel"><?= $kindName ?>yi Sil</a>
    </div></div>
</div>
<form method="post" action="<?= aurl('acc/party-delete') ?>" id="pvDelForm" hidden><?= csrf_field() ?><input type="hidden" name="id" value="<?= (int)$p['id'] ?>"></form>
<div class="pl-toast" id="plToast"></div>
<?php if ($isCust): $ac = aras_config(); admin_view('acc_aras_modal', compact('p', 'ac')); endif; ?>
<?php admin_view('acc_party_modals', compact('p', 'bal', 'isCust')); ?>

<?php
$statusTxt = function ($s) { return $s === 'cancelled' ? ['İptal', 'bad'] : ($s === 'proforma' ? ['Proforma', 'bad'] : ['Kesinleşti', 'ok']); };
$panel = function ($ttl, $body) { return '<div class="pv-panel"><div class="pv-ph"><span>' . $ttl . '</span><button type="button" class="pv-col" aria-label="Aç/Kapat">⌄</button></div><div class="pv-pb">' . $body . '</div></div>'; };
$docTable = function ($rows, $kind) use ($statusTxt) {
    $h = '<table class="pv-t"><thead><tr><th></th><th>Tarih</th><th>No</th><th>Durum</th><th class="r">Tutar</th></tr></thead><tbody>';
    foreach ($rows as $d) { [$st, $cl] = $statusTxt($d['status']);
        $h .= '<tr class="pv-r"><td><i class="pv-plus">+</i></td><td>' . e(acc_date($d['doc_date'])) . '</td><td><a href="' . e(aurl('acc/doc/' . $kind . '/' . $d['id'])) . '">' . e($d['doc_no']) . '</a></td><td class="' . $cl . '">' . $st . '</td><td class="r">' . e(acc_money($d['total'], $d['currency'])) . '</td></tr>'
           . '<tr class="pv-x" hidden><td></td><td colspan="4">' . (int)$d['n'] . ' kalem' . ($d['note'] ? ' · ' . e($d['note']) : '') . ' · <a href="' . e(aurl('acc/doc/' . $kind . '/' . $d['id'])) . '">Belgeyi aç</a></td></tr>'; }
    return $h . '</tbody></table>';
};
$empty = function ($txt, $link, $lt) { return '<div class="pv-empty">' . $txt . '<br><a href="' . e($link) . '">' . $lt . '</a></div>'; };
?>
<div class="pv-grid">
  <div class="pv-col2">
    <?= $panel($isCust ? 'ÖNCEKİ SATIŞLAR' : 'ÖNCEKİ ALIŞLAR', $mainDocs ? $docTable($mainDocs, $mainKind) . (count($mainDocs) >= 12 ? '<a class="pv-more" href="' . e($ekstre) . '">tümünü gör...</a>' : '') : $empty('Bu ' . strtolower($kindName) . 'nin daha önceden kayıtlı ' . ($isCust ? 'satışı' : 'alışı') . ' yok.', $mainNew, ($isCust ? 'Satış' : 'Alış') . ' yapmak için tıklayınız.')) ?>
    <?= $panel($isCust ? 'ALIŞLAR / İADELER' : 'SATIŞLAR / İADELER', $otherDocs ? $docTable($otherDocs, $otherKind) : $empty('Bu ' . strtolower($kindName) . 'nin daha önceden kayıtlı ' . ($isCust ? 'iadesi' : 'satışı') . ' yok.', $otherNew, ($isCust ? 'Alış' : 'Satış') . ' yapmak için tıklayınız.')) ?>
  </div>
  <div class="pv-col2">
    <?php
    $pt = '<table class="pv-t"><thead><tr><th></th><th>Tarih</th><th class="r">Tutar</th><th>Şekli</th></tr></thead><tbody>';
    foreach ($pays as $y) {
        $isIn = $y['kind'] === 'in';
        $pt .= '<tr class="pv-r"><td><i class="pv-plus">+</i></td><td>' . e(acc_date($y['pay_at'])) . '</td><td class="r ' . ($isIn ? 'pos' : 'neg') . '">' . ($isIn ? '' : '-') . e(acc_money($y['amount'], $y['currency'] ?: 'TRY')) . '</td><td>' . e(acc_pay_label($y)) . '</td></tr>'
            . '<tr class="pv-x" hidden><td></td><td colspan="3">' . e($y['account'] ?: 'Hesapsız fiş') . ($y['note'] ? ' · ' . e($y['note']) : '')
            . ' <form method="post" action="' . e(aurl('acc/payment-delete')) . '" class="inline" data-confirm="Bu kayıt silinsin mi? Cari bakiyesi ve hesap bakiyesi buna göre değişir.">' . csrf_field() . '<input type="hidden" name="id" value="' . (int)$y['id'] . '"><button class="tp-del">Sil</button></form></td></tr>';
    }
    $pt .= '</tbody></table>';
    echo $panel('ÖNCEKİ ÖDEMELERİ', $pays ? $pt . '<a class="pv-more" href="' . e($allPays) . '">tamamı için tıklayın...</a>' : $empty('Bu ' . strtolower($kindName) . 'nin daha önceden kayıtlı ödemesi yok.', $payNew, ($isCust ? 'Tahsilat' : 'Ödeme') . ' girmek için tıklayınız.'));
    if ($isCust) {
        $tt = '<table class="pv-t"><thead><tr><th></th><th>Tarih</th><th>No</th><th>Durum</th><th class="r">Tutar</th></tr></thead><tbody>';
        foreach ($props as $d) {
            $stl = $d['status'] === 'converted' ? ['Satışa çevrildi', 'ok'] : ($d['status'] === 'cancelled' ? ['İptal', 'bad'] : ['Açık', 'ok']);
            $tt .= '<tr class="pv-r"><td><i class="pv-plus">+</i></td><td>' . e(acc_date($d['doc_date'])) . '</td><td><a href="' . e(aurl('acc/doc/proposal/' . $d['id'])) . '">' . e($d['doc_no']) . '</a></td><td class="' . $stl[1] . '">' . $stl[0] . '</td><td class="r">' . e(acc_money($d['total'], $d['currency'])) . '</td></tr>'
                . '<tr class="pv-x" hidden><td></td><td colspan="4">' . (int)$d['n'] . ' kalem' . ($d['note'] ? ' · ' . e($d['note']) : '') . ' · <a href="' . e(aurl('acc/doc/proposal/' . $d['id'])) . '">Teklifi aç</a></td></tr>';
        }
        $tt .= '</tbody></table>';
        echo $panel('VERİLEN TEKLİFLER', $props ? $tt : $empty('Bu müşteri için daha önceden verdiğiniz teklif bulunmuyor.', aurl('acc/doc/proposal/new' . $qs), 'Teklif hazırlamak için tıklayın.'));
    }
    ?>
  </div>
</div>
<script>
(function(){
  var $=function(s,r){return (r||document).querySelector(s)},$$=function(s,r){return [].slice.call((r||document).querySelectorAll(s))};
  var csrf=$('#pvDelForm input[name=_csrf]').value;
  // açılır menüler
  $$('.pv-dd>button').forEach(function(b){b.addEventListener('click',function(e){e.stopPropagation();var d=b.parentNode,on=d.classList.contains('open');$$('.pv-dd').forEach(function(x){x.classList.remove('open')});if(!on)d.classList.add('open')})});
  document.addEventListener('click',function(){$$('.pv-dd').forEach(function(x){x.classList.remove('open')})});
  // yakında
  var tt=$('#plToast'),tm;
  $$('[data-soon]').forEach(function(b){b.addEventListener('click',function(e){e.preventDefault();tt.textContent=b.dataset.soon;tt.classList.add('on');clearTimeout(tm);tm=setTimeout(function(){tt.classList.remove('on')},2800)})});
  // silme
  $('#pvDel').addEventListener('click',function(e){e.preventDefault();if(confirm('Bu cari kalıcı olarak silinsin mi? İşlem kaydı varsa silinemez.'))$('#pvDelForm').submit()});
  // paneller
  $$('.pv-col').forEach(function(b){b.addEventListener('click',function(){var p=b.closest('.pv-panel');p.classList.toggle('shut')})});
  $$('.pv-r').forEach(function(r){r.addEventListener('click',function(e){if(e.target.tagName==='A')return;var x=r.nextElementSibling;x.hidden=!x.hidden;r.classList.toggle('open',!x.hidden)})});
  // not
  var box=$('#pvNote'),txt=$('#pvNoteT'),ta=$('#pvNoteA'),ph='<span>Bu <?= strtolower($kindName) ?> ile ilgili not kaydetmek için tıklayın.</span>';
  box.addEventListener('click',function(){if(!ta.hidden)return;txt.hidden=true;ta.hidden=false;ta.focus()});
  function save(){var fd=new FormData();fd.append('_csrf',csrf);fd.append('id','<?= (int)$p['id'] ?>');fd.append('note',ta.value);
    fetch('<?= aurl('acc/party-note') ?>',{method:'POST',body:fd}).then(function(r){return r.json()}).then(function(j){if(j.ok){var v=ta.value.trim();txt.innerHTML=v?v.replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/\n/g,'<br>'):ph;ta.hidden=true;txt.hidden=false}})}
  ta.addEventListener('blur',save);
  // sayı animasyonu
  $$('[data-count]').forEach(function(el){var end=parseFloat(el.dataset.count)||0,t0=null;if(!end||matchMedia('(prefers-reduced-motion:reduce)').matches)return;
    var f=function(n){return 'TL '+n.toLocaleString('tr-TR',{minimumFractionDigits:2,maximumFractionDigits:2})};
    (function step(t){t0=t0||t;var k=Math.min(1,(t-t0)/900);el.textContent=f(end*(1-Math.pow(1-k,3)));if(k<1)requestAnimationFrame(step)})(performance.now());
  });
})();
</script>
<?php admin_view('layout_bottom'); ?>
