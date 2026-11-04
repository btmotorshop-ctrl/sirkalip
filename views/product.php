<?php
[$price, $old] = product_prices($p);
$name = tr_field($p, 'name');
$catRow = ['name_tr' => $p['category_name_tr']]; foreach (extra_locales() as $__l) $catRow['name_' . $__l] = $p['category_name_' . $__l] ?? '';
$catName = tr_field($catRow, 'name');
$isFav = in_array((int)$p['id'], fav_ids(), true);
$refLabels = ['asem' => L('ASEM Kodu', 'ASEM Code'), 'original' => L('Orijinal Kod', 'OEM Code'), 'manufacturer' => L('Üretici Kodu', 'Manufacturer Code'), 'equivalent' => L('Muadil Kod', 'Equivalent Code')];
$media = $images ?: [];
$shipInfo = setting('shipping', []);
$compatBrands = array_keys($compat);
$waText = LV('Merhaba, {code} kodlu "{name}" ürünü hakkında bilgi almak istiyorum. ', 'Hello, I would like information about "{name}" ({code}). ', ['code' => $p['code'], 'name' => $name]) . product_url($p);
$tabList = [
  'desc' => L('Ürün Açıklaması', 'Description'),
  'specs' => L('Teknik Bilgiler', 'Specifications'),
  'compat' => L('Uyumluluk', 'Compatibility'),
  'refs' => L('Referans Numaraları', 'Reference Numbers'),
];
foreach ($tabs as $tb) $tabList['t' . $tb['id']] = tr_field($tb, 'title');
if (($ps['showReviews'] ?? true) !== false) $tabList['reviews'] = L('Yorumlar', 'Reviews') . ' (' . count($reviews) . ')';
if (($ps['showQuestions'] ?? true) !== false) $tabList['qa'] = L('Soru & Cevap', 'Q&A') . ' (' . count($questions) . ')';
?>
<?= breadcrumb([[L('Balya Makinesi Yedek Parçaları', 'Baler Spare Parts'), url('/kategori/balya-makinesi')], [$catName, url('/kategori/' . $p['category_slug'])], [$name, null]]) ?>

