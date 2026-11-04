<?php $t = cart_totals(); ?>
<div class="flex items-center justify-between border-b border-line px-5 py-4">
  <div class="text-lg font-extrabold"><?= L('Sepetim', 'My Cart') ?> <span class="text-sm font-semibold text-muted">(<?= (int)$t['count'] ?> <?= L('ürün', 'items') ?>)</span></div>
  <button type="button" data-close-cart class="rounded-full p-2 hover:bg-soft" aria-label="<?= L('Kapat', 'Close') ?>"><?= icon('x', 'h-5 w-5') ?></button>
</div>
<?php if (!$t['items']): ?>
  <div class="flex flex-1 flex-col items-center justify-center p-8 text-center">
    <?= icon('shopping-cart', 'h-14 w-14 text-muted', 1.5) ?>
    <div class="mt-4 text-lg font-bold"><?= L('Sepetiniz boş', 'Your cart is empty') ?></div>
    <p class="mt-1 text-sm text-muted"><?= L('Makinenize uygun parçaları keşfedin.', 'Discover parts for your machine.') ?></p>
    <a href="<?= url('/kategori/balya-makinesi') ?>" class="btn-primary mt-5 h-11 px-6"><?= L('Alışverişe Başla', 'Start Shopping') ?></a>
  </div>
<?php else: ?>
  <?php if ($t['free_limit'] > 0): $pct = min(100, round(($t['free_limit'] - $t['remaining_free']) / $t['free_limit'] * 100)); ?>
    <div class="border-b border-line bg-soft/60 px-5 py-3 text-xs font-semibold">
      <?= $t['remaining_free'] > 0 ? LV('Ücretsiz kargo için <b class="text-brand">{amt}</b> kaldı', '<b class="text-brand">{amt}</b> left for free shipping', ['amt' => money($t['remaining_free'])]) : '<span class="text-ok">' . L('🎉 Kargonuz ücretsiz!', '🎉 Free shipping!') . '</span>' ?>
      <div class="mt-2 h-1.5 overflow-hidden rounded-full bg-line"><div class="h-full rounded-full bg-brand" style="width: <?= $pct ?>%"></div></div>
    </div>
  <?php endif; ?>
  <div class="flex-1 overflow-y-auto px-5">
    <?php foreach ($t['items'] as $it): $p = $it['p']; ?>
      <div class="flex gap-3 border-b border-line py-4" data-mc-item="<?= e($p['code']) ?>">
        <a href="<?= product_url($p) ?>" class="w-20 shrink-0 overflow-hidden rounded-lg border border-line"><?= product_thumb($p) ?></a>
        <div class="min-w-0 flex-1">
          <a href="<?= product_url($p) ?>" class="line-clamp-2 text-sm font-semibold hover:text-brand"><?= e(tr_field($p, 'name')) ?></a>
          <div class="text-xs text-muted"><?= e($p['code']) ?></div>
          <div class="mt-2 flex items-center justify-between gap-2">
            <div class="flex h-8 items-center rounded-md border border-line">
              <button type="button" data-mc-qty="-1" class="flex h-full w-8 items-center justify-center hover:text-brand"><?= icon('minus', 'h-3.5 w-3.5') ?></button>
              <input type="text" inputmode="numeric" pattern="[0-9]*" data-mc-input data-max="<?= (int)$p['stock'] ?>" value="<?= (int)$it['qty'] ?>" aria-label="<?= L('Adet', 'Quantity') ?>" class="h-full w-11 border-x border-line bg-transparent p-0 text-center text-sm font-bold outline-none focus:bg-soft" style="-moz-appearance:textfield">
              <button type="button" data-mc-qty="1" <?= $it['qty'] >= $p['stock'] ? 'disabled' : '' ?> class="flex h-full w-8 items-center justify-center hover:text-brand disabled:opacity-30"><?= icon('plus', 'h-3.5 w-3.5') ?></button>
            </div>
            <div class="text-sm font-extrabold text-brand"><?= money($it['total']) ?><?= vat_tag('text-[10px]') ?></div>
          </div>
        </div>
        <button type="button" data-mc-remove class="self-start text-muted hover:text-brand" aria-label="<?= L('Kaldır', 'Remove') ?>"><?= icon('trash2', 'h-4 w-4') ?></button>
      </div>
    <?php endforeach; ?>
  </div>
  <div class="border-t border-line p-5">
    <?php if (!empty($t['vat'])): ?><div class="flex justify-between text-sm"><span><?= L('Ara Toplam', 'Subtotal') ?> <span class="text-xs text-muted">(<?= L('KDV hariç', 'excl. VAT') ?>)</span></span><b><?= money($t['subtotal']) ?></b></div><?php endif; ?>
    <?php if ($t['discount'] > 0): ?><div class="flex justify-between text-sm text-ok"><span><?= L('İndirim', 'Discount') ?></span><b>-<?= money($t['discount']) ?></b></div><?php endif; ?>
    <?php if (!empty($t['vat'])): ?><div class="flex justify-between text-sm"><span><?= L('KDV', 'VAT') ?></span><b><?= money($t['vat']) ?></b></div><?php endif; ?>
    <div class="flex justify-between text-sm"><span><?= L('Kargo', 'Shipping') ?></span><b><?= $t['shipping'] > 0 ? money($t['shipping']) : '<span class="text-ok">' . L('Ücretsiz', 'Free') . '</span>' ?></b></div>
    <div class="mt-2 flex items-end justify-between"><span class="font-bold"><?= L('Toplam', 'Total') ?></span><span class="text-2xl font-black text-brand"><?= money($t['total']) ?></span></div>
    <div class="mt-4 grid grid-cols-2 gap-2">
      <a href="<?= url('/sepet') ?>" class="btn h-12 border-2 border-ink text-sm hover:bg-ink hover:text-white"><?= L('Sepete Git', 'View Cart') ?></a>
      <a href="<?= url('/odeme') ?>" class="btn-primary h-12 text-sm"><?= L('Satın Al', 'Checkout') ?> <?= icon('arrow-right', 'h-4 w-4') ?></a>
    </div>
  </div>
<?php endif; ?>
