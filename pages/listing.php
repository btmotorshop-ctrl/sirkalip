<?php
$seg = trim($path, '/'); $parts = explode('/', $seg);
if ($parts[0] === 'arama' && isset($_GET['ajax'])) {
    header('Content-Type: application/json; charset=utf-8');
    $r = product_search(['q' => $_GET['q'] ?? '', 'per' => 6]);
    echo json_encode(['total' => $r['total'], 'items' => array_map(function ($p) { [$pr] = product_prices($p); return ['name' => tr_field($p, 'name'), 'code' => $p['code'], 'price' => money($pr) . (prices_ex_vat() ? ' + ' . L('KDV', 'VAT') : ''), 'url' => product_url($p), 'img' => product_main_image($p), 'stock' => (int)$p['stock']]; }, $r['rows'])], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}
$f = ['q' => $_GET['q'] ?? '', 'min' => $_GET['min'] ?? '', 'max' => $_GET['max'] ?? '', 'stock' => !empty($_GET['stok']), 'sort' => $_GET['sirala'] ?? '',
      'page' => $_GET['sayfa'] ?? 1, 'per' => 24, 'brands' => array_filter((array)($_GET['marka'] ?? [])), 'spec' => $_GET['ozellik'] ?? []];
$category = null; $brand = null; $title = ''; $crumbs = []; $intro = '';
$allCats = get_categories();
switch ($parts[0]) {
    case 'kategori':
        if ($parts[1] === 'balya-makinesi') { $title = L('Balya Makinesi Yedek Parçaları', 'Baler Spare Parts'); break; }
        $category = category_by_slug($parts[1]);
        if (!$category) { http_response_code(404); view('layout/top', ['title' => '404']); view('404'); view('layout/bottom'); exit; }
        $f['category_ids'] = category_children_ids((int)$category['id']);
        $title = tr_field($category, 'name');
        $crumbs[] = [L('Balya Makinesi Yedek Parçaları', 'Baler Spare Parts'), url('/kategori/balya-makinesi')];
        $intro = tr_field($category, 'desc');
        break;
    case 'marka':
        $st = db()->prepare('SELECT * FROM brands WHERE slug = ? AND is_active = 1'); $st->execute([$parts[1]]); $brand = $st->fetch();
        if (!$brand) { http_response_code(404); view('layout/top', ['title' => '404']); view('404'); view('layout/bottom'); exit; }
        // Marka sayfası: bu markanın makinelerine uyumlu parçalar + marka ürünleri
        $f['brand_or_compat'] = $brand['slug'];
        $title = $brand['name'] . ' ' . L('Yedek Parçaları', 'Spare Parts');
        $crumbs[] = [L('Markalar', 'Brands'), url('/markalar')];
        $intro = LV('{brand} balya makineleri için uyumlu yedek parçalar.', 'Compatible spare parts for {brand} balers.', ['brand' => $brand['name']]);
        break;
    case 'yeni-urunler': $f['only'] = 'new'; $title = L('Yeni Ürünler', 'New Products'); break;
    case 'kampanyalar': $f['only'] = 'sale'; $title = L('Kampanyalı Ürünler', 'Campaign Products'); break;
    case 'arama': $title = $f['q'] !== '' ? LV('"{q}" için arama sonuçları', 'Search results for "{q}"', ['q' => $f['q']]) : L('Tüm Ürünler', 'All Products');
        if ($f['q'] !== '' && empty($_GET['sayfa'])) { try { $st = db()->prepare('UPDATE search_terms SET hits = hits + 1 WHERE term = ?'); $st->execute([mb_strtolower($f['q'])]); if (!$st->rowCount()) db()->prepare('INSERT INTO search_terms (term) VALUES (?)')->execute([mb_strtolower($f['q'])]); } catch (Throwable $e) {} }
        break;
    case 'parca-bul':
        $f['machine_brand'] = $_GET['marka_makine'] ?? ($_GET['marka'] ?? '');
        if (is_array($f['machine_brand'])) $f['machine_brand'] = '';
        $f['brands'] = [];
        $f['model'] = $_GET['model'] ?? '';
        if (!empty($_GET['grup']) && ($gc = category_by_slug($_GET['grup']))) { $f['category_ids'] = category_children_ids((int)$gc['id']); $category = $gc; }
        $bn = ''; foreach (get_brands() as $b) if ($b['slug'] === $f['machine_brand']) $bn = $b['name'];
        $title = trim($bn . ' ' . $f['model']) !== '' ? trim($bn . ' ' . $f['model']) . ' ' . L('için uyumlu parçalar', 'compatible parts') : L('Makinenize Göre Parça Bul', 'Find Parts for Your Machine');
        if ($category) $title .= ' – ' . tr_field($category, 'name');
        break;
    default: $title = L('Tüm Ürünler', 'All Products');
}
if (isset($f['brand_or_compat'])) { $f['machine_brand'] = $f['brand_or_compat']; }
$res = product_search($f);
// Marka sayfasında uyumlu ürün yoksa marka ürünlerini göster
if (isset($f['brand_or_compat']) && !$res['total']) { unset($f['machine_brand']); $f['brands'] = [$f['brand_or_compat']]; $res = product_search($f); }
// Seçilen makineye henüz parça eşleştirilmemişse sayfa boş kalmasın: tüm parçalar listelenir
if (!$res['total'] && (!empty($f['machine_brand']) || !empty($f['brands']) || !empty($f['model']))) {
    $bn = $brand['name'] ?? ''; if (!$bn) foreach (get_brands() as $b) if ($b['slug'] === ($f['machine_brand'] ?? '')) $bn = $b['name'];
    unset($f['machine_brand'], $f['brand_or_compat']); $f['brands'] = []; $f['model'] = '';
    $res = product_search($f);
    $intro = LV('{machine} için eşleştirilmiş parça listemiz hazırlanıyor. Aşağıda tüm parçalarımızı görebilirsiniz; aradığınız parçayı bulamazsanız fotoğrafını WhatsApp hattımıza gönderin, size uygun parçayı bulalım.',
                'Parts for {machine} are being listed. Below are all our parts; if you cannot find yours, send us a photo on WhatsApp.', ['machine' => trim($bn . ' ' . ($_GET['model'] ?? ''))]);
}
$specs = filterable_specs($f['category_ids'] ?? []);
$crumbs[] = [$title, null];
view('layout/top', ['title' => $title]);
view('listing', compact('res', 'f', 'title', 'crumbs', 'intro', 'category', 'brand', 'allCats', 'specs'));
view('layout/bottom');
