<?php
$title = 'Cari Bakiye Raporu';
admin_view('layout_top', compact('title'));
admin_view('page_head', ['title' => $title, 'crumbs' => [['Ön Muhasebe', aurl('acc')], [$title, null]]]);
$tot = function ($rows) { $t = 0; foreach ($rows as $r) $t += $r['bal']; return $t; };
?>
<div class="grid gap-4 lg:grid-cols-2">
  <?php foreach ([['Müşteri Bakiyeleri (alacağımız)', $cust], ['Tedarikçi Bakiyeleri (borcumuz)', $supp]] as [$ttl, $rows]): ?>
    <div class="acard">
      <div class="flex items-center justify-between border-b border-line p-4"><b><?= e($ttl) ?></b><b><?= acc_money($tot($rows)) ?></b></div>
      <table class="atable"><tbody>
        <?php foreach ($rows as $r): ?><tr><td><a class="hover:text-brand" href="<?= aurl('acc/party/' . $r['id']) ?>"><?= e($r['name']) ?></a></td><td class="text-right"><b><?= acc_money($r['bal']) ?></b></td></tr><?php endforeach; ?>
        <?php if (!$rows): ?><tr><td class="py-8 text-center text-muted">Bakiyesi olan kayıt yok.</td></tr><?php endif; ?>
      </tbody></table>
    </div>
  <?php endforeach; ?>
</div>
<?php admin_view('layout_bottom'); ?>
