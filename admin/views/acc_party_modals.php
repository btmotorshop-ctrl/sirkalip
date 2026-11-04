<?php
/** Cari kartı pencereleri: Bakiye Değiştirme, Borç-Alacak Fişleri, Cari Virman, Temassız POS bilgisi (BizimHesap tarzı) */
$pk = $isCust ? 'müşteri' : 'tedarikçi';
$others = acc_q('SELECT id, name, kind FROM acc_parties WHERE id <> ? AND is_active = 1 ORDER BY name', [(int)$p['id']])->fetchAll();
$today = date('Y-m-d');
$action = aurl('acc/party-adjust');
$hdr = function ($t) { return '<div class="df-mh">' . e($t) . '<button type="button" class="pm-x" data-close aria-label="Kapat">×</button></div>'; };
?>
<!-- Bakiye Değiştirme -->
<div class="df-modal" id="pmBal" hidden><div class="df-mbox pm-box">
  <?= $hdr('Bakiye Değiştirme') ?>
  <form method="post" action="<?= $action ?>"><?= csrf_field() ?><input type="hidden" name="party_id" value="<?= (int)$p['id'] ?>"><input type="hidden" name="type" value="balance">
    <div class="pm-b">
      <div class="pm-r"><label>Güncel Bakiye (TL)</label><input name="balance" class="df-i" inputmode="decimal" autocomplete="off" value="<?= e(number_format($bal, 2, ',', '.')) ?>"></div>
      <div class="pm-r top"><label>Açıklama</label><textarea name="note" rows="3" maxlength="250" class="df-i"></textarea></div>
    </div>
    <div class="df-mf"><button class="pm-save rd" type="submit"><?= icon('check', '') ?> Kaydet</button></div>
  </form>
</div></div>

<!-- Borç-Alacak Fişleri -->
<div class="df-modal" id="pmRec" hidden><div class="df-mbox pm-box">
  <?= $hdr('Borç-Alacak Fişleri') ?>
  <form method="post" action="<?= $action ?>"><?= csrf_field() ?><input type="hidden" name="party_id" value="<?= (int)$p['id'] ?>"><input type="hidden" name="type" value="receipt">
    <div class="pm-note">Herhangi bir tahsilat, ödeme, satış ya da iade işlemi olmadan <?= $pk ?>nin bakiyesini değiştirmek için borç ya da alacak fişi kaydı oluşturabilirsiniz.<br>
      <?= ucfirst($pk) ?>nin güncel borç bakiyesi buraya gireceğiniz tutar kadar değişecek ve ekstresine yansıyacaktır.</div>
    <div class="pm-b">
      <div class="pm-r"><label>İşlem Tipi</label><div><select name="rtype" id="pmRType" class="df-i"><option value="1">Alacak Fişi</option><option value="2">Borç Fişi</option></select>
        <small class="pm-hint" id="pmRHint"><?= $pk ?> alacaklanacak</small></div></div>
      <div class="pm-r"><label>İşlem Tarihi</label><input type="date" name="date" class="df-i" value="<?= $today ?>"></div>
      <div class="pm-r"><label>Vade Tarihi</label><input type="date" name="due_date" class="df-i" value="<?= $today ?>"></div>
      <div class="pm-r"><label>Tutar</label><div><input name="amount" id="pmRAm" class="df-i" inputmode="decimal" autocomplete="off"><small class="pm-hint pm-words" id="pmRWords"></small></div></div>
      <div class="pm-r top"><label>Açıklama</label><textarea name="note" rows="3" maxlength="250" class="df-i"></textarea></div>
    </div>
    <div class="df-mf"><button type="button" class="pm-save or" data-close><?= icon('x', '') ?> Vazgeç</button><button class="pm-save gr" type="submit"><?= icon('check', '') ?> Kaydet</button></div>
  </form>
</div></div>

