<?php
/* Ön Muhasebe › Depo Durumu (BizimHesap "Stok Değeri" raporu): ürünler alt alta, alış / satış fiyatı, KDV, kritik ve mevcut miktar */

/** Sayıyı gereksiz sıfır olmadan (en az 2 ondalık) biçimler: 6,5 → 6,50 · 530,5125 → 530,5125 */
function acc_stock_num($v, int $max = 6): string
{
    $s = rtrim(rtrim(number_format((float)$v, $max, ',', '.'), '0'), ',');
    if (!preg_match('/,\d{2}/', $s)) $s = number_format((float)$v, 2, ',', '.');
    return $s;
}

function acc_stock_report(): void
{
    $g = $_GET;
    $date = acc_pay_date($g['date'] ?? '');
    $q = trim((string)($g['q'] ?? ''));
    $status = ($g['status'] ?? '') === 'active' ? 'active' : 'all';
    $cat = (int)($g['category'] ?? 0); $brand = (int)($g['brand'] ?? 0);
    $stockMode = in_array($g['stock'] ?? '', ['in', 'out', 'critical', 'all'], true) ? $g['stock'] : 'in';
    $pics = ($g['pics'] ?? '1') === '0' ? 0 : 1;

    // geçmiş tarih: o günden sonraki giriş/çıkış hareketleri geri alınır
    $past = $date < date('Y-m-d');
    $stExpr = 'p.stock';
    $params = [];
    if ($past) {
        $from = date('Y-m-d', strtotime($date . ' +1 day')) . ' 00:00:00';   // acc_pay_date ile doğrulanmış tarih: güvenle gömülür
        $stExpr = "(p.stock - COALESCE((SELECT SUM(CASE WHEN m.kind IN ('in','return') THEN m.quantity WHEN m.kind IN ('out','order') THEN -m.quantity ELSE 0 END) FROM stock_movements m WHERE m.product_id = p.id AND m.created_at >= '$from'), 0))";
    }
    $where = ['p.deleted_at IS NULL'];
    if ($status === 'active') $where[] = 'p.is_active = 1';
    if ($cat) { $where[] = 'p.category_id = ?'; $params[] = $cat; }
    if ($brand) { $where[] = 'p.brand_id = ?'; $params[] = $brand; }
    if ($q !== '') { $where[] = '(p.name_tr LIKE ? OR p.code LIKE ? OR p.barcode LIKE ?)'; array_push($params, "%$q%", "%$q%", "%$q%"); }
    if ($stockMode === 'in') $where[] = "$stExpr > 0";
    elseif ($stockMode === 'out') $where[] = "$stExpr <= 0";
    elseif ($stockMode === 'critical') $where[] = "$stExpr <= p.critical_stock";

    $sql = "SELECT p.id, p.code, p.barcode, p.name_tr name, p.price, p.purchase_price, p.currency, p.vat_rate, p.critical_stock, $stExpr AS qty,
            b.name brand, c.name_tr category,
            (SELECT i.url FROM product_images i WHERE i.product_id = p.id AND i.kind <> 'video' ORDER BY i.is_main DESC, i.sort_order LIMIT 1) img
        FROM products p LEFT JOIN brands b ON b.id = p.brand_id LEFT JOIN categories c ON c.id = p.category_id
        WHERE " . implode(' AND ', $where) . ' ORDER BY p.name_tr';
    $rows = acc_q($sql, $params)->fetchAll();
    $total = 0; foreach ($rows as $r) $total += (int)$r['qty'];

    if (($g['export'] ?? '') === 'csv') {
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="depo-durumu-' . $date . '.csv"');
        $o = fopen('php://output', 'w'); fwrite($o, "\xEF\xBB\xBF");
        fputcsv($o, ['Depo', 'Kod', 'Barkod', 'Ürün', 'Marka', 'Kategori', 'Alış', 'Para Birimi', 'Satış', 'KDV%', 'Kritik Miktar', 'Miktar', 'Birim'], ';');
        foreach ($rows as $r) fputcsv($o, ['Ana Depo', $r['code'], $r['barcode'], $r['name'], $r['brand'], $r['category'],
            $r['purchase_price'] > 0 ? acc_stock_num($r['purchase_price']) : '', $r['currency'], $r['price'] > 0 ? acc_stock_num($r['price']) : '', $r['vat_rate'], $r['critical_stock'], (int)$r['qty'], 'ad'], ';');
        fclose($o); exit;
    }

    $cats = acc_q('SELECT id, name_tr name FROM categories ORDER BY name_tr')->fetchAll();
    $brands = acc_q('SELECT id, name FROM brands ORDER BY name')->fetchAll();
    $f = compact('date', 'q', 'status', 'cat', 'brand', 'stockMode', 'pics');
    admin_view('acc_stock', compact('rows', 'total', 'cats', 'brands', 'f', 'past'));
}
