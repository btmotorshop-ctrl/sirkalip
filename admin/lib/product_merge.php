<?php
/** Ürün birleştirme: seçilen ürünlerden yeni bir set/paket ürünü oluşturur. */

function page_products_merge(): void
{
    if (isset($_GET['q'])) { merge_search(); return; }
    $errors = []; $in = ['name' => '', 'code' => '', 'category_id' => '', 'price' => '', 'vat_rate' => 20, 'currency' => 'TRY', 'stock' => 0, 'note' => '', 'items' => []];
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $in = array_merge($in, [
            'name' => trim($_POST['name'] ?? ''), 'code' => trim($_POST['code'] ?? ''), 'category_id' => (int)($_POST['category_id'] ?? 0),
            'price' => str_replace(',', '.', trim($_POST['price'] ?? '')), 'vat_rate' => (int)($_POST['vat_rate'] ?? 20),
            'currency' => in_array($_POST['currency'] ?? '', ['TRY', 'USD', 'EUR'], true) ? $_POST['currency'] : 'TRY',
            'stock' => max(0, (int)($_POST['stock'] ?? 0)), 'note' => trim($_POST['note'] ?? ''),
        ]);
        $ids = array_map('intval', $_POST['item_id'] ?? []); $qtys = $_POST['item_qty'] ?? [];
        $items = [];
        foreach ($ids as $i => $pid) {
            $q = max(1, (int)($qtys[$i] ?? 1));
            $st = db()->prepare('SELECT id, code, name_tr, category_id FROM products WHERE id = ? AND deleted_at IS NULL'); $st->execute([$pid]);
            if ($r = $st->fetch()) { $r['qty'] = $q; $items[$pid] = isset($items[$pid]) ? array_merge($r, ['qty' => $items[$pid]['qty'] + $q]) : $r; }
        }
        $in['items'] = array_values($items);
        if (count($items) < 2) $errors[] = 'Birleştirmek için en az 2 ürün seçin.';
        if ($in['name'] === '') $errors[] = 'Yeni ürün adı zorunludur.';
        if ($in['price'] === '' || !is_numeric($in['price'])) $errors[] = 'Satış fiyatı girin.';
        if (!$in['category_id']) $in['category_id'] = (int)($in['items'][0]['category_id'] ?? 0);
        if (!$in['category_id']) $errors[] = 'Kategori seçin.';
        if (!$errors) {
            $pdo = db();
            try {
                $pdo->beginTransaction();
                $code = $in['code'] !== '' ? $in['code'] : 'SET-' . date('ymd') . '-' . strtoupper(bin2hex(random_bytes(2)));
                $slug = slugify($in['name'] . '-' . $code);
                $desc = "Bu set aşağıdaki ürünlerden oluşur:\n";
                foreach ($in['items'] as $it) $desc .= '- ' . $it['qty'] . ' adet × ' . $it['code'] . ' – ' . $it['name_tr'] . "\n";
                if ($in['note'] !== '') $desc .= "\n" . $in['note'];
                $short = 'Set ürün: ' . implode(', ', array_map(function ($it) { return $it['qty'] . '× ' . $it['code']; }, $in['items']));
                $pdo->prepare('INSERT INTO products (code, slug, name_tr, short_desc_tr, desc_tr, category_id, price, currency, vat_rate, stock, is_active, published_at) VALUES (?,?,?,?,?,?,?,?,?,?,?,?)')
                    ->execute([$code, $slug, $in['name'], mb_substr($short, 0, 1000), trim($desc), $in['category_id'], $in['price'], $in['currency'], $in['vat_rate'], $in['stock'], 1, date('Y-m-d H:i:s')]);
                $newId = (int)$pdo->lastInsertId();
                // İlk bileşenin ana görselini yeni ürüne taşı
                foreach ($in['items'] as $it) {
                    $im = $pdo->prepare('SELECT url FROM product_images WHERE product_id = ? ORDER BY is_main DESC, sort_order, id LIMIT 1'); $im->execute([$it['id']]);
                    if ($u = $im->fetchColumn()) { $pdo->prepare('INSERT INTO product_images (product_id, url, kind, is_main, sort_order) VALUES (?,?,?,?,?)')->execute([$newId, $u, 'photo', 1, 0]); break; }
                }
                if ($in['stock'] > 0) $pdo->prepare("INSERT INTO stock_movements (product_id, kind, quantity, note, user_id) VALUES (?, 'in', ?, 'Set ürün oluşturuldu', ?)")->execute([$newId, $in['stock'], admin_user()['id'] ?? null]);
                $pdo->commit();
                log_action('create', 'products', $newId, ['merge' => array_column($in['items'], 'id')]);
                flash('Set ürün oluşturuldu. Gerekirse görsel ve diğer bilgileri buradan düzenleyebilirsiniz.');
                redirect(aurl("products/$newId"));
            } catch (PDOException $e) {
                if ($pdo->inTransaction()) $pdo->rollBack();
                $errors[] = strpos($e->getMessage(), 'Duplicate') !== false ? 'Bu ürün kodu zaten kullanılıyor.' : $e->getMessage();
            }
        }
    }
    $cats = opts_query('SELECT id, name_tr FROM categories ORDER BY sort_order, name_tr');
    admin_view('products_merge', compact('in', 'errors', 'cats'));
}

function merge_search(): void
{
    header('Content-Type: application/json; charset=utf-8');
    $q = trim((string)($_GET['q'] ?? ''));
    if (mb_strlen($q) < 2) { echo '[]'; exit; }
    $like = '%' . str_replace(['%', '_'], ['\\%', '\\_'], $q) . '%';
    $st = db()->prepare('SELECT id, code, name_tr name, price, currency, stock, category_id FROM products WHERE deleted_at IS NULL AND (name_tr LIKE ? OR code LIKE ? OR barcode LIKE ?) ORDER BY name_tr LIMIT 20');
    $st->execute([$like, $like, $like]);
    $rows = $st->fetchAll();
    foreach ($rows as &$r) { $r['id'] = (int)$r['id']; $r['price'] = (float)$r['price']; $r['stock'] = (int)$r['stock']; $r['category_id'] = (int)$r['category_id']; }
    echo json_encode($rows, JSON_UNESCAPED_UNICODE); exit;
}
