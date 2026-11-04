<?php
$title = 'Anasayfa Ürün Sıralama';
admin_view('layout_top', compact('title'));
admin_view('page_head', ['title' => $title, 'crumbs' => [['Ürünler', null], [$title, null]]]);
?>
<form method="post" class="acard">
  <?= csrf_field() ?>
  <div class="border-b border-line p-4 text-sm text-muted">"Vitrinde" işaretli ürünler ana sayfadaki <b>Çok Satan Ürünler</b> alanında, küçük sıra numarası önce olacak şekilde gösterilir.</div>
  <table class="atable"><thead><tr><th class="w-24">Sıra</th><th class="w-20">Vitrinde</th><th>Kod</th><th>Ürün</th><th class="text-right">Satış</th></tr></thead><tbody>
    <?php foreach ($rows as $r): ?><tr>
      <td><input name="sort[<?= $r['id'] ?>]" value="<?= (int)$r['sort_order'] ?>" type="number" class="ainput h-8 w-20"></td>
      <td><input type="checkbox" name="featured[<?= $r['id'] ?>]" value="1" <?= $r['is_featured'] ? 'checked' : '' ?> class="h-4 w-4 accent-[#e30613]"></td>
      <td class="font-bold"><?= e($r['code']) ?></td><td><?= e($r['name_tr']) ?></td><td class="text-right"><?= (int)$r['sales_count'] ?></td>
    </tr><?php endforeach; ?>
  </tbody></table>
  <div class="border-t border-line p-4"><button class="abtn-primary">Kaydet</button></div>
</form>
<?php admin_view('layout_bottom'); ?>
