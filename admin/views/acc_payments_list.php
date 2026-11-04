<?php
/** Carinin tüm ödemeleri (BizimHesap: "… Tüm Ödemeler") */
$title = $p['name'] . ' – Tüm Ödemeler';
admin_view('layout_top', compact('title'));
$isCust = $p['kind'] === 'customer';
$back = aurl('acc/party/' . (int)$p['id']);
?>
<div class="tp-bar"><a class="tp-b gr" href="<?= e($back) ?>"><?= icon('arrow-left', '') ?> Geri Dön</a>
  <a class="tp-b nv" href="<?= aurl('acc/payment?party_id=' . (int)$p['id']) ?>"><?= icon('banknote', '') ?> <?= $isCust ? 'Tahsilat / Ödeme Gir' : 'Ödeme / Tahsilat Gir' ?></a></div>
<div class="pv-panel tp-list">
  <div class="pv-ph"><span><?= e(mb_strtoupper($p['name'], 'UTF-8')) ?> TÜM ÖDEMELER</span></div>
  <div class="pv-pb">
    <?php if (!$rows): ?><div class="pv-empty">Bu cariye ait kayıtlı ödeme yok.</div><?php else: ?>
    <table class="pv-t"><thead><tr><th></th><th>Tarih</th><th class="r">Tutar</th><th>Şekli</th></tr></thead><tbody>
    <?php foreach ($rows as $y): $in = $y['kind'] === 'in'; ?>
      <tr class="pv-r"><td><i class="pv-plus">+</i></td><td><?= e(acc_date($y['pay_at'])) ?></td>
        <td class="r <?= $in ? 'pos' : 'neg' ?>"><?= $in ? '' : '-' ?><?= e(acc_money($y['amount'], $y['currency'] ?: 'TRY')) ?></td><td><?= e(acc_pay_label($y)) ?></td></tr>
      <tr class="pv-x" hidden><td></td><td colspan="3">
        <?= e($y['account'] ?: 'Hesapsız fiş') ?><?= ($y['currency'] && $y['currency'] !== 'TRY') ? ' · kur ' . e(number_format((float)$y['rate'], 4, ',', '.')) . ' = ' . e(acc_money($y['amount'] * $y['rate'])) : '' ?><?= $y['due_date'] ? ' · vade ' . e(acc_date($y['due_date'])) : '' ?><?= $y['note'] ? ' · ' . e($y['note']) : '' ?>
        <form method="post" action="<?= aurl('acc/payment-delete') ?>" class="inline" data-confirm="Bu kayıt silinsin mi? Cari bakiyesi ve hesap bakiyesi buna göre değişir.<?= !empty($y['link_id']) ? ' (Karşı carideki virman kaydı da silinir.)' : '' ?>" style="margin-left:10px">
          <?= csrf_field() ?><input type="hidden" name="id" value="<?= (int)$y['id'] ?>"><input type="hidden" name="back" value="list"><button class="tp-del">Sil</button></form>
      </td></tr>
    <?php endforeach; ?></tbody></table>
    <?php endif; ?>
  </div>
</div>
<script>
[].forEach.call(document.querySelectorAll('.pv-r'),function(r){r.addEventListener('click',function(){var x=r.nextElementSibling;x.hidden=!x.hidden;r.classList.toggle('open',!x.hidden)})});
</script>
<?php admin_view('layout_bottom'); ?>
