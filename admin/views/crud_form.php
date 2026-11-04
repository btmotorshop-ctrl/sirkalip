<?php
$title = ($id === null ? 'Yeni Kayıt' : 'Kaydı Düzenle') . ' – ' . $r['title'];
admin_view('layout_top', compact('title'));
admin_view('page_head', ['title' => $id === null ? 'Yeni Kayıt' : 'Kaydı Düzenle', 'crumbs' => [[$r['title'], aurl("r/$key")], [$id === null ? 'Yeni' : '#' . $id, null]]]);
?>
<form method="post" enctype="multipart/form-data" class="acard max-w-4xl p-6">
  <?= csrf_field() ?>
  <?php if (!empty($errors['_'])): ?><div class="mb-4 rounded bg-red-50 px-4 py-3 text-sm font-semibold text-red-700"><?= e($errors['_']) ?></div><?php endif; ?>
  <div class="grid gap-4 md:grid-cols-2">
    <?php foreach ($r['fields'] as $f => $spec):
      $wide = in_array($spec[1], ['textarea', 'html', 'json'], true); ?>
      <div class="<?= $wide ? 'md:col-span-2' : '' ?>"><?php admin_view('field', ['f' => $f, 'spec' => $spec, 'val' => $row[$f] ?? '', 'err' => $errors[$f] ?? null]); ?></div>
    <?php endforeach; ?>
  </div>
  <div class="mt-6 flex gap-2 border-t border-line pt-5">
    <?php if (empty($r['readonly'])): ?><button class="abtn-primary"><?= icon('check', 'h-4 w-4') ?> Kaydet</button><?php endif; ?>
    <a href="<?= aurl("r/$key") ?>" class="abtn">Vazgeç</a>
  </div>
</form>
<?php admin_view('layout_bottom'); ?>
