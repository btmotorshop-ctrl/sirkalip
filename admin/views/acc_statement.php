<?php
/** Hesap Ekstresi – BizimHesap "ngnaccountabstract" / "ngnaccountabstractdetails" */
$title = $p['name'] . ' Hesap Ekstresi';
admin_view('layout_top', compact('title'));
$base = aurl('acc/party/' . (int)$p['id'] . ($detail ? '/detay' : '/ekstre'));
$q = ['from' => $from, 'to' => $to];
$csv = $base . '?' . http_build_query($q + ['export' => 'csv']);
$bt = function ($v) { return $v ? acc_st_n($v) : ''; };
$cols = $detail ? 11 : 8;
$mail = 'mailto:' . rawurlencode(trim(explode(',', (string)$p['email'])[0])) . '?subject=' . rawurlencode(mb_strtoupper($p['name'], 'UTF-8') . ' hesap ekstresi')
  . '&body=' . rawurlencode("Sayın yetkili,\n\n" . acc_date($from) . ' - ' . acc_date($to) . " tarihleri arasındaki hesap ekstrenizin özeti:\nToplam Borç: " . acc_st_n($st['debit']) . " TL\nToplam Alacak: " . acc_st_n($st['credit']) . " TL\nBakiye: " . acc_st_n($st['bal']) . " TL\n\nİyi çalışmalar.");
?>
<div class="ek-back"><a class="ek-btn tl" href="<?= aurl('acc/party/' . (int)$p['id']) ?>"><?= icon('chevron-left', '') ?> Geri Dön</a>
  <span class="ek-sw"><a href="<?= e(aurl('acc/party/' . (int)$p['id'] . '/ekstre?' . http_build_query($q))) ?>" class="<?= $detail ? '' : 'on' ?>">Ekstre</a><a href="<?= e(aurl('acc/party/' . (int)$p['id'] . '/detay?' . http_build_query($q))) ?>" class="<?= $detail ? 'on' : '' ?>">Detaylı Ekstre</a></span></div>

