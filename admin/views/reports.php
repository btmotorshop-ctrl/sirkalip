<?php
admin_view('layout_top', compact('title'));
admin_view('page_head', ['title' => $title, 'crumbs' => [['Raporlar', null], [$title, null]]]);
$money = ['Tutar', 'Toplam', 'Ara Toplam', 'İndirim', 'Kargo', 'Satış', 'Maliyet', 'Kâr'];
$sums = [];
foreach ($rows as $r) foreach ($r as $k => $v) if (is_numeric($v) && !in_array($k, ['Kod', 'Saat', 'Tarih'], true)) $sums[$k] = ($sums[$k] ?? 0) + $v;
?>
<div class="grid gap-4 lg:grid-cols-4">
  <div class="acard h-fit p-2 text-sm"><?php foreach ($defs as $k => [$t]): ?><a href="<?= aurl("reports/$k") ?>?from=<?= e($from) ?>&to=<?= e($to) ?>" class="block rounded px-3 py-2 font-semibold <?= $k === $type ? 'bg-brand-50 text-brand' : 'hover:bg-soft' ?>"><?= e($t) ?></a><?php endforeach; ?></div>
  <div class="acard lg:col-span-3">
    <form class="flex flex-wrap items-end gap-3 border-b border-line p-4">
      <label><span class="alabel">Başlangıç</span><input type="date" name="from" value="<?= e($from) ?>" class="ainput"></label>
      <label><span class="alabel">Bitiş</span><input type="date" name="to" value="<?= e($to) ?>" class="ainput"></label>
      <button class="abtn-primary">Raporu Getir</button>
      <a href="?from=<?= e($from) ?>&to=<?= e($to) ?>&export=csv" class="abtn"><?= icon('download', 'h-4 w-4') ?> Excel'e Aktar</a>
    </form>
    <?php if ($sums): ?><div class="grid gap-3 border-b border-line p-4 sm:grid-cols-4"><?php foreach ($sums as $k => $v): ?><div class="rounded bg-soft p-3"><div class="text-xs text-muted">Toplam <?= e($k) ?></div><div class="text-lg font-extrabold"><?= in_array($k, $money, true) ? money($v) : number_format($v, 0, ',', '.') ?></div></div><?php endforeach; ?></div><?php endif; ?>
    <div class="overflow-x-auto"><table class="atable">
      <?php if ($rows): ?><thead><tr><?php foreach (array_keys($rows[0]) as $h) echo '<th>' . e($h) . '</th>'; ?></tr></thead><?php endif; ?>
      <tbody>
        <?php foreach ($rows as $r): ?><tr><?php foreach ($r as $k => $v) echo '<td>' . (in_array($k, $money, true) ? money($v) : e($v)) . '</td>'; ?></tr><?php endforeach; ?>
        <?php if (!$rows): ?><tr><td class="py-12 text-center text-muted">Seçilen tarih aralığında veri yok.</td></tr><?php endif; ?>
      </tbody></table></div>
  </div>
</div>
<?php admin_view('layout_bottom'); ?>
