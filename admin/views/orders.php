<?php
$title = !empty($_GET['deleted']) ? 'Silinen Siparişler' : 'Siparişler Listesi';
admin_view('layout_top', compact('title'));
admin_view('page_head', ['title' => $title, 'crumbs' => [['Siparişler', null], [$title, null]]]);
$sl = order_status_labels(); $pl = payment_status_labels(); $ml = payment_method_labels();
$all = array_sum($tabs); $cur = $_GET['status'] ?? '';
?>
<div class="acard">
  <div class="no-scrollbar flex gap-1 overflow-x-auto border-b border-line px-4 pt-3 text-sm font-semibold">
    <a href="<?= aurl('orders') ?>" class="atab <?= $cur === '' ? 'is-active' : '' ?>">Tüm Siparişler (<?= $all ?>)</a>
    <?php foreach ($sl as $k => [$l]): ?><a href="<?= aurl('orders?status=' . $k) ?>" class="atab <?= $cur === $k ? 'is-active' : '' ?>"><?= $l ?> (<?= (int)($tabs[$k] ?? 0) ?>)</a><?php endforeach; ?>
  </div>
  <form class="flex flex-wrap items-end gap-3 border-b border-line p-4">
    <?php if ($cur): ?><input type="hidden" name="status" value="<?= e($cur) ?>"><?php endif; ?>
    <label class="w-64"><span class="alabel">Sipariş no, müşteri adı, e-posta, telefon</span><input name="q" value="<?= e($_GET['q'] ?? '') ?>" class="ainput"></label>
    <label class="w-44"><span class="alabel">Ödeme Durumu</span><select name="payment" class="ainput"><option value="">Tümü</option><?php foreach ($pl as $k => [$l]) echo '<option value="' . $k . '"' . (($_GET['payment'] ?? '') === $k ? ' selected' : '') . ">$l</option>"; ?></select></label>
    <label class="w-44"><span class="alabel">Ödeme Tipi</span><select name="method" class="ainput"><option value="">Tümü</option><?php foreach ($ml as $k => $l) echo '<option value="' . $k . '"' . (($_GET['method'] ?? '') === $k ? ' selected' : '') . ">$l</option>"; ?></select></label>
    <label><span class="alabel">Başlangıç</span><input type="date" name="from" value="<?= e($_GET['from'] ?? '') ?>" class="ainput"></label>
    <label><span class="alabel">Bitiş</span><input type="date" name="to" value="<?= e($_GET['to'] ?? '') ?>" class="ainput"></label>
    <button class="abtn"><?= icon('filter', 'h-4 w-4') ?> Filtrele</button>
  </form>
  <div class="overflow-x-auto">
    <table class="atable"><thead><tr><th>Sipariş No</th><th>Tarih</th><th>Müşteri</th><th class="text-right">Adet</th><th class="text-right">Tutar</th><th>Ödeme Tipi</th><th>Ödeme Durumu</th><th>Sipariş Durumu</th><th></th></tr></thead><tbody>
      <?php foreach ($rows as $o): ?><tr>
        <td><a href="<?= aurl('orders/' . $o['id']) ?>" class="font-bold text-brand">#<?= e($o['order_no']) ?></a></td>
        <td class="text-xs"><?= date('d.m.Y H:i', strtotime($o['created_at'])) ?></td>
        <td><div class="font-semibold"><?= e($o['customer_name']) ?></div><div class="text-xs text-muted"><?= e($o['phone']) ?></div></td>
        <td class="text-right"><?= (int)$o['qty'] ?></td><td class="text-right font-bold"><?= money($o['total']) ?></td>
        <td class="text-xs"><?= e($ml[$o['payment_method']] ?? $o['payment_method']) ?></td>
        <td><span class="<?= $pl[$o['payment_status']][1] ?>"><?= $pl[$o['payment_status']][0] ?></span></td>
        <td><span class="<?= $sl[$o['status']][1] ?>"><?= $sl[$o['status']][0] ?></span></td>
        <td class="text-right"><a href="<?= aurl('orders/' . $o['id']) ?>" class="aicon"><?= icon('eye', 'h-4 w-4') ?></a></td>
      </tr><?php endforeach; ?>
      <?php if (!$rows): ?><tr><td colspan="9" class="py-12 text-center text-muted">Sipariş bulunamadı. Siteden sipariş geldiğinde burada listelenecek.</td></tr><?php endif; ?>
    </tbody></table>
  </div>
</div>
<?php admin_view('layout_bottom'); ?>