<section class="container-x">
  <div class="grid grid-cols-1 gap-8 lg:grid-cols-[minmax(0,1fr)_minmax(0,1fr)_300px]">
    <!-- Galeri -->
    <div data-gallery class="lg:sticky lg:top-40 lg:self-start">
      <div class="flex gap-3">
        <?php if (count($media) > 1): ?>
          <div class="hidden w-20 shrink-0 flex-col gap-2 sm:flex">
            <?php foreach ($media as $i => $m): ?>
              <button type="button" data-thumb="<?= $i ?>" class="aspect-square overflow-hidden rounded-md border-2 <?= $i ? 'border-line' : 'border-brand' ?> bg-white">
                <?php if ($m['kind'] === 'video'): ?><span class="flex h-full items-center justify-center bg-ink text-white"><?= icon('eye', 'h-5 w-5') ?></span><?php else: ?><img src="<?= e(media_url($m['url'])) ?>" alt="" class="h-full w-full object-contain"><?php endif; ?>
              </button>
            <?php endforeach; ?>
          </div>
        <?php endif; ?>
        <div class="relative flex-1 overflow-hidden rounded-xl border border-line bg-white">
          <?php if ($media): foreach ($media as $i => $m): ?>
            <div data-slide="<?= $i ?>" class="<?= $i ? 'hidden' : '' ?>">
              <?php if ($m['kind'] === 'video'): ?><video src="<?= e(media_url($m['url'])) ?>" controls class="aspect-square w-full bg-black"></video>
              <?php else: ?><div data-zoom class="aspect-square cursor-zoom-in overflow-hidden"><img src="<?= e(media_url($m['url'])) ?>" alt="<?= e($name) ?>" class="h-full w-full object-contain transition-transform duration-200"></div><?php endif; ?>
            </div>
          <?php endforeach; else: ?>
            <?= part_art($p['icon'] ?? 'other', 'rounded-none') ?>
          <?php endif; ?>
          <?php if ($old): ?><span class="absolute left-3 top-3 rounded-md bg-brand px-2 py-1 text-sm font-bold text-white">%<?= round((1 - $price / $old) * 100) ?> <?= L('İndirim', 'Off') ?></span><?php endif; ?>
          <button type="button" data-fav="<?= (int)$p['id'] ?>" class="absolute right-3 top-3 rounded-full bg-white p-2.5 shadow" aria-label="Favori"><?= icon('heart', 'h-5 w-5 ' . ($isFav ? 'is-fav' : '')) ?></button>
        </div>
      </div>
      <?php if (count($media) > 1): ?><div class="mt-3 flex justify-center gap-1.5 sm:hidden"><?php foreach ($media as $i => $m) echo '<button type="button" data-thumb="' . $i . '" class="h-2 w-2 rounded-full bg-line"></button>'; ?></div><?php endif; ?>
    </div>

    <!-- Bilgi -->
    <div>
      <?php if ($p['brand_name']): ?><a href="<?= url('/marka/' . $p['brand_slug']) ?>" class="text-sm font-bold tracking-wide text-brand"><?= e($p['brand_name']) ?></a><?php endif; ?>
      <h1 class="mt-1 text-2xl font-extrabold leading-tight md:text-3xl"><?= e($name) ?></h1>
      <div class="mt-3 flex flex-wrap items-center gap-x-4 gap-y-2 text-sm">
        <span class="text-muted"><?= L('Ürün Kodu', 'Product Code') ?>: <b class="text-ink"><?= e($p['code']) ?></b></span>
        <?php if ($p['stock'] > 0): ?><span class="rounded-full bg-green-50 px-2.5 py-0.5 text-xs font-bold text-ok"><?= e(t('product.inStock', ['count' => $p['stock']])) ?></span>
        <?php else: ?><span class="rounded-full bg-soft px-2.5 py-0.5 text-xs font-bold text-muted"><?= e(t('product.outOfStock')) ?></span><?php endif; ?>
      </div>
      <a href="#tab-reviews" data-goto-tab="reviews" class="mt-2 flex items-center gap-2 text-sm"><?= stars($rating) ?> <b><?= $reviews ? number_format($rating, 1, ',', '') : '' ?></b> <span class="text-muted">(<?= count($reviews) ?> <?= L('değerlendirme', 'reviews') ?>)</span> <span class="text-brand underline"><?= L('Yorum Yaz', 'Write a Review') ?></span></a>
      <?php if ($sd = tr_field($p, 'short_desc')): ?><p class="mt-4 text-[15px] leading-relaxed text-ink-2"><?= nl2br(e($sd)) ?></p><?php endif; ?>

      <div class="mt-5 flex flex-wrap items-end gap-x-3 gap-y-1">
        <div class="text-3xl font-black text-brand md:text-4xl"><?= money($price) ?></div>
        <?php if ($old): ?><div class="pb-1 text-lg text-muted line-through"><?= money($old) ?></div><?php endif; ?>
        <div class="pb-1.5 text-xs font-semibold text-muted"><?= prices_ex_vat() ? '+ ' . L('KDV', 'VAT') : L('KDV Dahil', 'VAT incl.') ?></div>
      </div>
      <?php if (prices_ex_vat()): ?><div class="mt-1 text-sm text-muted"><?= L('KDV dahil', 'Incl. VAT') ?>: <b class="text-ink"><?= money($price * (1 + (int)$p['vat_rate'] / 100)) ?></b> <span class="text-xs">(%<?= (int)$p['vat_rate'] ?> <?= L('KDV', 'VAT') ?>)</span></div><?php endif; ?>
      <?php $pay = setting('payment', []); if (!empty($pay['transfer']) && !empty($pay['transferDiscount'])): ?>
        <div class="mt-1 text-sm text-ok"><?= L('Havale/EFT ile', 'With bank transfer') ?> <b><?= money($price * (1 - $pay['transferDiscount'] / 100)) ?></b> (%<?= (float)$pay['transferDiscount'] ?> <?= L('indirim', 'discount') ?>)</div>
      <?php endif; ?>

      <?php if ($p['stock'] > 0): ?>
        <form data-add-form class="mt-5 flex gap-2 sm:gap-3">
          <div class="flex h-12 items-center rounded-md border border-line">
            <button type="button" data-qty="-1" class="flex h-full w-11 items-center justify-center hover:text-brand" aria-label="Azalt"><?= icon('minus', 'h-4 w-4') ?></button>
            <input name="qty" value="1" inputmode="numeric" data-max="<?= (int)$p['stock'] ?>" class="h-full w-12 border-x border-line text-center font-bold outline-none">
            <button type="button" data-qty="1" class="flex h-full w-11 items-center justify-center hover:text-brand" aria-label="Artır"><?= icon('plus', 'h-4 w-4') ?></button>
          </div>
          <button type="button" data-add-cart="<?= e($p['code']) ?>" class="btn-primary h-12 min-w-0 flex-1 px-4 text-base"><?= icon('shopping-cart', 'h-5 w-5') ?> <?= e(t('product.addToCart')) ?></button>
          <button type="button" data-buy-now="<?= e($p['code']) ?>" class="btn h-12 shrink-0 bg-ink px-5 text-base text-white hover:bg-ink-2"><?= L('Hemen Al', 'Buy Now') ?></button>
        </form>
      <?php else: ?>
        <form id="stok-alarm" method="post" action="<?= url('/urun-islem') ?>" class="mt-5 rounded-lg border border-line bg-soft p-4">
          <?= csrf_input() ?><input type="hidden" name="action" value="stock_alert"><input type="hidden" name="product_id" value="<?= (int)$p['id'] ?>">
          <div class="text-sm font-bold"><?= L('Bu ürün şu an stokta yok', 'This product is out of stock') ?></div>
          <div class="mt-1 text-xs text-muted"><?= L('Stoğa girdiğinde size e-posta ile haber verelim.', 'We will email you when it is back in stock.') ?></div>
          <div class="mt-3 flex gap-2"><input type="email" name="email" required value="<?= e(customer()['email'] ?? '') ?>" placeholder="<?= L('E-posta adresiniz', 'Your email') ?>" class="h-11 flex-1 rounded-md border border-line px-3 text-sm"><button class="btn-primary h-11 px-4 text-sm"><?= icon('bell', 'h-4 w-4') ?> <?= L('Haber Ver', 'Notify') ?></button></div>
        </form>
      <?php endif; ?>
      <a href="<?= e(wa_link($waText)) ?>" target="_blank" rel="noopener" class="btn mt-3 h-12 w-full border-2 border-wa text-base text-green-700 hover:bg-green-50"><?= social('whatsapp', 'h-5 w-5') ?> <?= L('WhatsApp ile Sor', 'Ask on WhatsApp') ?></a>

      <div class="mt-5 grid grid-cols-3 gap-2 text-center text-xs">
        <div class="rounded-lg bg-soft p-3"><?= icon('truck', 'mx-auto h-5 w-5 text-brand') ?><div class="mt-1 font-semibold"><?= !empty($shipInfo['freeShippingLimit']) ? money($shipInfo['freeShippingLimit']) . ' ' . L('üzeri ücretsiz kargo', '+ free shipping') : L('Hızlı kargo', 'Fast shipping') ?></div></div>
        <div class="rounded-lg bg-soft p-3"><?= icon('rotate-ccw', 'mx-auto h-5 w-5 text-brand') ?><div class="mt-1 font-semibold"><?= L('14 gün kolay iade', '14-day returns') ?></div></div>
        <div class="rounded-lg bg-soft p-3"><?= icon('shield-check', 'mx-auto h-5 w-5 text-brand') ?><div class="mt-1 font-semibold"><?= L('Güvenli ödeme', 'Secure payment') ?></div></div>
      </div>
      <div class="mt-3 text-xs text-muted"><?= icon('package', 'mr-1 inline h-4 w-4') ?><?= L('Tahmini kargoya veriliş', 'Estimated dispatch') ?>: <b class="text-ink"><?= (int)($p['estimated_ship_days'] ?: 1) <= 1 ? L('Bugün / yarın', 'Today / tomorrow') : (int)$p['estimated_ship_days'] . ' ' . L('iş günü', 'business days') ?></b></div>
    </div>

    <!-- Sağ kutu -->
    <aside class="space-y-4">
      <div class="rounded-xl border border-line p-5">
        <div class="flex items-center gap-2 text-sm font-bold"><?= icon('badge-check', 'h-5 w-5 text-brand') ?> <?= L('Uyumlu Ürün', 'Compatible Part') ?></div>
        <?php if ($compatBrands): ?><div class="mt-2 flex flex-wrap gap-1.5"><?php foreach ($compatBrands as $cb) echo '<span class="rounded bg-soft px-2 py-0.5 text-xs font-bold">' . e($cb) . '</span>'; ?></div><?php endif; ?>
        <ul class="mt-3 space-y-2 text-sm">
          <?php foreach ([L('Orijinal ölçülere uygun', 'Original dimensions'), L('Yüksek dayanım', 'High durability'), L('Kolay montaj', 'Easy installation'), L('Uzun ömürlü kullanım', 'Long service life')] as $li): ?><li class="flex items-center gap-2"><?= icon('check', 'h-4 w-4 text-ok') ?><?= e($li) ?></li><?php endforeach; ?>
        </ul>
      </div>
      <div class="rounded-xl border border-red-100 bg-brand-50 p-5">
        <div class="font-bold text-brand"><?= L('Doğru parçayı bulamadınız mı?', 'Can\'t find the right part?') ?></div>
        <p class="mt-1 text-sm text-ink-2"><?= L('Makinenizin veya parçanın fotoğrafını gönderin, uzman ekibimiz yardımcı olsun.', 'Send a photo of your machine or part and our experts will help.') ?></p>
        <a href="<?= e(wa_link(L('Merhaba, bir parça arıyorum. Fotoğrafını gönderiyorum.', 'Hello, I am looking for a part. Sending a photo.'))) ?>" target="_blank" rel="noopener" class="btn mt-3 min-h-11 w-full whitespace-normal bg-green-600 px-3 py-2 text-center text-sm leading-tight text-white hover:bg-green-700"><?= social('whatsapp', 'h-5 w-5') ?> <?= L('WhatsApp ile Fotoğraf Gönder', 'Send Photo via WhatsApp') ?></a>
      </div>
      <div class="rounded-xl border border-line p-5 text-sm">
        <div class="font-bold"><?= L('Bize Ulaşın', 'Contact Us') ?></div>
        <?php $g = setting('general', []); ?>
        <a href="tel:<?= e(preg_replace('/[^\d+]/', '', $g['phone'] ?? '')) ?>" class="mt-2 flex items-center gap-2 font-semibold hover:text-brand"><?= icon('phone', 'h-4 w-4 text-brand') ?><?= e($g['phone'] ?? '') ?></a>
        <?php if (!empty($g['workingHours'])): ?><div class="mt-1 text-xs text-muted"><?= e($g['workingHours']) ?></div><?php endif; ?>
      </div>
    </aside>
  </div>

  <!-- Sekmeler -->
  <div class="mt-12" data-ptabs>
    <div class="no-scrollbar flex overflow-x-auto border-b border-line">
      <?php $first = true; foreach ($tabList as $k => $l): ?><button type="button" data-ptab="<?= $k ?>" id="tab-<?= $k ?>" class="ptab <?= $first ? 'is-active' : '' ?>"><?= e($l) ?></button><?php $first = false; endforeach; ?>
    </div>
    <div class="py-6">
      <div data-ppane="desc" class="grid grid-cols-1 gap-8 lg:grid-cols-[minmax(0,1fr)_320px]">
        <div class="prose-sk"><?= tr_field($p, 'desc') ?: '<p>' . e(tr_field($p, 'short_desc')) . '</p>' ?></div>
        <?php if ($specs): ?><div class="rounded-xl border border-line p-5"><div class="mb-3 font-bold"><?= L('Öne Çıkan Özellikler', 'Key Specs') ?></div><dl class="space-y-2 text-sm"><?php foreach (array_slice($specs, 0, 6) as $s): ?><div class="flex justify-between gap-3 border-b border-line pb-2"><dt class="text-muted"><?= e(tr_field($s, 'name')) ?></dt><dd class="text-right font-semibold"><?= e($s['value']) ?></dd></div><?php endforeach; ?></dl></div><?php endif; ?>
      </div>
      <div data-ppane="specs" class="hidden">
        <?php if ($specs): ?>
          <table class="w-full max-w-2xl overflow-hidden rounded-lg border border-line text-sm">
            <tr class="bg-soft"><td class="px-4 py-2.5 font-semibold text-muted"><?= L('Parça Grubu', 'Part Group') ?></td><td class="px-4 py-2.5 font-semibold"><?= e($catName) ?></td></tr>
            <?php foreach ($specs as $i => $s): ?><tr class="<?= $i % 2 ? 'bg-soft' : '' ?>"><td class="w-1/2 px-4 py-2.5 font-semibold text-muted"><?= e(tr_field($s, 'name')) ?></td><td class="px-4 py-2.5 font-semibold"><?= e($s['value']) ?><?= $s['unit'] && strpos($s['value'], $s['unit']) === false ? ' ' . e($s['unit']) : '' ?></td></tr><?php endforeach; ?>
          </table>
        <?php else: ?><p class="text-sm text-muted"><?= L('Bu ürün için teknik bilgi henüz eklenmedi. Ölçü bilgisi için bize WhatsApp\'tan yazabilirsiniz.', 'No specifications added yet. Contact us on WhatsApp for dimensions.') ?></p><?php endif; ?>
      </div>
      <div data-ppane="compat" class="hidden">
        <?php if ($compat): ?>
          <div data-ctabs>
            <div class="mb-3 flex flex-wrap gap-2"><?php $i = 0; foreach ($compat as $bn => $ms): ?><button type="button" data-ctab="<?= $i ?>" class="rounded-full border px-4 py-1.5 text-sm font-semibold <?= $i ? 'border-line' : 'border-brand bg-brand-50 text-brand' ?>"><?= e($bn) ?> (<?= count($ms) ?>)</button><?php $i++; endforeach; ?></div>
            <?php $i = 0; foreach ($compat as $bn => $ms): ?>
              <div data-cpane="<?= $i ?>" class="<?= $i ? 'hidden' : '' ?> max-w-2xl overflow-hidden rounded-lg border border-line">
                <div class="grid grid-cols-2 bg-soft px-4 py-2 text-xs font-bold text-muted"><span><?= L('Model', 'Model') ?></span><span><?= L('Uyumluluk', 'Compatibility') ?></span></div>
                <?php foreach ($ms as $m): ?><div class="grid grid-cols-2 border-t border-line px-4 py-2.5 text-sm"><span class="font-semibold"><?= e($bn . ' ' . $m['model']) ?></span><span class="flex items-center gap-1.5 font-semibold text-ok"><?= icon('check', 'h-4 w-4') ?><?= L('Uyumlu', 'Compatible') ?></span></div><?php endforeach; ?>
              </div>
            <?php $i++; endforeach; ?>
          </div>
        <?php else: ?><p class="text-sm text-muted"><?= L('Uyumlu model bilgisi için bize ulaşın.', 'Contact us for compatibility information.') ?></p><?php endif; ?>
        <p class="mt-4 text-xs text-muted"><?= L('Makinenizin listede olmaması uyumsuz olduğu anlamına gelmez; emin olmak için bize fotoğraf gönderin.', 'If your machine is not listed, send us a photo to confirm.') ?></p>
      </div>
      <div data-ppane="refs" class="hidden">
        <table class="w-full max-w-xl overflow-hidden rounded-lg border border-line text-sm">
          <tr class="border-b border-line"><td class="w-1/2 bg-soft px-4 py-2.5 font-semibold text-muted"><?= L('Stok Kodu', 'Stock Code') ?></td><td class="px-4 py-2.5 font-semibold"><?= e($p['code']) ?></td></tr>
          <?php $byKind = []; foreach ($refs as $r) $byKind[$r['kind']][] = $r['code'];
          foreach ($refLabels as $k => $l): if (empty($byKind[$k])) continue; ?><tr class="border-b border-line"><td class="w-1/2 bg-soft px-4 py-2.5 font-semibold text-muted"><?= $l ?></td><td class="px-4 py-2.5 font-semibold"><?= e(implode(', ', $byKind[$k] ?? ['—'])) ?></td></tr><?php endforeach; ?>
        </table>
      </div>
      <?php foreach ($tabs as $tb): ?><div data-ppane="t<?= $tb['id'] ?>" class="prose-sk hidden"><?= tr_field($tb, 'content') ?></div><?php endforeach; ?>
      <div data-ppane="reviews" class="hidden">
        <div class="grid grid-cols-1 gap-8 lg:grid-cols-[280px_minmax(0,1fr)]">
          <div class="rounded-xl bg-soft p-6 text-center">
            <div class="text-5xl font-black"><?= $reviews ? number_format($rating, 1, ',', '') : '–' ?></div>
            <div class="mt-2 flex justify-center"><?= stars($rating, 'h-5 w-5') ?></div>
            <div class="mt-1 text-sm text-muted"><?= count($reviews) ?> <?= L('değerlendirme', 'reviews') ?></div>
          </div>
          <div>
            <?php foreach ($reviews as $r): ?>
              <div class="border-b border-line py-4"><div class="flex items-center gap-2"><?= stars((float)$r['rating']) ?><b class="text-sm"><?= e($r['name'] ?: L('Müşteri', 'Customer')) ?></b><span class="text-xs text-muted"><?= date('d.m.Y', strtotime($r['created_at'])) ?></span></div><p class="mt-1 text-sm"><?= nl2br(e($r['comment'])) ?></p></div>
            <?php endforeach; ?>
            <?php if (!$reviews): ?><p class="text-sm text-muted"><?= L('Bu ürün için henüz yorum yapılmamış. İlk yorumu siz yapın!', 'No reviews yet. Be the first!') ?></p><?php endif; ?>
            <form method="post" action="<?= url('/urun-islem') ?>" class="mt-6 rounded-xl border border-line p-5">
              <?= csrf_input() ?><input type="hidden" name="action" value="review"><input type="hidden" name="product_id" value="<?= (int)$p['id'] ?>">
              <div class="font-bold"><?= L('Yorum Yaz', 'Write a Review') ?></div>
              <div class="mt-3 flex flex-row-reverse justify-end gap-1" data-rating>
                <?php for ($i = 5; $i >= 1; $i--): ?><input type="radio" name="rating" value="<?= $i ?>" id="r<?= $i ?>" class="peer sr-only" <?= $i === 5 ? 'checked' : '' ?>><label for="r<?= $i ?>" class="cursor-pointer text-slate-300 peer-checked:text-amber-400 hover:text-amber-400"><?= icon('star', 'h-7 w-7 fill-current') ?></label><?php endfor; ?>
              </div>
              <div class="mt-3 grid gap-3 sm:grid-cols-2">
                <input name="name" required value="<?= e(trim((customer()['first_name'] ?? '') . ' ' . mb_substr(customer()['last_name'] ?? '', 0, 1) . (customer() ? '.' : ''))) ?>" placeholder="<?= L('Adınız', 'Your name') ?>" class="h-11 rounded-md border border-line px-3 text-sm">
              </div>
              <textarea name="comment" required rows="3" placeholder="<?= L('Ürün hakkındaki düşünceleriniz', 'Your thoughts about the product') ?>" class="mt-3 w-full rounded-md border border-line p-3 text-sm"></textarea>
              <button class="btn-primary mt-3 h-11 px-6 text-sm"><?= L('Yorumu Gönder', 'Submit Review') ?></button>
              <p class="mt-2 text-xs text-muted"><?= L('Yorumunuz onaylandıktan sonra yayınlanır.', 'Your review will be published after approval.') ?></p>
            </form>
          </div>
        </div>
      </div>
      <div data-ppane="qa" class="hidden max-w-3xl">
        <?php foreach ($questions as $q): ?>
          <div class="border-b border-line py-4 text-sm">
            <div class="flex gap-2"><span class="flex h-6 w-6 shrink-0 items-center justify-center rounded-full bg-soft text-xs font-bold">S</span><div><b><?= e(preg_replace('/\n\[[^\]]*\]\s*$/', '', $q['question'])) ?></b><div class="text-xs text-muted"><?= e($q['name'] ?: '') ?> · <?= date('d.m.Y', strtotime($q['created_at'])) ?></div></div></div>
            <?php if ($q['answer']): ?><div class="mt-2 flex gap-2"><span class="flex h-6 w-6 shrink-0 items-center justify-center rounded-full bg-brand text-xs font-bold text-white">C</span><div><?= nl2br(e($q['answer'])) ?><div class="text-xs text-muted">SIR KALIP</div></div></div><?php endif; ?>
          </div>
        <?php endforeach; ?>
        <form method="post" action="<?= url('/urun-islem') ?>" class="mt-6 rounded-xl border border-line p-5">
          <?= csrf_input() ?><input type="hidden" name="action" value="question"><input type="hidden" name="product_id" value="<?= (int)$p['id'] ?>">
          <div class="font-bold"><?= L('Satıcıya Soru Sor', 'Ask a Question') ?></div>
          <div class="mt-3 grid gap-3 sm:grid-cols-2">
            <input name="name" required value="<?= e(trim((customer()['first_name'] ?? '') . ' ' . (customer()['last_name'] ?? ''))) ?>" placeholder="<?= L('Adınız', 'Your name') ?>" class="h-11 rounded-md border border-line px-3 text-sm">
            <input name="email" type="email" value="<?= e(customer()['email'] ?? '') ?>" placeholder="<?= L('E-posta (cevap için)', 'Email (for reply)') ?>" class="h-11 rounded-md border border-line px-3 text-sm">
          </div>
          <textarea name="question" required rows="3" placeholder="<?= L('Sorunuz, ör. Gallignani 9250 ile uyumlu mu?', 'Your question') ?>" class="mt-3 w-full rounded-md border border-line p-3 text-sm"></textarea>
          <button class="btn-primary mt-3 h-11 px-6 text-sm"><?= L('Soruyu Gönder', 'Send Question') ?></button>
        </form>
      </div>
    </div>
  </div>

  <?php if ($related): ?>
  <div class="mt-8" data-tabs>
    <div class="mb-4 flex items-center justify-between"><h2 class="text-xl font-extrabold md:text-2xl"><?= L('Bu Ürünle Birlikte Alınanlar', 'Frequently Bought Together') ?></h2>
      <div class="hidden gap-2 md:flex"><button type="button" data-scroll="-1" class="rounded-full border border-line p-2 hover:border-brand"><?= icon('chevron-left', 'h-4 w-4') ?></button><button type="button" data-scroll="1" class="rounded-full border border-line p-2 hover:border-brand"><?= icon('chevron-right', 'h-4 w-4') ?></button></div></div>
    <div data-row class="no-scrollbar flex snap-x gap-3 overflow-x-auto pb-2">
      <?php $favs = fav_ids(); foreach ($related as $rp): ?><div class="w-[46%] shrink-0 snap-start sm:w-[31%] lg:w-[calc((100%-60px)/6)]"><?php view('partials/product_card', ['p' => $rp, 'favs' => $favs]); ?></div><?php endforeach; ?>
    </div>
  </div>
  <?php endif; ?>
