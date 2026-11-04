<?php
/** Kargo Siparişi Oluştur penceresi (BizimHesap benzeri). Gerekli değişkenler: $p (cari), $ac (aras_config) */
$iller = ['Adana', 'Adıyaman', 'Afyonkarahisar', 'Ağrı', 'Aksaray', 'Amasya', 'Ankara', 'Antalya', 'Ardahan', 'Artvin', 'Aydın', 'Balıkesir', 'Bartın', 'Batman', 'Bayburt', 'Bilecik', 'Bingöl', 'Bitlis', 'Bolu', 'Burdur', 'Bursa', 'Çanakkale', 'Çankırı', 'Çorum', 'Denizli', 'Diyarbakır', 'Düzce', 'Edirne', 'Elazığ', 'Erzincan', 'Erzurum', 'Eskişehir', 'Gaziantep', 'Giresun', 'Gümüşhane', 'Hakkari', 'Hatay', 'Iğdır', 'Isparta', 'İstanbul', 'İzmir', 'Kahramanmaraş', 'Karabük', 'Karaman', 'Kars', 'Kastamonu', 'Kayseri', 'Kilis', 'Kırıkkale', 'Kırklareli', 'Kırşehir', 'Kocaeli', 'Konya', 'Kütahya', 'Malatya', 'Manisa', 'Mardin', 'Mersin', 'Muğla', 'Muş', 'Nevşehir', 'Niğde', 'Ordu', 'Osmaniye', 'Rize', 'Sakarya', 'Samsun', 'Şanlıurfa', 'Siirt', 'Sinop', 'Sivas', 'Şırnak', 'Tekirdağ', 'Tokat', 'Trabzon', 'Tunceli', 'Uşak', 'Van', 'Yalova', 'Yozgat', 'Zonguldak'];
$ilKey = function ($s) { return mb_strtolower(strtr(trim((string)$s), ['İ' => 'i', 'I' => 'ı']), 'UTF-8'); };
$gen = setting('general', []) ?: [];
$sTitle = $gen['companyTitle'] ?? ($gen['siteName'] ?? 'SIR KALIP');
$sAddr = $gen['address'] ?? '';
$sPhone = $gen['phone'] ?? '';
$rAddr = ($p['delivery_address'] ?? '') !== '' ? $p['delivery_address'] : ($p['address'] ?? '');
$ilSel = function ($name, $cur) use ($iller, $ilKey) {
    $h = '<select name="' . $name . '" class="df-i" required><option value="">şehir seçin</option>';
    foreach ($iller as $il) $h .= '<option' . ($ilKey($il) === $ilKey($cur) ? ' selected' : '') . '>' . e($il) . '</option>';
    return $h . '</select>';
};
?>
<div class="df-modal" id="pvArasM" hidden><div class="df-mbox kg-box">
  <div class="df-mh">Kargo Bilgileri</div>
  <?php if (!$ac['ready']): ?>
    <div class="df-mb"><p style="margin:0 0 14px;color:#7d5a0b">Aras Kargo gönderisi şu an oluşturulamıyor: <b><?= e($ac['why'] ?: 'ayarlar eksik') ?></b> <a href="<?= aurl('settings/aras') ?>" style="color:#18a371;font-weight:700">Yönetim › Ayarlar › Aras Kargo</a></p></div>
    <div class="df-mf"><button type="button" class="df-link" data-close>Kapat</button></div>
  <?php else: ?>
  <form method="post" action="<?= aurl('acc/aras-send') ?>" id="kgF">
    <?= csrf_field() ?><input type="hidden" name="party_id" value="<?= (int)$p['id'] ?>">
    <div class="kg-info"><b>Sırkalıp Ön Muhasebe üzerinden kendi anlaşmanız ile dosyalarınız için kolaylıkla kargo siparişi oluşturabilirsiniz.</b>
      <ul><li>Bu ekrandan alacağınız çıktıyı gönderinize yapıştırmanız yeterli olacaktır.</li><li>Kargo elemanı paketi sizden teslim alırken sadece etiketteki barkodu okutur ve ayrıca alıcı bilgilerini girmekle uğraşmaz.</li></ul></div>
    <div class="kg-cols">
      <div class="kg-col">
        <div class="kg-r"><label>Gönderen</label><input class="df-i" value="<?= e($sTitle) ?>" disabled></div>
        <div class="kg-r top"><label>Gönderici Adresi</label><textarea class="df-i" rows="3" disabled><?= e($sAddr) ?></textarea></div>
        <div class="kg-r"><label>Şehir</label><?= $ilSel('s_city', $ac['senderCity'] ?? '') ?></div>
        <div class="kg-r"><label>İlçe</label><input name="s_town" class="df-i" value="<?= e($ac['senderTown'] ?? '') ?>"></div>
        <div class="kg-r"><label>Gönderici Telefon</label><input name="s_phone" class="df-i" value="<?= e($sPhone) ?>" inputmode="tel"></div>
        <div class="kg-note">yukarıdaki bilgileri güncellemek için <a href="<?= aurl('settings/general') ?>">Genel Ayarlar</a> ve <a href="<?= aurl('settings/aras') ?>">Aras Kargo</a> sayfalarını kullanın.</div>
      </div>
      <div class="kg-col">
        <div class="kg-r"><label>Alıcı İsim/Unvan</label><input name="name" class="df-i" value="<?= e($p['name']) ?>" required></div>
        <div class="kg-r top"><label>Alıcı Adresi</label><textarea name="address" class="df-i" rows="3" required><?= e($rAddr) ?></textarea></div>
        <div class="kg-r"><label>Şehir</label><?= $ilSel('city', $p['city'] ?? '') ?></div>
        <div class="kg-r"><label>İlçe</label><input name="town" class="df-i" required></div>
        <div class="kg-r"><label>Alıcı Telefonu</label><input name="phone" class="df-i" value="<?= e($p['phone'] ?? '') ?>" inputmode="tel" required></div>
        <div class="kg-r"><label>Gönderi Türü</label><select name="cargo_type" id="kgType" class="df-i"><option value="file">Dosya/Zarf</option><option value="parcel">Koli</option></select></div>
        <div class="kg-r"><label>Koli / Dosya Sayısı</label><input type="number" min="1" max="50" name="pieces" id="kgPieces" value="1" class="df-i" readonly></div>
        <div class="kg-r"><label><b>Ödeme Tipi</b></label><select name="payor" id="kgPayor" class="df-i"><option value="1" <?= (string)$ac['payor'] !== '2' ? 'selected' : '' ?>>Gönderici Ödemeli</option><option value="2" <?= (string)$ac['payor'] === '2' ? 'selected' : '' ?>>Alıcı Ödemeli</option></select></div>
        <div class="kg-r"><label><b>Teslim Yeri</b></label><select name="delivery" class="df-i"><option value="address">Adrese Teslim</option><option value="branch">Şubeden Teslim</option></select></div>
        <div class="kg-r"><label><b>Alıcıya SMS gönderilsin mi?</b></label><select name="sms" class="df-i"><option value="no">Hayır</option><option value="yes">Evet</option></select></div>
        <div class="kg-r"><label><b>Tahsilatlı mı?</b></label><select name="cod" id="kgCod" class="df-i"><option value="no">Hayır</option><option value="yes">Evet</option></select></div>
        <div class="kg-r kg-cod" hidden><label><b>Tahsilat Tipi</b></label><select name="cod_type" class="df-i"><option value="0">Nakit</option><option value="1">Kredi Kartı</option></select></div>
        <div class="kg-r kg-cod" hidden><label><b>Tahsilat Tutarı</b></label><input name="cod_amount" class="df-i" inputmode="decimal" placeholder="0,00"></div>
      </div>
    </div>
    <div class="df-mf"><button type="button" class="pl-btn or" data-close>Vazgeç</button><button type="submit" class="pl-btn gr"><?= icon('truck', 'h-4 w-4') ?> Kargo Siparişi Oluştur</button></div>
  </form>
  <?php endif; ?>
  <button type="button" class="df-x" data-close>×</button>
