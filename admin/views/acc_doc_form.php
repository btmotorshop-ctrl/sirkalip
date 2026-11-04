<?php
$isProp = $kind === 'proposal';
$isSale = $kind !== 'purchase';
$title = !empty($editDoc) ? 'Teklifi Düzenle – ' . $editDoc['doc_no'] : ($isProp ? 'Teklif Hazırla' : ($isSale ? 'Satış Yap' : 'Alış Yap'));
admin_view('layout_top', compact('title'));
$back = !empty($row['party_id']) ? aurl('acc/party/' . $row['party_id']) : aurl($isProp ? 'acc/docs/proposal' : "acc/docs/$kind");
$v = function ($k) use ($row) { return e($row[$k] ?? ''); };
$pName = $party['name'] ?? '';
$jsLines = array_map(function ($l) { return ['pid' => $l['product_id'], 'name' => $l['name'], 'qty' => $l['qty'], 'unit' => $l['unit'], 'price' => $l['price'], 'disc' => $l['disc'], 'vat' => $l['vat'], 'desc' => $l['desc'], 'cost' => 0]; }, $lines0);
$info = $party ? [
  'Açık bakiye' => acc_money($partyBal ?? 0),
  'Risk limiti' => $party['risk_limit'] !== null && $party['risk_limit'] !== '' ? acc_money($party['risk_limit']) : 'Sınırsız',
  'Vade' => ($party['due_days'] !== null && $party['due_days'] !== '') ? (int)$party['due_days'] . ' gün' : '—',
  'Sabit iskonto' => ($party['discount'] !== null && $party['discount'] !== '') ? '%' . rtrim(rtrim(number_format((float)$party['discount'], 2, ',', ''), '0'), ',') : '—',
  'Para birimi' => acc_party_currencies()[$party['currency'] ?? 'TRY'] ?? 'TL',
] : [];
$riskLimit = ($party && $party['risk_limit'] !== null && $party['risk_limit'] !== '') ? (float)$party['risk_limit'] : null;
$fixedDisc = ($party && $party['discount'] !== null && $party['discount'] !== '') ? (float)$party['discount'] : 0;
?>
<form method="post" id="dfForm" novalidate autocomplete="off">
  <?= csrf_field() ?>
  <input type="hidden" name="action" id="dfAct" value="final">
  <input type="hidden" name="party_id" id="dfPid" value="<?= (int)($row['party_id'] ?: 0) ?>">
  <input type="hidden" name="footer_discount" id="dfFd" value="0">
  <div class="df-bar">
<?php if ($isProp): ?>
    <button type="button" class="pl-btn gr" data-act="offer"><?= icon('file-text', 'h-4 w-4') ?> Teklifi Kaydet</button>
<?php else: ?>
    <button type="button" class="pl-btn gr" data-act="proforma"><?= icon('file-text', 'h-4 w-4') ?> Proforma/Sipariş Kaydet</button>
    <button type="button" class="pl-btn bl" data-soon="İrsaliye kaydı bir sonraki adımlarda eklenecek."><?= icon('truck', 'h-4 w-4') ?> İrsaliye Kaydet</button>
    <button type="button" class="pl-btn rd" data-act="final"><?= icon('zap', 'h-4 w-4') ?> <?= $isSale ? 'Fatura Kaydet' : 'Alış Faturası Kaydet' ?></button>
<?php endif; ?>
    <a href="<?= $back ?>" class="pl-btn or"><?= icon('undo2', 'h-4 w-4') ?> Geri Dön</a>
  </div>
  <?php if ($err): ?><div class="pf-err"><?= e($err) ?></div><?php endif; ?>

  <div class="df-cols">
  <div class="df-left">
  <div class="df-card">
    <div class="df-head blue">
      <?php if ($party): ?><span id="dfName"><?= e($pName) ?></span>
      <?php else: ?><input list="dfParties" id="dfPick" class="df-pick" placeholder="<?= $isSale ? ($isProp ? 'Müşteri seçin' : 'Müşteri seçin (boşsa perakende)') : 'Tedarikçi seçin' ?>..." value=""><datalist id="dfParties"><?php foreach ($parties as $pid => $pn): ?><option value="<?= e($pn) ?>" data-id="<?= (int)$pid ?>"></option><?php endforeach; ?></datalist><?php endif; ?>
      <?php if ($info): ?><span class="df-info" tabindex="0"><?= icon('circle-help', '') ?>
        <span class="df-pop"><?php foreach ($info as $k => $val): ?><span><em><?= e($k) ?></em><b><?= e($val) ?></b></span><?php endforeach; ?></span></span><?php endif; ?>
    </div>
    <div class="df-body">
