<?php
$isProp = $kind === 'proposal';
$isSale = $kind !== 'purchase';
?>
<?php if ($isProp): ?>
<?php
$title = 'Teklif ' . $doc['doc_no'];
admin_view('layout_top', compact('title'));
$id = (int)$doc['id'];
$party = $doc['party_id'] ? acc_q('SELECT * FROM acc_parties WHERE id = ?', [$doc['party_id']])->fetch() : null;
$cur = $doc['currency'];
$tpls = acct_for_kind('proposal');
$stLabel = ['offer' => ['Açık Teklif', 'ok'], 'converted' => ['Satışa Çevrildi', 'muted'], 'cancelled' => ['İptal Edildi', 'muted']][$doc['status']] ?? [$doc['status'], 'muted'];
$qtyFmt = function ($q) { return rtrim(rtrim(number_format((float)$q, 3, ',', '.'), '0'), ','); };
$totQty = 0.0; $gross = 0.0; $lineDisc = 0.0;
foreach ($lines as $l) { $totQty += (float)$l['qty']; $gross += round($l['qty'] * $l['unit_price'], 2); $lineDisc += (float)$l['discount']; }
$discAll = $lineDisc + (float)$doc['discount_total'];
$vatPct = []; foreach ($lines as $l) $vatPct[(float)$l['vat_rate']] = 1;
$vatLabel = $vatPct ? implode('/', array_map(function ($v) { return '%' . $v; }, array_keys($vatPct))) : '';
$open = $doc['status'] === 'offer';
$shareMsg = 'Merhaba, ' . $doc['doc_no'] . ' numaralı teklifimiz: ' . acc_money($doc['total'], $cur) . ' (KDV dahil). Geçerlilik: ' . acc_date($doc['due_date']) . '.';
$mail = $party['email'] ?? ''; $tel = preg_replace('/[^0-9+]/', '', (string)($party['phone'] ?? ''));
$pu = function ($tid) use ($id) { return aurl("acc/doc/proposal/$id/print") . '?tpl=' . (int)$tid; };
?>
<style>
.tp-bar{display:flex;flex-wrap:wrap;gap:8px;align-items:center;margin-bottom:16px}
.tp-bar form{display:inline}
.tp-dd{position:relative}.tp-dd>summary{list-style:none;cursor:pointer}.tp-dd>summary::-webkit-details-marker{display:none}
.tp-dd .tp-menu{position:absolute;z-index:30;top:calc(100% + 4px);left:0;min-width:230px;background:#fff;border:1px solid #e2e8f0;border-radius:10px;box-shadow:0 10px 30px rgba(15,23,42,.15);padding:6px}
.tp-menu a,.tp-menu button{display:block;width:100%;text-align:left;padding:8px 10px;border-radius:7px;font-size:13px;color:#1e293b;background:none;border:0;cursor:pointer}
.tp-menu a:hover,.tp-menu button:hover{background:#f1f5f9}
.tp-menu .tp-h{font-size:11px;font-weight:800;text-transform:uppercase;letter-spacing:.04em;color:#64748b;padding:8px 10px 2px}
.tp-menu .tp-s{border-top:1px solid #e2e8f0;margin-top:4px;padding-top:4px;font-size:12px;color:#2563eb}
.tp-grid{display:grid;gap:16px;grid-template-columns:minmax(0,5fr) minmax(0,7fr)}
@media(max-width:900px){.tp-grid{grid-template-columns:1fr}}
.tp-card{background:#fff;border:1px solid #e2e8f0;border-radius:12px;overflow:hidden}
.tp-hd{padding:12px 16px;color:#fff;font-weight:800;font-size:14px;display:flex;justify-content:space-between;gap:8px;align-items:center}
.tp-hd.bl{background:#2b8fd6}.tp-hd.gr{background:#3aa655}
.tp-hd a{color:#fff;text-decoration:underline}
.tp-row{display:flex;justify-content:space-between;gap:12px;padding:10px 16px;border-bottom:1px solid #eef2f6;font-size:14px}
.tp-row span:first-child{color:#64748b}.tp-row b{text-align:right}
.tp-foot{padding:10px 16px;font-size:12px;color:#64748b}
.tp-card table{width:100%;border-collapse:collapse;font-size:13px}
.tp-card th{background:#f8fafc;text-align:right;padding:9px 12px;font-size:12px;color:#475569;border-bottom:1px solid #e2e8f0}
.tp-card th:first-child,.tp-card td:first-child{text-align:left}
.tp-card td{text-align:right;padding:9px 12px;border-bottom:1px solid #eef2f6}
.tp-card td small{display:block;color:#64748b}
.tp-tot{margin-left:auto;max-width:360px;padding:12px 16px;font-size:14px}
.tp-tot div{display:flex;justify-content:space-between;padding:4px 0}
.tp-tot .big{border-top:2px solid #e2e8f0;margin-top:6px;padding-top:8px;font-size:16px;font-weight:800}
.tp-bar .abtn,.tp-bar .abtn-primary{color:#fff!important;border:0!important;font-weight:700;box-shadow:0 2px 6px rgba(15,23,42,.15);transition:filter .15s,transform .15s}
.tp-bar .abtn:hover,.tp-bar .abtn-primary:hover{filter:brightness(1.08);transform:translateY(-1px)}
.tp-bar .c-print{background:linear-gradient(135deg,#3b82f6,#1d4ed8)!important}
.tp-bar .c-edit{background:linear-gradient(135deg,#fbbf24,#d97706)!important}
.tp-bar .c-cancel{background:linear-gradient(135deg,#f87171,#dc2626)!important}
.tp-bar .c-conv{background:linear-gradient(135deg,#34d399,#059669)!important}
.tp-bar .c-share{background:linear-gradient(135deg,#a78bfa,#7c3aed)!important}
.tp-bar .c-copy{background:linear-gradient(135deg,#2dd4bf,#0d9488)!important}
.tp-bar .c-cust{background:linear-gradient(135deg,#64748b,#334155)!important}
.tp-bar svg{stroke:#fff}
.tp-card:first-child{background:#f0f8ff;border-color:#bfdcf5}
.tp-card:first-child .tp-row{border-bottom-color:#d6e9f8}
.tp-card:last-child{background:#f3fbf5;border-color:#bfe3c8}
.tp-card:last-child th{background:#e1f4e6;color:#276b3a}
.tp-card:last-child td{border-bottom-color:#d6eddc}
.tp-card:last-child tbody tr:nth-child(even){background:#eaf7ee}
.tp-tot .big{color:#15803d;border-top-color:#9bd3ab}
.tp-grid{max-width:100%}.tp-card{min-width:0}
</style>
<?php admin_view('page_head', ['title' => $title, 'crumbs' => [['Ön Muhasebe', aurl('acc')], ['Teklifler', aurl('acc/docs/proposal')], [$doc['doc_no'], null]], 'actions' => '']); ?>
<div class="tp-bar">
  <details class="tp-dd">
    <summary class="abtn c-print"><?= icon('file-text', 'h-4 w-4') ?> Yazdır ▾</summary>
    <div class="tp-menu">
      <div class="tp-h">Özel Şablonlar</div>
      <?php foreach ($tpls as $t): ?><a target="_blank" href="<?= e($pu($t['id'])) ?>"><?= e($t['name']) ?></a><?php endforeach; ?>
      <?php if (!$tpls): ?><a target="_blank" href="<?= e($pu(0)) ?>">Teklif Formu</a><?php endif; ?>
      <div class="tp-h">Diğer</div>
      <a target="_blank" href="<?= e(aurl("acc/doc/proposal/$id/packing")) ?>">📦 Çeki Listesi Oluştur</a>
      <button type="button" onclick="window.print()">Bu sayfayı yazdır</button>
      <a class="tp-s" href="<?= e(aurl('acc/templates')) ?>">Şablon ayarları için tıklayın</a>
    </div>
  </details>
  <?php if ($doc['status'] !== 'cancelled'): ?><a class="abtn c-edit" href="<?= e(aurl("acc/doc/proposal/$id/edit")) ?>"><?= icon('pencil', 'h-4 w-4') ?> Düzenle</a><?php endif; ?>
  <?php if ($open): ?><form method="post" action="<?= e(aurl("acc/doc/proposal/$id/cancel")) ?>" data-confirm="Teklif iptal edilsin mi?"><?= csrf_field() ?><button class="abtn c-cancel">İptal Et</button></form><?php endif; ?>
  <?php if ($open): ?><form method="post" action="<?= e(aurl("acc/doc/proposal/$id/convert")) ?>" data-confirm="Teklif satışa (proforma / sipariş) çevrilsin mi?"><?= csrf_field() ?><button class="abtn-primary c-conv">Siparişe Çevir</button></form><?php endif; ?>
  <details class="tp-dd">
    <summary class="abtn c-share">Paylaş ▾</summary>
    <div class="tp-menu">
      <a href="mailto:<?= e($mail) ?>?subject=<?= rawurlencode('Teklif ' . $doc['doc_no']) ?>&body=<?= rawurlencode($shareMsg) ?>">E-posta gönder</a>
      <a href="sms:<?= e($tel) ?>?body=<?= rawurlencode($shareMsg) ?>">SMS gönder</a>
      <a target="_blank" href="https://wa.me/<?= e(ltrim($tel, '+')) ?>?text=<?= rawurlencode($shareMsg) ?>">WhatsApp ile gönder</a>
    </div>
  </details>
  <details class="tp-dd">
    <summary class="abtn c-copy">Kopyala ▾</summary>
    <div class="tp-menu">
      <a href="<?= e(aurl('acc/doc/proposal/new') . '?copy=' . $id . '&same=1') ?>">Aynı müşteri için</a>
      <a href="<?= e(aurl('acc/doc/proposal/new') . '?copy=' . $id . '&same=0') ?>">Farklı bir müşteri için</a>
    </div>
  </details>
  <?php if ($party): ?><a class="abtn c-cust" href="<?= e(aurl('acc/party/' . $party['id'])) ?>">Müşteri Sayfası</a><?php endif; ?>
</div>
<div class="tp-grid">
  <div class="tp-card" style="align-self:start">
    <div class="tp-hd bl"><span><?= $party ? e($party['name']) : 'Perakende' ?></span><span class="badge-<?= $stLabel[1] ?>" style="font-size:11px"><?= e($stLabel[0]) ?></span></div>
    <div class="tp-row"><span>Teklif No</span><b><?= e($doc['doc_no']) ?></b></div>
    <div class="tp-row"><span>Teklif Tarihi</span><b><?= acc_date($doc['doc_date']) ?></b></div>
    <div class="tp-row"><span>Durum</span><b><?= e($stLabel[0]) ?></b></div>
    <div class="tp-row"><span>Geçerlilik</span><b><?= acc_date($doc['due_date']) ?></b></div>
    <div class="tp-row"><span>Kur</span><b><?= $cur === 'TRY' ? '1,0000' : '1 ' . e($cur) . ' = ' . number_format((float)$doc['rate'], 4, ',', '.') . ' TL' ?></b></div>
    <?php if ($doc['note']): ?><div class="tp-row"><span>Açıklama</span><b><?= nl2br(e($doc['note'])) ?></b></div><?php endif; ?>
    <?php if ($party && ($party['phone'] || $party['email'])): ?><div class="tp-row"><span>İletişim</span><b><?= e(trim(($party['phone'] ?? '') . ' ' . ($party['email'] ?? ''))) ?></b></div><?php endif; ?>
    <div class="tp-foot">Oluşturulma: <?= e(date('d.m.Y H:i', strtotime($doc['created_at']))) ?></div>
  </div>
  <div class="tp-card">
    <div class="tp-hd gr"><span>ÜRÜN / HİZMETLER</span><span><?= count($lines) ?> kalem</span></div>
    <div style="overflow-x:auto"><table>
      <thead><tr><th>Açıklama</th><th>Miktar</th><th>Fiyat</th><th>Tutar</th><th>İndirim</th><th>Net</th></tr></thead>
      <tbody>
      <?php foreach ($lines as $l): $g = round($l['qty'] * $l['unit_price'], 2); ?>
        <tr><td><?= e($l['name']) ?><?php if ($l['description']): ?><small><?= e($l['description']) ?></small><?php endif; ?></td>
          <td><?= $qtyFmt($l['qty']) ?> <?= e($l['unit']) ?></td><td><?= acc_money($l['unit_price'], $cur) ?></td><td><?= acc_money($g, $cur) ?></td>
          <td><?= (float)$l['discount'] > 0 ? acc_money($l['discount'], $cur) : '–' ?></td><td><b><?= acc_money($g - $l['discount'], $cur) ?></b></td></tr>
      <?php endforeach; ?>
      </tbody>
    </table></div>
    <div class="tp-tot">
      <div><span>Toplam Miktar</span><b><?= $qtyFmt($totQty) ?></b></div>
      <div><span>Brüt Toplam</span><b><?= acc_money($gross, $cur) ?></b></div>
      <div><span>İndirim</span><b><?= acc_money($discAll, $cur) ?></b></div>
      <div><span>Net Toplam</span><b><?= acc_money($doc['subtotal'], $cur) ?></b></div>
      <div><span>KDV <?= e($vatLabel) ?></span><b><?= acc_money($doc['vat_total'], $cur) ?></b></div>
      <div class="big"><span>TOPLAM</span><span><?= acc_money($doc['total'], $cur) ?></span></div>
      <?php if ($cur !== 'TRY'): ?><div style="font-size:12px;color:#64748b"><span>TL karşılığı</span><span><?= acc_money($doc['total'] * $doc['rate']) ?></span></div><?php endif; ?>
    </div>
  </div>
</div>
<?php admin_view('layout_bottom'); return; ?>
<?php endif; ?>
<?php
$title = $isProp ? 'Teklif ' . $doc['doc_no'] : (($isSale ? 'Satış' : 'Alış') . ' Belgesi ' . $doc['doc_no']);
admin_view('layout_top', compact('title'));
$convBtn = $doc['status'] === 'proforma' ? '<form method="post" action="' . aurl("acc/doc/$kind/" . $doc['id'] . '/convert') . '" class="inline" data-confirm="Belge faturaya çevrilsin mi? (Cari bakiyeye işlenir, stok hareketi yapılır)">' . csrf_field() . '<button class="abtn-primary">Faturaya Çevir</button></form> ' : '';
$cancelBtn = in_array($doc['status'], ['final', 'proforma', 'offer'], true) ? '<form method="post" action="' . aurl("acc/doc/$kind/" . $doc['id'] . '/cancel') . '" class="inline" data-confirm="Belge iptal edilsin mi? (Stok hareketi yapıldıysa geri alınır)">' . csrf_field() . '<button class="abtn">İptal Et</button></form>' : '';
$propBtn = ($isProp && $doc['status'] === 'offer') ? '<form method="post" action="' . aurl('acc/doc/proposal/' . $doc['id'] . '/convert') . '" class="inline" data-confirm="Teklif satışa (proforma / sipariş) çevrilsin mi?">' . csrf_field() . '<button class="abtn-primary">Satışa Çevir</button></form> ' : '';
$__tp = acct_for_kind($kind);
$__pu = function ($tid) use ($kind, $doc) { return aurl("acc/doc/$kind/" . $doc['id'] . '/print') . '?tpl=' . (int)$tid; };
$printBtn = '<a class="abtn" target="_blank" id="tplPrint" href="' . e($__pu($__tp[0]['id'] ?? 0)) . '">' . icon('file-text', 'h-4 w-4') . ' Şablonlu Yazdır / PDF</a> ';
if (count($__tp) > 1) {
  $__sel = '<select class="ainput" style="height:40px;width:auto" title="Yazdırma şablonu" onchange="document.getElementById(\'tplPrint\').href=this.value">';
  foreach ($__tp as $__x) $__sel .= '<option value="' . e($__pu($__x['id'])) . '">' . e($__x['name']) . '</option>';
  $printBtn = $__sel . '</select> ' . $printBtn;
}
$printBtn .= '<a class="abtn" target="_blank" href="' . e(aurl("acc/doc/$kind/" . $doc['id'] . '/packing')) . '">Çeki Listesi</a> ';
$printBtn .= '<button type="button" class="abtn" onclick="window.print()">Yazdır</button> ';
$editLink = in_array($doc['status'], ['proforma', 'final'], true) ? '<a class="abtn" href="' . aurl("acc/doc/$kind/" . $doc['id'] . '/edit') . '">Düzenle</a> ' : '';
$payLink = ($doc['party_id'] && !$isProp) ? '<a class="abtn-primary" href="' . aurl('r/' . ($isSale ? 'acc_collections' : 'acc_payouts') . '/new?party_id=' . $doc['party_id']) . '">' . ($isSale ? 'Tahsilat Ekle' : 'Ödeme Ekle') . '</a>' : '';
admin_view('page_head', ['title' => $title, 'crumbs' => [['Ön Muhasebe', aurl('acc')], [$isProp ? 'Teklifler' : ($isSale ? 'Satışlar' : 'Alışlar'), aurl("acc/docs/$kind")], [$doc['doc_no'], null]], 'actions' => $printBtn . $editLink . $propBtn . $convBtn . $payLink . $cancelBtn]);
$paid = 0; foreach ($pays as $y) $paid += $y['amount'] * ($y['rate'] ?: 1);
?>
<div class="acard p-6">
  <div class="grid gap-4 text-sm md:grid-cols-4">
    <div><div class="alabel"><?= $isSale ? 'Müşteri' : 'Tedarikçi' ?></div><b><?= $doc['party_id'] ? '<a class="text-brand" href="' . aurl('acc/party/' . $doc['party_id']) . '">' . e($doc['party_name']) . '</a>' : 'Perakende' ?></b></div>
    <div><div class="alabel">Tarih</div><b><?= acc_date($doc['doc_date']) ?></b></div>
    <div><div class="alabel"><?= $isProp ? 'Geçerlilik' : 'Vade' ?></div><b><?= acc_date($doc['due_date']) ?></b></div>
    <div><div class="alabel">Durum</div><?= $doc['status'] === 'final' ? '<span class="badge-ok">Kesinleşmiş</span>' : ($doc['status'] === 'proforma' ? '<span class="badge-muted">Proforma / Sipariş</span>' : ($doc['status'] === 'offer' ? '<span class="badge-ok">Açık Teklif</span>' : ($doc['status'] === 'converted' ? '<span class="badge-muted">Satışa Çevrildi</span>' : '<span class="badge-muted">İptal</span>'))) ?></div>
    <?php if ($doc['currency'] !== 'TRY'): ?><div><div class="alabel">Kur</div><b>1 <?= e($doc['currency']) ?> = <?= number_format((float)$doc['rate'], 4, ',', '.') ?> TL</b></div><?php endif; ?>
    <?php if ($doc['note']): ?><div class="md:col-span-4"><div class="alabel">Açıklama</div><?= e($doc['note']) ?></div><?php endif; ?>
  </div>
</div>
<div class="acard mt-4">
  <table class="atable">
    <thead><tr><th>Ürün / Hizmet</th><th>Miktar</th><th>Birim Fiyat</th><th>KDV %</th><th class="text-right">Toplam</th></tr></thead>
    <tbody>
    <?php foreach ($lines as $l): ?>
      <tr><td><?= e($l['name']) ?></td><td><?= rtrim(rtrim(number_format((float)$l['qty'], 3, ',', '.'), '0'), ',') ?></td><td><?= acc_money($l['unit_price'], $doc['currency']) ?></td><td>%<?= (float)$l['vat_rate'] ?></td><td class="text-right"><b><?= acc_money($l['line_total'], $doc['currency']) ?></b></td></tr>
    <?php endforeach; ?>
    </tbody>
  </table>
  <div class="ml-auto max-w-xs space-y-1 p-4 text-sm">
    <?php if (!empty($doc['discount_total']) && (float)$doc['discount_total'] > 0): ?><div class="flex justify-between"><span>Fatura altı iskonto</span><b>-<?= acc_money($doc['discount_total'], $doc['currency']) ?></b></div><?php endif; ?>
    <div class="flex justify-between"><span>Ara Toplam</span><b><?= acc_money($doc['subtotal'], $doc['currency']) ?></b></div>
    <div class="flex justify-between"><span>KDV</span><b><?= acc_money($doc['vat_total'], $doc['currency']) ?></b></div>
    <div class="flex justify-between border-t border-line pt-1 text-base"><span class="font-extrabold">Genel Toplam</span><b><?= acc_money($doc['total'], $doc['currency']) ?></b></div>
    <div class="flex justify-between text-xs text-muted"><span>TL karşılığı</span><span><?= acc_money($doc['total'] * $doc['rate']) ?></span></div>
  </div>
</div>
<?php if (!$isProp): ?>
<div class="acard mt-4 p-6">
  <div class="mb-2 text-sm font-extrabold"><?= $isSale ? 'TAHSİLATLAR' : 'ÖDEMELER' ?> <span class="font-normal text-muted">(bu belgeye bağlı)</span></div>
  <?php foreach ($pays as $y): ?>
    <div class="flex justify-between border-b border-line py-2 text-sm"><span><?= acc_date($y['pay_at']) ?> – <?= e($y['account']) ?></span><b><?= acc_money($y['amount']) ?></b></div>
  <?php endforeach; ?>
  <?php if (!$pays): ?><div class="text-sm text-muted">Bu belgeye bağlı kayıt yok. Cari hesap ekstresinden genel tahsilat/ödemeleri görebilirsiniz.</div><?php endif; ?>
  <div class="mt-3 text-sm">Belgeye bağlı toplam: <b><?= acc_money($paid) ?></b></div>
</div>
<?php endif; ?>
<?php admin_view('layout_bottom'); ?>