<!-- Cari Virman -->
<div class="df-modal" id="pmVir" hidden><div class="df-mbox pm-box">
  <?= $hdr('Cari Virman Fişleri') ?>
  <form method="post" action="<?= $action ?>" id="pmVForm"><?= csrf_field() ?><input type="hidden" name="party_id" value="<?= (int)$p['id'] ?>"><input type="hidden" name="type" value="virement">
    <div class="pm-b">
      <div class="pm-r"><label>İşlem Tipi</label><div><select name="vtype" id="pmVType" class="df-i"><option value="1">Bu cari alacaklansın</option><option value="2">Bu cari borçlansın</option></select>
        <small class="pm-hint" id="pmVHint">seçilen diğer cari borçlanacak</small></div></div>
      <div class="pm-r"><label>İşlem Tarihi</label><input type="date" name="date" class="df-i" value="<?= $today ?>"></div>
      <div class="pm-r"><label>Vade Tarihi</label><div><input type="date" name="due_date" class="df-i" value="<?= $today ?>"><small class="pm-hint">vade aşım hesabı için</small></div></div>
      <div class="pm-r top"><label>Seçilen Diğer Cari</label><div class="pm-pick">
        <input type="hidden" name="other_id" id="pmOther"><input type="search" id="pmQ" class="df-i" placeholder="Cari adı yazarak arayın..." autocomplete="off">
        <div class="pm-list" id="pmList"></div><div class="pm-sel" id="pmSel">Henüz cari seçilmedi.</div></div></div>
      <div class="pm-r"><label>Tutar</label><div><input name="amount" id="pmVAm" class="df-i" inputmode="decimal" autocomplete="off"><small class="pm-hint pm-words" id="pmVWords"></small></div></div>
      <div class="pm-r top"><label>Açıklama</label><textarea name="note" rows="3" maxlength="250" class="df-i"></textarea></div>
    </div>
    <div class="df-mf"><button type="button" class="pm-save or" data-close><?= icon('x', '') ?> Vazgeç</button><button class="pm-save gr" type="submit"><?= icon('check', '') ?> Kaydet</button></div>
  </form>
</div></div>

<!-- Temassız Kredi Kartı -->
<div class="df-modal" id="pmPos" hidden><div class="df-mbox pm-box">
  <?= $hdr('BizimHesap Temassız POS Tahsilatı') ?>
  <div class="pm-b" style="line-height:1.6;color:#33435b">
    <p><b>Telefonunuz POS cihazına dönüşüyor.</b> Mobil uygulama ile müşterilerinizden kredi kartı ile temassız tahsilat alma özelliği, mobil uygulama ve POS başvurusu tamamlandığında bu ekranda kullanıma açılacaktır.</p>
    <p style="color:#6b7889;font-size:13px">Şu an kart tahsilatlarını "Nakit - Kredi Kartı - Banka" ekranından, POS veya banka hesabınızı seçerek kaydedebilirsiniz.</p>
  </div>
  <div class="df-mf"><button type="button" class="pm-save or" data-close>Kapat</button></div>
</div></div>

