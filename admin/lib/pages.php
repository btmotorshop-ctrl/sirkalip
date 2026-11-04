<?php
/* ───────── Giriş ───────── */
function page_login(): void
{
    $err = null;
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        check_csrf();
        if (admin_login($_POST['email'] ?? '', $_POST['password'] ?? '')) redirect(aurl());
        $err = 'E-posta veya şifre hatalı.';
    }
    admin_view('login', compact('err'));
}

/* ───────── Dashboard ───────── */
function period_range(string $p): array
{
    $days = ['today' => 0, '7' => 6, '15' => 14, '30' => 29, 'month' => (int)date('j') - 1, '90' => 89][$p] ?? 6;
    return [date('Y-m-d 00:00:00', strtotime("-$days days")), date('Y-m-d 23:59:59'), $days + 1];
}

function pct_change(float $now, float $prev): ?float
{
    if ($prev <= 0) return $now > 0 ? 100.0 : null;
    return round(($now - $prev) / $prev * 100, 1);
}

function page_dashboard(): void
{
    $p = $_GET['p'] ?? '7';
    [$from, $to, $days] = period_range($p);
    $pFrom = date('Y-m-d 00:00:00', strtotime($from . " -$days days")); $pTo = date('Y-m-d 23:59:59', strtotime($from . ' -1 day'));
    $q = function ($sql, $params = []) { $st = db()->prepare($sql); $st->execute($params); return $st; };
    $ok = "deleted_at IS NULL AND status <> 'cancelled'";
    $sum = function ($f, $t) use ($q, $ok) { return $q("SELECT COUNT(*) c, COALESCE(SUM(total),0) t, COUNT(DISTINCT email) cu FROM orders WHERE $ok AND created_at BETWEEN ? AND ?", [$f, $t])->fetch(); };
    $cur = $sum($from, $to); $prev = $sum($pFrom, $pTo);
    $vis = function ($f, $t) use ($q) { return $q('SELECT COALESCE(SUM(visitors),0) v, COALESCE(SUM(pageviews),0) pv, COALESCE(SUM(product_views),0) prv FROM page_visits WHERE d BETWEEN ? AND ?', [substr($f, 0, 10), substr($t, 0, 10)])->fetch(); };
    $vCur = $vis($from, $to); $vPrev = $vis($pFrom, $pTo);
    $newMem = function ($f, $t) use ($q) { return (int)$q("SELECT COUNT(*) FROM users WHERE role='customer' AND created_at BETWEEN ? AND ?", [$f, $t])->fetchColumn(); };
    $avg = $cur['c'] ? $cur['t'] / $cur['c'] : 0; $avgP = $prev['c'] ? $prev['t'] / $prev['c'] : 0;
    $conv = $vCur['v'] ? $cur['c'] / $vCur['v'] * 100 : 0; $convP = $vPrev['v'] ? $prev['c'] / $vPrev['v'] * 100 : 0;
    $kpis = [
        ['Toplam Ciro', money($cur['t']), pct_change((float)$cur['t'], (float)$prev['t']), 'wallet', 'red', 'reports/sales'],
        ['Sipariş', number_format((int)$cur['c'], 0, ',', '.'), pct_change((float)$cur['c'], (float)$prev['c']), 'clipboard-list', 'blue', 'orders'],
        ['Ziyaretçi', number_format((int)$vCur['v'], 0, ',', '.'), pct_change((float)$vCur['v'], (float)$vPrev['v']), 'users', 'green', 'visitors'],
        ['Ürün Görüntülenme', number_format((int)$vCur['prv'], 0, ',', '.'), pct_change((float)$vCur['prv'], (float)$vPrev['prv']), 'eye', 'violet', 'visitors?type=product'],
        ['Ortalama Sepet', money($avg), pct_change($avg, $avgP), 'shopping-cart', 'orange', null],
        ['Dönüşüm Oranı', '%' . number_format($conv, 2, ',', '.'), pct_change($conv, $convP), 'percent', 'teal', null],
    ];
    // Günlük seri
    $byDay = [];
    foreach ($q("SELECT DATE(created_at) d, COUNT(*) c, SUM(total) t FROM orders WHERE $ok AND created_at BETWEEN ? AND ? GROUP BY DATE(created_at)", [$from, $to])->fetchAll() as $r) $byDay[$r['d']] = $r;
    $visDay = $q('SELECT d, visitors FROM page_visits WHERE d BETWEEN ? AND ?', [substr($from, 0, 10), substr($to, 0, 10)])->fetchAll(PDO::FETCH_KEY_PAIR);
    $series = [];
    if ($days === 1) { // bugün: saatlik
        $byH = $q("SELECT HOUR(created_at) h, COUNT(*) c, SUM(total) t FROM orders WHERE $ok AND created_at BETWEEN ? AND ? GROUP BY HOUR(created_at)", [$from, $to])->fetchAll(PDO::FETCH_UNIQUE);
        for ($h = 0; $h < 24; $h++) $series[] = ['l' => sprintf('%02d:00', $h), 'c' => (int)($byH[$h]['c'] ?? 0), 't' => (float)($byH[$h]['t'] ?? 0), 'v' => 0];
    } else for ($i = $days - 1; $i >= 0; $i--) { $d = date('Y-m-d', strtotime("-$i days")); $series[] = ['l' => date('d.m', strtotime($d)), 'c' => (int)($byDay[$d]['c'] ?? 0), 't' => (float)($byDay[$d]['t'] ?? 0), 'v' => (int)($visDay[$d] ?? 0)]; }

    $statusCounts = $q("SELECT status, COUNT(*) c FROM orders WHERE deleted_at IS NULL AND created_at BETWEEN ? AND ? GROUP BY status", [$from, $to])->fetchAll(PDO::FETCH_KEY_PAIR);
    $payTypes = $q("SELECT payment_method, COUNT(*) c, SUM(total) t FROM orders WHERE $ok AND created_at BETWEEN ? AND ? GROUP BY payment_method", [$from, $to])->fetchAll(PDO::FETCH_UNIQUE);
    $catSales = $q("SELECT c.name_tr n, SUM(i.total) t, SUM(i.quantity) q FROM order_items i JOIN orders o ON o.id = i.order_id JOIN products p ON p.id = i.product_id JOIN categories c ON c.id = p.category_id WHERE o.deleted_at IS NULL AND o.status <> 'cancelled' AND o.created_at BETWEEN ? AND ? GROUP BY c.id ORDER BY t DESC LIMIT 6", [$from, $to])->fetchAll();
    $citySales = $q("SELECT JSON_UNQUOTE(JSON_EXTRACT(shipping_address, '$.city')) n, COUNT(*) c, SUM(total) t FROM orders WHERE $ok AND created_at BETWEEN ? AND ? GROUP BY n ORDER BY t DESC LIMIT 6", [$from, $to])->fetchAll();
    $topProducts = $q("SELECT i.product_id id, i.code, i.name, SUM(i.quantity) q, SUM(i.total) t FROM order_items i JOIN orders o ON o.id = i.order_id WHERE o.deleted_at IS NULL AND o.status <> 'cancelled' AND o.created_at BETWEEN ? AND ? GROUP BY i.product_id, i.code, i.name ORDER BY q DESC LIMIT 6", [$from, $to])->fetchAll();
    if (!$topProducts) $topProducts = $q('SELECT id, code, name_tr name, sales_count q, 0 t FROM products WHERE deleted_at IS NULL AND sales_count > 0 ORDER BY sales_count DESC LIMIT 6')->fetchAll();
    $lastOrders = $q("SELECT * FROM orders WHERE deleted_at IS NULL ORDER BY id DESC LIMIT 7")->fetchAll();
    $critical = $q("SELECT id, code, name_tr, stock, critical_stock FROM products WHERE deleted_at IS NULL AND is_active = 1 AND stock <= critical_stock ORDER BY stock, name_tr LIMIT 6")->fetchAll();
    $counts = $q("SELECT
        (SELECT COUNT(*) FROM products WHERE deleted_at IS NULL AND is_active = 1) active_products,
        (SELECT COUNT(*) FROM products WHERE deleted_at IS NULL AND is_active = 1 AND stock <= critical_stock) critical,
        (SELECT COUNT(*) FROM products WHERE deleted_at IS NULL AND stock = 0) out_of_stock,
        (SELECT COUNT(*) FROM users WHERE role='customer') members,
        (SELECT COUNT(*) FROM questions WHERE answer IS NULL) q_wait,
        (SELECT COUNT(*) FROM reviews WHERE is_approved = 0) r_wait,
        (SELECT COUNT(*) FROM contact_messages WHERE is_read = 0) m_wait,
        (SELECT COUNT(*) FROM transfer_notices WHERE is_approved = 0) t_wait,
        (SELECT COUNT(*) FROM return_requests WHERE status = 'pending') ret_wait,
        (SELECT COUNT(*) FROM orders WHERE payment_status = 'waiting' AND status <> 'cancelled' AND deleted_at IS NULL) pay_wait,
        (SELECT COUNT(*) FROM orders WHERE status = 'pending' AND deleted_at IS NULL) to_prepare,
        (SELECT COUNT(*) FROM stock_alerts WHERE notified_at IS NULL) alerts,
        (SELECT COALESCE(SUM(stock * COALESCE(purchase_price, 0)), 0) FROM products WHERE deleted_at IS NULL) stock_value")->fetch();
    $newMembers = $newMem($from, $to);
    $todayTotals = $sum(date('Y-m-d 00:00:00'), date('Y-m-d 23:59:59'));
    admin_view('dashboard', compact('p', 'days', 'from', 'to', 'kpis', 'series', 'statusCounts', 'payTypes', 'catSales', 'citySales', 'topProducts', 'lastOrders', 'critical', 'counts', 'newMembers', 'todayTotals', 'cur'));
}

/* ───────── Ürünler ───────── */
function page_products(): void
{
    $where = ['1=1']; $params = [];
    $deleted = !empty($_GET['deleted']);
    $where[] = $deleted ? 'p.deleted_at IS NOT NULL' : 'p.deleted_at IS NULL';
    if (($q = trim($_GET['q'] ?? '')) !== '') {
        $where[] = '(p.name_tr LIKE ? OR p.code LIKE ? OR p.barcode LIKE ? OR EXISTS (SELECT 1 FROM product_refs r WHERE r.product_id = p.id AND r.code LIKE ?))';
        array_push($params, "%$q%", "%$q%", "%$q%", "%$q%");
    }
    foreach (['category_id' => 'p.category_id', 'brand_id' => 'p.brand_id', 'supplier_id' => 'p.supplier_id'] as $k => $col)
        if (!empty($_GET[$k])) { $where[] = "$col = ?"; $params[] = $_GET[$k]; }
    if (isset($_GET['active']) && $_GET['active'] !== '') { $where[] = 'p.is_active = ?'; $params[] = (int)$_GET['active']; }
    if (($s = $_GET['stock'] ?? '') === 'out') $where[] = 'p.stock = 0';
    if ($s === 'critical') $where[] = 'p.stock <= p.critical_stock';
    if ($s === 'in') $where[] = 'p.stock > 0';
    if (!empty($_GET['featured'])) $where[] = 'p.is_featured = 1';
    if (!empty($_GET['discount'])) $where[] = 'p.discount_price IS NOT NULL';
    $miss = $_GET['missing'] ?? '';
    if ($miss === 'desc') $where[] = "(p.desc_tr IS NULL OR p.desc_tr = '')";
    if ($miss === 'image') $where[] = 'NOT EXISTS (SELECT 1 FROM product_images i WHERE i.product_id = p.id)';
    if ($miss === 'seo') $where[] = "(p.seo_title IS NULL OR p.seo_title = '')";
    foreach (['price_min' => 'p.price >= ?', 'price_max' => 'p.price <= ?'] as $k => $cond) if (($_GET[$k] ?? '') !== '') { $where[] = $cond; $params[] = (float)$_GET[$k]; }

    $sort = ['new' => 'p.id DESC', 'name' => 'p.name_tr', 'price' => 'p.price', 'price_desc' => 'p.price DESC', 'stock' => 'p.stock', 'sales' => 'p.sales_count DESC'][$_GET['sort'] ?? 'new'] ?? 'p.id DESC';
    $w = implode(' AND ', $where);
    $page = max(1, (int)($_GET['page'] ?? 1)); $per = (int)($_GET['per'] ?? 25); $per = in_array($per, [25, 50, 100], true) ? $per : 25;
    $st = db()->prepare("SELECT COUNT(*) FROM products p WHERE $w"); $st->execute($params); $total = (int)$st->fetchColumn();
    $st = db()->prepare("SELECT p.*, c.name_tr AS cat, b.name AS brand, (SELECT url FROM product_images i WHERE i.product_id = p.id ORDER BY is_main DESC, sort_order LIMIT 1) img
        FROM products p LEFT JOIN categories c ON c.id = p.category_id LEFT JOIN brands b ON b.id = p.brand_id WHERE $w ORDER BY $sort LIMIT $per OFFSET " . (($page - 1) * $per));
    $st->execute($params);
    $rows = $st->fetchAll();

    // Toplu işlem
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_POST['ids'])) {
        $ids = array_map('intval', $_POST['ids']); $in = implode(',', $ids);
        switch ($_POST['bulk'] ?? '') {
            case 'activate': db()->exec("UPDATE products SET is_active = 1 WHERE id IN ($in)"); break;
            case 'deactivate': db()->exec("UPDATE products SET is_active = 0 WHERE id IN ($in)"); break;
            case 'feature': db()->exec("UPDATE products SET is_featured = 1 WHERE id IN ($in)"); break;
            case 'unfeature': db()->exec("UPDATE products SET is_featured = 0 WHERE id IN ($in)"); break;
            case 'delete': db()->exec("UPDATE products SET deleted_at = NOW() WHERE id IN ($in)"); break;
            case 'restore': db()->exec("UPDATE products SET deleted_at = NULL WHERE id IN ($in)"); break;
            case 'purge': foreach ($ids as $pid) purge_product($pid); break;
        }
        log_action('bulk_' . ($_POST['bulk'] ?? ''), 'products', null, $ids);
        flash(count($ids) . ' ürün güncellendi.');
        redirect($_SERVER['REQUEST_URI']);
    }
    $cats = opts_query('SELECT id, name_tr FROM categories ORDER BY sort_order');
    $brands = opts_query('SELECT id, name FROM brands ORDER BY sort_order');
    $suppliers = opts_query('SELECT id, name FROM suppliers ORDER BY name');
    admin_view('products', compact('rows', 'total', 'page', 'per', 'cats', 'brands', 'suppliers', 'deleted'));
}

