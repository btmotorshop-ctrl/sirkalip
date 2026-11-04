<?php
$title = 'Toplu Ürün İşlemleri';
admin_view('layout_top', compact('title'));
admin_view('page_head', ['title' => $title, 'crumbs' => [['Ürün Yönetimi', aurl('products')], [$title, null]]]);
?>
<div class="grid gap-4 lg:grid-cols-3">
  <form method="post" enctype="multipart/form-data" class="acard p-6 lg:col-span-2">
    <?= csrf_field() ?>
    <div class="mb-5 flex gap-6 text-xs font-semibold">
      <?php foreach (['Dosya Yükle', 'Önizleme', 'Eşleştirme', 'Tamamla'] as $i => $s): ?><div class="flex items-center gap-2 <?= ($result ? 3 : 0) >= $i ? 'text-brand' : 'text-muted' ?>"><span class="flex h-6 w-6 items-center justify-center rounded-full border-2 border-current"><?= $i + 1 ?></span><?= $s ?></div><?php endforeach; ?>
    </div>
    <label class="block rounded-lg border-2 border-dashed border-line p-10 text-center hover:border-brand">
      <div class="mx-auto flex h-14 w-14 items-center justify-center rounded-lg bg-green-600 text-2xl font-black text-white">X</div>
      <div class="mt-3 font-semibold">Excel (CSV) dosyanızı seçin</div>
      <div class="text-xs text-muted">Excel'de "Farklı Kaydet → CSV UTF-8 (virgülle / noktalı virgülle ayrılmış)" seçin</div>
      <input type="file" name="file" accept=".csv,text/csv" required class="mt-4 text-sm">
    </label>
    <div class="mt-3 text-xs text-muted">Ürün kodu sistemde varsa ürün <b>güncellenir</b>, yoksa <b>yeni eklenir</b>.</div>
    <button class="abtn-primary mt-5 bg-green-600 hover:bg-green-700"><?= icon('upload', 'h-4 w-4') ?> Ürünleri Aktar</button>
  </form>
  <div class="space-y-4">
    <div class="acard p-5">
      <div class="acard-title">Excel Şablonunu İndir</div>
      <p class="mt-1 text-xs text-muted">Ürün eklemek için hazır şablonu kullanın.</p>
      <a href="<?= aurl('products/bulk?do=template') ?>" class="abtn mt-3"><?= icon('download', 'h-4 w-4') ?> Şablonu İndir</a>
      <a href="<?= aurl('products/bulk?do=export') ?>" class="abtn mt-2"><?= icon('download', 'h-4 w-4') ?> Tüm Ürünleri Dışa Aktar</a>
    </div>
    <div class="acard p-5 text-xs">
      <div class="acard-title">Sütunlar</div>
      <div class="mt-2 flex flex-wrap gap-1"><?php foreach ($cols as $c) echo '<code class="rounded bg-soft px-1.5 py-0.5">' . $c . '</code>'; ?></div>
      <p class="mt-2 text-muted"><b>category_slug</b> ve <b>brand_slug</b> için kategori/marka URL'lerini kullanın (ör. <code>pickup-grubu</code>, <code>gallignani</code>).</p>
    </div>
  </div>
</div>
<?php if ($result): ?>
  <div class="acard mt-4 p-6">
    <div class="acard-title">Yükleme Sonucu</div>
    <div class="mt-3 grid gap-3 sm:grid-cols-4 text-center">
      <div class="rounded bg-soft p-3"><div class="text-2xl font-black"><?= $result['total'] ?></div><div class="text-xs">Toplam Satır</div></div>
      <div class="rounded bg-green-50 p-3 text-green-700"><div class="text-2xl font-black"><?= $result['insert'] ?></div><div class="text-xs">Yeni Eklenen</div></div>
      <div class="rounded bg-blue-50 p-3 text-blue-700"><div class="text-2xl font-black"><?= $result['update'] ?></div><div class="text-xs">Güncellenen</div></div>
      <div class="rounded bg-red-50 p-3 text-red-700"><div class="text-2xl font-black"><?= count($result['errors']) ?></div><div class="text-xs">Hatalı</div></div>
    </div>
    <?php if ($result['errors']): ?><ul class="mt-4 list-disc space-y-1 pl-5 text-xs text-red-700"><?php foreach ($result['errors'] as $er) echo '<li>' . e($er) . '</li>'; ?></ul><?php endif; ?>
  </div>
<?php endif; ?>
<?php admin_view('layout_bottom'); ?>
