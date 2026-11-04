<?php
admin_view('layout_top', compact('title'));
admin_view('page_head', ['title' => $title, 'crumbs' => [['SEO', null], [$title, null]], 'actions' => '<a href="' . aurl("seo/$type?auto=1") . '" class="abtn">Boş başlıkları otomatik doldur</a>']);
?>
<form method="post" class="acard">
  <?= csrf_field() ?>
  <div class="overflow-x-auto"><table class="atable"><thead><tr><th>Ad</th><th class="w-56">URL</th><th class="w-72">SEO Başlık</th><th>SEO Açıklama</th></tr></thead><tbody>
    <?php foreach ($rows as $r): ?><tr>
      <td class="font-semibold"><?= e($r['name']) ?></td>
      <td><input name="slug[<?= $r['id'] ?>]" value="<?= e($r['slug']) ?>" class="ainput h-8 text-xs"></td>
      <td><input name="seo_title[<?= $r['id'] ?>]" value="<?= e($r['seo_title']) ?>" class="ainput h-8 text-xs"></td>
      <td><input name="seo_description[<?= $r['id'] ?>]" value="<?= e($r['seo_description']) ?>" class="ainput h-8 text-xs"></td>
    </tr><?php endforeach; ?>
  </tbody></table></div>
  <div class="border-t border-line p-4"><button class="abtn-primary">Tümünü Kaydet</button></div>
</form>
<?php admin_view('layout_bottom'); ?>