function purge_product(int $id): void
{
    foreach (['product_images', 'product_specs', 'product_compat', 'product_refs', 'product_tags', 'reviews', 'questions', 'stock_alerts'] as $t)
        db()->prepare("DELETE FROM $t WHERE product_id = ?")->execute([$id]);
    db()->prepare('DELETE FROM product_related WHERE product_id = ? OR related_id = ?')->execute([$id, $id]);
    db()->prepare('DELETE FROM products WHERE id = ?')->execute([$id]);
}

function page_product_delete(int $id): void
{
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') redirect(aurl('products'));
    db()->prepare('UPDATE products SET deleted_at = NOW() WHERE id = ?')->execute([$id]);
    log_action('delete', 'products', $id);
    flash('Ürün geri dönüşüm kutusuna taşındı.');
    redirect(aurl('products'));
}
function page_product_restore(int $id): void
{
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') redirect(aurl('products?deleted=1'));
    db()->prepare('UPDATE products SET deleted_at = NULL WHERE id = ?')->execute([$id]);
    flash('Ürün geri yüklendi.');
    redirect(aurl('products?deleted=1'));
}

function page_product_form(?int $id): void
{
    $p = ['is_active' => 1, 'vat_rate' => 20, 'currency' => 'TRY', 'stock' => 0, 'critical_stock' => 3, 'desi' => 1, 'estimated_ship_days' => 2];
    $specs = $compat = $refs = $images = $tags = $related = [];
    if ($id) {
        $st = db()->prepare('SELECT * FROM products WHERE id = ?'); $st->execute([$id]); $p = $st->fetch();
        if (!$p) { flash('Ürün bulunamadı', 'err'); redirect(aurl('products')); }
        $specs = db()->query("SELECT spec_key_id, value FROM product_specs WHERE product_id = $id")->fetchAll(PDO::FETCH_KEY_PAIR);
        $compat = db()->query("SELECT machine_model_id FROM product_compat WHERE product_id = $id")->fetchAll(PDO::FETCH_COLUMN);
        $refs = db()->query("SELECT kind, code FROM product_refs WHERE product_id = $id")->fetchAll();
        $images = db()->query("SELECT * FROM product_images WHERE product_id = $id ORDER BY is_main DESC, sort_order")->fetchAll();
        $tags = db()->query("SELECT tag_id FROM product_tags WHERE product_id = $id")->fetchAll(PDO::FETCH_COLUMN);
        $related = db()->query("SELECT related_id FROM product_related WHERE product_id = $id")->fetchAll(PDO::FETCH_COLUMN);
    }
    $errors = [];
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $in = $_POST;
        $locCols = [];
        foreach (['name', 'short_desc', 'desc'] as $__f) foreach (extra_locales() as $__l) if ($__l === 'en' || loc_ready()) $locCols[] = $__f . '_' . $__l;
        $cols = array_merge(['code', 'barcode', 'gtip', 'slug', 'name_tr', 'short_desc_tr', 'desc_tr'], $locCols, ['category_id', 'brand_id', 'supplier_id',
            'price', 'discount_price', 'market_price', 'purchase_price', 'currency', 'vat_rate', 'stock', 'critical_stock', 'desi', 'estimated_ship_days',
            'icon', 'seo_title', 'seo_description', 'sort_order']);
        $data = [];
        foreach ($cols as $c) { $v = isset($in[$c]) ? trim((string)$in[$c]) : null; $data[$c] = ($v === '' ? null : $v); }
        foreach (['price', 'discount_price', 'market_price', 'purchase_price', 'desi'] as $c) if ($data[$c] !== null) $data[$c] = str_replace(',', '.', $data[$c]);
        foreach (['is_active', 'is_featured', 'is_new', 'free_shipping'] as $c) $data[$c] = isset($in[$c]) ? 1 : 0;
        $data['slug'] = slugify($data['slug'] ?: (($data['name_tr'] ?? '') . '-' . ($data['code'] ?? '')));
        foreach (['code' => 'Ürün kodu', 'name_tr' => 'Ürün adı', 'category_id' => 'Kategori', 'price' => 'Satış fiyatı'] as $c => $l) if ($data[$c] === null) $errors[$c] = "$l zorunludur";
        foreach (['stock', 'critical_stock', 'vat_rate', 'estimated_ship_days', 'sort_order'] as $c) $data[$c] = (int)($data[$c] ?? 0);
        if (!$errors) {
            $pdo = db();
            try {
                $pdo->beginTransaction();
                if ($id) {
                    $oldStock = (int)$p['stock'];
                    $sets = implode(',', array_map(function ($c) { return "`$c` = ?"; }, array_keys($data)));
                    $pdo->prepare("UPDATE products SET $sets WHERE id = ?")->execute(array_merge(array_values($data), [$id]));
                    if ($oldStock !== $data['stock']) $pdo->prepare("INSERT INTO stock_movements (product_id, kind, quantity, note, user_id) VALUES (?, 'adjust', ?, 'Ürün kartından güncellendi', ?)")->execute([$id, $data['stock'], admin_user()['id']]);
                } else {
                    $data['published_at'] = date('Y-m-d H:i:s');
                    $c = array_keys($data);
                    $pdo->prepare('INSERT INTO products (`' . implode('`,`', $c) . '`) VALUES (' . implode(',', array_fill(0, count($c), '?')) . ')')->execute(array_values($data));
                    $id = (int)$pdo->lastInsertId();
                    if ($data['stock'] > 0) $pdo->prepare("INSERT INTO stock_movements (product_id, kind, quantity, note, user_id) VALUES (?, 'in', ?, 'İlk stok', ?)")->execute([$id, $data['stock'], admin_user()['id']]);
                }
                // Teknik detaylar
                $pdo->prepare('DELETE FROM product_specs WHERE product_id = ?')->execute([$id]);
                foreach ($in['spec'] ?? [] as $k => $v) if (trim($v) !== '') $pdo->prepare('INSERT INTO product_specs (product_id, spec_key_id, value) VALUES (?,?,?)')->execute([$id, (int)$k, trim($v)]);
                // Uyumlu modeller
                $pdo->prepare('DELETE FROM product_compat WHERE product_id = ?')->execute([$id]);
                foreach (array_unique($in['compat'] ?? []) as $m) $pdo->prepare('INSERT INTO product_compat (product_id, machine_model_id) VALUES (?,?)')->execute([$id, (int)$m]);
                // Referans kodları
                $pdo->prepare('DELETE FROM product_refs WHERE product_id = ?')->execute([$id]);
                foreach ($in['ref_code'] ?? [] as $i => $code) if (trim($code) !== '') $pdo->prepare('INSERT INTO product_refs (product_id, kind, code) VALUES (?,?,?)')->execute([$id, $in['ref_kind'][$i] ?? 'equivalent', trim($code)]);
                // Görseller
                $pdo->prepare('DELETE FROM product_images WHERE product_id = ?')->execute([$id]);
                foreach ($in['img_url'] ?? [] as $i => $u) if (trim($u) !== '') $pdo->prepare('INSERT INTO product_images (product_id, url, kind, is_main, sort_order) VALUES (?,?,?,?,?)')->execute([$id, trim($u), $in['img_kind'][$i] ?? 'photo', (int)(($in['img_main'] ?? '0') == $i), $i]);
                // Etiketler
                $pdo->prepare('DELETE FROM product_tags WHERE product_id = ?')->execute([$id]);
                foreach (array_unique($in['tags'] ?? []) as $t) $pdo->prepare('INSERT INTO product_tags (product_id, tag_id) VALUES (?,?)')->execute([$id, (int)$t]);
                // Birlikte alınanlar
                $pdo->prepare('DELETE FROM product_related WHERE product_id = ?')->execute([$id]);
                foreach (array_unique($in['related'] ?? []) as $rid) if ((int)$rid !== $id) $pdo->prepare('INSERT INTO product_related (product_id, related_id) VALUES (?,?)')->execute([$id, (int)$rid]);
                // Yüklenen dosyalar
                if (!empty($_FILES['upload']['name'][0])) {
                    $dir = ROOT . '/uploads/products'; if (!is_dir($dir)) mkdir($dir, 0775, true);
                    $n = count($in['img_url'] ?? []);
                    foreach ($_FILES['upload']['name'] as $i => $fn) {
                        if ($_FILES['upload']['error'][$i] !== UPLOAD_ERR_OK) continue;
                        $ext = strtolower(pathinfo($fn, PATHINFO_EXTENSION));
                        if (!in_array($ext, ['jpg', 'jpeg', 'png', 'webp', 'gif', 'mp4'], true)) continue;
                        $name = $data['slug'] . '-' . bin2hex(random_bytes(3)) . '.' . $ext;
                        move_uploaded_file($_FILES['upload']['tmp_name'][$i], "$dir/$name");
                        $pdo->prepare('INSERT INTO product_images (product_id, url, kind, is_main, sort_order) VALUES (?,?,?,?,?)')->execute([$id, base_path() . '/uploads/products/' . $name, $ext === 'mp4' ? 'video' : 'photo', $n === 0 && $i === 0 ? 1 : 0, $n + $i]);
                    }
                }
                $pdo->commit();
                log_action($id ? 'update' : 'create', 'products', $id);
                flash('Ürün kaydedildi.' . auto_translate_note('products', $id));
                redirect(!empty($in['_stay']) ? aurl("products/$id") : aurl('products'));
            } catch (PDOException $e) {
                $pdo->rollBack();
                $errors['_'] = strpos($e->getMessage(), 'Duplicate') !== false ? 'Bu ürün kodu veya URL zaten kullanılıyor.' : $e->getMessage();
            }
        }
        $p = array_merge($p, $data);
        $specs = $in['spec'] ?? []; $compat = $in['compat'] ?? []; $tags = $in['tags'] ?? []; $related = $in['related'] ?? [];
        $refs = []; foreach ($in['ref_code'] ?? [] as $i => $c) $refs[] = ['kind' => $in['ref_kind'][$i] ?? 'equivalent', 'code' => $c];
        $images = []; foreach ($in['img_url'] ?? [] as $i => $u) $images[] = ['url' => $u, 'kind' => $in['img_kind'][$i] ?? 'photo', 'is_main' => ($in['img_main'] ?? '0') == $i];
    }
    $cats = opts_query('SELECT id, name_tr FROM categories ORDER BY sort_order');
    $brands = db()->query('SELECT * FROM brands ORDER BY sort_order')->fetchAll();
    $models = db()->query('SELECT * FROM machine_models ORDER BY name')->fetchAll();
    $specKeys = db()->query('SELECT * FROM spec_keys ORDER BY sort_order')->fetchAll();
    $allTags = opts_query('SELECT id, name FROM tags ORDER BY name');
    $suppliers = opts_query('SELECT id, name FROM suppliers ORDER BY name');
    $allProducts = opts_query('SELECT id, CONCAT(code, " - ", name_tr) FROM products WHERE deleted_at IS NULL ORDER BY code');
    admin_view('product_form', compact('id', 'p', 'specs', 'compat', 'refs', 'images', 'tags', 'related', 'errors', 'cats', 'brands', 'models', 'specKeys', 'allTags', 'suppliers', 'allProducts'));
}