<?php if ($isProp): ?>
      <div class="df-r"><label>Teklif No</label><div><input name="doc_no" class="df-i w1" value="<?= $v('doc_no') ?>" placeholder="otomatik"></div></div>
      <div class="df-r"><label>Teklif Tarihi</label><div><input type="date" name="doc_date" class="df-i w2" value="<?= $v('doc_date') ?>"></div></div>
      <div class="df-r"><label>Geçerlilik</label><div><input type="date" name="due_date" class="df-i w2" value="<?= $v('due_date') ?>"></div></div>
<?php else: ?>
      <div class="df-r"><label>Belge No</label><div><input name="doc_no" class="df-i w1" value="<?= $v('doc_no') ?>" placeholder="otomatik"></div></div>
      <div class="df-r"><label>Tarihi</label><div class="df-g"><input type="date" name="doc_date" class="df-i w2" value="<?= $v('doc_date') ?>"><input type="time" name="doc_time" class="df-i w3" value="<?= $v('doc_time') ?>"></div></div>
      <div class="df-r"><label>Vadesi</label><div><input type="date" name="due_date" class="df-i w2" value="<?= $v('due_date') ?>"></div></div>
      <div class="df-r"><label>Sipariş Tarihi-No</label><div class="df-g"><input type="date" name="order_date" class="df-i w2" value="<?= $v('order_date') ?>"><input name="order_no" class="df-i w3" value="<?= $v('order_no') ?>" placeholder="sipariş no"></div></div>
      <div class="df-r"><label>İrsaliye No</label><div><input name="waybill_no" class="df-i w1" value="<?= $v('waybill_no') ?>"></div></div>
      <div class="df-r"><label>Sevk Tarihi</label><div><input type="date" name="ship_date" class="df-i w2" value="<?= $v('ship_date') ?>"></div></div>
<?php endif; ?>
      <div class="df-r top"><label>Açıklama</label><div><textarea name="note" class="df-i w4" rows="4"><?= $v('note') ?></textarea></div></div>
    </div>
  </div>

  </div>
  <div class="df-right">
  <div class="df-card">
    <div class="df-head green">ÜRÜN / HİZMETLER</div>
    <div class="df-body">
      <div class="df-search">
        <input type="search" id="dfQ" class="df-q" placeholder="Ürün isminden arayın veya barkod okutun" autocomplete="off">
        <div class="df-res" id="dfRes" hidden></div>
      </div>
      <a href="#" class="df-free" id="dfFree">listede olmayan ürün eklemek için tıklayın</a>
      <div class="df-tw"><table class="df-t" id="dfT" hidden>
        <thead><tr><th>#</th><th class="l">Açıklama</th><th>Miktar</th><th>Fiyat</th><th>Tutar</th><th>İndirim</th><th>Net</th><th>KDV</th><th>Toplam</th><th>Tevkifat/<br>Ek Vergi</th><th>Sil</th></tr></thead>
        <tbody></tbody>
      </table></div>
      <div class="df-none" id="dfNone">Henüz ürün eklenmedi. Yukarıdan arayarak ekleyin.</div>

      <div class="df-foot" id="dfFoot" hidden>
        <div class="df-fl">
          <?php if (!$isProp): ?><label class="pf-chk"><input type="checkbox" name="move_stock" value="1" checked> <span><?= $isSale ? 'Stoktan düş' : 'Stoğa ekle' ?> (fatura kaydedilince)</span></label><?php endif; ?>
          <div class="df-cur">
            <label>Para Birimi</label>
            <select name="currency" id="dfCur" class="df-i"><?php foreach (['TRY' => 'TL', 'USD' => 'USD', 'EUR' => 'EUR'] as $c => $cl): ?><option value="<?= $c ?>" <?= $row['currency'] === $c ? 'selected' : '' ?>><?= $cl ?></option><?php endforeach; ?></select>
            <label>Kur</label>
            <input name="rate" id="dfRate" class="df-i" value="<?= e(number_format((float)$row['rate'], 4, ',', '')) ?>" inputmode="decimal">
          </div>
        </div>
        <div class="df-sum" id="dfSum"></div>
      </div>
    </div>
  </div>

  <?php if (!$isProp): ?>
  <div class="df-card" id="dfPayCard" hidden>
    <div class="df-head grey"><?= $isSale ? 'PEŞİN TAHSİLAT' : 'PEŞİN ÖDEME' ?> <small>(isteğe bağlı – fatura kaydedilirken)</small></div>
    <div class="df-body">
      <div class="df-r"><label>Hesap</label><div><select name="pay_account" class="df-i w4"><option value="">— seçiniz —</option><?php foreach ($accounts as $aid => $an): ?><option value="<?= (int)$aid ?>"><?= e($an) ?></option><?php endforeach; ?></select></div></div>
      <div class="df-r"><label>Tutar</label><div class="df-g"><input name="pay_amount" id="dfPay" class="df-i w2" inputmode="decimal" placeholder="0,00"><button type="button" class="df-all" id="dfPayAll">Tamamı</button></div></div>
    </div>
  </div>
  <?php endif; ?>
  </div>
  </div>
