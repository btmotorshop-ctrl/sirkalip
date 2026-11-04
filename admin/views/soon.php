<?php
$title = $name;
admin_view('layout_top', compact('title'));
?>
<div class="acard mx-auto max-w-xl p-10 text-center">
  <div class="mx-auto flex h-14 w-14 items-center justify-center rounded-full bg-brand-50 text-brand"><?= icon('settings', 'h-7 w-7') ?></div>
  <h1 class="mt-4 text-xl font-extrabold"><?= e($name) ?></h1>
  <p class="mt-2 text-sm text-muted">Bu modül yol haritasında yer alıyor ve sonraki aşamalarda eklenecek.</p>
  <a href="<?= aurl() ?>" class="abtn mt-6">Anasayfaya Dön</a>
</div>
<?php admin_view('layout_bottom'); ?>
