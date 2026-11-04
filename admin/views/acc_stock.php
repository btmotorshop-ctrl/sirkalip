<?php
/** Depo Durumu raporu – BizimHesap "Stok Değeri" ekranı */
$title = 'Depo Durumu';
admin_view('layout_top', compact('title'));
$sel = function ($a, $b) { return (string)$a === (string)$b ? ' selected' : ''; };
$csv = aurl('acc/stock?' . http_build_query(array_merge($_GET, ['export' => 'csv'])));
?>
<div class="st-panel" id="stCrit">
  <div class="st-h"><span>DEPO DURUMU RAPOR KRİTERLERİ</span><button type="button" class="st-tg" data-tg="stCrit" aria-label="Aç/Kapat">⌃</button></div>
  <form method="get" action="<?= aurl('acc/stock') ?>" class="st-b" id="stForm">
    <div class="st-cols">
      <div>
        <div class="st-r"><label>Ürün</label><input type="search" name="q" class="df-i" value="<?= e($f['q']) ?>" placeholder="Ürün isminden arayın veya barkod okutun" autocomplete="off"></div>
        <div class="st-r"><label>Depo</label><select class="df-i" name="warehouse"><option>Ana Depo</option></select></div>
        <div class="st-r"><label>Ürün Resimleri</label><select name="pics" class="df-i"><option value="1"<?= $sel($f['pics'], 1) ?>>Ürün resimlerini göster</option><option value="0"<?= $sel($f['pics'], 0) ?>>Ürün resimlerini gösterme</option></select></div>
        <div class="st-r"><label>Stok Tarihi</label><div><input type="date" name="date" class="df-i" value="<?= e($f['date']) ?>"><small class="st-hint">Seçtiğiniz tarih itibarıyla stok miktarları gösterilir.</small></div></div>
        <div class="st-r"><label>Varyant</label><select class="df-i" disabled title="Bu mağazada varyant kullanılmıyor"><option>Tüm varyantlar</option></select></div>
      </div>
      <div>
        <div class="st-r"><label>Ürün Durumu</label><select name="status" class="df-i"><option value="all"<?= $sel($f['status'], 'all') ?>>Tüm ürünler</option><option value="active"<?= $sel($f['status'], 'active') ?>>Sadece aktif ürünler</option></select></div>
        <div class="st-r"><label>Kategori</label><select name="category" class="df-i"><option value="">Tüm kategoriler</option><?php foreach ($cats as $c): ?><option value="<?= (int)$c['id'] ?>"<?= $sel($f['cat'], $c['id']) ?>><?= e($c['name']) ?></option><?php endforeach; ?></select></div>
        <div class="st-r"><label>Marka</label><select name="brand" class="df-i"><option value="">Tüm markalar</option><?php foreach ($brands as $b): ?><option value="<?= (int)$b['id'] ?>"<?= $sel($f['brand'], $b['id']) ?>><?= e($b['name']) ?></option><?php endforeach; ?></select></div>
        <div class="st-r"><label>Raf Yeri</label><select class="df-i" disabled title="Bu mağazada raf yeri kullanılmıyor"><option>Tüm raflar</option></select></div>
        <div class="st-r"><label>Stok Durumu</label><select name="stock" class="df-i">
          <option value="in"<?= $sel($f['stockMode'], 'in') ?>>Stokta olanları göster</option><option value="out"<?= $sel($f['stockMode'], 'out') ?>>Stokta olmayanları göster</option>
          <option value="critical"<?= $sel($f['stockMode'], 'critical') ?>>Kritik stok seviyesindekileri göster</option><option value="all"<?= $sel($f['stockMode'], 'all') ?>>Tümünü göster</option></select></div>
      </div>
    </div>
    <div class="st-act"><button class="st-go" type="submit"><?= icon('upload', '') ?> Raporu Hazırla</button></div>
  </form>
</div>

<div class="st-panel" id="stRes">
  <div class="st-h"><span>RAPOR SONUCU</span><button type="button" class="st-tg" data-tg="stRes" aria-label="Aç/Kapat">⌃</button></div>
  <div class="st-b">
    <div class="st-tools"><a class="st-x gr" href="<?= e($csv) ?>"><?= icon('file-spreadsheet', '') ?> Excel</a><button type="button" class="st-x or" onclick="window.print()"><?= icon('file-text', '') ?> PDF</button></div>
    <div class="st-wrap">
    <table class="st-t">
      <thead><tr><?php if ($f['pics']): ?><th>Resim</th><?php endif; ?><th>Depo</th><th>Kod</th><th>Barkod</th><th>Ürün</th><th>Marka</th><th>Kategori</th><th class="r">Alış</th><th class="r">Satış</th><th class="r">KDV%</th><th class="r">Kritik Miktar</th><th class="r">Miktar</th><th>Birim</th><th>Etiket</th></tr></thead>
      <tbody>
      <?php foreach ($rows as $r): $cur = $r['currency'] === 'TRY' ? 'TL' : $r['currency']; $low = (int)$r['qty'] <= (int)$r['critical_stock']; ?>
        <tr>
          <?php if ($f['pics']): ?><td class="im"><?php if ($r['img']): ?><img src="<?= e(media_url($r['img'])) ?>" alt="" loading="lazy"><?php endif; ?></td><?php endif; ?>
          <td>Ana Depo</td><td><?= e($r['code']) ?></td><td><?= e($r['barcode']) ?></td>
          <td class="nm"><a href="<?= aurl('products/' . (int)$r['id']) ?>"><?= e($r['name']) ?></a></td>
          <td><?= e($r['brand']) ?></td><td><?= e($r['category']) ?></td>
          <td class="r"><?= $r['purchase_price'] > 0 ? e(acc_stock_num($r['purchase_price']) . ' ' . $cur) : '' ?></td>
          <td class="r"><?= $r['price'] > 0 ? e(acc_stock_num($r['price']) . ' ' . $cur) : '' ?></td>
          <td class="r"><?= (int)$r['vat_rate'] ?></td><td class="r"><?= (int)$r['critical_stock'] ?></td>
          <td class="r<?= $low ? ' low' : '' ?>"><?= (int)$r['qty'] ?></td><td>ad</td><td></td>
        </tr>
      <?php endforeach; ?>
      <?php if (!$rows): ?><tr><td colspan="14" class="st-empty">Bu kriterlere uyan ürün bulunamadı.</td></tr><?php endif; ?>
      </tbody>
      <tfoot><tr><td colspan="<?= $f['pics'] ? 11 : 10 ?>"></td><td class="r"><?= (int)$total ?> ad</td><td colspan="2"></td></tr></tfoot>
    </table>
    </div>
  </div>
</div>
<script>
[].forEach.call(document.querySelectorAll('.st-tg'),function(b){b.addEventListener('click',function(){var p=document.getElementById(b.dataset.tg);p.classList.toggle('shut')})});
</script>
<?php admin_view('layout_bottom'); ?>