</section>

<!-- Mobil sabit sepet çubuğu -->
<?php if ($p['stock'] > 0): ?>
<div class="fixed inset-x-0 bottom-[57px] z-30 flex items-center gap-3 border-t border-line bg-white px-4 py-2.5 shadow-[0_-4px_12px_rgba(0,0,0,.06)] md:hidden">
  <div><div class="text-lg font-black leading-none text-brand"><?= money($price) ?><?= vat_tag('text-[10px]') ?></div><?php if ($old): ?><div class="text-xs text-muted line-through"><?= money($old) ?></div><?php endif; ?></div>
  <button type="button" data-add-cart="<?= e($p['code']) ?>" data-qty-from="[data-add-form] [name=qty]" class="btn-primary h-11 flex-1 text-sm"><?= icon('shopping-cart', 'h-4 w-4') ?> <?= e(t('product.addToCart')) ?></button>
</div>
<?php endif; ?>

<script type="application/ld+json"><?= json_encode(['@context' => 'https://schema.org', '@type' => 'Product', 'name' => $name, 'sku' => $p['code'], 'brand' => ['@type' => 'Brand', 'name' => $p['brand_name'] ?: 'SIR KALIP'], 'description' => strip_tags((string)tr_field($p, 'short_desc')),
  'image' => array_values(array_map(function ($m) { return site_origin() . media_url($m['url']); }, array_filter($media, function ($m) { return $m['kind'] !== 'video'; }))),
  'offers' => ['@type' => 'Offer', 'priceCurrency' => 'TRY', 'price' => number_format($price, 2, '.', ''), 'availability' => $p['stock'] > 0 ? 'https://schema.org/InStock' : 'https://schema.org/OutOfStock']]
  + ($reviews ? ['aggregateRating' => ['@type' => 'AggregateRating', 'ratingValue' => round($rating, 1), 'reviewCount' => count($reviews)]] : []), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?></script>
