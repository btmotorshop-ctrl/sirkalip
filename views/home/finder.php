<?php
/* Parça bulucu banner'ı: Panel › Modüller › Ana Sayfa Banner Yönetimi › Konum: "Parça Bulucu Banner" */
$fb = db()->query("SELECT * FROM banners WHERE position = 'home_finder' AND is_active = 1 AND (starts_at IS NULL OR starts_at <= NOW()) AND (ends_at IS NULL OR ends_at >= NOW()) ORDER BY sort_order, id")->fetchAll();
$fb = (banner_pick($fb)[0] ?? []);
$fbImg = !empty($fb['image']) ? media_url($fb['image']) : asset('img/finder-banner.webp');
$fbImgM = !empty($fb['mobile_image']) ? media_url($fb['mobile_image']) : (!empty($fb['image']) ? media_url($fb['image']) : asset('img/finder-banner-m.webp'));
$fbLink = !empty($fb['link']) ? $fb['link'] : url('/markalar');
$fbAlt = (!empty($fb) ? tr_field($fb, 'title') : '') ?: t('finder.title');
$fl = db()->query("SELECT image, locale FROM banners WHERE position = 'home_finder_logo' AND is_active = 1 AND image IS NOT NULL AND image <> '' AND (starts_at IS NULL OR starts_at <= NOW()) AND (ends_at IS NULL OR ends_at >= NOW()) ORDER BY sort_order, id")->fetchAll();
$fl = banner_pick($fl)[0]['image'] ?? null;
$fbLogo = $fl ? media_url($fl) : asset('img/finder-baler.jpg');
$brands = array_values(array_filter($brands, function ($b) { return (int)($b['product_count'] ?? 0) > 0 || !empty($b['models']); }));
usort($brands, function ($a, $b) { return (int)$b['product_count'] <=> (int)$a['product_count']; });
$bo = ''; foreach ($brands as $b) $bo .= '<option value="' . e($b['slug']) . '" data-models="' . e(json_encode($b['models'], JSON_UNESCAPED_UNICODE)) . '">' . e($b['name']) . '</option>';
$mo = '<option value="">' . e(t('finder.all')) . '</option>'; foreach ($brands[0]['models'] ?? [] as $m) $mo .= '<option value="' . e($m) . '">' . e($m) . '</option>';
$go = '<option value="">' . e(t('finder.all')) . '</option>'; foreach ($categories as $c) $go .= '<option value="' . e($c['slug']) . '">' . e(tr_field($c, 'name')) . '</option>';
$sel = function ($label, $name, $opts, $attr = '') {
  return '<label class="block min-w-0 flex-1"><span class="finder-label mb-1 block font-semibold text-ink">' . e($label) . '</span><span class="relative block">'
    . '<select name="' . $name . '" ' . $attr . ' class="finder-input w-full appearance-none rounded-md border border-line bg-white pr-8 font-medium text-ink outline-none focus:border-brand">' . $opts . '</select>'
    . icon('chevron-down', 'pointer-events-none absolute right-2.5 top-1/2 h-4 w-4 -translate-y-1/2 text-muted') . '</span></label>';
};
$form = function ($extra = '') use ($sel, $bo, $mo, $go, $fbLogo) { ob_start(); ?>
  <form action="<?= url('/parca-bul') ?>" data-finder class="finder-card flex items-center rounded-xl bg-white shadow-2xl ring-1 ring-black/5 <?= $extra ?>">
    <img src="<?= e($fbLogo) ?>" alt="" class="finder-img hidden shrink-0 rounded-lg object-cover mix-blend-multiply sm:block">
    <div class="min-w-0 flex-1">
      <div class="finder-title font-extrabold text-ink"><?= e(t('finder.title')) ?></div>
      <div class="finder-desc text-muted"><?= e(t('finder.text')) ?></div>
      <div class="finder-row mt-2 flex flex-col gap-2.5 sm:flex-row sm:items-end">
        <?= $sel(t('finder.brand'), 'marka_makine', $bo, 'data-finder-brand') ?>
        <?= $sel(t('finder.model'), 'model', $mo, 'data-finder-model') ?>
        <?= $sel(t('finder.group'), 'grup', $go) ?>
        <button class="finder-btn btn-primary shrink-0 rounded-md"><?= e(t('finder.submit')) ?> <?= icon('arrow-right', 'h-4 w-4') ?></button>
      </div>
    </div>
  </form>
<?php return ob_get_clean(); };
?>
<section class="container-x mt-8">
  <!-- Masaüstü: banner üzerine yerleşen gerçek form -->
  <div class="finder-banner relative hidden overflow-hidden rounded-2xl shadow-lg lg:block" style="aspect-ratio: 2170 / 725">
    <img src="<?= e($fbImg) ?>" alt="<?= e($fbAlt) ?>" loading="lazy" class="absolute inset-0 h-full w-full object-cover">
    <div class="absolute" style="left: 30.6%; top: 55.2%; width: 66.2%; min-height: 25.6%;">
      <?= $form("w-full gap-[1.4%] px-[1.6%] py-[0.9%] min-h-full") ?>
    </div>
    <a href="<?= e($fbLink) ?>" class="absolute inset-x-0 bottom-0 h-[15%]" aria-label="<?= L('Markalar', 'Brands') ?>"></a>
  </div>
  <!-- Mobil / tablet -->
  <div class="lg:hidden">
    <img src="<?= e($fbImgM) ?>" alt="<?= e($fbAlt) ?>" loading="lazy" class="block w-full rounded-t-2xl">
    <?= $form('-mt-1 flex-col gap-3 rounded-t-none p-4 sm:flex-row sm:p-5') ?>
  </div>
</section>
