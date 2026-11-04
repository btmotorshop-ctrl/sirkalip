<?php
$title = 'Stok Yönetimi';
admin_view('layout_top', compact('title'));
admin_view('page_head', ['title' => $title, 'crumbs' => [['Ürünler', null], [$title, null]],
  'actions' => '<a href="' . aurl('r/stock_movements/new') . '" class="abtn">' . icon('plus', 'h-4 w-4') . ' Stok Hareketi Ekle</a><a href="' . aurl('r/stock_movements') . '" class="abtn">Stok Hareketleri</a>']);
?>
<div class="mb-4 grid gap-4 sm:grid-cols-4">
  <?php foreach ([['Ürün Çeşidi', (int)$sum['n']], ['Toplam Stok Adedi', (int)$sum['s']], ['Stok Değeri (alış)', money($sum['v'])], ['Kritik Stok', (int)$sum['c']]] as [$l, $v]): ?>
    <div class="acard p-4"><div class="text-xs text-muted"><?= $l ?></div><div class="mt-1 text-xl font-extrabold <?= $l === 'Kritik Stok' && $v ? 'text-brand' : '' ?>"><?= $v ?></div></div>
  <?php endforeach; ?>
</div>
<div class="acard">
  <div class="flex gap-1 border-b border-line px-4 pt-3 text-sm font-semibold">
    <a href="<?= aurl('stock') ?>" class="atab <?= empty($_GET['critical']) ? 'is-active' : '' ?>">Stok Listesi</a>
    <a href="<?= aurl('stock?critical=1') ?>" class="atab <?= !empty($_GET['critical']) ? 'is-active' : '' ?>">Kritik Stok (<?= (int)$sum['c'] ?>)</a>
    <a href="<?= aurl('r/stock_movements') ?>" class="atab">Stok Hareketleri</a>
  </div>
  <form class="flex flex-wrap items-end gap-3 border-b border-line p-4">
    <?php if (!empty($_GET['critical'])): ?><input type="hidden" name="critical" value="1"><?php endif; ?>
    <label class="w-64"><span class="alabel">Ürün ara</span><input name="q" value="<?= e($_GET['q'] ?? '') ?>" class="ainput"></label>
    <label class="w-52"><span class="alabel">Kategori</span><select name="category_id" class="ainput"><option value="">Tümü</option><?php foreach ($cats as $k => $l) echo '<option value="' . $k . '"' . (($_GET['category_id'] ?? '') == $k ? ' selected' : '') . '>' . e($l) . '</option>'; ?></select></label>
    <button class="abtn">Filtrele</button>
  </form>
  <form method="post">
    <?= csrf_field() ?>
    <table class="atable"><thead><tr><th>Ürün</th><th>Kod</th><th>Kategori</th><th class="w-28">Stok</th><th class="w-28">Kritik Stok</th><th>Durum</th><th>Son Giriş</th></tr></thead><tbody>
      <?php foreach ($rows as $r): ?><tr>
        <td><a class="font-semibold hover:text-brand" href="<?= aurl('products/' . $r['id']) ?>"><?= e($r['name_tr']) ?></a></td><td class="font-bold"><?= e($r['code']) ?></td><td><?= e($r['cat']) ?></td>
        <td><input type="number" name="stock[<?= $r['id'] ?>]" value="<?= (int)$r['stock'] ?>" class="ainput h-8"></td>
        <td><input type="number" name="critical[<?= $r['id'] ?>]" value="<?= (int)$r['critical_stock'] ?>" class="ainput h-8"></td>
        <td><?= $r['stock'] <= 0 ? '<span class="badge-err">Tükendi</span>' : ($r['stock'] <= $r['critical_stock'] ? '<span class="badge-warn">Kritik Stok</span>' : '<span class="badge-ok">Stokta</span>') ?></td>
        <td class="text-xs text-muted"><?= $r['last_in'] ? date('d.m.Y', strtotime($r['last_in'])) : '—' ?></td>
      </tr><?php endforeach; ?>
    </tbody></table>
    <div class="border-t border-line p-4"><button class="abtn-primary">Stokları Kaydet</button> <span class="ml-2 text-xs text-muted">Değişiklikler stok hareketlerine otomatik kaydedilir.</span></div>
  </form>
</div>
<?php admin_view('layout_bottom'); ?>