/* ───────── Toplu ürün işlemleri (Excel/CSV) ───────── */
function page_products_bulk(): void
{
    $cols = ['code', 'name_tr', 'name_en', 'category_slug', 'brand_slug', 'price', 'discount_price', 'market_price', 'purchase_price', 'stock', 'vat_rate', 'short_desc_tr', 'desc_tr', 'is_active'];
    if (($_GET['do'] ?? '') === 'template' || ($_GET['do'] ?? '') === 'export') {
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="' . (($_GET['do'] === 'export') ? 'urunler-' . date('Y-m-d') : 'urun-sablonu') . '.csv"');
        $out = fopen('php://output', 'w'); fwrite($out, "\xEF\xBB\xBF");
        fputcsv($out, $cols, ';');
        if ($_GET['do'] === 'export') {
            foreach (db()->query('SELECT p.*, c.slug category_slug, b.slug brand_slug FROM products p LEFT JOIN categories c ON c.id=p.category_id LEFT JOIN brands b ON b.id=p.brand_id WHERE p.deleted_at IS NULL') as $r)
                fputcsv($out, array_map(function ($c) use ($r) { return $r[$c] ?? ''; }, $cols), ';');
        } else fputcsv($out, ['AS99999', 'Örnek Ürün', 'Sample Product', 'pickup-grubu', 'gallignani', '1250', '', '1400', '800', '10', '20', 'Kısa açıklama', 'Uzun açıklama', '1'], ';');
        exit;
    }
    $result = null;
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_FILES['file']['tmp_name'])) {
        $result = ['total' => 0, 'insert' => 0, 'update' => 0, 'errors' => []];
        $catMap = db()->query('SELECT slug, id FROM categories')->fetchAll(PDO::FETCH_KEY_PAIR);
        $brandMap = db()->query('SELECT slug, id FROM brands')->fetchAll(PDO::FETCH_KEY_PAIR);
        $fh = fopen($_FILES['file']['tmp_name'], 'r');
        $first = fgets($fh); $delim = substr_count($first, ';') >= substr_count($first, ',') ? ';' : ','; rewind($fh);
        $head = array_map(function ($h) { return trim(str_replace("\xEF\xBB\xBF", '', $h)); }, fgetcsv($fh, 0, $delim));
        $line = 1;
        while (($row = fgetcsv($fh, 0, $delim)) !== false) {
            $line++; if (!array_filter($row)) continue; $result['total']++;
            $r = array_combine($head, array_pad($row, count($head), ''));
            if (empty($r['code']) || empty($r['name_tr'])) { $result['errors'][] = "Satır $line: ürün kodu ve adı zorunlu"; continue; }
            $catId = $catMap[$r['category_slug'] ?? ''] ?? null;
            if (!$catId) { $result['errors'][] = "Satır $line ({$r['code']}): kategori bulunamadı '{$r['category_slug']}'"; continue; }
            $d = ['code' => $r['code'], 'name_tr' => $r['name_tr'], 'name_en' => $r['name_en'] ?: null, 'category_id' => $catId, 'brand_id' => $brandMap[$r['brand_slug'] ?? ''] ?? null,
                'price' => (float)str_replace(',', '.', $r['price'] ?: 0), 'discount_price' => $r['discount_price'] !== '' ? (float)str_replace(',', '.', $r['discount_price']) : null,
                'market_price' => ($r['market_price'] ?? '') !== '' ? (float)str_replace(',', '.', $r['market_price']) : null,
                'purchase_price' => ($r['purchase_price'] ?? '') !== '' ? (float)str_replace(',', '.', $r['purchase_price']) : null,
                'stock' => (int)($r['stock'] ?? 0), 'vat_rate' => (int)($r['vat_rate'] ?: 20), 'short_desc_tr' => $r['short_desc_tr'] ?? null, 'desc_tr' => $r['desc_tr'] ?? null, 'is_active' => ($r['is_active'] ?? '1') === '0' ? 0 : 1];
            $ex = db()->prepare('SELECT id FROM products WHERE code = ?'); $ex->execute([$d['code']]); $pid = $ex->fetchColumn();
            try {
                if ($pid) {
                    $sets = implode(',', array_map(function ($c) { return "`$c` = ?"; }, array_keys($d)));
                    db()->prepare("UPDATE products SET $sets WHERE id = ?")->execute(array_merge(array_values($d), [$pid])); $result['update']++;
                } else {
                    $d['slug'] = slugify($d['name_tr'] . '-' . $d['code']); $d['published_at'] = date('Y-m-d H:i:s');
                    $c = array_keys($d);
                    db()->prepare('INSERT INTO products (`' . implode('`,`', $c) . '`) VALUES (' . implode(',', array_fill(0, count($c), '?')) . ')')->execute(array_values($d)); $result['insert']++;
                }
            } catch (PDOException $e) { $result['errors'][] = "Satır $line ({$r['code']}): " . $e->getMessage(); }
        }
        log_action('bulk_import', 'products', null, ['insert' => $result['insert'], 'update' => $result['update']]);
    }
    admin_view('products_bulk', compact('cols', 'result'));
}

