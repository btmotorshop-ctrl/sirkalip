<?php
$isCust = $kind === 'customer';
$lbl = $isCust ? 'Müşteri' : 'Tedarikçi';
$title = $id ? $lbl . ' Düzenle' : 'Yeni ' . $lbl;
admin_view('layout_top', compact('title'));
$v = function ($k) use ($val) { return e($val[$k] ?? ''); };
$m = function ($k) use ($val) { $x = $val[$k] ?? ''; if ($x === '' || $x === null) return ''; return is_numeric($x) ? e(number_format((float)$x, 2, ',', '')) : e($x); };
$mon = function ($ic, $name) use ($m) { return '<div class="pf-in">' . icon($ic, '') . '<input type="text" name="' . $name . '" id="f_' . $name . '" inputmode="decimal" value="' . $m($name) . '"></div>'; };
$camera = '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"><path d="M14.5 4h-5L8 6H5a2 2 0 0 0-2 2v10a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V8a2 2 0 0 0-2-2h-3z"/><circle cx="12" cy="13" r="3.5"/></svg>';
$help = function ($t) { return '<i class="pf-help" data-tip="' . e($t) . '">?</i>'; };
$inp = function ($ic, $name, $type = 'text', $extra = '') use ($val) {
    return '<div class="pf-in">' . icon($ic, '') . '<input type="' . $type . '" name="' . $name . '" id="f_' . $name . '" value="' . e($val[$name] ?? '') . '" ' . $extra . '></div>';
};
?>
<form method="post" enctype="multipart/form-data" id="pfForm" novalidate>
  <?= csrf_field() ?>
  <div class="pf-bar">
    <button type="submit" class="pl-btn gr"><?= icon('check', 'h-4 w-4') ?> Kaydet</button>
    <a href="<?= $back ?>" class="pl-btn bl"><?= icon('undo2', 'h-4 w-4') ?> Geri Dön</a>
    <button type="button" class="pl-btn pu" id="pfFind"><?= icon('search', 'h-4 w-4') ?> VKN/TCKN ile Cari Arama</button>
    <span class="pf-found" id="pfFound" hidden></span>
  </div>
  <?php foreach ($errors as $er): ?><div class="pf-err"><?= e($er) ?></div><?php endforeach; ?>

  <div class="pf-card">
    <div class="pf-tabs" role="tablist">
      <button type="button" data-t="about" class="on"><?= icon('user', '') ?> KİMLİK BİLGİLERİ</button>
      <button type="button" data-t="contact"><?= icon('mail', '') ?> İLETİŞİM</button>
      <button type="button" data-t="payment"><?= icon('banknote', '') ?> CARİ</button>
      <button type="button" data-t="other"><?= icon('layout-grid', '') ?> DİĞER</button>
      <button type="button" data-t="branch"><?= icon('store', '') ?> ŞUBELER <b id="pfBrCount"><?= count($branches) ?: '' ?></b></button>
    </div>

    <!-- KİMLİK -->
    <section class="pf-pane on" data-p="about">
      <div class="pf-grid">
        <div>
          <label class="pf-l" for="f_name">İsmi / Unvanı <em>*</em></label>
          <?= $inp('user', 'name', 'text', 'autocomplete="off" required') ?>
          <?php if (!empty($errors['name'])): ?><div class="pf-e"><?= e($errors['name']) ?></div><?php endif; ?>
        </div>
        <div>
          <div class="pf-photo" id="pfPhoto">
            <input type="file" name="image" id="pfFile" accept="image/*" hidden>
            <input type="hidden" name="image__remove" id="pfRem" value="">
            <div class="pf-ph-box" id="pfBox" onclick="document.getElementById('pfFile').click()">
              <img id="pfImg" alt="" <?= !empty($val['image']) ? 'src="' . e(media_url($val['image'])) . '"' : 'hidden' ?>>
              <span id="pfCam" <?= !empty($val['image']) ? 'hidden' : '' ?>><?= $camera ?><b>Resim Ekle</b></span>
            </div>
            <button type="button" class="pf-ph-del" id="pfDel" <?= empty($val['image']) ? 'hidden' : '' ?>><?= icon('trash2', 'h-4 w-4') ?> Resmi Sil</button>
            <?php if (!empty($errors['image'])): ?><div class="pf-e"><?= e($errors['image']) ?></div><?php endif; ?>
          </div>
        </div>
      </div>
    </section>

    <!-- İLETİŞİM -->
    <section class="pf-pane" data-p="contact">
      <div class="pf-grid">
        <div>
          <label class="pf-l" for="f_email">E-Posta</label>
          <?= $inp('mail', 'email', 'text', 'autocomplete="off"') ?>
          <small class="pf-s">virgül ile ayırarak birden fazla adres girebilirsiniz.</small>
          <label class="pf-l" for="f_phone">Cep Telefonu</label>
          <?= $inp('phone', 'phone', 'tel', 'autocomplete="off"') ?>
          <div style="height:10px"></div>
          <?= $inp('phone', 'phone2', 'tel', 'autocomplete="off"') ?>
          <label class="pf-l" for="f_other_contact">Diğer Erişim Bilgileri</label>
          <textarea name="other_contact" id="f_other_contact" class="pf-ta" placeholder="Sabit telefon, faks vb"><?= $v('other_contact') ?></textarea>
        </div>
        <div>
          <label class="pf-l" for="f_contact_person">Yetkili Kişi</label>
          <?= $inp('user', 'contact_person', 'text', 'autocomplete="off"') ?>
          <label class="pf-l" for="f_address">Adres</label>
          <textarea name="address" id="f_address" class="pf-ta" rows="4"><?= $v('address') ?></textarea>
          <?php $hasDel = !empty($val['delivery_address']); ?>
          <a href="#" class="pf-link" id="pfDelToggle" <?= $hasDel ? 'hidden' : '' ?>>farklı sevk adresi ekle</a>
          <div id="pfDelWrap" <?= $hasDel ? '' : 'hidden' ?>>
            <label class="pf-l" for="f_delivery_address">Sevk Adresi</label>
            <textarea name="delivery_address" id="f_delivery_address" class="pf-ta" rows="3"><?= $v('delivery_address') ?></textarea>
          </div>
        </div>
      </div>
    </section>

    <!-- CARİ -->
    <section class="pf-pane" data-p="payment">
      <div class="pf-grid">
        <div>
          <label class="pf-l" for="f_tax_office">Vergi Dairesi</label>
          <?= $inp('home', 'tax_office', 'text', 'autocomplete="off"') ?>
          <label class="pf-l" for="f_tax_no">Vergi / TC Kimlik No</label>
          <?= $inp('file-text', 'tax_no', 'text', 'autocomplete="off" inputmode="numeric" maxlength="11"') ?>
          <label class="pf-chk"><input type="checkbox" name="tax_free" value="1" <?= !empty($val['tax_free']) ? 'checked' : '' ?>> <span>Vergiden muaf?</span> <?= $help('Vergiden muaf (ör. ihracat, kamu kurumu) olan cariler için işaretleyin.') ?></label>
          <label class="pf-l" for="f_bank_info">Banka Bilgileri</label>
          <textarea name="bank_info" id="f_bank_info" class="pf-ta" rows="4" placeholder="<?= strtolower($lbl) === 'müşteri' ? 'müşterinizin' : 'tedarikçinizin' ?> banka hesap bilgilerini girebilirsiniz"><?= $v('bank_info') ?></textarea>
        </div>
        <div>
          <label class="pf-l"><?= $lbl ?> Para Birimi <?= $help('Bu cariyle çalışırken kullanılacak para birimi.') ?></label>
          <select name="currency" class="pf-sel"><?php foreach (acc_party_currencies() as $c => $cl): ?><option value="<?= $c ?>" <?= ($val['currency'] ?? 'TRY') === $c ? 'selected' : '' ?>><?= e($cl) ?></option><?php endforeach; ?></select>
          <label class="pf-l" for="f_risk_limit">Açık Hesap Risk Limiti <?= $help('Açık hesap bakiyesinin ulaşabileceği üst sınır. Boş bırakırsanız sınırsızdır.') ?></label>
          <?= $mon('banknote', 'risk_limit') ?>
          <label class="pf-l" for="f_due_days">Vadesi (gün) <?= $help('Bu cariye yapılan satış/alışlarda varsayılan vade günü.') ?></label>
          <?= $inp('calendar', 'due_days', 'text', 'inputmode="numeric"') ?>
          <label class="pf-l" for="f_discount">Sabit İskonto (%) <?= $help('Bu cariye uygulanacak sabit iskonto yüzdesi.') ?></label>
          <?= $inp('percent', 'discount', 'text', 'inputmode="decimal"') ?>
          <label class="pf-l">Özel Fiyat Listesi <?= $help('Bu cariye özel fiyat listesi uygulamak için (fiyat listeleri bir sonraki adımda eklenecek).') ?></label>
          <select class="pf-sel" disabled><option>Özel fiyat uygulanmasın</option></select>
          <label class="pf-l" for="f_opening_balance">Açılış Bakiyesi <?= $help($isCust ? 'Müşterinin size olan açık hesap borcu artı, sizin müşteriye borcunuz eksi girilir.' : 'Tedarikçiye olan borcunuz artı, tedarikçinin size borcu eksi girilir.') ?></label>
          <?= $mon('banknote', 'opening_balance') ?>
          <small class="pf-s"><?= $isCust ? 'müşteri sizden alacaklı ise eksi girin' : 'tedarikçi sizden alacaklı ise eksi girin' ?></small>
        </div>
      </div>
    </section>

    <!-- DİĞER -->
    <section class="pf-pane" data-p="other">
      <div class="pf-grid">
        <div>
          <?php foreach ([1, 2] as $slot): ?>
            <label class="pf-l">Sınıflandırma <?= $slot ?> <?= $help('Cari kartlarını gruplamak için kendi sınıflandırmalarınızı oluşturabilirsiniz.') ?></label>
            <button type="button" class="pf-add" data-slot="<?= $slot ?>"><?= icon('plus', 'h-3 w-3') ?> yeni sınıflandırma ekle</button>
            <select name="class<?= $slot ?>" id="class<?= $slot ?>" class="pf-sel">
              <option value=""></option>
              <?php foreach ($classes[$slot] as $c): ?><option value="<?= $c['id'] ?>" <?= (int)($val['class' . $slot] ?? 0) === (int)$c['id'] ? 'selected' : '' ?>><?= e($c['name']) ?></option><?php endforeach; ?>
            </select>
          <?php endforeach; ?>
          <label class="pf-l" for="f_code">Kodu <?= $help('Varsa muhasebe kodu veya barkod girebilirsiniz.') ?></label>
          <?= $inp('tags', 'code', 'text', 'placeholder="varsa muhasebe kodu veya barkod girebilirsiniz" autocomplete="off"') ?>
        </div>
        <div>
          <label class="pf-l">Yetkili Çalışanlar</label>
          <div class="pf-ms" id="pfMs">
            <button type="button" class="pf-ms-b" id="pfMsB">çalışan seçin... (isteğe bağlı)</button>
            <div class="pf-ms-l" hidden>
              <?php foreach ($users as $uid => $un): ?><label><input type="checkbox" name="agents[]" value="<?= (int)$uid ?>" <?= in_array((int)$uid, array_map('intval', $sel), true) ? 'checked' : '' ?>> <?= e($un) ?></label><?php endforeach; ?>
              <?php if (!$users): ?><div class="pf-s" style="padding:8px 12px">Tanımlı çalışan yok.</div><?php endif; ?>
            </div>
          </div>
          <small class="pf-s">Dilerseniz bu <?= strtolower($lbl) ?>yi sadece seçtiğiniz çalışanların görmesini sağlayabilirsiniz.</small>
          <label class="pf-l" for="f_note">Not</label>
          <textarea name="note" id="f_note" class="pf-ta" rows="6"><?= $v('note') ?></textarea>
          <label class="pf-chk" style="margin-top:14px"><input type="checkbox" name="is_inactive" value="1" <?= empty($val['is_active']) ? 'checked' : '' ?>> <span>Pasif <?= strtolower($lbl) ?> (listelerde varsayılan olarak gizlenir)</span></label>
        </div>
      </div>
    </section>

    <!-- ŞUBELER -->
    <section class="pf-pane" data-p="branch">
      <div class="pf-note">Bu <?= strtolower($lbl) ?>nin birden fazla şubesi varsa, her şube adresini burada ayrı ayrı ekleyebilirsiniz.<br>Satış ekranında dilediğiniz şubeyi seçerek işlem yapabilirsiniz.<br>Şubelere yaptığınız satışların carisi bu <?= strtolower($lbl) ?>ye yansıyacaktır.</div>
      <button type="button" class="pl-btn gr pf-sm" id="pfBrAdd"><?= icon('plus', 'h-4 w-4') ?> yeni şube ekle</button>
      <table class="pf-br" id="pfBr">
        <thead><tr><th>Şube Adı</th><th>Adres</th><th>Kodu</th><th></th></tr></thead>
        <tbody>
          <?php foreach ($branches as $b): ?>
          <tr><td><input name="branch_name[]" value="<?= e($b['name']) ?>"></td><td><input name="branch_addr[]" value="<?= e($b['address']) ?>"></td><td><input name="branch_code[]" value="<?= e($b['code']) ?>"></td><td><button type="button" class="pf-x" title="Sil">×</button></td></tr>
          <?php endforeach; ?>
        </tbody>
      </table>
      <div class="pf-s" id="pfBrNone" <?= $branches ? 'hidden' : '' ?> style="margin-top:12px">Henüz şube eklenmedi.</div>
    </section>
  </div>
