<?php
$faqs = db()->query('SELECT * FROM faqs WHERE is_active = 1 ORDER BY sort_order')->fetchAll();
view('layout/top', ['title' => L('Sıkça Sorulan Sorular', 'FAQ')]);
echo breadcrumb([[L('Sıkça Sorulan Sorular', 'FAQ'), null]]);
?>
<section class="container-x max-w-3xl">
  <h1 class="mb-6 text-3xl font-extrabold"><?= L('Sıkça Sorulan Sorular', 'Frequently Asked Questions') ?></h1>
  <div class="divide-y divide-line rounded-xl border border-line">
    <?php foreach ($faqs as $i => $f): ?>
      <details class="group p-5" <?= $i ? '' : 'open' ?>><summary class="flex cursor-pointer list-none items-center justify-between gap-4 font-bold"><?= e(tr_field($f, 'question')) ?><?= icon('chevron-down', 'h-5 w-5 shrink-0 transition group-open:rotate-180') ?></summary><div class="mt-3 text-sm leading-relaxed text-ink-2"><?= nl2br(e(tr_field($f, 'answer'))) ?></div></details>
    <?php endforeach; ?>
  </div>
  <div class="mt-8 rounded-xl bg-soft p-6 text-center"><b><?= L('Sorunuzun cevabını bulamadınız mı?', 'Still have questions?') ?></b><div class="mt-3 flex flex-wrap justify-center gap-3"><a href="<?= url('/iletisim') ?>" class="btn-primary h-11 px-5"><?= L('Bize Yazın', 'Contact Us') ?></a><a href="<?= e(wa_link()) ?>" target="_blank" class="btn h-11 bg-wa px-5 text-white"><?= social('whatsapp', 'h-5 w-5') ?> WhatsApp</a></div></div>
</section>
<?php view('layout/bottom');