function page_products_sort(): void
{
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        foreach ($_POST['sort'] ?? [] as $pid => $s) db()->prepare('UPDATE products SET sort_order = ?, is_featured = ? WHERE id = ?')->execute([(int)$s, isset($_POST['featured'][$pid]) ? 1 : 0, (int)$pid]);
        flash('Sıralama kaydedildi.'); redirect(aurl('products/sort'));
    }
    $rows = db()->query('SELECT id, code, name_tr, icon, is_featured, sort_order, sales_count FROM products WHERE deleted_at IS NULL ORDER BY is_featured DESC, sort_order, id')->fetchAll();
    admin_view('products_sort', compact('rows'));
}

/* ───────── Stok ───────── */
function page_stock(): void
{
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        foreach ($_POST['stock'] ?? [] as $pid => $new) {
            $pid = (int)$pid; $new = (int)$new;
            $old = (int)db()->query("SELECT stock FROM products WHERE id = $pid")->fetchColumn();
            if ($old !== $new) {
                db()->prepare('UPDATE products SET stock = ?, critical_stock = ? WHERE id = ?')->execute([$new, (int)($_POST['critical'][$pid] ?? 3), $pid]);
                db()->prepare("INSERT INTO stock_movements (product_id, kind, quantity, note, user_id) VALUES (?, ?, ?, 'Stok yönetiminden', ?)")->execute([$pid, $new > $old ? 'in' : 'out', abs($new - $old), admin_user()['id']]);
            } else db()->prepare('UPDATE products SET critical_stock = ? WHERE id = ?')->execute([(int)($_POST['critical'][$pid] ?? 3), $pid]);
        }
        flash('Stoklar güncellendi.'); redirect($_SERVER['REQUEST_URI']);
    }
    $where = 'p.deleted_at IS NULL'; $params = [];
    if (!empty($_GET['critical'])) $where .= ' AND p.stock <= p.critical_stock';
    if (!empty($_GET['category_id'])) { $where .= ' AND p.category_id = ?'; $params[] = $_GET['category_id']; }
    if (($q = trim($_GET['q'] ?? '')) !== '') { $where .= ' AND (p.name_tr LIKE ? OR p.code LIKE ?)'; $params[] = "%$q%"; $params[] = "%$q%"; }
    $st = db()->prepare("SELECT p.*, c.name_tr cat, (SELECT MAX(created_at) FROM stock_movements m WHERE m.product_id = p.id AND m.kind='in') last_in FROM products p LEFT JOIN categories c ON c.id=p.category_id WHERE $where ORDER BY p.stock <= p.critical_stock DESC, p.stock");
    $st->execute($params); $rows = $st->fetchAll();
    $sum = db()->query('SELECT COUNT(*) n, SUM(stock) s, SUM(stock * COALESCE(purchase_price,0)) v, SUM(stock <= critical_stock) c FROM products WHERE deleted_at IS NULL')->fetch();
    $cats = opts_query('SELECT id, name_tr FROM categories ORDER BY sort_order');
    admin_view('stock', compact('rows', 'sum', 'cats'));
}