</div></div>
<script>
(function(){var m=document.getElementById('pvArasM'),b=document.getElementById('pvAras');if(!m||!b)return;
  b.onclick=function(){m.hidden=false};
  m.addEventListener('mousedown',function(e){if(e.target===m)m.hidden=true});
  [].forEach.call(m.querySelectorAll('[data-close]'),function(x){x.onclick=function(){m.hidden=true}});
  document.addEventListener('keydown',function(e){if(e.key==='Escape')m.hidden=true});
  var c=document.getElementById('kgCod'),t=document.getElementById('kgType'),n=document.getElementById('kgPieces'),py=document.getElementById('kgPayor');
  function rules(){
    var file=t.value==='file';n.readOnly=file;if(file)n.value=1;                       /* Aras: dosya gönderileri tek parça olabilir */
    var cod=c.value==='yes';[].forEach.call(m.querySelectorAll('.kg-cod'),function(r){r.hidden=!cod});
    if(cod){py.value='1'}py.querySelector('option[value="2"]').disabled=cod;           /* Aras: alıcı ödemeli tahsilatlı gönderi yapılamaz */
  }
  if(c){c.onchange=rules;t.onchange=rules;rules()}
  var f=document.getElementById('kgF');
  if(f)f.addEventListener('submit',function(){var s=f.querySelector('button[type=submit]');setTimeout(function(){s.disabled=true;s.textContent='Gönderiliyor...'},0)});
})();
</script>
