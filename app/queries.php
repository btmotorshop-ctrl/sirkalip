<?php
function get_categories(): array
{
    static $c = null;
    if ($c === null) $c = db()->query('SELECT * FROM categories WHERE is_active = 1 AND parent_id IS NULL ORDER BY sort_order')->fetchAll();
    return $c;
}

function get_brands(): array
{
    static $b = null;
    if ($b === null) {
        $b = db()->query('SELECT b.*, (SELECT COUNT(*) FROM products p WHERE p.brand_id = b.id AND p.is_active = 1 AND p.deleted_at IS NULL) AS product_count FROM brands b WHERE b.is_active = 1 ORDER BY b.sort_order, b.name')->fetchAll();
        $models = db()->query('SELECT brand_id, name FROM machine_models ORDER BY name')->fetchAll();
        foreach ($b as &$br) {
            $br['models'] = array_values(array_map(function ($m) { return $m['name']; }, array_filter($models, function ($m) use ($br) { return (int)$m['brand_id'] === (int)$br['id']; })));
        }
        unset($br);
    }
    return $b;
}

function get_featured_products(int $limit = 12): array
{
    $st = db()->prepare('SELECT p.*, c.slug AS category_slug FROM products p JOIN categories c ON c.id = p.category_id
        WHERE p.is_active = 1 AND p.is_featured = 1 AND p.deleted_at IS NULL ORDER BY p.sales_count DESC LIMIT ?');
    $st->bindValue(1, $limit, PDO::PARAM_INT);
    $st->execute();
    return $st->fetchAll();
}

/** Ürünün gösterilecek fiyatı ve üstü çizili fiyatı */
function product_prices(array $p): array
{
    if (!empty($p['discount_price'])) return [(float)$p['discount_price'], (float)$p['price']];
    return [(float)$p['price'], !empty($p['market_price']) ? (float)$p['market_price'] : null];
}

/**
 * Ürün listeleme (kategori, marka, arama, parça bulucu, kampanyalı, yeni ürünler)
 * $f: category_id, brand_id(s), model, q, min, max, stock, sort, page, per, only (new|sale|featured), spec[keyId]=value, ids
 */
function product_search(array $f): array
{
    $w = ['p.is_active = 1', 'p.deleted_at IS NULL']; $params = [];
    if (!empty($f['category_ids'])) { $w[] = 'p.category_id IN (' . implode(',', array_map('intval', $f['category_ids'])) . ')'; }
    if (!empty($f['brands'])) { $w[] = 'b.slug IN (' . implode(',', array_fill(0, count($f['brands']), '?')) . ')'; $params = array_merge($params, $f['brands']); }
    if (!empty($f['machine_brand'])) {
        // Makine markası: ürünün kendi markası o makine ise veya uyumluluk listesinde o marka/model varsa.
        // Model seçildiğinde, modele özel uyumluluk girilmemiş marka ürünleri de (markanın tüm modellerine uygun kabul edilerek) listelenir.
        $compat = 'EXISTS (SELECT 1 FROM product_compat pc JOIN machine_models mm ON mm.id = pc.machine_model_id JOIN brands mb ON mb.id = mm.brand_id WHERE pc.product_id = p.id AND mb.slug = ?' . (!empty($f['model']) ? ' AND mm.name = ?' : '') . ')';
        $own = empty($f['model']) ? 'b.slug = ?' : '(b.slug = ? AND NOT EXISTS (SELECT 1 FROM product_compat pc2 WHERE pc2.product_id = p.id))';
        $w[] = "($compat OR $own)";
        $params[] = $f['machine_brand']; if (!empty($f['model'])) $params[] = $f['model']; $params[] = $f['machine_brand'];
    }
    if (($q = trim($f['q'] ?? '')) !== '') {
        // Türkçe karakter ve büyük/küçük harf duyarsız arama ("ip siyirici" = "İp Sıyırıcı", "sir00116" = "SIR00116")
        $fold = function ($col) { return "REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(LOWER($col),'ı','i'),'ş','s'),'ğ','g'),'ü','u'),'ö','o'),'ç','c')"; };
        $fq = strtr(mb_strtolower(strtr($q, ['I' => 'ı', 'İ' => 'i']), 'UTF-8'), ['ı' => 'i', 'ş' => 's', 'ğ' => 'g', 'ü' => 'u', 'ö' => 'o', 'ç' => 'c']);
        $w[] = '(' . $fold('p.name_tr') . ' LIKE ? OR ' . $fold('p.name_en') . ' LIKE ? OR ' . $fold('p.code') . ' LIKE ? OR p.barcode LIKE ? OR ' . $fold('b.name') . ' LIKE ? OR EXISTS (SELECT 1 FROM product_refs r WHERE r.product_id = p.id AND ' . $fold('r.code') . ' LIKE ?))';
        for ($i = 0; $i < 6; $i++) $params[] = "%$fq%";
    }
    $priceExpr = 'COALESCE(p.discount_price, p.price)';
    if (($f['min'] ?? '') !== '' && is_numeric($f['min'])) { $w[] = "$priceExpr >= ?"; $params[] = (float)$f['min']; }
    if (($f['max'] ?? '') !== '' && is_numeric($f['max'])) { $w[] = "$priceExpr <= ?"; $params[] = (float)$f['max']; }
    if (!empty($f['stock'])) $w[] = 'p.stock > 0';
    if (($f['only'] ?? '') === 'new') $w[] = 'p.is_new = 1';
    if (($f['only'] ?? '') === 'sale') $w[] = '(p.discount_price IS NOT NULL OR (p.market_price IS NOT NULL AND p.market_price > p.price))';
    foreach ($f['spec'] ?? [] as $k => $vals) {
        $vals = array_filter((array)$vals, 'strlen'); if (!$vals) continue;
        $w[] = 'EXISTS (SELECT 1 FROM product_specs s WHERE s.product_id = p.id AND s.spec_key_id = ? AND s.value IN (' . implode(',', array_fill(0, count($vals), '?')) . '))';
        $params[] = (int)$k; $params = array_merge($params, array_values($vals));
    }
    $where = implode(' AND ', $w);
    $sort = ['price' => "$priceExpr ASC", 'price_desc' => "$priceExpr DESC", 'name' => 'p.name_tr', 'new' => 'p.id DESC', 'best' => 'p.sales_count DESC'][$f['sort'] ?? ''] ?? 'p.stock > 0 DESC, p.sort_order, p.sales_count DESC';
    $per = (int)($f['per'] ?? 24); $page = max(1, (int)($f['page'] ?? 1));
    $st = db()->prepare("SELECT COUNT(*) FROM products p LEFT JOIN brands b ON b.id = p.brand_id WHERE $where"); $st->execute($params);
    $total = (int)$st->fetchColumn();
    $st = db()->prepare("SELECT p.*, c.slug category_slug, c.name_tr category_name_tr, " . loc_cols('c', 'name', 'category_name') . ", b.name brand_name, b.slug brand_slug
        FROM products p JOIN categories c ON c.id = p.category_id LEFT JOIN brands b ON b.id = p.brand_id WHERE $where ORDER BY $sort LIMIT $per OFFSET " . (($page - 1) * $per));
    $st->execute($params);
    // Filtre seçenekleri (aynı arama içinde)
    $st2 = db()->prepare("SELECT b.slug, b.name, COUNT(*) n FROM products p JOIN brands b ON b.id = p.brand_id WHERE $where GROUP BY b.id ORDER BY b.sort_order");
    $st2->execute($params);
    $st3 = db()->prepare("SELECT MIN($priceExpr) mn, MAX($priceExpr) mx FROM products p LEFT JOIN brands b ON b.id = p.brand_id WHERE $where");
    $st3->execute($params);
    return ['rows' => $st->fetchAll(), 'total' => $total, 'page' => $page, 'per' => $per, 'pages' => max(1, (int)ceil($total / $per)), 'brand_facets' => $st2->fetchAll(), 'range' => $st3->fetch()];
}

function filterable_specs(array $categoryIds = []): array
{
    $cw = $categoryIds ? ' AND p.category_id IN (' . implode(',', array_map('intval', $categoryIds)) . ')' : '';
    $rows = db()->query("SELECT k.id, k.name_tr, " . loc_cols('k', 'name') . ", k.unit, s.value, COUNT(*) n FROM spec_keys k JOIN product_specs s ON s.spec_key_id = k.id JOIN products p ON p.id = s.product_id
        WHERE k.is_filterable = 1 AND p.is_active = 1 AND p.deleted_at IS NULL $cw GROUP BY k.id, s.value ORDER BY k.sort_order, s.value")->fetchAll();
    $out = [];
    foreach ($rows as $r) { $out[$r['id']]['key'] = $r; $out[$r['id']]['values'][] = [$r['value'], $r['n']]; }
    return $out;
}

function category_by_slug(string $slug): ?array
{
    $st = db()->prepare('SELECT * FROM categories WHERE slug = ? AND is_active = 1'); $st->execute([$slug]);
    return $st->fetch() ?: null;
}

function category_children_ids(int $id): array
{
    $ids = [$id]; $all = db()->query('SELECT id, parent_id FROM categories')->fetchAll();
    $changed = true;
    while ($changed) { $changed = false; foreach ($all as $c) if ($c['parent_id'] && in_array((int)$c['parent_id'], $ids, true) && !in_array((int)$c['id'], $ids, true)) { $ids[] = (int)$c['id']; $changed = true; } }
    return $ids;
}

function page_by_slug(string $slug, ?string $kind = null): ?array
{
    $st = db()->prepare('SELECT * FROM pages WHERE slug = ? AND is_active = 1' . ($kind ? ' AND kind = ?' : ''));
    $st->execute($kind ? [$slug, $kind] : [$slug]);
    return $st->fetch() ?: null;
}