/* ───────── Siparişler ───────── */
function order_status_labels(): array { return ['pending' => ['Bekliyor', 'badge-warn'], 'preparing' => ['Hazırlanıyor', 'badge-info'], 'shipped' => ['Kargoda', 'badge-info'], 'delivered' => ['Teslim Edildi', 'badge-ok'], 'cancelled' => ['İptal', 'badge-err'], 'returned' => ['İade', 'badge-muted']]; }
function payment_status_labels(): array { return ['waiting' => ['Ödeme Bekleniyor', 'badge-warn'], 'paid' => ['Ödendi', 'badge-ok'], 'failed' => ['Başarısız', 'badge-err'], 'refunded' => ['İade Edildi', 'badge-muted']]; }
function payment_method_labels(): array { return ['card' => 'Kredi Kartı', 'transfer' => 'Havale / EFT', 'cod' => 'Kapıda Ödeme']; }

function page_orders(): void
{
    $where = [!empty($_GET['deleted']) ? 'o.deleted_at IS NOT NULL' : 'o.deleted_at IS NULL']; $params = [];
    foreach (['status', 'payment' => 'payment_status', 'method' => 'payment_method'] as $k => $col) {
        if (is_int($k)) { $k = $col; }
        if (!empty($_GET[$k])) { $where[] = "o.$col = ?"; $params[] = $_GET[$k]; }
    }
    if (($q = trim($_GET['q'] ?? '')) !== '') { $where[] = '(o.order_no LIKE ? OR o.customer_name LIKE ? OR o.email LIKE ? OR o.phone LIKE ?)'; array_push($params, "%$q%", "%$q%", "%$q%", "%$q%"); }
    if (!empty($_GET['from'])) { $where[] = 'o.created_at >= ?'; $params[] = $_GET['from'] . ' 00:00:00'; }
    if (!empty($_GET['to'])) { $where[] = 'o.created_at <= ?'; $params[] = $_GET['to'] . ' 23:59:59'; }
    $w = implode(' AND ', $where);
    $st = db()->prepare("SELECT o.*, (SELECT SUM(quantity) FROM order_items i WHERE i.order_id = o.id) qty FROM orders o WHERE $w ORDER BY o.id DESC LIMIT 200");
    $st->execute($params); $rows = $st->fetchAll();
    $tabs = db()->query("SELECT status, COUNT(*) FROM orders WHERE deleted_at IS NULL GROUP BY status")->fetchAll(PDO::FETCH_KEY_PAIR);
    admin_view('orders', compact('rows', 'tabs'));
}