</form>

<!-- Ürün satırı penceresi -->
<div class="df-modal" id="dfM" hidden><div class="df-mbox">
  <div class="df-mh" id="dfMT">Ürün</div>
  <div class="df-mb">
    <div class="df-mrow" id="mNameRow"><label style="flex:1">Ürün / Hizmet adı<input id="mName" class="df-i" style="width:100%"></label></div>
    <div class="df-mrow">
      <label><b class="lt">Miktar (<span id="mUnitL">Ad</span>)</b><input id="mQty" class="df-i" inputmode="decimal" value="1"></label>
      <label>Birim<select id="mUnit" class="df-i"><option>Ad</option><option>Kg</option><option>Mt</option><option>Lt</option><option>Kutu</option><option>Takım</option><option>Paket</option></select></label>
      <label class="grow">Depo<select class="df-i" disabled><option id="mStock">Ana Depo</option></select></label>
    </div>
    <div class="df-mrow">
      <label>Fiyat<input id="mPrice" class="df-i" inputmode="decimal"></label>
      <label>KDV(%)<select id="mVat" class="df-i"><option>0</option><option>1</option><option>10</option><option selected>20</option></select></label>
      <label>İndirim<span class="df-g"><input id="mDisc" class="df-i" inputmode="decimal" value="0" style="width:70px"><select id="mDT" class="df-i"><option value="pct">%</option><option value="amt">TL</option></select></span></label>
      <div class="df-mtot"><span>TOPLAM</span><b id="mTot">0,00</b><small id="mTotS"></small></div>
    </div>
    <div class="df-mlinks"><a href="#" id="mPrev">önceki fiyatlar</a><a href="#" data-soon="Çoklu iskonto bir sonraki adımlarda eklenecek.">çoklu iskonto</a></div>
    <div class="df-mrow"><label style="flex:1">Açıklama<textarea id="mDesc" class="df-i" rows="2" style="width:100%" placeholder="isteğe bağlı açıklama girebilirsiniz"></textarea></label></div>
  </div>
  <div class="df-mf"><button type="button" class="pl-btn gr" id="mAdd"><?= icon('plus', 'h-4 w-4') ?> <span>Ekle</span></button><button type="button" class="df-link" data-close>Vazgeç</button></div>
  <button type="button" class="df-x" data-close>×</button>
</div></div>

<!-- Önceki fiyatlar -->
<div class="df-modal" id="dfP" hidden><div class="df-mbox wide">
  <div class="df-mh">Önceki Fiyatlar</div>
  <div class="df-mb"><div class="df-tabs"><button type="button" class="on" data-pt="party"><?= $isSale ? 'Bu müşteriye satışlar' : 'Bu tedarikçiden alışlar' ?></button><button type="button" data-pt="all"><?= $isSale ? 'Tüm satışlar' : 'Tüm alışlar' ?></button></div>
    <table class="df-t small"><thead><tr><th class="l">Tarih</th><th class="l">Cari</th><th>Fiyat</th><th>Kur</th></tr></thead><tbody id="pBody"></tbody></table></div>
  <div class="df-mf"><button type="button" class="df-link" data-close>Kapat</button></div><button type="button" class="df-x" data-close>×</button>
</div></div>

