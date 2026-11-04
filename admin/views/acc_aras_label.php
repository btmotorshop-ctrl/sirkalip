<?php
/** Aras "Dummy Etiket" şablonu: parça bazlı = parça başına 1 etiket; sipariş bazlı = tek etiket (entegrasyon kodu barkodu) */
$title = 'Kargo Etiketi';
admin_view('layout_top', compact('title'));
$g = setting('general', []) ?: []; $ac = aras_config();
$sTitle = $g['companyTitle'] ?? ($g['siteName'] ?? 'SIR KALIP');
$back = ($row['source'] === 'order' && !empty($row['ref_id'])) ? aurl('orders/' . (int)$row['ref_id']) : (!empty($row['ref_id']) && $row['source'] === 'party' ? aurl('acc/party/' . $row['ref_id']) : aurl('acc/aras-list'));
$code = (string)$row['integration_code'];
$pieces = max(1, (int)$row['pieces']);
$byPiece = ($d['ship_type'] ?? ($ac['shipType'] ?? 'piece')) !== 'order';
$cod = !empty($d['cod']);
$codAmt = !empty($d['cod_amount_num']) ? (float)$d['cod_amount_num'] : (float)str_replace(',', '.', str_replace('.', '', (string)($d['cod_amount'] ?? '0')));
$dummy = trim((string)($ac['dummyName'] ?? '')) ?: ($sTitle . ($byPiece ? ' Parça Bazlı' : ' Sipariş Bazlı'));
$labels = [];
if ($byPiece) { for ($i = 1; $i <= $pieces; $i++) $labels[] = ['no' => $i, 'barcode' => $code . str_pad((string)$i, 2, '0', STR_PAD_LEFT)]; }
else $labels[] = ['no' => 0, 'barcode' => $code];
admin_view('page_head', ['title' => $title, 'crumbs' => [['Ön Muhasebe', aurl('acc')], ['Kargo Etiketi', null]],
  'actions' => '<button type="button" class="abtn-primary" onclick="window.print()">Yazdır</button> <a class="abtn" href="' . e(aurl('acc/aras-list')) . '">Kargolar</a> <a class="abtn" href="' . e($back) . '">Geri Dön</a>']);
?>
<div class="kg-sheet">
<?php foreach ($labels as $L): ?>
  <div class="kg-label">
    <div class="kg-ltop"><b><?= $cod ? 'TAHSİLATLI KARGO' : 'STANDART KARGO' ?></b><span><?= acc_date(substr((string)$row['created_at'], 0, 10)) ?></span></div>
    <div class="kg-brand"><span>ARAS KARGO</span></div>
    <div class="kg-lg">
      <div><em>Gönderici</em><b><?= e($sTitle) ?></b></div>
      <div><em>Alıcı</em><b><?= e($d['name'] ?? $row['receiver']) ?></b><small><?= e(($d['town'] ?? $row['town']) . ' / ' . ($d['city'] ?? $row['city'])) ?></small></div>
    </div>
    <div class="kg-row"><span><?= $cod ? 'Tahsilatlı Kargo' : 'Standart Kargo' ?></span><?php if ($byPiece): ?><span>Paket : <b><?= $L['no'] ?> / <?= $pieces ?></b></span><?php endif; ?></div>
    <?php if ($cod): ?><div class="kg-row"><span>Nakit Tahsilat Tutarı :</span><b><?= e(acc_money($codAmt)) ?></b></div><?php endif; ?>
    <div class="kg-row"><span>Entegrasyon Kodu :</span><b><?= e($code) ?></b></div>
    <div class="kg-bc"><?php if ($byPiece): ?><div class="kg-bn">Parça Barkod No : <b><?= e($L['barcode']) ?></b></div><?php endif; ?><?= aras_barcode_svg($L['barcode']) ?></div>
    <div class="kg-warn"><b>DİKKAT!</b> Lütfen Entegrasyon ile Sevk İşlemleri ekranında <b><?= e($dummy) ?></b> seçeneğini seçerek işlem yapınız.</div>
  </div>
<?php endforeach; ?>
</div>
<p class="kg-hint">Bu çıktı Aras'ın "dummy etiket" şablonudur: paketin üzerine yapıştırın, şubede/kuryede <?= $byPiece ? 'parça barkod numarası' : 'entegrasyon kodu' ?> okutulunca resmî Aras barkodu oluşur ve takip numarası verilir. Ayrıntılar için <a href="<?= aurl('acc/aras-list') ?>">Kargolar</a> sayfasına bakın.</p>
<?php admin_view('layout_bottom'); ?>
