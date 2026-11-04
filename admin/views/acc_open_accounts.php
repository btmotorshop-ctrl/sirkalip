<?php
/** Açık Hesap Raporu – BizimHesap */
$title = 'Açık Hesap Raporu';
admin_view('layout_top', compact('title'));
$sel = function ($a, $b) { return (string)$a === (string)$b ? ' selected' : ''; };
$csv = aurl('acc/open-accounts?' . http_build_query(['type' => $type, 'who' => $who, 'export' => 'csv']));
?>
<div class="st-panel" id="acCrit">
  <div class="st-h"><span>AÇIK HESAP RAPORU KRİTERLERİ</span><button type="button" class="st-tg" data-tg="acCrit" aria-label="Aç/Kapat">⌃</button></div>
  <form method="get" action="<?= aurl('acc/open-accounts') ?>" class="st-b">
    <div class="ac-crit">
      <div class="st-r"><label>Hesap Tipi</label><select name="who" class="df-i"><option value="0"<?= $sel($who, '0') ?>>Hepsi</option><option value="customer"<?= $sel($who, 'customer') ?>>Müşteriler</option><option value="supplier"<?= $sel($who, 'supplier') ?>>Tedarikçiler</option></select></div>
      <div class="st-r"><label>Borç Tipi</label><select name="type" class="df-i"><option value="B"<?= $sel($type, 'B') ?>>Borçlular</option><option value="A"<?= $sel($type, 'A') ?>>Alacaklılar</option></select></div>
    </div>
    <div class="ac-act"><button class="st-go" type="submit"><?= icon('upload', '') ?> Raporu Hazırla</button></div>
  </form>
</div>

<div class="st-panel" id="acRes">
  <div class="st-h"><span>Rapor Sonucu</span><button type="button" class="st-tg" data-tg="acRes" aria-label="Aç/Kapat">⌃</button></div>
  <div class="st-b">
    <div class="st-tools"><a class="st-x gr" href="<?= e($csv) ?>"><?= icon('file-spreadsheet', '') ?> Excel</a><button type="button" class="st-x or" onclick="window.print()"><?= icon('file-text', '') ?> PDF</button></div>
    <div class="st-wrap">
    <table class="st-t ac-t">
      <thead><tr><th>İsim / Unvan</th><th class="r">Açık Bakiyesi</th><th>Telefon</th></tr></thead>
      <tbody>
      <?php foreach ($rows as $r): ?>
        <tr><td><a href="<?= aurl('acc/party/' . (int)$r['id']) ?>"><?= e($r['name']) ?></a><?= $who === '0' ? '<small class="ac-k">' . ($r['kind'] === 'customer' ? 'Müşteri' : 'Tedarikçi') . '</small>' : '' ?></td>
          <td class="r"><?= e(number_format($r['sbal'], 2, ',', '.')) ?> TL</td><td><?= e($r['phone']) ?></td></tr>
      <?php endforeach; ?>
      <?php if (!$rows): ?><tr><td colspan="3" class="st-empty"><?= $type === 'B' ? 'Borçlu cari bulunmuyor.' : 'Alacaklı cari bulunmuyor.' ?></td></tr><?php endif; ?>
      </tbody>
      <?php if ($rows): ?><tfoot><tr><td></td><td class="r"><?= e(number_format($total, 2, ',', '.')) ?> TL</td><td></td></tr></tfoot><?php endif; ?>
    </table>
    </div>
  </div>
</div>
<script>
[].forEach.call(document.querySelectorAll('.st-tg'),function(b){b.addEventListener('click',function(){document.getElementById(b.dataset.tg).classList.toggle('shut')})});
</script>
<?php admin_view('layout_bottom'); ?>