<script>
(function(){
  var $=function(s,r){return (r||document).querySelector(s)},$$=function(s,r){return [].slice.call((r||document).querySelectorAll(s))};
  var pk='<?= $pk ?>',people=<?= json_encode(array_map(function ($o) { return ['id' => (int)$o['id'], 'n' => $o['name'], 'k' => $o['kind'] === 'customer' ? 'Müşteri' : 'Tedarikçi']; }, $others), JSON_UNESCAPED_UNICODE) ?>;
  function open(id){var m=$(id);if(m){m.hidden=false;var i=$('input:not([type=hidden]),textarea',m);if(i)setTimeout(function(){i.focus()},50)}}
  $$('[data-pm]').forEach(function(a){a.addEventListener('click',function(e){e.preventDefault();$$('.pv-dd').forEach(function(x){x.classList.remove('open')});open({bal:'#pmBal',rec:'#pmRec',vir:'#pmVir',pos:'#pmPos'}[a.dataset.pm])})});
  ['#pmBal','#pmRec','#pmVir','#pmPos'].forEach(function(id){var m=$(id);
    m.addEventListener('mousedown',function(e){if(e.target===m)m.hidden=true});
    $$('[data-close]',m).forEach(function(x){x.addEventListener('click',function(){m.hidden=true})})});
  document.addEventListener('keydown',function(e){if(e.key==='Escape')$$('#pmBal,#pmRec,#pmVir,#pmPos').forEach(function(m){m.hidden=true})});

  /* tutarın yazıyla gösterimi */
  function num(s){s=String(s||'').replace(/\s/g,'');if(s.indexOf(',')>-1)s=s.replace(/\./g,'').replace(',','.');var n=parseFloat(s);return isNaN(n)?0:n}
  function words(n){n=Math.round(n*100)/100;var l=Math.floor(n),k=Math.round((n-l)*100),b=['','bir','iki','üç','dört','beş','altı','yedi','sekiz','dokuz'],
    o=['','on','yirmi','otuz','kırk','elli','altmış','yetmiş','seksen','doksan'],g=['','bin','milyon','milyar'];
    function u(x){var s='',y=Math.floor(x/100),r=x%100;if(y)s+=(y>1?b[y]:'')+'yüz';return s+o[Math.floor(r/10)]+b[r%10]}
    var s='',i=0,t=l;if(!l)s='sıfır';else while(t>0){var p=t%1000;if(p){var w=u(p);if(i===1&&p===1)w='';s=w+g[i]+s}t=Math.floor(t/1000);i++}
    return 'Yalnız '+s+' lira'+(k?' '+u(k)+' kuruş':'')}
  [['#pmRAm','#pmRWords'],['#pmVAm','#pmVWords']].forEach(function(p){var a=$(p[0]),w=$(p[1]);a.addEventListener('input',function(){var n=num(a.value);w.textContent=n>0?words(n):''})});

  /* işlem tipi ipuçları */
  $('#pmRType').addEventListener('change',function(){$('#pmRHint').textContent=pk+(this.value==='1'?' alacaklanacak':' borçlanacak')});
  $('#pmVType').addEventListener('change',function(){$('#pmVHint').textContent='seçilen diğer cari '+(this.value==='1'?'borçlanacak':'alacaklanacak')});

  /* virman: diğer cari seçimi */
  var q=$('#pmQ'),list=$('#pmList'),sel=$('#pmSel'),oth=$('#pmOther');
  function norm(s){return s.toLocaleLowerCase('tr-TR')}
  function render(){var t=norm(q.value.trim()),h='',c=0;
    for(var i=0;i<people.length&&c<40;i++){var x=people[i];if(t&&norm(x.n).indexOf(t)<0)continue;c++;
      h+='<button type="button" data-id="'+x.id+'"><span>'+x.n.replace(/&/g,'&amp;').replace(/</g,'&lt;')+'</span><em>'+x.k+'</em></button>'}
    list.innerHTML=h||'<div class="pm-none">Eşleşen cari yok.</div>'}
  q.addEventListener('input',render);q.addEventListener('focus',function(){list.hidden=false;render()});
  list.addEventListener('click',function(e){var b=e.target.closest('button');if(!b)return;oth.value=b.dataset.id;
    var x=people.filter(function(p){return String(p.id)===b.dataset.id})[0];sel.textContent='Seçilen: '+x.n+' ('+x.k+')';sel.classList.add('on');list.hidden=true;q.value=''});
  list.hidden=true;
  $('#pmVForm').addEventListener('submit',function(e){if(!oth.value){e.preventDefault();sel.textContent='Lütfen virman yapılacak diğer cariyi seçin.';sel.classList.add('err');list.hidden=false;render()}});
})();
</script>
