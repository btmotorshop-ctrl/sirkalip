<?php
$title = $r['title'];
$ro = !empty($r['readonly']);
$qs = $_GET; unset($qs['page']);
$pages = max(1, (int)ceil($total / $per));
admin_view('layout_top', compact('title'));
admin_view('page_head', ['title' => $title, 'crumbs' => [[$title, null]],
  'actions' => $ro || !empty($r['no_new']) ? '' : '<a href="' . aurl("r/$key/new") . '" class="abtn-primary">' . icon('plus', 'h-4 w-4') . ' Yeni Ekle</a>']);
?>
<div class="acard">
  <form class="flex flex-wrap items-end gap-3 border-b border-line p-4">
    <label class="w-64"><span class="alabel">Ara</span><input name="q" value="<?= e($_GET['q'] ?? '') ?>" class="ainput" placeholder="Aranacak kelime..."></label>
    <?php foreach ($r['filters'] ?? [] as $f): $spec = $r['fields'][$f] ?? [$f, 'bool']; ?>
      <label class="w-52"><span class="alabel"><?= e($spec[0]) ?></span>
        <select name="<?= e($f) ?>" class="ainput"><option value="">Tümü</option>
          <?php foreach (($spec[1] === 'bool' ? ['1' => 'Evet', '0' => 'Hayır'] : field_options($spec)) as $k => $v): ?><option value="<?= e($k) ?>" <?= (string)($_GET[$f] ?? '') === (string)$k ? 'selected' : '' ?>><?= e($v) ?></option><?php endforeach; ?>
        </select></label>
    <?php endforeach; ?>
    <button class="abtn"><?= icon('filter', 'h-4 w-4') ?> Filtrele</button>
    <?php if (array_filter($qs)): ?><a href="<?= aurl("r/$key") ?>" class="text-xs font-semibold text-muted hover:text-brand">Temizle</a><?php endif; ?>
    <div class="ml-auto text-xs text-muted">Toplam <b><?= $total ?></b> kayıt</div>
  </form>
  <div class="overflow-x-auto">
    <table class="atable">
      <thead><tr><th class="w-16">#</th><?php foreach ($r['list'] as $c): ?><th><?= e($r['fields'][$c][0] ?? ucwords(str_replace('_', ' ', $c === 'created_at' ? 'Eklenme' : ($c === 'updated_at' ? 'Güncellenme' : $c)))) ?></th><?php endforeach; ?><th class="w-32 text-right">İşlemler</th></tr></thead>
      <tbody>
      <?php foreach ($rows as $row): ?>
        <tr>
          <td class="text-muted"><?= e($row[$pk]) ?></td>
          <?php foreach ($r['list'] as $c): ?><td><?= crud_display($r, $c, $row[$c] ?? null) ?></td><?php endforeach; ?>
          <td class="text-right whitespace-nowrap">
            <?php if (!$ro && empty($r['no_edit'])): ?><a href="<?= aurl("r/$key/" . rawurlencode((string)$row[$pk])) ?>" class="aicon" title="Düzenle"><?= icon('pencil', 'h-4 w-4') ?></a><?php endif; ?>
            <?php if (!$ro || $key === 'not_found_logs'): ?>
            <form method="post" action="<?= aurl("r/$key/" . rawurlencode((string)$row[$pk]) . '/delete') ?>" class="inline" data-confirm="Bu kaydı silmek istediğinize emin misiniz?"><?= csrf_field() ?><button class="aicon hover:text-brand" title="Sil"><?= icon('trash2', 'h-4 w-4') ?></button></form>
            <?php endif; ?>
          </td>
        </tr>
      <?php endforeach; ?>
      <?php if (!$rows): ?><tr><td colspan="<?= count($r['list']) + 2 ?>" class="py-12 text-center text-muted">Henüz kayıt yok.</td></tr><?php endif; ?>
      </tbody>
    </table>
  </div>
  <?php if ($pages > 1): ?>
    <div class="flex justify-end gap-1 border-t border-line p-3">
      <?php for ($i = 1; $i <= $pages; $i++): ?><a href="?<?= e(http_build_query($qs + ['page' => $i])) ?>" class="apage <?= $i === $page ? 'is-active' : '' ?>"><?= $i ?></a><?php endfor; ?>
    </div>
  <?php endif; ?>
</div>
<?php admin_view('layout_bottom'); ?>