function page_order_view(int $id): void
{
    $st = db()->prepare('SELECT * FROM orders WHERE id = ?'); $st->execute([$id]); $o = $st->fetch();
    if (!$o) { flash('Sipariş bulunamadı', 'err'); redirect(aurl('orders')); }
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $a = $_POST['action'] ?? '';
        if ($a === 'status') {
            db()->prepare('UPDATE orders SET status = ?, payment_status = ?, admin_note = ? WHERE id = ?')->execute([$_POST['status'], $_POST['payment_status'], $_POST['admin_note'] ?: null, $id]);
            if ($_POST['status'] === 'cancelled' && $o['status'] !== 'cancelled') {
                foreach (db()->query("SELECT product_id, quantity FROM order_items WHERE order_id = $id") as $it) {
                    db()->prepare('UPDATE products SET stock = stock + ? WHERE id = ?')->execute([$it['quantity'], $it['product_id']]);
                    db()->prepare("INSERT INTO stock_movements (product_id, kind, quantity, note, order_id) VALUES (?, 'return', ?, 'Sipariş iptali', ?)")->execute([$it['product_id'], $it['quantity'], $id]);
                }
            }
        } elseif ($a === 'aras') {
            $sa = json_decode($o['shipping_address'], true) ?: [];
            $isCod = $o['payment_method'] === 'cod' && $o['payment_status'] !== 'paid';
            [$ok, $msg, $code, $lid] = aras_send(['code' => aras_code('order', $id), 'name' => $sa['fullName'] ?? $o['customer_name'], 'phone' => $sa['phone'] ?? $o['phone'], 'city' => $sa['city'] ?? '', 'town' => $sa['district'] ?? '', 'address' => $sa['address'] ?? '',
                'pieces' => $_POST['pieces'] ?? 1, 'weight' => $_POST['weight'] ?? 1, 'payor' => $isCod ? '1' : ($_POST['payor'] ?? '1'), 'desc' => 'Siparis ' . $o['order_no'], 'cargo_type' => ($_POST['cargo_type'] ?? '') === 'file' ? 'file' : 'parcel',
                'cod' => $isCod, 'cod_amount_num' => $isCod ? (float)$o['total'] : 0, 'cod_type' => '0', 'source' => 'order', 'ref_id' => $id]);
            if ($ok) {
                $cid = (int)db()->query("SELECT id FROM shipping_companies WHERE code = 'aras' LIMIT 1")->fetchColumn();
                if (!(int)db()->query("SELECT COUNT(*) FROM shipments WHERE order_id = $id AND tracking_no = " . db()->quote($code))->fetchColumn())
                    db()->prepare('INSERT INTO shipments (order_id, company_id, tracking_no, shipped_at) VALUES (?,?,?,NOW())')->execute([$id, $cid, $code]);
                db()->prepare("UPDATE orders SET status = 'shipped' WHERE id = ?")->execute([$id]);
                $msg .= $isCod ? ' · Kapıda ödeme tutarı tahsilatlı olarak gönderildi.' : '';
            }
            log_action('order_aras', 'orders', $id);
            flash($msg, $ok ? 'ok' : 'err'); redirect($ok && $lid ? aurl('acc/aras-label/' . $lid) : aurl("orders/$id"));
        } elseif ($a === 'ship') {
            db()->prepare('INSERT INTO shipments (order_id, company_id, tracking_no, shipped_at) VALUES (?,?,?,NOW())')->execute([$id, (int)$_POST['company_id'], $_POST['tracking_no']]);
            db()->prepare("UPDATE orders SET status = 'shipped' WHERE id = ?")->execute([$id]);
        } elseif ($a === 'delete') {
            db()->prepare('UPDATE orders SET deleted_at = NOW() WHERE id = ?')->execute([$id]);
            flash('Sipariş silindi.'); redirect(aurl('orders'));
        }
        log_action('order_' . $a, 'orders', $id);
        flash('Sipariş güncellendi.'); redirect(aurl("orders/$id"));
    }
    $items = db()->query("SELECT * FROM order_items WHERE order_id = $id")->fetchAll();
    $ships = db()->query("SELECT s.*, c.name company, c.tracking_url FROM shipments s LEFT JOIN shipping_companies c ON c.id = s.company_id WHERE order_id = $id")->fetchAll();
    $pays = db()->query("SELECT * FROM payments WHERE order_id = $id ORDER BY id DESC")->fetchAll();
    $companies = opts_query('SELECT id, name FROM shipping_companies WHERE is_active = 1');
    admin_view('order_view', compact('o', 'items', 'ships', 'pays', 'companies'));
}

