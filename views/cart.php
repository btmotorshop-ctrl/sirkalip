<?= breadcrumb([[L('Sepetim', 'My Cart'), null]]) ?>
<section class="container-x">
  <h1 class="mb-6 text-2xl font-extrabold md:text-3xl"><?= L('Sepetim', 'My Cart') ?> <?php if ($t['count']): ?><span class="text-base font-semibold text-muted">(<?= (int)$t['count'] ?> <?= L('ürün', 'items') ?>)</span><?php endif; ?></h1>
  <?php if (!$t['items']): ?>
    <div class="rounded-xl border border-dashed border-line p-12 text-center">
      <?= icon('shopping-cart', 'mx-auto h-12 w-12 text-muted') ?>
      <h2 class="mt-4 text-xl font-bold"><?= L('Sepetiniz boş', 'Your cart is empty') ?></h2>
      <p class="mt-1 text-sm text-muted"><?= L('Makinenize uygun parçaları bulmak için kategorilere göz atın.', 'Browse categories to find parts for your machine.') ?></p>
      <div class="mt-6 flex flex-wrap justify-center gap-3"><a href="<?= url('/kategori/balya-makinesi') ?>" class="btn-primary h-11 px-6"><?= L('Alışverişe Başla', 'Start Shopping') ?></a><a href="<?= url('/parca-bul') ?>" class="btn h-11 border border-line px-6"><?= L('Makineme Göre Parça Bul', 'Find by Machine') ?></a></div>
    </div>
  <?php else: ?>
  <div class="grid grid-cols-1 gap-6 lg:grid-cols-[minmax(0,1fr)_360px]">
    <div>
      <?php if ($t['free_limit'] > 0): $pct = min(100, round(($t['free_limit'] - $t['remaining_free']) / $t['free_limit'] * 100)); ?>
        <div class="mb-4 rounded-xl border border-line p-4">
          <div class="flex items-center gap-2 text-sm font-semibold"><?= icon('truck', 'h-5 w-5 text-brand') ?>
            <?= $t['remaining_free'] > 0 ? LV('Ücretsiz kargo için <b class="text-brand">{amt}</b> daha ekleyin', 'Add <b class="text-brand">{amt}</b> more for free shipping', ['amt' => money($t['remaining_free'])]) : '<span class="text-ok">' . L('Tebrikler! Kargonuz ücretsiz.', 'Congrats! Free shipping.') . '</span>' ?></div>
          <div class="mt-2 h-2 overflow-hidden rounded-full bg-soft"><div class="h-full rounded-full bg-brand transition-all" style="width: <?= $pct ?>%"></div></div>
        </div>
      <?php endif; ?>
      <form method="post" action="<?= url('/sepet/guncelle') ?>" data-cart-form class="overflow-hidden rounded-xl border border-line">
        <?= csrf_input() ?>
        <?php foreach ($t['items'] as $it): $p = $it['p']; [$pr, $old] = product_prices($p); ?>
          <div class="flex gap-4 border-b border-line p-4 last:border-b-0">
            <a href="<?= product_url($p) ?>" class="w-20 shrink-0 sm:w-24"><?= product_thumb($p) ?></a>
            <div class="flex min-w-0 flex-1 flex-col gap-2 sm:flex-row sm:items-center">
              <div class="min-w-0 flex-1">
                <div class="text-xs font-semibold text-muted"><?= e($p['code']) ?></div>
                <a href="<?= product_url($p) ?>" class="font-semibold hover:text-brand"><?= e(tr_field($p, 'name')) ?></a>
                <div class="mt-0.5 text-sm"><b class="text-brand"><?= money($pr) ?></b><?= vat_tag() ?><?php if ($old): ?> <span class="text-xs text-muted line-through"><?= money($old) ?></span><?php endif; ?></div>
                <?php if ($it['qty'] >= $p['stock']): ?><div class="text-xs text-amber-600"><?= L('Stoktaki son ürünler', 'Last items in stock') ?></div><?php endif; ?>
              </div>
              <div class="flex items-center gap-4">
                <div class="flex h-10 items-center rounded-md border border-line">
                  <button type="button" data-cart-qty="-1" class="flex h-full w-9 items-center justify-center hover:text-brand"><?= icon('minus', 'h-4 w-4') ?></button>
                  <input name="qty[<?= e($p['code']) ?>]" value="<?= (int)$it['qty'] ?>" data-max="<?= (int)$p['stock'] ?>" inputmode="numeric" class="h-full w-11 border-x border-line text-center text-sm font-bold outline-none">
                  <button type="button" data-cart-qty="1" class="flex h-full w-9 items-center justify-center hover:text-brand"><?= icon('plus', 'h-4 w-4') ?></button>
                </div>
                <div class="w-28 text-right text-base font-extrabold"><?= money($it['total']) ?><?= vat_tag('block text-[10px]') ?></div>
                <button type="submit" name="qty[<?= e($p['code']) ?>]" value="0" class="text-muted hover:text-brand" aria-label="<?= L('Kaldır', 'Remove') ?>" title="<?= L('Kaldır', 'Remove') ?>"><?= icon('trash2', 'h-5 w-5') ?></button>
              </div>
            </div>
          </div>
        <?php endforeach; ?>
        <noscript><div class="p-4"><button class="btn border border-line px-4 py-2"><?= L('Sepeti Güncelle', 'Update Cart') ?></button></div></noscript>
      </form>
      <a href="<?= url('/kategori/balya-makinesi') ?>" class="mt-4 inline-flex items-center gap-1 text-sm font-semibold text-muted hover:text-brand"><?= icon('chevron-left', 'h-4 w-4') ?> <?= L('Alışverişe devam et', 'Continue shopping') ?></a>
    </div>
    <aside class="lg:sticky lg:top-40 lg:self-start">
      <div class="rounded-xl border border-line p-5">
        <h2 class="mb-4 text-lg font-extrabold"><?= L('Sipariş Özeti', 'Order Summary') ?></h2>
        <?php view('partials/summary', ['t' => $t]); ?>
        <a href="<?= url('/odeme') ?>" class="btn-primary mt-5 h-12 w-full text-base"><?= L('Alışverişi Tamamla', 'Checkout') ?> <?= icon('arrow-right', 'h-5 w-5') ?></a>
        <div class="mt-5 border-t border-line pt-4">
          <?php if (!empty($_SESSION['coupon']) && $t['coupon']): ?>
            <form method="post" action="<?= url('/sepet/kupon-kaldir') ?>" class="flex items-center justify-between rounded-md bg-green-50 px-3 py-2 text-sm"><?= csrf_input() ?><span><?= icon('check', 'mr-1 inline h-4 w-4 text-ok') ?><b><?= e($t['coupon']['code']) ?></b> <?= !empty($t['coupon']['_min_not_met']) ? '<span class="text-xs text-amber-600">(' . L('min. tutar: ', 'min: ') . money($t['coupon']['min_cart_total']) . ')</span>' : '' ?></span><button class="text-xs font-semibold text-brand"><?= L('Kaldır', 'Remove') ?></button></form>
          <?php else: ?>
            <form method="post" action="<?= url('/sepet/kupon') ?>" class="flex gap-2"><?= csrf_input() ?><input name="coupon" placeholder="<?= L('İndirim kuponu', 'Coupon code') ?>" class="h-10 flex-1 rounded-md border border-line px-3 text-sm uppercase"><button class="btn h-10 border border-line px-4 text-sm hover:border-brand"><?= L('Uygula', 'Apply') ?></button></form>
          <?php endif; ?>
        </div>
        <div class="mt-5 flex items-center justify-center gap-4 text-xs text-muted"><span class="flex items-center gap-1"><?= icon('lock', 'h-4 w-4') ?> <?= L('Güvenli ödeme', 'Secure payment') ?></span><span class="flex items-center gap-1"><?= icon('rotate-ccw', 'h-4 w-4') ?> <?= L('14 gün iade', '14-day returns') ?></span></div>
      </div>
    </aside>
  </div>
  <?php endif; ?>
</section>
