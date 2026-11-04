<?php [$price, $old] = product_prices($p); $favs = $favs ?? fav_ids(); $isFav = in_array((int)$p['id'], $favs, true); $u = product_url($p); ?>
<div class="group flex w-full flex-col rounded-lg border border-line bg-white p-3 transition hover:border-slate-300 hover:shadow-lg">
  <div class="relative">
    <a href="<?= $u ?>" class="block overflow-hidden rounded-md"><?= product_thumb($p, 'transition duration-300 group-hover:scale-[1.04]') ?></a>
    <button type="button" data-fav="<?= (int)$p['id'] ?>" class="absolute right-1 top-1 rounded-full bg-white/95 p-1.5 shadow-sm" aria-label="<?= L('Favorilere ekle', 'Add to favorites') ?>"><?= icon('heart', 'h-4 w-4 ' . ($isFav ? 'is-fav' : 'text-ink')) ?></button>
    <div class="absolute left-1 top-1 flex flex-col gap-1">
      <?php if ($old): ?><span class="rounded bg-brand px-1.5 py-0.5 text-[11px] font-bold text-white">%<?= round((1 - $price / $old) * 100) ?></span><?php endif; ?>
      <?php if (!empty($p['is_new'])): ?><span class="rounded bg-ink px-1.5 py-0.5 text-[11px] font-bold text-white"><?= L('YENİ', 'NEW') ?></span><?php endif; ?>
    </div>
  </div>
  <div class="mt-3 text-xs font-semibold text-muted"><?= e($p['code']) ?></div>
  <a href="<?= $u ?>" class="line-clamp-2 min-h-[2.5rem] text-sm font-semibold hover:text-brand"><?= e(tr_field($p, 'name')) ?></a>
  <div class="mt-1 flex flex-wrap items-baseline gap-x-2">
    <span class="whitespace-nowrap text-lg font-extrabold text-brand"><?= money($price) ?></span><?= vat_tag() ?>
    <?php if ($old): ?><span class="text-xs text-muted line-through"><?= money($old) ?></span><?php endif; ?>
  </div>
  <?= price_fx($price) ?>
  <div class="mt-0.5 text-xs font-semibold <?= $p['stock'] > 0 ? 'text-ok' : 'text-muted' ?>">● <?= $p['stock'] > 0 ? e(t('product.inStock', ['count' => $p['stock']])) : e(t('product.outOfStock')) ?></div>
  <div class="mt-auto pt-3">
  <?php if ($p['stock'] > 0): ?>
    <button type="button" data-add-cart="<?= e($p['code']) ?>" class="btn-primary h-10 w-full text-sm"><?= icon('shopping-cart', 'h-4 w-4') ?><?= e(t('product.addToCart')) ?></button>
  <?php else: ?>
    <a href="<?= $u ?>#stok-alarm" class="btn h-10 w-full border border-line text-sm hover:border-brand hover:text-brand"><?= icon('bell', 'h-4 w-4') ?><?= L('Gelince Haber Ver', 'Notify Me') ?></a>
  <?php endif; ?>
  </div>
</div>
