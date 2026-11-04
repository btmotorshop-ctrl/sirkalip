<?php
$title = 'Sipariş #' . $o['order_no'];
admin_view('layout_top', compact('title'));
admin_view('page_head', ['title' => $title, 'crumbs' => [['Siparişler', aurl('orders')], ['#' . $o['order_no'], null]], 'actions' => '<button onclick="window.print()" class="abtn">Yazdır</button>']);
$sl = order_status_labels(); $pl = payment_status_labels(); $ml = payment_method_labels();
$ship = json_decode($o['shipping_address'], true) ?: []; $bill = json_decode($o['billing_address'], true) ?: [];
?>
<div class="grid gap-4 lg:grid-cols-3">
  <div class="space-y-4 lg:col-span-2">
    <div class="acard">
      <div class="acard-title border-b border-line p-4">Sipariş Ürünleri</div>
      <table class="atable"><thead><tr><th>Kod</th><th>Ürün</th><th class="text-right">Birim</th><th class="text-right">Adet</th><th class="text-right">Toplam</th></tr></thead><tbody>
        <?php foreach ($items as $it): ?><tr><td class="font-bold"><?= e($it['code']) ?></td><td><?= e($it['name']) ?></td><td class="text-right"><?= money($it['unit_price']) ?></td><td class="text-right"><?= (int)$it['quantity'] ?></td><td class="text-right font-bold"><?= money($it['total']) ?></td></tr><?php endforeach; ?>
      </tbody></table>
      <div class="ml-auto max-w-xs space-y-1 p-4 text-sm">
        <div class="flex justify-between"><span>Ara Toplam</span><span><?= money($o['subtotal']) ?></span></div>
        <div class="flex justify-between"><span>İndirim</span><span>-<?= money($o['discount']) ?></span></div>
        <?php if ((float)($o['vat_total'] ?? 0) > 0): ?><div class="flex justify-between"><span>KDV</span><span><?= money($o['vat_total']) ?></span></div><?php endif; ?>
        <div class="flex justify-between"><span>Kargo</span><span><?= money($o['shipping_fee']) ?></span></div>
        <div class="flex justify-between border-t border-line pt-2 text-base font-extrabold"><span>Genel Toplam</span><span class="text-brand"><?= money($o['total']) ?></span></div>
      </div>
    </div>
    <div class="grid gap-4 md:grid-cols-2">
      <?php foreach (['Teslimat Adresi' => $ship, 'Fatura Adresi' => $bill] as $t => $a): ?>
        <div class="acard p-5 text-sm"><div class="acard-title mb-2"><?= $t ?></div>
          <div class="font-semibold"><?= e($a['fullName'] ?? $o['customer_name']) ?></div><div><?= e($a['address'] ?? '') ?></div><div><?= e(($a['district'] ?? '') . ' / ' . ($a['city'] ?? '')) ?></div><div><?= e($a['phone'] ?? $o['phone']) ?></div>
          <?php if (!empty($a['taxNumber'])): ?><div class="mt-1 text-xs text-muted"><?= e(($a['companyName'] ?? '') . ' · ' . ($a['taxOffice'] ?? '') . ' · ' . $a['taxNumber']) ?></div><?php endif; ?>
        </div>
      <?php endforeach; ?>
    </div>
    <?php if ($pays): ?><div class="acard"><div class="acard-title border-b border-line p-4">Ödeme Kayıtları</div><table class="atable"><tbody><?php foreach ($pays as $py) echo '<tr><td>' . e($py['provider']) . '</td><td>' . money($py['amount']) . '</td><td>' . e($py['status']) . '</td><td>' . e($py['transaction_id']) . '</td><td class="text-xs">' . e($py['created_at']) . '</td></tr>'; ?></tbody></table></div><?php endif; ?>
  </div>
  <div class="space-y-4">
    <div class="acard p-5 text-sm">
      <div class="acard-title mb-3">Müşteri</div>
      <div class="font-semibold"><?= e($o['customer_name']) ?></div><div><?= e($o['email']) ?></div><div><?= e($o['phone']) ?></div>
      <div class="mt-3 text-xs text-muted">Tarih: <?= date('d.m.Y H:i', strtotime($o['created_at'])) ?> · Kaynak: <?= e($o['source']) ?> · Ödeme: <?= e($ml[$o['payment_method']] ?? '') ?><?= $o['installment'] > 1 ? ' (' . (int)$o['installment'] . ' taksit)' : '' ?></div>
      <?php if ($o['note']): ?><div class="mt-3 rounded bg-yellow-50 p-2 text-xs"><b>Müşteri notu:</b> <?= e($o['note']) ?></div><?php endif; ?>
    </div>
    <form method="post" class="acard p-5">
      <?= csrf_field() ?><input type="hidden" name="action" value="status">
      <div class="acard-title mb-3">Durum Güncelle</div>
      <label class="block"><span class="alabel">Sipariş Durumu</span><select name="status" class="ainput"><?php foreach ($sl as $k => [$l]) echo '<option value="' . $k . '"' . ($o['status'] === $k ? ' selected' : '') . ">$l</option>"; ?></select></label>
      <label class="mt-3 block"><span class="alabel">Ödeme Durumu</span><select name="payment_status" class="ainput"><?php foreach ($pl as $k => [$l]) echo '<option value="' . $k . '"' . ($o['payment_status'] === $k ? ' selected' : '') . ">$l</option>"; ?></select></label>
      <label class="mt-3 block"><span class="alabel">Yönetici Notu</span><textarea name="admin_note" rows="2" class="ainput h-auto py-2"><?= e($o['admin_note']) ?></textarea></label>
      <button class="abtn-primary mt-4 w-full">Güncelle</button>
    </form>
    <?php $ac = aras_config(); $arasRows = db()->query("SELECT * FROM aras_shipments WHERE source = 'order' AND ref_id = " . (int)$o['id'] . " ORDER BY id DESC")->fetchAll();
      $arasLive = 0; foreach ($arasRows as $ar) if ($ar['ok'] && !$ar['cancelled']) $arasLive++;
      $orderCod = $o['payment_method'] === 'cod' && $o['payment_status'] !== 'paid'; ?>
    <form method="post" class="acard p-5" data-confirm="Sipariş Aras Kargo'ya iletilsin mi?">
      <?= csrf_field() ?><input type="hidden" name="action" value="aras">
      <div class="acard-title mb-3">Aras Kargo ile Gönder</div>
      <?php foreach ($arasRows as $ar): ?>
        <div class="mb-2 rounded bg-soft p-2 text-xs">
          <b><?= $ar['ok'] ? ($ar['cancelled'] ? 'İptal edildi' : 'Gönderildi') : 'Hata' ?></b> · <?= e($ar['integration_code']) ?> · <?= e(substr((string)$ar['created_at'], 0, 16)) ?>
          <?php if ($ar['ok'] && !$ar['cancelled']): ?><br><?= e($ar['status_text'] ?: 'Durum henüz sorgulanmadı') ?><?= $ar['tracking_no'] ? ' · Takip no: <b>' . e($ar['tracking_no']) . '</b>' : '' ?>
            <br><a class="font-semibold text-brand underline" href="<?= aurl('acc/aras-label/' . (int)$ar['id']) ?>">Etiket</a> ·
            <a class="font-semibold text-brand underline" href="<?= aurl('acc/aras-list') ?>">Durum sorgula / iptal</a>
          <?php elseif (!$ar['ok']): ?><br><span class="text-red-600"><?= e($ar['message']) ?></span><?php endif; ?>
        </div>
      <?php endforeach; ?>
      <?php if (!$ac['ready']): ?>
        <p class="text-xs text-muted">Entegrasyon kapalı: <?= e($ac['why'] ?: 'ayarlar eksik') ?> <a class="font-semibold text-brand underline" href="<?= aurl('settings/aras') ?>">Aras Kargo ayarlarını</a> doldurup açın.</p>
      <?php else: ?>
        <?php if ($arasLive): ?><p class="mb-2 rounded bg-soft p-2 text-xs">Bu sipariş Aras'a zaten iletildi. Yeniden gönderirseniz bilgiler güncellenir ve daha önce basılan etiket geçersiz olur; yeni etiketi basıp yapıştırın.</p><?php endif; ?>
        <?php if ($orderCod): ?><p class="mb-2 rounded bg-amber-50 p-2 text-xs text-amber-800">Kapıda ödemeli sipariş: <b><?= money($o['total']) ?></b> tahsilatlı kargo olarak gönderilir (Aras kuralı: 5–5000 TL, ücret göndericiden).</p><?php endif; ?>
        <label class="block"><span class="alabel">Gönderi türü</span><select name="cargo_type" class="ainput"><option value="parcel">Koli</option><option value="file">Dosya/Zarf (tek parça)</option></select></label>
        <div class="mt-3 grid grid-cols-2 gap-3">
          <label class="block"><span class="alabel">Parça</span><input type="number" min="1" max="50" name="pieces" value="1" class="ainput"></label>
          <label class="block"><span class="alabel">Ağırlık / Desi</span><input name="weight" value="<?= e((string)($ac['weight'] ?: 1)) ?>" class="ainput"></label>
        </div>
        <?php if (!$orderCod): ?><label class="mt-3 block"><span class="alabel">Kargo ücreti</span><select name="payor" class="ainput"><option value="1" <?= (string)$ac['payor'] !== '2' ? 'selected' : '' ?>>Gönderici öder</option><option value="2" <?= (string)$ac['payor'] === '2' ? 'selected' : '' ?>>Alıcı öder</option></select></label><?php endif; ?>
        <button class="abtn-primary mt-4 w-full"><?= icon('truck', 'h-4 w-4') ?> Aras Kargo'ya Gönder</button>
      <?php endif; ?>
    </form>
    <form method="post" class="acard p-5">
      <?= csrf_field() ?><input type="hidden" name="action" value="ship">
      <div class="acard-title mb-3">Kargoya Ver</div>
      <?php foreach ($ships as $s): ?><div class="mb-2 rounded bg-soft p-2 text-xs"><b><?= e($s['company']) ?></b> · <?= e($s['tracking_no']) ?> · <?= e($s['shipped_at']) ?></div><?php endforeach; ?>
      <label class="block"><span class="alabel">Kargo Firması</span><select name="company_id" class="ainput"><?php foreach ($companies as $k => $l) echo "<option value=\"$k\">" . e($l) . '</option>'; ?></select></label>
      <label class="mt-3 block"><span class="alabel">Takip Numarası</span><input name="tracking_no" required class="ainput"></label>
      <button class="abtn mt-4 w-full"><?= icon('truck', 'h-4 w-4') ?> Kargoya Ver</button>
    </form>
    <form method="post" data-confirm="Sipariş silinsin mi?"><?= csrf_field() ?><input type="hidden" name="action" value="delete"><button class="text-xs font-semibold text-brand hover:underline">Siparişi sil</button></form>
  </div>
</div>
<?php admin_view('layout_bottom'); ?>
