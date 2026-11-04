<?php
$slug = $params[2] ?? '';
if ($slug) {
    $a = page_by_slug($slug, 'article');
    if (!$a) { http_response_code(404); view('layout/top', ['title' => '404']); view('404'); view('layout/bottom'); exit; }
    $more = db()->query("SELECT * FROM pages WHERE kind = 'article' AND is_active = 1 AND id <> {$a['id']} ORDER BY id DESC LIMIT 3")->fetchAll();
    view('layout/top', ['title' => tr_field($a, 'title'), 'meta' => $a['seo_description']]);
    echo breadcrumb([[L('Teknik Bilgiler', 'Technical Info'), url('/teknik-bilgiler')], [tr_field($a, 'title'), null]]); ?>
    <article class="container-x max-w-3xl">
      <div class="text-xs font-semibold text-muted"><?= date('d.m.Y', strtotime($a['created_at'])) ?></div>
      <h1 class="mt-1 text-3xl font-extrabold leading-tight"><?= e(tr_field($a, 'title')) ?></h1>
      <?php if ($a['image']): ?><img src="<?= e(media_url($a['image'])) ?>" alt="" class="mt-6 w-full rounded-xl"><?php endif; ?>
      <div class="prose-sk mt-6"><?= tr_field($a, 'content') ?></div>
      <div class="mt-8 rounded-xl bg-soft p-5 text-sm"><b><?= L('Parça mı arıyorsunuz?', 'Looking for a part?') ?></b> <?= L('Makinenize uygun parçaları kolayca bulun.', 'Find parts for your machine.') ?> <a href="<?= url('/parca-bul') ?>" class="font-bold text-brand"><?= L('Parça Bul ›', 'Find Parts ›') ?></a></div>
    </article>
    <?php if ($more): ?><section class="container-x mt-10"><h2 class="mb-4 text-xl font-extrabold"><?= L('Diğer Yazılar', 'More Articles') ?></h2><div class="grid grid-cols-1 gap-4 md:grid-cols-3"><?php foreach ($more as $m) view('partials/article_card', ['a' => $m]); ?></div></section><?php endif;
} else {
    $rows = db()->query("SELECT * FROM pages WHERE kind = 'article' AND is_active = 1 ORDER BY id DESC")->fetchAll();
    view('layout/top', ['title' => L('Teknik Bilgiler', 'Technical Info')]);
    echo breadcrumb([[L('Teknik Bilgiler', 'Technical Info'), null]]); ?>
    <section class="container-x">
      <div class="mb-8 max-w-2xl"><h1 class="text-3xl font-extrabold"><?= L('Teknik Bilgiler', 'Technical Info') ?></h1><p class="mt-2 text-muted"><?= L('Montaj rehberleri, bakım ipuçları ve balya makinesi hakkında faydalı bilgiler.', 'Installation guides, maintenance tips and useful baler info.') ?></p></div>
      <div class="grid grid-cols-1 gap-5 sm:grid-cols-2 lg:grid-cols-3"><?php foreach ($rows as $a) view('partials/article_card', ['a' => $a]); ?></div>
      <?php if (!$rows): ?><p class="text-muted"><?= L('Yakında yeni içerikler eklenecek.', 'Coming soon.') ?></p><?php endif; ?>
    </section>
<?php }
view('layout/bottom');