/* ───────── Ayarlar (settings tablosu, JSON) ───────── */
function settings_schema(): array
{
    return [
        'general' => ['Genel Ayarlar', ['siteName' => ['Site Adı', 'text'], 'companyTitle' => ['Firma Ünvanı', 'text'], 'phone' => ['Telefon', 'text'], 'email' => ['E-posta', 'email'], 'pricesExcludeVat' => ['Ürün fiyatları KDV hariç (KDV sepette ayrıca eklenir)', 'bool'],
            'address' => ['Adres', 'textarea'], 'workingHours' => ['Çalışma Saatleri', 'text'], 'mapLat' => ['Harita Enlem (lat)', 'text'], 'mapLng' => ['Harita Boylam (lng)', 'text'], 'mapUrl' => ['Google Haritalar Bağlantısı (Yol Tarifi)', 'text'], 'taxOffice' => ['Vergi Dairesi', 'text'], 'taxNumber' => ['Vergi No', 'text'], 'mersis' => ['MERSİS No', 'text'], 'etbis' => ['ETBİS Kodu', 'text'],
            'facebook' => ['Facebook URL', 'text'], 'instagram' => ['Instagram URL', 'text'], 'youtube' => ['YouTube URL', 'text'], 'maintenance' => ['Bakım Modu', 'bool']]],
        'shipping' => ['Kargo Ayarları', ['freeShippingLimit' => ['Ücretsiz Kargo Limiti (TL)', 'number'], 'defaultFee' => ['Varsayılan Kargo Ücreti (TL)', 'number'], 'desiPrice' => ['Desi Başına Ek Ücret', 'number'], 'shipDays' => ['Kargoya Veriliş (iş günü)', 'number'], 'sameDayUntil' => ['Aynı Gün Kargo Saati (ör. 14:00)', 'text']]],
        'payment' => ['Ödeme Ayarları', ['card' => ['Kredi Kartı ile Ödeme', 'bool'], 'transfer' => ['Havale / EFT', 'bool'], 'transferDiscount' => ['Havale İndirimi (%)', 'number'], 'cod' => ['Kapıda Ödeme', 'bool'], 'codFee' => ['Kapıda Ödeme Hizmet Bedeli (TL)', 'number'], 'minOrder' => ['Minimum Sipariş Tutarı (TL)', 'number']]],
        'pos' => ['Sanal POS Kurulumu', ['provider' => ['Aktif POS', 'select', ['iyzico' => 'iyzico', 'paytr' => 'PayTR', 'none' => 'Kapalı']], 'iyzicoApiKey' => ['iyzico API Key', 'text'], 'iyzicoSecret' => ['iyzico Secret Key', 'password'], 'iyzicoSandbox' => ['iyzico Test Modu', 'bool'],
            'paytrMerchantId' => ['PayTR Mağaza No', 'text'], 'paytrKey' => ['PayTR Merchant Key', 'password'], 'paytrSalt' => ['PayTR Merchant Salt', 'password'], 'paytrTest' => ['PayTR Test Modu', 'bool'], 'maxInstallment' => ['Maksimum Taksit', 'number']]],

        'aras' => ['Aras Kargo Entegrasyonu', ['enabled' => ['Aras Kargo entegrasyonu AKTİF', 'bool'], 'mode' => ['Ortam (önce Test ile deneyin)', 'select', ['live' => 'Canlı', 'test' => 'Test']],
            'shipType' => ['Sevkiyat Türü (Aras ile anlaşmanıza göre)', 'select', ['piece' => 'Parça bazlı (koli sayısı/desi/kg gönderilir)', 'order' => 'Sipariş bazlı (ölçüler şubede girilir)']],
            'setUsername' => ['GÖNDERİ servisi (SetOrder) Kullanıcı Adı – Test ortamında boş bırakılırsa Aras test kullanıcısı kullanılır', 'text'], 'setPassword' => ['GÖNDERİ servisi (SetOrder) Şifresi – kayıtlıysa boş bırakın', 'password', null, 'Test ortamında boş bırakın: Aras test şifresi otomatik kullanılır'],
            'customerCode' => ['SORGULAMA servisi (XML) – Müşteri Kodu', 'text'], 'username' => ['SORGULAMA servisi (XML) – Kullanıcı Adı', 'text'], 'password' => ['SORGULAMA servisi (XML) – Şifre (kayıtlıysa boş bırakın)', 'password', null, 'Test ortamında boş bırakın: Aras test şifresi otomatik kullanılır'],
            'payor' => ['Kargo Ücretini Kim Öder?', 'select', ['1' => 'Gönderici (biz) öder', '2' => 'Alıcı öder']], 'weight' => ['Varsayılan Parça Ağırlığı / Desi', 'number'],
            'dummyName' => ['Şube kabul ekranındaki gönderici kaydı adı (etikette uyarı olarak çıkar, örn. SIRKALIP Parça Bazlı)', 'text'],
            'senderCity' => ['Gönderici İl (kargo fişinde görünür)', 'text'], 'senderTown' => ['Gönderici İlçe', 'text'], 'senderAddressId' => ['Gönderici Adres Kodu (birden fazla çıkış noktanız varsa – SenderAccountAddressId)', 'text']]],
        'translate' => ['Çeviri Ayarları (Otomatik Çeviri)', ['provider' => ['Çeviri Servisi', 'select', ['free' => 'Ücretsiz (anahtar gerekmez – garanti yok)', 'google' => 'Google Cloud Translation (anahtar)', 'deepl' => 'DeepL (anahtar)']], 'apiKey' => ['API Anahtarı (yalnızca Google/DeepL için; boş bırakırsanız mevcut anahtar korunur)', 'password'], 'auto' => ['Kaydederken boş dilleri otomatik çevir (ürün, kategori, sayfa vb.)', 'bool']]],
        'whatsapp' => ['WhatsApp Ayarları', ['number' => ['WhatsApp Numarası (90 ile, ör. 905xxxxxxxxx)', 'text'], 'showButton' => ['Sitede WhatsApp Butonu Göster', 'bool'], 'message' => ['Otomatik Mesaj', 'textarea'], 'productAsk' => ['Ürün Sayfasında "WhatsApp ile Sor"', 'bool']]],
        'email' => ['E-posta Hesapları (SMTP)', ['host' => ['SMTP Sunucu', 'text'], 'port' => ['Port', 'number'], 'secure' => ['Güvenlik', 'select', ['tls' => 'TLS', 'ssl' => 'SSL', '' => 'Yok']], 'user' => ['Kullanıcı Adı', 'text'], 'pass' => ['Şifre', 'password'], 'fromName' => ['Gönderen Adı', 'text'], 'fromEmail' => ['Gönderen E-posta', 'email'], 'notifyEmail' => ['Sipariş Bildirim E-postası', 'email']]],
        'design' => ['Tasarım Ayarları', ['primaryColor' => ['Ana Renk', 'color'], 'logo' => ['Logo URL', 'text'], 'favicon' => ['Favicon URL', 'text'], 'topbarText' => ['Üst Bar Sloganı', 'text'], 'heroTitle1' => ['Ana Başlık 1. Satır', 'text'], 'heroTitle2' => ['Ana Başlık 2. Satır', 'text'], 'heroText' => ['Ana Başlık Açıklama', 'textarea']]],
        'content' => ['Sabit Site İçerikleri', ['topbar1' => ['Üst Bar 1', 'text'], 'topbar2' => ['Üst Bar 2', 'text'], 'topbar3' => ['Üst Bar 3', 'text'], 'topbar4' => ['Üst Bar 4', 'text'], 'footerAbout' => ['Footer Hakkında Yazısı', 'textarea'], 'cartNote' => ['Sepet Sayfası Notu', 'textarea']]],
        'product' => ['Ürün Detay Ayarları', ['showStock' => ['Stok Adedini Göster', 'bool'], 'allowOutOfStock' => ['Stokta Olmayanı Satışa Aç', 'bool'], 'showReviews' => ['Yorumları Göster', 'bool'], 'autoApproveReviews' => ['Yorumları Otomatik Onayla', 'bool'], 'showQuestions' => ['Soru & Cevap Göster', 'bool'], 'perPage' => ['Listede Ürün Sayısı', 'number']]],
        'scripts' => ['Script Yönetimi', ['head' => ['<head> içine eklenecek kod (Google Analytics, Meta Pixel...)', 'textarea'], 'body' => ['</body> öncesine eklenecek kod', 'textarea'], 'gaId' => ['Google Analytics ID', 'text'], 'gtmId' => ['Google Tag Manager ID', 'text'], 'pixelId' => ['Meta Pixel ID', 'text']]],
        'cookies' => ['Çerez Yönetimi', ['enabled' => ['Çerez Bildirimi Göster', 'bool'], 'text' => ['Bildirim Metni', 'textarea'], 'policyUrl' => ['Çerez Politikası Sayfası', 'text']]],
        'sitemap' => ['XML Sitemap', ['enabled' => ['Sitemap Aktif', 'bool'], 'includeProducts' => ['Ürünler', 'bool'], 'includeCategories' => ['Kategoriler', 'bool'], 'includeBrands' => ['Markalar', 'bool'], 'includePages' => ['Sayfalar', 'bool']]],
    ];
}

function page_settings(string $key): void
{
    $schema = settings_schema();
    if (!isset($schema[$key])) { page_soon('settings/' . $key); return; }
    [$title, $fields] = $schema[$key];
    $st = db()->prepare('SELECT value FROM settings WHERE `key` = ?'); $st->execute([$key]);
    $val = json_decode((string)$st->fetchColumn(), true) ?: [];
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        foreach ($fields as $f => $spec) {
            if ($spec[1] === 'bool') $val[$f] = isset($_POST[$f]);
            elseif ($spec[1] === 'password') { if (($_POST[$f] ?? '') !== '') $val[$f] = $_POST[$f]; }
            elseif ($spec[1] === 'number') $val[$f] = $_POST[$f] === '' ? null : (float)str_replace(',', '.', $_POST[$f]);
            else $val[$f] = trim((string)($_POST[$f] ?? ''));
        }
        db()->prepare('INSERT INTO settings (`key`, value) VALUES (?, ?) ON DUPLICATE KEY UPDATE value = VALUES(value)')->execute([$key, json_encode($val, JSON_UNESCAPED_UNICODE)]);
        log_action('settings', 'settings', null, $key);
        flash('Ayarlar kaydedildi.'); redirect(aurl("settings/$key"));
    }
    if ($key === 'aras') $val = aras_form_values($val);
    admin_view('settings', compact('key', 'title', 'fields', 'val', 'schema'));
}