<!-- Fatura altı iskonto -->
<div class="df-modal" id="dfD" hidden><div class="df-mbox">
  <div class="df-mh">Fatura Altı İskonto</div>
  <div class="df-mb">
    <div class="df-mrow"><label style="flex:1">Mevcut Tutar (net)<input id="dCur" class="df-i" style="width:100%" disabled></label></div>
    <div class="df-mrow"><label>% İndirim Girin<input id="dPct" class="df-i" inputmode="decimal"></label><label>Yeni Tutar Girin<input id="dNew" class="df-i" inputmode="decimal"></label></div>
  </div>
  <div class="df-mf"><button type="button" class="pl-btn rd" id="dRem">İndirimi Kaldır</button><button type="button" class="pl-btn gr" id="dApply">Uygula</button><button type="button" class="df-link" data-close>Vazgeç</button></div><button type="button" class="df-x" data-close>×</button>
</div></div>

<!-- Uyarı / risk -->
<div class="df-modal" id="dfA" hidden><div class="df-mbox">
  <div class="df-mh" id="aT">Dikkat</div><div class="df-mb"><p id="aM" style="font-size:16px;margin:6px 0"></p></div>
  <div class="df-mf"><button type="button" class="pl-btn gr" id="aOk">Tamam</button><button type="button" class="df-link" id="aNo" hidden>Vazgeç</button></div>
</div></div>
<div class="pl-toast" id="plToast"></div>

