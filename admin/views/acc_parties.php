<?php
$isCust = $kind === 'customer';
$title = $isCust ? 'Müşteriler' : 'Tedarikçiler';
$res = $isCust ? 'acc_customers' : 'acc_suppliers';
admin_view('layout_top', compact('title'));
$activeCount = 0; foreach ($rows as $r) if ($r['is_active']) $activeCount++;
?>
<div class="pl-bar">
  <a href="<?= aurl("acc/party-edit/$kind/new") ?>" class="pl-btn gr"><?= icon('plus', 'h-4 w-4') ?> <?= $isCust ? 'Yeni Müşteri Ekle' : 'Yeni Tedarikçi Ekle' ?></a>
  <button type="button" class="pl-btn or" data-soon="Excel'den yükleme bir sonraki adımda eklenecek."><?= icon('file-spreadsheet', 'h-4 w-4') ?> Excelden <?= $isCust ? 'Müşteri' : 'Tedarikçi' ?> Yükle</button>
  <button type="button" class="pl-btn bl" data-soon="Toplu güncelleme bir sonraki adımda eklenecek."><?= icon('refresh-ccw', 'h-4 w-4') ?> Toplu <?= $isCust ? 'Müşteri' : 'Tedarikçi' ?> Güncelleme</button>
  <span class="pl-total"><?= icon('users', 'h-4 w-4') ?> <b id="plTotal"><?= count($rows) ?></b> Toplam <?= $isCust ? 'Müşteri' : 'Tedarikçi' ?></span>
</div>
<?php if (array_sum($pending) > 0): ?>
<form method="post" action="<?= aurl('acc/import-firms') ?>" class="pl-import" onsubmit="this.querySelector('button').disabled=true;this.querySelector('button').textContent='Aktarılıyor...'">
  <?= csrf_field() ?>
  <span>Hazır firma listesi bekliyor: <b><?= (int)$pending[0] ?></b> müşteri ve <b><?= (int)$pending[1] ?></b> tedarikçi henüz eklenmedi.</span>
  <button type="submit" class="pl-btn gr">Firmaları Aktar</button>
</form>
<?php endif; ?>
<div class="pl-toast" id="plToast"></div>

<div class="pl-card">
  <div class="pl-filters">
    <select id="plStatus" class="pl-sel">
      <option value="1">Aktif <?= $isCust ? 'Müşteriler' : 'Tedarikçiler' ?></option>
      <option value="0">Pasif <?= $isCust ? 'Müşteriler' : 'Tedarikçiler' ?></option>
      <option value="">Tümü</option>
    </select>
    <select id="plBal" class="pl-sel">
      <option value="">Hepsini göster</option>
      <option value="open"><?= $isCust ? 'Borcu olanlar' : 'Borcumuz olanlar' ?></option>
      <option value="credit">Alacağı olanlar</option>
      <option value="zero">Bakiyesi sıfır olanlar</option>
    </select>
    <label class="pl-search"><span>Ara:</span><input id="plQ" type="search" placeholder="arama... (en az 2 karakter)" autocomplete="off"></label>
  </div>
  <div class="pl-scroll">
    <table class="pl-table" id="plTable">
      <thead><tr>
        <th data-k="name"><?= $isCust ? 'İsim / Unvan' : 'Tedarikçi Adı / Unvanı' ?></th>
        <th data-k="bal" data-num="1" class="r">Açık Bakiye</th>
        <th data-k="last" class="r">Son İşlem</th>
        <th class="x"></th>
      </tr></thead>
      <tbody>
      <?php foreach ($rows as $r): $has = abs($r['bal']) > 0.005; ?>
        <tr data-a="<?= (int)$r['is_active'] ?>" data-name="<?= e(mb_strtolower($r['name'] . ' ' . $r['phone'])) ?>" data-bal="<?= round($r['bal'], 2) ?>" data-last="<?= $r['last_at'] > '0000-00-00' ? e($r['last_at']) : '' ?>" onclick="location='<?= aurl('acc/party/' . $r['id']) ?>'">
          <td><span class="pl-name <?= $r['is_active'] ? '' : 'off' ?>"><?= e($r['name']) ?><?php if ($r['phone']): ?><i class="pl-ph"><?= e($r['phone']) ?></i><?php endif; ?></span></td>
          <td class="r <?= $has ? 'has' : 'nil' ?> <?= $r['bal'] < -0.005 ? 'neg' : '' ?>"><?= acc_money($r['bal']) ?></td>
          <td class="r nil"><?= $r['last_at'] > '0000-00-00' ? acc_date($r['last_at']) : '—' ?></td>
          <td class="x"><a href="<?= aurl("acc/party-edit/$kind/" . $r['id']) ?>" onclick="event.stopPropagation()" title="Düzenle"><?= icon('pencil', 'h-4 w-4') ?></a></td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
    <div class="pl-none" id="plNone" hidden>Kayıt bulunamadı.</div>
  </div>
</div>
<script>
(function(){
  var rows=[].slice.call(document.querySelectorAll('#plTable tbody tr')),st=document.getElementById('plStatus'),bl=document.getElementById('plBal'),q=document.getElementById('plQ'),tot=document.getElementById('plTotal'),none=document.getElementById('plNone');
  function tr(s){return s.toLocaleLowerCase('tr-TR')}
  function apply(){
    var term=tr(q.value.trim()),n=0;if(term.length<2)term='';
    rows.forEach(function(r){
      var b=parseFloat(r.dataset.bal),ok=true;
      if(st.value!==''&&r.dataset.a!==st.value)ok=false;
      if(bl.value==='open'&&!(b>0.005))ok=false;
      if(bl.value==='credit'&&!(b<-0.005))ok=false;
      if(bl.value==='zero'&&!(Math.abs(b)<=0.005))ok=false;
      if(term&&r.dataset.name.indexOf(term)<0)ok=false;
      r.style.display=ok?'':'none';if(ok)n++;
    });
    tot.textContent=n;none.hidden=n>0;
  }
  [st,bl].forEach(function(e){e.addEventListener('change',apply)});q.addEventListener('input',apply);apply();
  // sıralama
  var dir={};
  [].forEach.call(document.querySelectorAll('#plTable th[data-k]'),function(th){
    th.addEventListener('click',function(){
      var k=th.dataset.k,num=th.dataset.num==='1',d=dir[k]=-(dir[k]||-1);
      [].forEach.call(th.parentNode.children,function(x){x.classList.remove('asc','desc')});th.classList.add(d>0?'asc':'desc');
      var body=document.querySelector('#plTable tbody');
      rows.sort(function(a,b){var x=a.dataset[k]||'',y=b.dataset[k]||'';
        if(num)return (parseFloat(x)-parseFloat(y))*d;
        return x.localeCompare(y,'tr')*d}).forEach(function(r){body.appendChild(r)});
    });
  });
  // yakında bildirimi
  var tt=document.getElementById('plToast'),tm;
  [].forEach.call(document.querySelectorAll('[data-soon]'),function(b){b.addEventListener('click',function(){tt.textContent=b.dataset.soon;tt.classList.add('on');clearTimeout(tm);tm=setTimeout(function(){tt.classList.remove('on')},2800)})});
})();
</script>
<?php admin_view('layout_bottom'); ?>
