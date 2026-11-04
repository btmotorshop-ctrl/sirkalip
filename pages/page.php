<?php
$slug = $params[1];
$pg = page_by_slug($slug);
if (!$pg) { http_response_code(404); view('layout/top', ['title' => '404']); view('404'); view('layout/bottom'); exit; }
$title = tr_field($pg, 'title');
$others = db()->query("SELECT * FROM pages WHERE is_active = 1 AND kind IN ('page','contract') ORDER BY kind, id")->fetchAll();
view('layout/top', ['title' => $pg['seo_title'] ?: $title, 'meta' => $pg['seo_description']]);
echo breadcrumb([[$title, null]]);
?>
<section class="container-x">
  <div class="grid grid-cols-1 gap-8 lg:grid-cols-[240px_minmax(0,1fr)]">
    <aside class="h-fit rounded-xl border border-line p-3 text-sm font-semibold">
      <?php foreach ($others as $o): $h = $o['kind'] === 'contract' ? url('/sozlesmeler/' . $o['slug']) : url('/' . $o['slug']); ?>
        <a href="<?= $h ?>" class="block rounded-md px-3 py-2 <?= $o['slug'] === $slug ? 'bg-brand-50 text-brand' : 'hover:bg-soft' ?>"><?= e(tr_field($o, 'title')) ?></a>
      <?php endforeach; ?>
      <a href="<?= url('/sss') ?>" class="block rounded-md px-3 py-2 hover:bg-soft"><?= L('Sıkça Sorulan Sorular', 'FAQ') ?></a>
      <a href="<?= url('/iletisim') ?>" class="block rounded-md px-3 py-2 hover:bg-soft"><?= L('İletişim', 'Contact') ?></a>
    </aside>
    <article class="max-w-3xl">
      <h1 class="mb-5 text-3xl font-extrabold"><?= e($title) ?></h1>
      <?php if ($pg['image']): ?><img src="<?= e(media_url($pg['image'])) ?>" alt="" class="mb-6 w-full rounded-xl"><?php endif; ?>
      <div class="prose-sk"><?= tr_field($pg, 'content') ?></div>
    </article>
  </div>
</section>
<?php view('layout/bottom');
