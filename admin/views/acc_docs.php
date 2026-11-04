<?php
$isSale = $kind === 'sale';
$title = $isSale ? 'Satışlar' : 'Alışlar';
$qs = $_GET; unset($qs['page']);
$pages = max(1, (int)ceil($total / $per));
$stLabel = ['final' => 'Kesinleşmiş', 'proforma' => 'Proforma / Sipariş', 'cancelled' => 'İptal'];
admin_view('layout_top', compact('title'));
admin_view('page_head', ['title' => $title, 'crumbs' => [['Ön Muhasebe', aurl('acc')], [$title, null]],
  'actions' => '<a href="' . aurl("acc/doc/$kind/new") . '" class="abtn-primary">' . icon('plus', 'h-4 w-4') . ($isSale ? ' Satış Gir' : ' Alış Gir') . '</a>']);
?>
<div class="acard">
  <form class="flex flex-wrap items-end gap-3 border-b border-line p-4">
    <label class="w-64"><span class="alabel">Ara (cari, belge no, not)</span><input name="q" value="<?= e($_GET['q'] ?? '') ?>" class="ainput"></label>
    <label class="w-40"><span class="alabel">Başlangıç</span><input type="date" name="from" value="<?= e($_GET['from'] ?? '') ?>" class="ainput"></label>
    <label class="w-40"><span class="alabel">Bitiş</span><input type="date" name="to" value="<?= e($_GET['to'] ?? '') ?>" class="ainput"></label>
    <label class="w-40"><span class="alabel">Durum</span>
      <select name="status" class="ainput"><option value="">Tümü</option><?php foreach ($stLabel as $k => $v): ?><option value="<?= $k ?>" <?= ($_GET['status'] ?? '') === $k ? 'selected' : '' ?>><?= $v ?></option><?php endforeach; ?></select></label>
    <button class="abtn"><?= icon('filter', 'h-4 w-4') ?> Filtrele</button>
    <div class="ml-auto text-xs text-muted">Toplam <b><?= $total ?></b> belge · Kesinleşmiş tutar <b><?= acc_money($sum) ?></b></div>
  </form>
  <div class="overflow-x-auto">
    <table class="atable">
      <thead><tr><th>Tarih</th><th><?= $isSale ? 'Müşteri' : 'Tedarikçi' ?></th><th>Belge No</th><th>Vade</th><th>Tutar</th><th>Ödenen (TL)</th><th>Durum</th><th></th></tr></thead>
      <tbody>
      <?php foreach ($rows as $r): ?>
        <tr>
          <td><?= acc_date($r['doc_date']) ?></td>
          <td><?= e($r['party_name'] ?: 'Perakende') ?><?php if ($r['currency'] !== 'TRY'): ?><div class="text-xs text-muted">1 <?= e($r['currency']) ?> = <?= number_format((float)$r['rate'], 4, ',', '.') ?> TL</div><?php endif; ?></td>
          <td><?= e($r['doc_no']) ?></td>
          <td><?= acc_date($r['due_date']) ?></td>
          <td><b><?= acc_money($r['total'], $r['currency']) ?></b></td>
          <td><?= acc_money($r['paid_try']) ?></td>
          <td><?= $r['status'] === 'final' ? '<span class="badge-ok">Kesinleşmiş</span>' : ($r['status'] === 'proforma' ? '<span class="badge-muted">Proforma</span>' : '<span class="badge-muted">İptal</span>') ?></td>
          <td class="text-right"><a href="<?= aurl("acc/doc/$kind/" . $r['id']) ?>" class="aicon" title="Görüntüle"><?= icon('eye', 'h-4 w-4') ?></a></td>
        </tr>
      <?php endforeach; ?>
      <?php if (!$rows): ?><tr><td colspan="8" class="py-12 text-center text-muted">Belge yok.</td></tr><?php endif; ?>
      </tbody>
    </table>
  </div>
  <?php if ($pages > 1): ?>
    <div class="flex justify-end gap-1 border-t border-line p-3">
      <?php for ($i = 1; $i <= $pages; $i++): ?><a href="?<?= e(http_build_query($qs + ['page' => $i])) ?>" class="apage <?= $i === $page ? 'is-active' : '' ?>"><?= $i ?></a><?php endfor; ?>
    </div>
  <?php endif; ?>
</div>
<?php admin_view('layout_bottom'); ?>