</form>
<script>
(function(){
  var $=function(s,r){return (r||document).querySelector(s)},$$=function(s,r){return [].slice.call((r||document).querySelectorAll(s))};
  var csrf=$('input[name=_csrf]').value;
  // sekmeler
  function tab(n){$$('.pf-tabs button').forEach(function(b){b.classList.toggle('on',b.dataset.t===n)});$$('.pf-pane').forEach(function(p){p.classList.toggle('on',p.dataset.p===n)});try{history.replaceState(null,'','#'+n)}catch(e){}}
  $$('.pf-tabs button').forEach(function(b){b.addEventListener('click',function(){tab(b.dataset.t)})});
  var h=(location.hash||'').slice(1);if(h&&$('.pf-pane[data-p="'+h+'"]'))tab(h);
  // doğrulama
  $('#pfForm').addEventListener('submit',function(e){var n=$('#f_name');if(!n.value.trim()){e.preventDefault();tab('about');n.classList.add('bad');n.focus()}});
  $('#f_name').addEventListener('input',function(){this.classList.remove('bad')});
  // resim
  var f=$('#pfFile'),img=$('#pfImg'),cam=$('#pfCam'),del=$('#pfDel'),rem=$('#pfRem');
  f.addEventListener('change',function(){if(f.files[0]){img.src=URL.createObjectURL(f.files[0]);img.hidden=false;cam.hidden=true;del.hidden=false;rem.value=''}});
  del.addEventListener('click',function(){f.value='';img.hidden=true;img.removeAttribute('src');cam.hidden=false;del.hidden=true;rem.value='1'});
  // sevk adresi
  $('#pfDelToggle').addEventListener('click',function(e){e.preventDefault();this.hidden=true;$('#pfDelWrap').hidden=false;$('#f_delivery_address').focus()});
  // sınıflandırma ekle
  $$('.pf-add').forEach(function(b){b.addEventListener('click',function(){
    var n=prompt('Yeni sınıflandırma adı:');if(!n||!n.trim())return;
    var fd=new FormData();fd.append('_csrf',csrf);fd.append('slot',b.dataset.slot);fd.append('name',n.trim());
    fetch('<?= aurl('acc/class-add') ?>',{method:'POST',body:fd}).then(function(r){return r.json()}).then(function(j){
      if(!j.ok)return;var s=$('#class'+b.dataset.slot),o=[].find.call(s.options,function(x){return x.value==j.id});
      if(!o){o=new Option(j.name,j.id);s.add(o)}s.value=j.id});
  })});
  // çalışan seçimi
  var ms=$('#pfMs'),mb=$('#pfMsB'),ml=$('.pf-ms-l',ms);
  function msTxt(){var c=$$('input:checked',ml).length;mb.textContent=c?c+' çalışan seçildi':'çalışan seçin... (isteğe bağlı)'}
  mb.addEventListener('click',function(){ml.hidden=!ml.hidden});document.addEventListener('click',function(e){if(!ms.contains(e.target))ml.hidden=true});
  ml.addEventListener('change',msTxt);msTxt();
  // şubeler
  var tb=$('#pfBr tbody');
  function brSync(){var n=tb.rows.length;$('#pfBrCount').textContent=n||'';$('#pfBrNone').hidden=n>0}
  $('#pfBrAdd').addEventListener('click',function(){var r=tb.insertRow();r.innerHTML='<td><input name="branch_name[]"></td><td><input name="branch_addr[]"></td><td><input name="branch_code[]"></td><td><button type="button" class="pf-x" title="Sil">×</button></td>';r.querySelector('input').focus();brSync()});
  tb.addEventListener('click',function(e){if(e.target.classList.contains('pf-x')){e.target.closest('tr').remove();brSync()}});
  // VKN/TCKN ile arama
  $('#pfFind').addEventListener('click',function(){
    var q=($('#f_tax_no').value||'').trim(),box=$('#pfFound');
    if(!q){tab('payment');$('#f_tax_no').focus();box.hidden=false;box.className='pf-found warn';box.textContent='Önce Cari sekmesinden Vergi / TC Kimlik No girin.';return}
    fetch('<?= aurl('acc/party-find') ?>?q='+encodeURIComponent(q)).then(function(r){return r.json()}).then(function(j){
      box.hidden=false;
      if(!j.ok){box.className='pf-found warn';box.textContent=j.msg;return}
      if(j.found){box.className='pf-found warn';box.innerHTML='Bu numara ile kayıtlı cari var: <a href="'+j.url+'">'+j.name.replace(/</g,'&lt;')+'</a>'}
      else{box.className='pf-found ok';box.textContent='Bu numara ile kayıtlı cari bulunamadı, yeni kayıt açabilirsiniz.'}
    });
  });
})();
</script>
<?php admin_view('layout_bottom'); ?>