<script>
(function(){
  var $=function(s,r){return (r||document).querySelector(s)},$$=function(s,r){return [].slice.call((r||document).querySelectorAll(s))};
  var KIND='<?= $kind ?>',RATES=<?= json_encode($rates) ?>,PARTY=<?= (int)($row['party_id'] ?: 0) ?>,BAL=<?= json_encode((float)($partyBal ?? 0)) ?>,RISK=<?= json_encode($riskLimit) ?>,DISC=<?= json_encode($fixedDisc) ?>;
  var L=<?= json_encode($jsLines, JSON_UNESCAPED_UNICODE) ?>,FD=0,cur=null;
  var fmt=function(n){return (n||0).toLocaleString('tr-TR',{minimumFractionDigits:2,maximumFractionDigits:2})};
  var num=function(s){s=String(s==null?'':s).trim().replace(/\s/g,'');if(s.indexOf(',')>-1)s=s.replace(/\./g,'').replace(',','.');var n=parseFloat(s);return isNaN(n)?0:n};
  var esc=function(s){return String(s==null?'':s).replace(/[&<>"]/g,function(c){return {'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;'}[c]})};
  var sym=function(){var c=$('#dfCur').value;return c==='TRY'?'TL':c};
  function show(m,on){m.hidden=!on}
  var tm;
  function toast(msg){var t=$('#plToast');t.textContent=msg;t.classList.add('on');clearTimeout(tm);tm=setTimeout(function(){t.classList.remove('on')},2800)}
  $$('[data-close]').forEach(function(b){b.addEventListener('click',function(){show(b.closest('.df-modal'),false)})});
  $$('.df-modal').forEach(function(m){m.addEventListener('mousedown',function(e){if(e.target===m)show(m,false)})});
  $$('[data-soon]').forEach(function(b){b.addEventListener('click',function(e){e.preventDefault();toast(b.dataset.soon)})});
  function alertBox(title,msg,ok,cancel){$('#aT').textContent=title;$('#aM').textContent=msg;$('#aNo').hidden=!cancel;show($('#dfA'),true);
    $('#aOk').onclick=function(){show($('#dfA'),false);if(ok)ok()};$('#aNo').onclick=function(){show($('#dfA'),false)}}

  // ---- hesaplama
  function calc(){var t={q:0,gross:0,disc:0,net:0,vat:{},vatT:0};
    L.forEach(function(l){var g=l.qty*l.price,d=Math.min(g,l.disc),n=g-d;l._g=g;l._d=d;l._n=n;l._v=n*l.vat/100;l._t=n+l._v;
      t.q+=l.qty;t.gross+=g;t.disc+=d;t.net+=n;t.vat[l.vat]=(t.vat[l.vat]||0)+l._v});
    var fd=Math.min(FD,t.net),k=t.net>0?(t.net-fd)/t.net:1;t.fd=fd;t.netAfter=t.net-fd;
    Object.keys(t.vat).forEach(function(r){t.vat[r]*=k;t.vatT+=t.vat[r]});
    t.total=t.netAfter+t.vatT;return t}
  function render(){
    var S=sym(),tb=$('#dfT tbody');tb.innerHTML='';
    var t=calc();
    L.forEach(function(l,i){var tr=document.createElement('tr');
      tr.innerHTML='<td>'+(i+1)+'</td><td class="l"><a href="#" class="ed">'+esc(l.name)+'</a>'+(l.desc?'<small>'+esc(l.desc)+'</small>':'')+'</td><td>'+fmt(l.qty).replace(/,00$/,'')+' '+esc(l.unit)+'</td><td>'+fmt(l.price)+'</td><td>'+fmt(l._g)+'</td><td>'+fmt(l._d)+'</td><td>'+fmt(l._n)+'</td><td>'+fmt(l._v)+'</td><td><b>'+fmt(l._t)+'</b></td><td><button type="button" class="df-tv">▾</button></td><td><button type="button" class="df-del" title="Sil">✕</button></td>';
      tr.querySelector('.ed').onclick=function(e){e.preventDefault();openLine(null,i)};
      tr.querySelector('.df-del').onclick=function(){L.splice(i,1);render()};
      tr.querySelector('.df-tv').onclick=function(){toast('Tevkifat / ek vergi bir sonraki adımlarda eklenecek.')};
      tb.appendChild(tr)});
    var has=L.length>0;
    $('#dfT').hidden=!has;$('#dfNone').hidden=has;$('#dfFoot').hidden=!has;var pc=$('#dfPayCard');if(pc)pc.hidden=!has;
    var h='<div><span>Toplam Miktar</span><b>'+fmt(t.q).replace(/,00$/,'')+' Ad</b></div><div><span>Brüt Toplam</span><b>'+fmt(t.gross)+' '+S+'</b></div><div><span>İndirim</span><b>'+fmt(t.disc+t.fd)+' '+S+'</b></div><div><span>Net Toplam</span><b>'+fmt(t.netAfter)+' '+S+'</b><button type="button" class="df-sm rd" data-s="Stopaj bir sonraki adımlarda eklenecek.">▾ stopaj</button></div>';
    Object.keys(t.vat).sort(function(a,b){return a-b}).forEach(function(r){h+='<div><span>KDV (%'+r+')</span><b>'+fmt(t.vat[r])+' '+S+'</b><button type="button" class="df-sm gr" data-s="Tevkifat bir sonraki adımlarda eklenecek.">▾ tevkifat</button></div>'});
    h+='<div class="tot"><span>TOPLAM</span><b>'+fmt(t.total)+' '+S+'</b></div><button type="button" class="df-sm rd big" id="dfFdBtn">▾ fatura altı iskonto yap</button>';
    $('#dfSum').innerHTML=h;
    $$('#dfSum [data-s]').forEach(function(b){b.onclick=function(){toast(b.dataset.s)}});
    $('#dfFdBtn').onclick=openDisc;$('#dfFd').value=FD;
    var pa=$('#dfPayAll');if(pa)pa.onclick=function(){var r=(S==='TL'?1:num($('#dfRate').value)||1);$('#dfPay').value=fmt(t.total*r)};
  }
  // ---- ürün arama
  var q=$('#dfQ'),res=$('#dfRes'),to,seq=0;
  q.addEventListener('input',function(){clearTimeout(to);var s=q.value.trim();
    if(s.length<3){res.hidden=!s;res.innerHTML=s?'<div class="df-rm">En az üç harf yazın...</div>':'';return}
    res.hidden=false;res.innerHTML='<div class="df-rm">bekleyin...</div>';var my=++seq;
    to=setTimeout(function(){fetch('<?= aurl('acc/product-search') ?>?q='+encodeURIComponent(s)).then(function(r){return r.json()}).then(function(rows){if(my!==seq)return;
      if(!rows.length){res.innerHTML='<div class="df-rm">Sonuç bulunamadı</div>';return}
      res.innerHTML='';rows.forEach(function(p,i){var d=document.createElement('div');d.className='df-ri'+(i?'':' on');d.innerHTML='<b>'+esc(p.name)+'</b><span>'+p.stock+' ad</span><small>'+esc(p.code||'')+'</small>';d.onmousedown=function(e){e.preventDefault();pick(p)};d._p=p;res.appendChild(d)})})},250)});
  q.addEventListener('keydown',function(e){var it=$$('.df-ri',res);if(!it.length)return;var i=it.findIndex(function(x){return x.classList.contains('on')});
    if(e.key==='ArrowDown'||e.key==='ArrowUp'){e.preventDefault();if(it[i])it[i].classList.remove('on');i=(i+(e.key==='ArrowDown'?1:-1)+it.length)%it.length;it[i].classList.add('on');it[i].scrollIntoView({block:'nearest'})}
    if(e.key==='Enter'){e.preventDefault();if(it[i])pick(it[i]._p)}});
  q.addEventListener('blur',function(){setTimeout(function(){res.hidden=true},150)});
  function pick(p){res.hidden=true;q.value='';openLine(p)}
  $('#dfFree').addEventListener('click',function(e){e.preventDefault();openLine({id:0,name:'',code:'',price:0,cost:0,vat:20,stock:0,free:true})});
  // ---- satır penceresi
  function openLine(p,idx){
    var l=idx!=null?L[idx]:null;cur={p:p,idx:idx,pid:p?(p.id||null):(l?l.pid:null),cost:p?p.cost:(l?l.cost:0)};
    var free=p?!!p.free:!l.pid;cur.free=free;
    $('#dfMT').textContent=l?l.name:(p.free?'Listede olmayan ürün / hizmet':(p.code?p.code+' ':'')+p.name);
    $('#mNameRow').hidden=!free;$('#mName').value=l?l.name:'';
    $('#mQty').value=l?String(l.qty).replace('.',','):'1';$('#mUnit').value=l?l.unit:'Ad';$('#mUnitL').textContent=$('#mUnit').value;
    $('#mPrice').value=l?String(l.price).replace('.',','):(p&&p.price?String(p.price).replace('.',','):'');
    $('#mVat').value=String(l?l.vat:(p?p.vat:20));if($('#mVat').value===''){$('#mVat').value='20'}
    var dp=(!l&&DISC>0)?DISC:0;$('#mDT').value=dp?'pct':'amt';$('#mDisc').value=dp?String(dp).replace('.',','):(l&&l.disc?String(l.disc).replace('.',','):'0');
    $('#mDesc').value=l&&l.desc?l.desc:'';$('#mStock').textContent='Ana Depo ('+(p&&!p.free?(p.stock>0?p.stock+' ad':'Stokta yok'):'—')+')';
    $('#mAdd span').textContent=l?'Güncelle':'Ekle';mcalc();show($('#dfM'),true);setTimeout(function(){(free&&!l?$('#mName'):$('#mQty')).select()},50)}
  function discAmt(){var g=num($('#mQty').value)*num($('#mPrice').value),d=num($('#mDisc').value);return $('#mDT').value==='pct'?g*d/100:d}
  function mcalc(){var g=num($('#mQty').value)*num($('#mPrice').value),d=Math.min(g,discAmt()),n=g-d,v=n*num($('#mVat').value)/100;$('#mTot').textContent=fmt(n+v);$('#mTotS').textContent='('+fmt(n)+' + '+fmt(v)+')'}
  ['mQty','mPrice','mDisc','mDT','mVat'].forEach(function(id){$('#'+id).addEventListener('input',mcalc)});$('#mUnit').addEventListener('change',function(){$('#mUnitL').textContent=this.value});
  $('#mAdd').addEventListener('click',function(){
    var name;
    if(cur.free)name=$('#mName').value.trim();
    else if(cur.idx!=null)name=L[cur.idx].name;
    else name=(cur.p.code?cur.p.code+' ':'')+cur.p.name;
    if(!name){$('#mName').focus();return}
    var qty=num($('#mQty').value);if(qty<=0){$('#mQty').focus();return}
    var o={pid:cur.pid||null,name:name,qty:qty,unit:$('#mUnit').value,price:num($('#mPrice').value),disc:Math.max(0,discAmt()),vat:num($('#mVat').value),desc:$('#mDesc').value.trim(),cost:cur.cost};
    if(cur.idx!=null)L[cur.idx]=o;else L.push(o);
    show($('#dfM'),false);render();
    if(KIND==='sale'&&o.cost>0&&(o.price*o.qty-o.disc)/o.qty<o.cost)alertBox('Dikkat','Ürün alış fiyatından daha düşük bir fiyatla eklendi');
    q.focus()});
  // ---- önceki fiyatlar
  var pdata=null;
  $('#mPrev').addEventListener('click',function(e){e.preventDefault();if(!cur||!cur.pid){toast('Bu ürün için önceki fiyat bulunmuyor.');return}
    fetch('<?= aurl('acc/prev-prices') ?>?product='+cur.pid+'&party='+PARTY+'&kind='+KIND).then(function(r){return r.json()}).then(function(d){pdata=d;ptab('party');show($('#dfP'),true)})});
  function ptab(k){$$('[data-pt]').forEach(function(b){b.classList.toggle('on',b.dataset.pt===k)});var rows=(pdata&&pdata[k])||[];
    $('#pBody').innerHTML=rows.length?rows.map(function(r){var dd=String(r.d).split('-').reverse().join('.');return '<tr><td class="l">'+dd+'</td><td class="l">'+esc(r.party||'—')+'</td><td>'+fmt(parseFloat(r.price))+' '+(r.cur==='TRY'?'TL':esc(r.cur))+'</td><td>'+(r.cur==='TRY'?'—':fmt(parseFloat(r.rate)))+'</td></tr>'}).join(''):'<tr><td colspan="4" class="df-rm">Kayıt yok</td></tr>'}
  $$('[data-pt]').forEach(function(b){b.addEventListener('click',function(){ptab(b.dataset.pt)})});
  // ---- fatura altı iskonto
  function openDisc(){var t=calc(),base=t.net;$('#dCur').value=fmt(base)+' '+sym();$('#dPct').value=base&&FD?String((FD/base*100).toFixed(2)).replace('.',','):'';$('#dNew').value=FD?String((base-FD).toFixed(2)).replace('.',','):'';show($('#dfD'),true);
    $('#dPct').oninput=function(){var p=num(this.value);$('#dNew').value=String((base-base*p/100).toFixed(2)).replace('.',',')};
    $('#dNew').oninput=function(){var n=num(this.value);$('#dPct').value=base?String(((base-n)/base*100).toFixed(2)).replace('.',','):''};
    $('#dApply').onclick=function(){var nv=$('#dNew').value.trim()===''?base:num($('#dNew').value);FD=Math.max(0,Math.min(base,base-nv));show($('#dfD'),false);render()};
    $('#dRem').onclick=function(){FD=0;show($('#dfD'),false);render()}}
  // ---- para birimi
  $('#dfCur').addEventListener('change',function(){var c=this.value;$('#dfRate').value=c==='TRY'?'1':String((RATES[c]||1).toFixed(4)).replace('.',',');render()});
  // ---- cari seçimi (liste)
  var pk=$('#dfPick');if(pk)pk.addEventListener('input',function(){var o=$$('#dfParties option').find(function(x){return x.value===pk.value});$('#dfPid').value=o?o.dataset.id:0;PARTY=o?+o.dataset.id:0});
  // ---- kaydet
  $$('[data-act]').forEach(function(b){b.addEventListener('click',function(){submit(b.dataset.act)})});
  function submit(act){
    if(!L.length){alertBox('Dikkat','En az bir ürün / hizmet ekleyin.');return}
    if((KIND==='purchase'||KIND==='proposal')&&!+$('#dfPid').value){alertBox('Dikkat',KIND==='proposal'?'Teklif için müşteri seçin.':'Tedarikçi seçin.');return}
    var go=function(){var f=$('#dfForm');$$('.df-h',f).forEach(function(x){x.remove()});
      L.forEach(function(l){[['l_pid',l.pid||''],['l_name',l.name],['l_qty',l.qty],['l_unit',l.unit],['l_price',l.price],['l_disc',l.disc],['l_vat',l.vat],['l_desc',l.desc||'']].forEach(function(kv){var i=document.createElement('input');i.type='hidden';i.className='df-h';i.name=kv[0]+'[]';i.value=kv[1];f.appendChild(i)})});
      $('#dfAct').value=act;f.submit()};
    var t=calc();
    var step2=function(){var d=$('input[name=doc_date]').value;if(d&&(Date.now()-new Date(d).getTime())/864e5>7){alertBox('Dikkat','İşlem tarihi 7 günden eski gözüküyor, emin misiniz?',go,true);return}go()};
    if(act==='final'&&KIND==='sale'&&RISK!==null&&BAL+t.total>RISK){alertBox('Risk Uyarısı','Müşterinin risk limiti aşılıyor. Devam etmek istediğinize emin misiniz?',step2,true);return}
    step2()}
  render();
})();
</script>
<?php admin_view('layout_bottom'); ?>
