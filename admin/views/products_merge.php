<?php
admin_view('layout_top', ['title' => 'Ürün Birleştirme']);
admin_view('page_head', ['title' => 'Ürün Birleştirme', 'crumbs' => [['Ürünler', aurl('products')], ['Ürün Birleştirme', null]], 'actions' => '']);
$js = array_map(function ($i) { return ['id' => (int)$i['id'], 'code' => $i['code'], 'name' => $i['name_tr'], 'qty' => (int)$i['qty']]; }, $in['items']);
?>
<?php if ($errors): ?><div class="acard mb-4 border-red-300 bg-red-50 p-4 text-sm text-red-700"><?php foreach ($errors as $er) echo '<div>' . e($er) . '</div>'; ?></div><?php endif; ?>
<form method="post" id="mgForm" autocomplete="off" class="grid gap-4 lg:grid-cols-5">
  <?= csrf_field() ?>
  <div class="acard p-5 lg:col-span-3">
    <div class="mb-1 text-sm font-extrabold">1. Birleştirilecek ürünleri seçin</div>
    <div class="mb-3 text-xs text-muted">Kod veya ada göre arayıp ekleyin. Her ürün için sette kaç adet olacağını yazın. Asıl ürünler olduğu gibi kalır.</div>
    <div class="relative">
      <input id="mgSearch" class="ainput" placeholder="Ürün kodu veya adı yazın (en az 2 harf)…">
      <div id="mgRes" class="absolute left-0 right-0 z-20 mt-1 hidden max-h-72 overflow-auto rounded-lg border border-line bg-white shadow-lg"></div>
    </div>
    <table class="atable mt-4"><thead><tr><th>Ürün</th><th style="width:90px">Adet</th><th style="width:40px"></th></tr></thead><tbody id="mgList"></tbody></table>
    <div id="mgEmpty" class="py-6 text-center text-sm text-muted">Henüz ürün eklenmedi.</div>
  </div>
  <div class="acard p-5 lg:col-span-2">
    <div class="mb-3 text-sm font-extrabold">2. Yeni ürün bilgileri</div>
    <div class="space-y-3">
      <div><label class="alabel">Yeni ürün adı *</label><input name="name" class="ainput" value="<?= e($in['name']) ?>" required></div>
      <div class="grid grid-cols-2 gap-3">
        <div><label class="alabel">Ürün kodu</label><input name="code" class="ainput" value="<?= e($in['code']) ?>" placeholder="Boşsa otomatik"></div>
        <div><label class="alabel">Kategori</label><select name="category_id" class="ainput"><option value="">İlk ürünün kategorisi</option><?php foreach ($cats as $k => $v): ?><option value="<?= (int)$k ?>" <?= (int)$in['category_id'] === (int)$k ? 'selected' : '' ?>><?= e($v) ?></option><?php endforeach; ?></select></div>
      </div>
      <div class="grid grid-cols-3 gap-3">
        <div class="col-span-2"><label class="alabel">Satış fiyatı *</label><input name="price" id="mgPrice" class="ainput" inputmode="decimal" value="<?= e($in['price']) ?>" required></div>
        <div><label class="alabel">Para birimi</label><select name="currency" class="ainput"><?php foreach (['TRY', 'USD', 'EUR'] as $c): ?><option <?= $in['currency'] === $c ? 'selected' : '' ?>><?= $c ?></option><?php endforeach; ?></select></div>
      </div>
      <div class="grid grid-cols-2 gap-3">
        <div><label class="alabel">KDV %</label><input name="vat_rate" class="ainput" type="number" value="<?= (int)$in['vat_rate'] ?>"></div>
        <div><label class="alabel">Stok</label><input name="stock" class="ainput" type="number" min="0" value="<?= (int)$in['stock'] ?>"></div>
      </div>
      <div class="rounded-lg bg-slate-50 p-3 text-xs text-muted">Ürünlerin tek tek toplam fiyatı: <b id="mgSum" class="text-ink">–</b> <span>(bilgi amaçlı)</span></div>
      <div><label class="alabel">Ek açıklama (isteğe bağlı)</label><textarea name="note" rows="3" class="ainput" placeholder="Açıklamanın altına eklenir"><?= e($in['note']) ?></textarea></div>
      <div class="text-xs text-muted">Açıklama kısmına “Bu set şu ürünlerden oluşur: …” listesi otomatik yazılır.</div>
      <button class="abtn-primary w-full justify-center">Birleştir ve Yeni Ürün Oluştur</button>
    </div>
  </div>
</form>
<script>
(function(){
var items=<?= json_encode($js, JSON_UNESCAPED_UNICODE) ?>, prices={}, list=document.getElementById('mgList'), empty=document.getElementById('mgEmpty'), res=document.getElementById('mgRes'), inp=document.getElementById('mgSearch'), t;
function esc(s){var d=document.createElement('div');d.textContent=s;return d.innerHTML}
function render(){list.innerHTML='';items.forEach(function(it,i){var tr=document.createElement('tr');
tr.innerHTML='<td><input type="hidden" name="item_id[]" value="'+it.id+'"><b>'+esc(it.code)+'</b> – '+esc(it.name)+'</td><td><input class="ainput" type="number" min="1" name="item_qty[]" value="'+it.qty+'" style="height:34px"></td><td><button type="button" class="text-red-600" title="Kaldır">✕</button></td>';
tr.querySelector('[type=number]').oninput=function(){it.qty=Math.max(1,parseInt(this.value)||1);sum()};
tr.querySelector('button').onclick=function(){items.splice(i,1);render()};list.appendChild(tr)});empty.style.display=items.length?'none':'';sum()}
function sum(){var s=0,ok=items.length>0;items.forEach(function(it){if(prices[it.id]===undefined)ok=false;else s+=prices[it.id]*it.qty});document.getElementById('mgSum').textContent=ok?s.toLocaleString('tr-TR',{minimumFractionDigits:2})+' (liste fiyatları)':'–'}
inp.addEventListener('input',function(){clearTimeout(t);var q=inp.value.trim();if(q.length<2){res.classList.add('hidden');return}
t=setTimeout(function(){fetch('<?= e(aurl('products/merge')) ?>?q='+encodeURIComponent(q),{credentials:'same-origin'}).then(function(r){return r.json()}).then(function(rows){
res.innerHTML=rows.length?'':'<div class="p-3 text-sm text-muted">Sonuç yok</div>';rows.forEach(function(r){prices[r.id]=r.price;var d=document.createElement('div');d.className='cursor-pointer border-b border-line p-2 text-sm hover:bg-slate-50';
d.innerHTML='<b>'+esc(r.code)+'</b> – '+esc(r.name)+' <span class="text-muted">· '+r.price.toLocaleString('tr-TR')+' '+r.currency+' · stok '+r.stock+'</span>';
d.onclick=function(){var f=items.filter(function(x){return x.id===r.id})[0];if(f)f.qty++;else items.push({id:r.id,code:r.code,name:r.name,qty:1});res.classList.add('hidden');inp.value='';render()};res.appendChild(d)});res.classList.remove('hidden')})},250)});
document.addEventListener('click',function(e){if(!res.contains(e.target)&&e.target!==inp)res.classList.add('hidden')});
inp.addEventListener('keydown',function(e){if(e.key==='Enter')e.preventDefault()});
if(items.length){fetch('<?= e(aurl('products/merge')) ?>?q='+encodeURIComponent(items[0].code),{credentials:'same-origin'});}
render();
})();
</script>
<?php admin_view('layout_bottom'); ?>