/* ───────── Toplu SEO ───────── */
function page_seo_bulk(string $type): void
{
    $map = ['products' => ['products', 'name_tr', 'Toplu Ürün SEO Güncelleme', 'deleted_at IS NULL'], 'categories' => ['categories', 'name_tr', 'Toplu Kategori SEO Güncelleme', '1=1'],
        'brands' => ['brands', 'name', 'Toplu Marka SEO Güncelleme', '1=1'], 'pages' => ['pages', 'title_tr', 'Toplu Sayfa SEO Güncelleme', '1=1']];
    if (!isset($map[$type])) { page_soon("seo/$type"); return; }
    [$table, $nameCol, $title, $w] = $map[$type];
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        foreach ($_POST['seo_title'] ?? [] as $rid => $t) {
            db()->prepare("UPDATE $table SET seo_title = ?, seo_description = ?, slug = ? WHERE id = ?")->execute([trim($t) ?: null, trim($_POST['seo_description'][$rid] ?? '') ?: null, slugify($_POST['slug'][$rid] ?? '') ?: null, (int)$rid]);
        }
        flash('SEO bilgileri güncellendi.'); redirect(aurl("seo/$type"));
    }
    if (!empty($_GET['auto'])) {
        foreach (db()->query("SELECT id, $nameCol n FROM $table WHERE $w AND (seo_title IS NULL OR seo_title = '')") as $r)
            db()->prepare("UPDATE $table SET seo_title = ? WHERE id = ?")->execute([$r['n'] . ' | SIR KALIP', $r['id']]);
        flash('Boş SEO başlıkları otomatik dolduruldu.'); redirect(aurl("seo/$type"));
    }
    $rows = db()->query("SELECT id, $nameCol AS name, slug, seo_title, seo_description FROM $table WHERE $w ORDER BY id")->fetchAll();
    admin_view('seo_bulk', compact('type', 'title', 'rows'));
}

/* ───────── Raporlar ───────── */
function page_reports(string $type): void
{
    $from = $_GET['from'] ?? date('Y-m-01'); $to = $_GET['to'] ?? date('Y-m-d');
    $f = $from . ' 00:00:00'; $t = $to . ' 23:59:59';
    $ok = "o.deleted_at IS NULL AND o.status <> 'cancelled' AND o.created_at BETWEEN ? AND ?";
    $defs = [
        'sales' => ['Satış Raporu', "SELECT DATE(o.created_at) 'Tarih', COUNT(*) 'Sipariş', SUM(o.subtotal) 'Ara Toplam', SUM(o.discount) 'İndirim', SUM(o.shipping_fee) 'Kargo', SUM(o.total) 'Toplam' FROM orders o WHERE $ok GROUP BY DATE(o.created_at) ORDER BY 1 DESC"],
        'products' => ['Ürün Bazlı Satış', "SELECT i.code 'Kod', i.name 'Ürün', SUM(i.quantity) 'Adet', SUM(i.total) 'Tutar' FROM order_items i JOIN orders o ON o.id=i.order_id WHERE $ok GROUP BY i.code, i.name ORDER BY 4 DESC"],
        'categories' => ['Kategori Bazlı Satış', "SELECT c.name_tr 'Kategori', SUM(i.quantity) 'Adet', SUM(i.total) 'Tutar' FROM order_items i JOIN orders o ON o.id=i.order_id JOIN products p ON p.id=i.product_id JOIN categories c ON c.id=p.category_id WHERE $ok GROUP BY c.id ORDER BY 3 DESC"],
        'brands' => ['Marka Bazlı Satış', "SELECT COALESCE(b.name,'-') 'Marka', SUM(i.quantity) 'Adet', SUM(i.total) 'Tutar' FROM order_items i JOIN orders o ON o.id=i.order_id JOIN products p ON p.id=i.product_id LEFT JOIN brands b ON b.id=p.brand_id WHERE $ok GROUP BY b.id ORDER BY 3 DESC"],
        'cities' => ['İl Bazlı Satış', "SELECT JSON_UNQUOTE(JSON_EXTRACT(o.shipping_address,'$.city')) 'İl', COUNT(*) 'Sipariş', SUM(o.total) 'Tutar' FROM orders o WHERE $ok GROUP BY 1 ORDER BY 3 DESC"],
        'payment' => ['Ödeme Tipine Göre', "SELECT o.payment_method 'Ödeme Tipi', COUNT(*) 'Sipariş', SUM(o.total) 'Tutar' FROM orders o WHERE $ok GROUP BY 1 ORDER BY 3 DESC"],
        'source' => ['Kaynağına Göre', "SELECT o.source 'Kaynak', COUNT(*) 'Sipariş', SUM(o.total) 'Tutar' FROM orders o WHERE $ok GROUP BY 1 ORDER BY 3 DESC"],
        'hourly' => ['Saatlik Satış', "SELECT LPAD(HOUR(o.created_at),2,'0') 'Saat', COUNT(*) 'Sipariş', SUM(o.total) 'Tutar' FROM orders o WHERE $ok GROUP BY 1 ORDER BY 1"],
        'profit' => ['Ürün Kârlılık Raporu', "SELECT i.code 'Kod', i.name 'Ürün', SUM(i.quantity) 'Adet', SUM(i.total) 'Satış', SUM(i.quantity*COALESCE(p.purchase_price,0)) 'Maliyet', SUM(i.total) - SUM(i.quantity*COALESCE(p.purchase_price,0)) 'Kâr' FROM order_items i JOIN orders o ON o.id=i.order_id JOIN products p ON p.id=i.product_id WHERE $ok GROUP BY i.code, i.name ORDER BY 6 DESC"],
        'bestsellers' => ['En Çok Satan Ürünler', "SELECT p.code 'Kod', p.name_tr 'Ürün', p.sales_count 'Toplam Satış', p.stock 'Stok' FROM products p WHERE p.deleted_at IS NULL AND ? IS NOT NULL AND ? IS NOT NULL ORDER BY p.sales_count DESC LIMIT 100"],
        'topcustomers' => ['En Fazla Sipariş Veren Üyeler', "SELECT o.customer_name 'Müşteri', o.email 'E-posta', COUNT(*) 'Sipariş', SUM(o.total) 'Toplam' FROM orders o WHERE $ok GROUP BY o.email, o.customer_name ORDER BY 4 DESC LIMIT 100"],
    ];
    if (!isset($defs[$type])) { page_soon("reports/$type"); return; }
    [$title, $sql] = $defs[$type];
    $st = db()->prepare($sql); $st->execute([$f, $t]); $rows = $st->fetchAll();
    if (($_GET['export'] ?? '') === 'csv') {
        header('Content-Type: text/csv; charset=utf-8'); header("Content-Disposition: attachment; filename=\"rapor-$type-$from-$to.csv\"");
        $o = fopen('php://output', 'w'); fwrite($o, "\xEF\xBB\xBF");
        if ($rows) fputcsv($o, array_keys($rows[0]), ';'); foreach ($rows as $r) fputcsv($o, $r, ';'); exit;
    }
    admin_view('reports', compact('type', 'title', 'rows', 'from', 'to', 'defs'));
}

function page_soon(string $route): void
{
    $name = null;
    foreach (admin_menu() as [, , , $children]) foreach ($children as [$cl, $cr]) if ('soon/' . slugify($cl) === $route) $name = $cl;
    admin_view('soon', ['name' => $name ?? 'Bu sayfa']);
}