<div class="st-panel ek-panel" id="ekPanel">
  <div class="ek-h"><?= e(mb_strtoupper($p['name'], 'UTF-8')) ?> HESAP EKSTRESİ</div>
  <div class="ek-b">
    <form method="get" action="<?= $base ?>" class="ek-range">
      <label>Tarih Aralığı</label>
      <div class="ek-dates"><input type="date" name="from" class="df-i" value="<?= e($from) ?>"><span>-</span><input type="date" name="to" class="df-i" value="<?= e($to) ?>"></div>
      <button class="ek-btn rd" type="submit"><?= icon('refresh-ccw', '') ?> Raporu Hazırla</button>
    </form>

    <div class="ek-sum">
      <div class="ek-s a"><i><?= icon('banknote', '') ?></i><div><small>Toplam Borç</small><b><?= acc_st_n($st['debit']) ?></b></div></div>
      <div class="ek-s b"><i><?= icon('banknote', '') ?></i><div><small>Toplam Alacak</small><b><?= acc_st_n($st['credit']) ?></b></div></div>
      <div class="ek-s c"><i><?= icon('banknote', '') ?></i><div><small>Bakiye</small><b><?= acc_st_n($st['bal']) ?></b></div></div>
    </div>

    <div class="ek-tools">
      <a class="ek-btn gr" href="<?= e($csv) ?>"><?= icon('file-spreadsheet', '') ?> Excel</a>
      <button type="button" class="ek-btn or" onclick="window.print()"><?= icon('file-text', '') ?> PDF</button>
      <div class="pv-dd ek-dd"><button type="button" class="ek-btn dk"><?= icon('mail', '') ?> Paylaş <i>▾</i></button>
        <div class="pv-menu">
          <a href="<?= e($mail) ?>"><?= icon('mail', '') ?> E-posta gönder</a>
          <a href="#" data-soon="SMS gönderimi bir sonraki adımlarda eklenecek."><?= icon('phone', '') ?> SMS gönder <small>yakında</small></a>
        </div></div>
    </div>

    <div class="st-wrap">
    <table class="ek-t<?= $detail ? ' det' : '' ?>">
      <thead><tr><th>Tarih</th><th>Vade</th><th>Hareket</th><?php if ($detail): ?><th>Belge No</th><?php endif; ?><th>Açıklama</th><?php if ($detail): ?><th class="r">Miktar</th><?php endif; ?><th>Ödeme</th><?php if ($detail): ?><th class="r">Fiyat</th><?php endif; ?><th class="r">Borç</th><th class="r">Alacak</th><th class="r">Bakiye</th></tr></thead>
      <tbody>
        <tr class="dv"><td><?= acc_date($from) ?></td><td><?= $detail ? '' : acc_date($from) ?></td><td></td><?php if ($detail): ?><td></td><?php endif; ?><td>Devir bakiye</td><?php if ($detail): ?><td></td><?php endif; ?><td></td><?php if ($detail): ?><td></td><?php endif; ?>
          <td class="r"><?= $bt($st['dBorc']) ?></td><td class="r"><?= $bt($st['dAlacak']) ?></td><td class="r"><?= acc_st_n($st['devir']) ?> TL</td></tr>
        <?php foreach ($st['rows'] as $m): ?>
        <tr>
          <td><?= acc_date($m['d']) ?></td><td><?= acc_date($m['v']) ?></td><td><?= e($m['type']) ?></td>
          <?php if ($detail): ?><td><?= $m['link'] ? '<a href="' . e($m['link']) . '">' . e($m['no']) . '</a>' : e($m['no']) ?></td><?php endif; ?>
          <td><?= !$detail && $m['link'] ? '<a href="' . e($m['link']) . '">' . e($m['desc']) . '</a>' : e($m['desc']) ?></td>
          <?php if ($detail): ?><td></td><?php endif; ?>
          <td><?= e($m['pay']) ?></td><?php if ($detail): ?><td></td><?php endif; ?>
          <td class="r"><?= $bt($m['borc']) ?></td><td class="r"><?= $bt($m['alacak']) ?></td><td class="r"><?= acc_st_n($m['bal']) ?> TL</td>
        </tr>
          <?php foreach ($m['lines'] as $l): ?>
          <tr class="ln"><td></td><td></td><td></td><td></td><td><?= e($l['name']) ?></td><td class="r"><?= e(acc_st_qty($l['qty'])) ?></td><td></td><td class="r"><?= acc_st_n($l['price']) ?></td><td class="r"><?= acc_st_n($l['total']) ?></td><td></td><td></td></tr>
          <?php endforeach; ?>
        <?php endforeach; ?>
        <?php if (!$st['rows']): ?><tr><td colspan="<?= $cols ?>" class="st-empty">Seçtiğiniz tarih aralığında hesap hareketi bulunmuyor.</td></tr><?php endif; ?>
      </tbody>
      <tfoot><tr><td colspan="<?= $cols - 3 ?>"></td><td class="r"><?= acc_st_n($st['debit']) ?></td><td class="r"><?= acc_st_n($st['credit']) ?></td><td class="r"><?= acc_st_n($st['bal']) ?> TL</td></tr></tfoot>
    </table>
    </div>
  </div>
</div>
<script>
(function(){
  [].forEach.call(document.querySelectorAll('.ek-dd > button'),function(b){b.addEventListener('click',function(e){e.stopPropagation();b.parentNode.classList.toggle('open')})});
  document.addEventListener('click',function(){[].forEach.call(document.querySelectorAll('.ek-dd'),function(d){d.classList.remove('open')})});
  [].forEach.call(document.querySelectorAll('[data-soon]'),function(a){a.addEventListener('click',function(e){e.preventDefault();alert(a.dataset.soon)})});
})();
</script>
<?php admin_view('layout_bottom'); ?>
