<?php /* $t: cart_totals() */ ?>
<div class="space-y-2 text-sm">
  <div class="flex justify-between"><span><?= L('Ara Toplam', 'Subtotal') ?> (<?= (int)$t['count'] ?> <?= L('ürün', 'items') ?>)<?= !empty($t['ex_vat']) ? ' <span class="text-xs text-muted">' . L('KDV hariç', 'excl. VAT') . '</span>' : '' ?></span><b><?= money($t['subtotal']) ?></b></div>
  <?php foreach ($t['lines'] as [$l, $d]): ?><div class="flex justify-between text-ok"><span><?= e($l) ?></span><b>-<?= money($d) ?></b></div><?php endforeach; ?>
  <?php foreach ($t['vat_lines'] ?? [] as $r => $v): ?><div class="flex justify-between"><span><?= L('KDV', 'VAT') ?> (%<?= (int)$r ?>)</span><b><?= money($v) ?></b></div><?php endforeach; ?>
  <div class="flex justify-between"><span><?= L('Kargo', 'Shipping') ?></span><b><?= $t['shipping'] > 0 ? money($t['shipping']) : '<span class="text-ok">' . L('Ücretsiz', 'Free') . '</span>' ?></b></div>
  <?php if ($t['cod_fee'] > 0): ?><div class="flex justify-between"><span><?= L('Kapıda ödeme bedeli', 'COD fee') ?></span><b><?= money($t['cod_fee']) ?></b></div><?php endif; ?>
  <div class="flex items-end justify-between border-t border-line pt-3"><span class="font-bold"><?= L('Toplam', 'Total') ?></span><span class="text-2xl font-black text-brand"><?= money($t['total']) ?></span></div>
  <div class="text-right text-xs text-muted"><?= L('KDV dahil', 'VAT included') ?></div>
</div>
