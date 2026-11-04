<?php
require_once __DIR__ . '/acc_payments.php';
require_once __DIR__ . '/acc_stock.php';
require_once __DIR__ . '/acc_open.php';
require_once __DIR__ . '/acc_accounts.php';
require_once __DIR__ . '/acc_statement.php';
require_once __DIR__ . '/acc_templates.php';
/* Ön Muhasebe modülü (BizimHesap benzeri): cariler, satış/alış belgeleri, hesaplar, tahsilat/ödeme, masraflar */

function acc_money($v, string $cur = 'TRY'): string
{
    $sym = ['TRY' => 'TL', 'USD' => 'USD', 'EUR' => 'EUR'][$cur] ?? $cur;
    return number_format((float)$v, 2, ',', '.') . ' ' . $sym;
}
/** Bu sayfa Ön Muhasebe tam ekran kabuğunda mı gösterilsin? */
function acc_shell(): bool
{
    $r = (string)($GLOBALS['ADMIN_ROUTE'] ?? '');
    return $r === 'acc' || strpos($r, 'acc/') === 0 || strpos($r, 'r/acc_') === 0;
}
function acc_date($d): string { return $d ? date('d.m.Y', strtotime((string)$d)) : '—'; }
function acc_q(string $sql, array $p = []): PDOStatement { $st = db()->prepare($sql); $st->execute($p); return $st; }
function acc_ready(): bool
{
    static $r = null;
    if ($r === null) { try { db()->query('SELECT 1 FROM acc_parties LIMIT 1'); $r = true; } catch (Throwable $e) { $r = false; } }
    return $r;
}
function acc_rate(string $cur): float
{
    if ($cur === 'TRY' || $cur === '') return 1.0;
    try { $v = acc_q('SELECT rate FROM currency_rates WHERE code = ?', [$cur])->fetchColumn(); } catch (Throwable $e) { $v = null; }
    return $v ? (float)$v : 1.0;
}

/** Cari bakiyeleri (TL): açılış + belgeler − tahsilat/ödemeler. Pozitif = açık hesap (müşteri alacağı / tedarikçi borcu). */
function acc_balances(string $kind): array
{
    $sql = "SELECT p.id, p.name, p.phone, COALESCE(p.opening_balance,0)
        + COALESCE((SELECT SUM(d.total * d.rate) FROM acc_docs d WHERE d.party_id = p.id AND d.status = 'final'), 0)
        - " . acc_pay_sum_sql() . " AS bal
        FROM acc_parties p WHERE p.kind = ? AND p.is_active = 1 ORDER BY p.name";
    return acc_q($sql, [$kind])->fetchAll();
}

function page_acc(array $seg): void
{
    if (!acc_ready()) { flash('Ön Muhasebe tabloları henüz oluşmadı. Siteyi bir kez açıp sayfayı yenileyin.', 'err'); redirect(aurl()); }
    $a = $seg[1] ?? 'home';
    switch ($a) {
        case 'home': acc_home(); break;
        case 'docs': if (($seg[2] ?? '') === 'proposal') { acc_proposals_list(); break; } acc_docs_list(($seg[2] ?? 'sale') === 'purchase' ? 'purchase' : 'sale'); break;
        case 'doc': acc_doc_route($seg); break;
        case 'proposals': acc_proposals_list(); break;
        case 'templates': case 'template': case 'template-preview': acc_templates_route($seg); break;
        case 'party-quick': acc_party_quick(); break;
        case 'aras-send': acc_aras_send(); break;
        case 'aras-label': acc_aras_label((int)($seg[2] ?? 0)); break;
        case 'aras-list': acc_aras_list(); break;
        case 'aras-status': acc_aras_action('status', (int)($seg[2] ?? 0)); break;
        case 'aras-cancel': acc_aras_action('cancel', (int)($seg[2] ?? 0)); break;
        case 'aras-refresh': acc_aras_action('all', 0); break;
        case 'parties': acc_parties_list(($seg[2] ?? 'customer') === 'supplier' ? 'supplier' : 'customer'); break;
        case 'party':
            if (($seg[3] ?? '') === 'ekstre' || ($seg[3] ?? '') === 'detay') { acc_statement_page((int)($seg[2] ?? 0), $seg[3] === 'detay'); break; }
            acc_party_statement((int)($seg[2] ?? 0), false); break;
        case 'party-note': acc_party_note(); break;
        case 'payment': acc_payment_form(); break;
        case 'stock': acc_stock_report(); break;
        case 'open-accounts': acc_open_accounts(); break;
        case 'payments': acc_party_payments((int)($seg[2] ?? 0)); break;
        case 'party-adjust': acc_party_adjust(); break;
        case 'payment-delete': acc_payment_delete(); break;
        case 'party-delete': acc_party_delete(); break;
        case 'party-edit': acc_party_edit(($seg[2] ?? 'customer') === 'supplier' ? 'supplier' : 'customer', $seg[3] ?? 'new'); break;
        case 'party-find': acc_party_find(); break;
        case 'class-add': acc_class_add(); break;
        case 'import-firms': acc_import_firms(); break;
        case 'product-search': acc_product_search(); break;
        case 'prev-prices': acc_prev_prices(); break;
        case 'accounts': acc_accounts_page(); break;
        case 'account': acc_account_moves((int)($seg[2] ?? 0)); break;
        case 'account-save': acc_account_save(); break;
        case 'account-move': acc_account_move_post(); break;
        case 'account-move-delete': acc_account_move_delete(); break;
        case 'balances': acc_balances_page(); break;
        default: redirect(aurl('acc'));
    }
}

/* ───────── Ana sayfa ───────── */
function acc_home(): void
{
    $one = function ($sql, $p = []) { return (float)acc_q($sql, $p)->fetchColumn(); };
    $d = [];
    $d['todaySales'] = $one("SELECT COALESCE(SUM(total*rate),0) FROM acc_docs WHERE kind='sale' AND status='final' AND doc_date = CURDATE()");
    $d['todayIn'] = $one("SELECT COALESCE(SUM(amount*COALESCE(rate,1)),0) FROM acc_payments WHERE kind='in' AND method = 'cash' AND DATE(pay_at) = CURDATE()");
    $d['monthSales'] = $one("SELECT COALESCE(SUM(total*rate),0) FROM acc_docs WHERE kind='sale' AND status='final' AND doc_date >= DATE_FORMAT(CURDATE(), '%Y-%m-01')");
    $d['sales30'] = $one("SELECT COALESCE(SUM(total*rate),0) FROM acc_docs WHERE kind='sale' AND status='final' AND doc_date >= CURDATE() - INTERVAL 30 DAY");
    $d['monthExp'] = $one("SELECT COALESCE(SUM(amount),0) FROM acc_expenses WHERE exp_at >= DATE_FORMAT(CURDATE(), '%Y-%m-01')");
    $d['exp30'] = $one("SELECT COALESCE(SUM(amount),0) FROM acc_expenses WHERE exp_at >= CURDATE() - INTERVAL 30 DAY");
    $d['monthPur'] = $one("SELECT COALESCE(SUM(total*rate),0) FROM acc_docs WHERE kind='purchase' AND status='final' AND doc_date >= DATE_FORMAT(CURDATE(), '%Y-%m-01')");
    $d['stockValue'] = $one('SELECT COALESCE(SUM(GREATEST(stock,0) * COALESCE(NULLIF(purchase_price,0), price)),0) FROM products WHERE deleted_at IS NULL');
    try { $d['webMonth'] = $one("SELECT COALESCE(SUM(total),0) FROM orders WHERE deleted_at IS NULL AND status <> 'cancelled' AND created_at >= DATE_FORMAT(CURDATE(), '%Y-%m-01')"); } catch (Throwable $e) { $d['webMonth'] = 0; }

    $cust = acc_balances('customer'); $supp = acc_balances('supplier');
    [$recv, $pay] = acc_open_totals();   // müşteri/tedarikçi fark etmeksizin: bize borçlular / biz borçluyuz
    $d['receivable'] = $recv; $d['payable'] = $pay;
    $cash = 0; $accounts = acc_q('SELECT * FROM acc_accounts WHERE is_active = 1')->fetchAll();
    foreach ($accounts as $ac) $cash += acc_account_balance($ac) * ($ac['currency'] === 'TRY' ? 1 : acc_rate($ac['currency']));
    $d['cash'] = $cash;

    // Vadesi geçen açık hesap alacaklar
    $due = acc_q("SELECT party_id, MIN(due_date) first_due FROM acc_docs WHERE kind='sale' AND status='final' AND due_date IS NOT NULL AND due_date < CURDATE() GROUP BY party_id")->fetchAll(PDO::FETCH_KEY_PAIR);
    $overdue = [];
    foreach ($cust as $c) if ($c['bal'] > 0.01 && isset($due[$c['id']])) $overdue[] = ['id' => $c['id'], 'name' => $c['name'], 'bal' => $c['bal'], 'days' => (int)floor((time() - strtotime($due[$c['id']])) / 86400)];
    usort($overdue, function ($a, $b) { return $b['bal'] <=> $a['bal']; });
    $d['overdue'] = array_slice($overdue, 0, 8);
    $suppDue = acc_q("SELECT party_id, MIN(due_date) first_due FROM acc_docs WHERE kind='purchase' AND status='final' AND due_date IS NOT NULL AND due_date < CURDATE() GROUP BY party_id")->fetchAll(PDO::FETCH_KEY_PAIR);
    $overdueSupp = [];
    foreach ($supp as $c) if ($c['bal'] > 0.01 && isset($suppDue[$c['id']])) $overdueSupp[] = ['id' => $c['id'], 'name' => $c['name'], 'bal' => $c['bal'], 'days' => (int)floor((time() - strtotime($suppDue[$c['id']])) / 86400)];
    usort($overdueSupp, function ($a, $b) { return $b['bal'] <=> $a['bal']; });
    $d['overdueSupp'] = array_slice($overdueSupp, 0, 8);

    $d['upcoming'] = acc_q('SELECT * FROM acc_expenses WHERE is_paid = 0 ORDER BY due_at IS NULL, due_at LIMIT 6')->fetchAll();
    $d['rates'] = [];
    try { $d['rates'] = acc_q("SELECT code, rate FROM currency_rates WHERE code IN ('USD','EUR')")->fetchAll(PDO::FETCH_KEY_PAIR); } catch (Throwable $e) {}

    $events = [];
    foreach (acc_q("SELECT d.kind, d.total, d.currency, d.created_at, p.name FROM acc_docs d LEFT JOIN acc_parties p ON p.id = d.party_id WHERE d.status='final' ORDER BY d.id DESC LIMIT 8")->fetchAll() as $r)
        $events[] = [$r['created_at'], ($r['name'] ?: 'Perakende') . ' için ' . acc_money($r['total'], $r['currency']) . ' tutarında ' . ($r['kind'] === 'sale' ? 'satış' : 'alış') . ' kaydedildi.'];
    foreach (acc_q('SELECT y.kind, y.amount, y.created_at, p.name FROM acc_payments y LEFT JOIN acc_parties p ON p.id = y.party_id WHERE y.method = \'cash\' ORDER BY y.id DESC LIMIT 8')->fetchAll() as $r)
        $events[] = [$r['created_at'], ($r['name'] ?: 'Genel') . ' için ' . acc_money($r['amount']) . ' ' . ($r['kind'] === 'in' ? 'tahsilat' : 'ödeme') . ' kaydedildi.'];
    foreach (acc_q('SELECT description, category, amount, created_at FROM acc_expenses ORDER BY id DESC LIMIT 8')->fetchAll() as $r)
        $events[] = [$r['created_at'], ($r['description'] ?: $r['category']) . ' masrafı ' . acc_money($r['amount']) . ' kaydedildi.'];
    usort($events, function ($a, $b) { return strcmp((string)$b[0], (string)$a[0]); });
    $d['events'] = array_slice($events, 0, 8);
    admin_view('acc_home', $d);
}

/* ───────── Belgeler (satış / alış) ───────── */
function acc_docs_list(string $kind): void
{
    $q = trim($_GET['q'] ?? ''); $st = $_GET['status'] ?? '';
    $where = ['d.kind = ?']; $p = [$kind];
    if ($q !== '') { $where[] = '(p.name LIKE ? OR d.doc_no LIKE ? OR d.note LIKE ?)'; array_push($p, "%$q%", "%$q%", "%$q%"); }
    if ($st !== '') { $where[] = 'd.status = ?'; $p[] = $st; }
    if (!empty($_GET['from'])) { $where[] = 'd.doc_date >= ?'; $p[] = $_GET['from']; }
    if (!empty($_GET['to'])) { $where[] = 'd.doc_date <= ?'; $p[] = $_GET['to']; }
    $page = max(1, (int)($_GET['page'] ?? 1)); $per = 25; $w = implode(' AND ', $where);
    $total = (int)acc_q("SELECT COUNT(*) FROM acc_docs d LEFT JOIN acc_parties p ON p.id = d.party_id WHERE $w", $p)->fetchColumn();
    $sum = (float)acc_q("SELECT COALESCE(SUM(d.total*d.rate),0) FROM acc_docs d LEFT JOIN acc_parties p ON p.id = d.party_id WHERE $w AND d.status = 'final'", $p)->fetchColumn();
    $rows = acc_q("SELECT d.*, p.name party_name, (SELECT COALESCE(SUM(y.amount*COALESCE(y.rate,1)),0) FROM acc_payments y WHERE y.doc_id = d.id) paid_try FROM acc_docs d LEFT JOIN acc_parties p ON p.id = d.party_id WHERE $w ORDER BY d.doc_date DESC, d.id DESC LIMIT $per OFFSET " . (($page - 1) * $per), $p)->fetchAll();
    admin_view('acc_docs', compact('kind', 'rows', 'total', 'sum', 'page', 'per'));
}

function acc_doc_route(array $seg): void
{
    $kind = ($seg[2] ?? 'sale') === 'purchase' ? 'purchase' : (($seg[2] ?? '') === 'proposal' ? 'proposal' : 'sale');
    $id = $seg[3] ?? 'new';
    if ($id === 'new') { acc_doc_form($kind); return; }
    $id = (int)$id;
    if (($seg[4] ?? '') === 'edit') { acc_doc_form($kind, $id); return; }
    if (($seg[4] ?? '') === 'cancel' && $_SERVER['REQUEST_METHOD'] === 'POST') { acc_doc_cancel($id, $kind); return; }
    if (($seg[4] ?? '') === 'convert') { acc_doc_convert($id, $kind); return; }
    if (($seg[4] ?? '') === 'print') { acc_doc_print($id, $kind); return; }
    if (($seg[4] ?? '') === 'packing') { acc_doc_packing($id, $kind); return; }
    acc_doc_view($id, $kind);
}

function acc_next_doc_no(string $kind): string
{
    $n = (int)acc_q('SELECT COALESCE(MAX(id),0) FROM acc_docs')->fetchColumn() + 1;
    return ($kind === 'sale' ? 'SAT' : ($kind === 'proposal' ? 'TKL' : 'ALS')) . date('Y') . str_pad((string)$n, 6, '0', STR_PAD_LEFT);
}

function acc_doc_form(string $kind, int $editId = 0): void
{
    $partyKind = $kind === 'purchase' ? 'supplier' : 'customer';
    $isProp = $kind === 'proposal';
    $err = null;
    $pid = (int)($_GET['party_id'] ?? 0);
    $row = ['party_id' => $pid ?: '', 'doc_date' => date('Y-m-d'), 'doc_time' => date('H:i'), 'due_date' => date('Y-m-d', strtotime($isProp ? '+15 days' : 'today')), 'ship_date' => date('Y-m-d'), 'doc_no' => '', 'order_date' => '', 'order_no' => '', 'waybill_no' => '', 'currency' => 'TRY', 'rate' => 1, 'note' => ''];
    $lines0 = [];
    $loadLines = function (int $did) {
        $o = [];
        foreach (acc_q('SELECT * FROM acc_doc_lines WHERE doc_id = ? ORDER BY id', [$did])->fetchAll() as $l)
            $o[] = ['product_id' => $l['product_id'], 'name' => $l['name'], 'qty' => (float)$l['qty'], 'unit' => $l['unit'], 'price' => (float)$l['unit_price'], 'disc' => (float)$l['discount'], 'vat' => (float)$l['vat_rate'], 'desc' => $l['description']];
        return $o;
    };
    $editDoc = null;
    if ($editId) {
        $editDoc = acc_q('SELECT * FROM acc_docs WHERE id = ?', [$editId])->fetch();
        if (!$editDoc || $editDoc['kind'] !== $kind || !in_array($editDoc['status'], $kind === 'proposal' ? ['offer', 'converted'] : ['proforma', 'final'], true)) { flash('Bu belge düzenlenemez (yalnızca açık teklif, satışa çevrilmiş teklif ve proforma/siparişler düzenlenebilir).', 'err'); redirect(aurl("acc/doc/$kind/$editId")); }
        foreach (['party_id', 'doc_date', 'doc_time', 'due_date', 'ship_date', 'doc_no', 'order_date', 'order_no', 'waybill_no', 'currency', 'rate', 'note'] as $f) $row[$f] = $editDoc[$f] ?? $row[$f];
        $row['doc_time'] = substr((string)$row['doc_time'], 0, 5);
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') $lines0 = $loadLines($editId);
    } elseif (!empty($_GET['copy']) && $_SERVER['REQUEST_METHOD'] !== 'POST') {
        $src = acc_q('SELECT * FROM acc_docs WHERE id = ?', [(int)$_GET['copy']])->fetch();
        if ($src && $src['kind'] === $kind) {
            if (($_GET['same'] ?? '1') === '1') $row['party_id'] = $src['party_id'] ?: '';
            $row['currency'] = $src['currency']; $row['rate'] = $src['rate']; $row['note'] = (string)$src['note'];
            $lines0 = $loadLines((int)$src['id']);
        }
    }
    $dec = function ($v) { $v = trim((string)$v); if ($v === '') return 0.0; $v = str_replace(' ', '', $v); if (strpos($v, ',') !== false) $v = str_replace(',', '.', str_replace('.', '', $v)); return (float)$v; };
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $act = $_POST['action'] ?? 'final';
        $status = $isProp ? 'offer' : ($act === 'proforma' ? 'proforma' : 'final');
        $row = array_merge($row, [
            'party_id' => (int)($_POST['party_id'] ?? 0) ?: '', 'doc_date' => $_POST['doc_date'] ?: date('Y-m-d'), 'doc_time' => $_POST['doc_time'] ?: null, 'due_date' => $_POST['due_date'] ?: '',
            'ship_date' => $_POST['ship_date'] ?: '', 'order_date' => $_POST['order_date'] ?: '', 'order_no' => trim($_POST['order_no'] ?? ''), 'waybill_no' => trim($_POST['waybill_no'] ?? ''),
            'doc_no' => trim($_POST['doc_no'] ?? ''), 'currency' => in_array($_POST['currency'] ?? '', ['TRY', 'USD', 'EUR'], true) ? $_POST['currency'] : 'TRY',
            'rate' => max(0.0001, $dec($_POST['rate'] ?? '1')), 'note' => trim($_POST['note'] ?? ''),
        ]);
        $lines = []; $sumNet = 0.0; $sumVat = 0.0;
        foreach ($_POST['l_name'] ?? [] as $i => $nm) {
            $nm = trim($nm); $qty = $dec($_POST['l_qty'][$i] ?? '0'); $price = $dec($_POST['l_price'][$i] ?? '0');
            if ($nm === '' || $qty <= 0) continue;
            $gross = round($qty * $price, 2); $disc = min($gross, max(0.0, $dec($_POST['l_disc'][$i] ?? '0')));
            $vat = $dec($_POST['l_vat'][$i] ?? '0'); $net = round($gross - $disc, 2); $vatAmt = round($net * $vat / 100, 2);
            $lines[] = ['product_id' => (int)($_POST['l_pid'][$i] ?? 0) ?: null, 'name' => mb_substr($nm, 0, 255), 'qty' => $qty, 'price' => $price, 'vat' => $vat, 'disc' => $disc, 'net' => $net,
                'total' => $net + $vatAmt, 'unit' => mb_substr(trim($_POST['l_unit'][$i] ?? 'Ad') ?: 'Ad', 0, 10), 'desc' => trim($_POST['l_desc'][$i] ?? '') ?: null];
            $sumNet += $net; $sumVat += $vatAmt;
        }
        $fd = min($sumNet, max(0.0, $dec($_POST['footer_discount'] ?? '0')));
        $k = $sumNet > 0 ? ($sumNet - $fd) / $sumNet : 1.0;
        $sub = round($sumNet - $fd, 2); $vatTot = round($sumVat * $k, 2); $tot = round($sub + $vatTot, 2);
        $lines0 = $lines;
        if (!$lines) $err = 'En az bir ürün/hizmet satırı ekleyin.';
        elseif (!$row['party_id'] && $kind === 'purchase') $err = 'Tedarikçi seçin.';
        elseif (!$row['party_id'] && $isProp) $err = 'Teklif için müşteri seçin.';
        if (!$err) {
            $pdo = db();
            try {
                $pdo->beginTransaction();
                $no = $row['doc_no'] !== '' ? $row['doc_no'] : acc_next_doc_no($kind);
                if ($editDoc) {
                    $pdo->prepare('UPDATE acc_docs SET party_id=?, doc_no=?, doc_date=?, doc_time=?, due_date=?, ship_date=?, order_date=?, order_no=?, waybill_no=?, currency=?, rate=?, subtotal=?, vat_total=?, discount_total=?, total=?, note=?, status=? WHERE id=?')
                        ->execute([$row['party_id'] ?: null, $no, $row['doc_date'], $row['doc_time'] ?: null, $row['due_date'] ?: null, $row['ship_date'] ?: null, $row['order_date'] ?: null, $row['order_no'] ?: null, $row['waybill_no'] ?: null, $row['currency'], $row['rate'], $sub, $vatTot, $fd, $tot, $row['note'] ?: null, $isProp ? $editDoc['status'] : $status, $editId]);
                    $pdo->prepare('DELETE FROM acc_doc_lines WHERE doc_id = ?')->execute([$editId]);
                    $docId = $editId;
                } else {
                $pdo->prepare('INSERT INTO acc_docs (kind, party_id, doc_no, doc_date, doc_time, due_date, ship_date, order_date, order_no, waybill_no, currency, rate, subtotal, vat_total, discount_total, total, status, note) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)')
                    ->execute([$kind, $row['party_id'] ?: null, $no, $row['doc_date'], $row['doc_time'] ?: null, $row['due_date'] ?: null, $row['ship_date'] ?: null, $row['order_date'] ?: null, $row['order_no'] ?: null, $row['waybill_no'] ?: null,
                        $row['currency'], $row['rate'], $sub, $vatTot, $fd, $tot, $status, $row['note'] ?: null]);
                $docId = (int)$pdo->lastInsertId();
                }
                $ins = $pdo->prepare('INSERT INTO acc_doc_lines (doc_id, product_id, name, qty, unit, unit_price, discount, vat_rate, line_total, description) VALUES (?,?,?,?,?,?,?,?,?,?)');
                foreach ($lines as $l) $ins->execute([$docId, $l['product_id'], $l['name'], $l['qty'], $l['unit'], $l['price'], $l['disc'], $l['vat'], $l['total'], $l['desc']]);
                $synced = false;
                if ($editDoc && $editDoc['status'] === 'converted') {
                    // Teklifden oluşan ve henüz faturalanmamış proforma satışı da güncelle
                    $sid = (int)acc_q("SELECT id FROM acc_docs WHERE kind = 'sale' AND status = 'proforma' AND note LIKE ? ORDER BY id DESC LIMIT 1", ['%[Teklif ' . $editDoc['doc_no'] . ']%'])->fetchColumn();
                    if ($sid) {
                        $pdo->prepare('UPDATE acc_docs SET party_id=?, currency=?, rate=?, subtotal=?, vat_total=?, discount_total=?, total=? WHERE id=?')->execute([$row['party_id'] ?: null, $row['currency'], $row['rate'], $sub, $vatTot, $fd, $tot, $sid]);
                        $pdo->prepare('DELETE FROM acc_doc_lines WHERE doc_id = ?')->execute([$sid]);
                        foreach ($lines as $l) $ins->execute([$sid, $l['product_id'], $l['name'], $l['qty'], $l['unit'], $l['price'], $l['disc'], $l['vat'], $l['total'], $l['desc']]);
                        $synced = true;
                    }
                }
                if ($status === 'final' && !empty($_POST['move_stock'])) acc_doc_apply_stock($docId, $kind, $no);
                $payAmt = $dec($_POST['pay_amount'] ?? '0'); $payAcc = (int)($_POST['pay_account'] ?? 0);
                if ($status === 'final' && $payAmt > 0 && $payAcc) {
                    $accCur = acc_q('SELECT currency FROM acc_accounts WHERE id = ?', [$payAcc])->fetchColumn() ?: 'TRY';
                    $pdo->prepare('INSERT INTO acc_payments (pay_at, kind, party_id, account_id, amount, rate, doc_id, note) VALUES (NOW(),?,?,?,?,?,?,?)')
                        ->execute([$kind === 'sale' ? 'in' : 'out', $row['party_id'] ?: null, $payAcc, $payAmt, acc_rate($accCur), $docId, $no . ' belgesi ödemesi']);
                }
                $pdo->commit();
                log_action($editDoc ? 'update' : 'create', 'acc_docs', $docId);
                flash($editDoc ? ('Teklif güncellendi.' . (!empty($synced) ? ' Bağlı proforma satış da güncellendi.' : ($editDoc['status'] === 'converted' ? ' (Bağlı satış faturalandığı için değişmedi.)' : ''))) : ($isProp ? 'Teklif' : ($status === 'proforma' ? 'Proforma / sipariş' : ($kind === 'sale' ? 'Satış faturası' : 'Alış faturası'))) . ' kaydedildi.');
                redirect(aurl("acc/doc/$kind/$docId"));
            } catch (PDOException $e) { if ($pdo->inTransaction()) $pdo->rollBack(); $err = 'Kayıt hatası: ' . $e->getMessage(); }
        }
    }
    $party = null;
    if ($row['party_id']) $party = acc_q('SELECT * FROM acc_parties WHERE id = ?', [(int)$row['party_id']])->fetch() ?: null;
    if ($party && $_SERVER['REQUEST_METHOD'] !== 'POST') {
        if (!$isProp && $party['due_days'] !== null && $party['due_days'] !== '') $row['due_date'] = date('Y-m-d', strtotime('+' . (int)$party['due_days'] . ' days'));
        if (!empty($party['currency']) && in_array($party['currency'], ['USD', 'EUR'], true)) { $row['currency'] = $party['currency']; $row['rate'] = acc_rate($party['currency']); }
    }
    $partyBal = null;
    if ($party) { foreach (acc_balances($partyKind) as $b) if ((int)$b['id'] === (int)$party['id']) { $partyBal = (float)$b['bal']; break; } }
    $parties = acc_q('SELECT id, name FROM acc_parties WHERE kind = ? AND is_active = 1 ORDER BY name', [$partyKind])->fetchAll(PDO::FETCH_KEY_PAIR);
    $accounts = acc_q('SELECT id, CONCAT(name, " (", currency, ")") FROM acc_accounts WHERE is_active = 1 ORDER BY name')->fetchAll(PDO::FETCH_KEY_PAIR);
    $rates = ['USD' => acc_rate('USD'), 'EUR' => acc_rate('EUR')];
    admin_view('acc_doc_form', compact('kind', 'row', 'err', 'parties', 'accounts', 'party', 'partyBal', 'lines0', 'rates', 'editDoc'));
}

/** Belgedeki ürün satırları için stok hareketi uygular (satış: çıkış, alış: giriş) */
function acc_doc_apply_stock(int $docId, string $kind, string $no): void
{
    $pdo = db(); $uid = admin_user()['id'] ?? null;
    foreach (acc_q('SELECT product_id, qty FROM acc_doc_lines WHERE doc_id = ? AND product_id IS NOT NULL', [$docId])->fetchAll() as $l) {
        $delta = (int)round($l['qty']) * ($kind === 'sale' ? -1 : 1);
        $pdo->prepare('UPDATE products SET stock = stock + ? WHERE id = ?')->execute([$delta, $l['product_id']]);
        $pdo->prepare('INSERT INTO stock_movements (product_id, kind, quantity, note, user_id) VALUES (?,?,?,?,?)')
            ->execute([$l['product_id'], $kind === 'sale' ? 'out' : 'in', abs($delta), ($kind === 'sale' ? 'Satış belgesi ' : 'Alış belgesi ') . $no, $uid]);
    }
}

/** Proforma / siparişi faturaya çevirir (bakiyeye işler, stoktan düşer) */
function acc_doc_convert(int $id, string $kind): void
{
    $doc = acc_q('SELECT * FROM acc_docs WHERE id = ?', [$id])->fetch();
    if ($doc && $doc['kind'] === 'proposal') {
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && $doc['status'] === 'offer') {
            $pdo = db(); $pdo->beginTransaction();
            $no = acc_next_doc_no('sale');
            $pdo->prepare('INSERT INTO acc_docs (kind, party_id, doc_no, doc_date, due_date, currency, rate, subtotal, vat_total, discount_total, total, status, note) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?)')
                ->execute(['sale', $doc['party_id'], $no, date('Y-m-d'), date('Y-m-d'), $doc['currency'], $doc['rate'], $doc['subtotal'], $doc['vat_total'], $doc['discount_total'], $doc['total'], 'proforma', trim(($doc['note'] ?? '') . ' [Teklif ' . $doc['doc_no'] . ']')]);
            $newId = (int)$pdo->lastInsertId();
            $pdo->prepare('INSERT INTO acc_doc_lines (doc_id, product_id, name, qty, unit, unit_price, discount, vat_rate, line_total, description) SELECT ?, product_id, name, qty, unit, unit_price, discount, vat_rate, line_total, description FROM acc_doc_lines WHERE doc_id = ?')->execute([$newId, $id]);
            $pdo->prepare("UPDATE acc_docs SET status = 'converted' WHERE id = ?")->execute([$id]);
            $pdo->commit();
            log_action('update', 'acc_docs', $id, ['proposal_to_sale' => $newId]);
            flash('Teklif satışa çevrildi (proforma / sipariş). Faturaya çevirmek için belgeyi açın.');
            redirect(aurl('acc/doc/sale/' . $newId));
        }
        redirect(aurl("acc/doc/proposal/$id"));
    }
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && $doc && $doc['status'] === 'proforma') {
        $pdo = db(); $pdo->beginTransaction();
        $pdo->prepare("UPDATE acc_docs SET status = 'final' WHERE id = ?")->execute([$id]);
        acc_doc_apply_stock($id, $doc['kind'], $doc['doc_no']);
        $pdo->commit();
        log_action('update', 'acc_docs', $id, ['convert' => true]);
        flash('Belge faturaya çevrildi.');
    }
    redirect(aurl("acc/doc/$kind/$id"));
}

/** Ürün arama (JSON): en az 3 karakter */
function acc_product_search(): void
{
    header('Content-Type: application/json; charset=utf-8');
    $q = trim((string)($_GET['q'] ?? ''));
    if (mb_strlen($q) < 3) { echo json_encode([]); exit; }
    $like = '%' . str_replace(['%', '_'], ['\\%', '\\_'], $q) . '%';
    $rows = acc_q('SELECT id, code, name_tr name, price, COALESCE(NULLIF(purchase_price,0), 0) cost, vat_rate vat, stock FROM products WHERE deleted_at IS NULL AND (name_tr LIKE ? OR code LIKE ?) ORDER BY (code = ?) DESC, name_tr LIMIT 25', [$like, $like, $q])->fetchAll();
    foreach ($rows as &$r) { $r['id'] = (int)$r['id']; $r['price'] = (float)$r['price']; $r['cost'] = (float)$r['cost']; $r['vat'] = (float)$r['vat']; $r['stock'] = (int)$r['stock']; }
    echo json_encode($rows, JSON_UNESCAPED_UNICODE); exit;
}

/** Bu cariyle bu ürüne daha önce kesilen fiyatlar (JSON) */
function acc_prev_prices(): void
{
    header('Content-Type: application/json; charset=utf-8');
    $pid = (int)($_GET['product'] ?? 0); $party = (int)($_GET['party'] ?? 0); $kind = ($_GET['kind'] ?? 'sale') === 'purchase' ? 'purchase' : 'sale';
    $out = ['party' => [], 'all' => []];
    if ($pid) {
        $sel = 'SELECT d.doc_date d, p.name party, l.unit_price price, d.rate, d.currency cur FROM acc_doc_lines l JOIN acc_docs d ON d.id = l.doc_id LEFT JOIN acc_parties p ON p.id = d.party_id WHERE l.product_id = ? AND d.kind = ? AND d.status <> \'cancelled\'';
        if ($party) $out['party'] = acc_q($sel . ' AND d.party_id = ? ORDER BY d.doc_date DESC, d.id DESC LIMIT 8', [$pid, $kind, $party])->fetchAll();
        $out['all'] = acc_q($sel . ' ORDER BY d.doc_date DESC, d.id DESC LIMIT 8', [$pid, $kind])->fetchAll();
    }
    echo json_encode($out, JSON_UNESCAPED_UNICODE); exit;
}

function acc_doc_view(int $id, string $kind): void
{
    $doc = acc_q('SELECT d.*, p.name party_name FROM acc_docs d LEFT JOIN acc_parties p ON p.id = d.party_id WHERE d.id = ?', [$id])->fetch();
    if (!$doc) { flash('Belge bulunamadı', 'err'); redirect(aurl("acc/docs/$kind")); }
    $lines = acc_q('SELECT * FROM acc_doc_lines WHERE doc_id = ? ORDER BY id', [$id])->fetchAll();
    $pays = acc_q('SELECT y.*, a.name account FROM acc_payments y LEFT JOIN acc_accounts a ON a.id = y.account_id WHERE y.doc_id = ? ORDER BY y.pay_at', [$id])->fetchAll();
    admin_view('acc_doc_view', compact('doc', 'lines', 'pays', 'kind'));
}

function acc_doc_cancel(int $id, string $kind): void
{
    $doc = acc_q('SELECT * FROM acc_docs WHERE id = ?', [$id])->fetch();
    if ($doc && $doc['kind'] === 'proposal' && $doc['status'] === 'offer') {
        db()->prepare("UPDATE acc_docs SET status = 'cancelled' WHERE id = ?")->execute([$id]);
        log_action('update', 'acc_docs', $id, ['cancel' => true]);
        flash('Teklif iptal edildi.');
    } elseif ($doc && in_array($doc['status'], ['final', 'proforma'], true)) {
        $pdo = db(); $uid = admin_user()['id'] ?? null;
        $pdo->beginTransaction();
        $pdo->prepare("UPDATE acc_docs SET status = 'cancelled' WHERE id = ?")->execute([$id]);
        // Belge kaydında stok hareketi yapıldıysa geri al
        $mv = acc_q("SELECT COUNT(*) FROM stock_movements WHERE note = ?", [($doc['kind'] === 'sale' ? 'Satış belgesi ' : 'Alış belgesi ') . $doc['doc_no']])->fetchColumn();
        if ($mv) foreach (acc_q('SELECT product_id, qty FROM acc_doc_lines WHERE doc_id = ? AND product_id IS NOT NULL', [$id])->fetchAll() as $l) {
            $delta = (int)round($l['qty']) * ($doc['kind'] === 'sale' ? 1 : -1);
            $pdo->prepare('UPDATE products SET stock = stock + ? WHERE id = ?')->execute([$delta, $l['product_id']]);
            $pdo->prepare('INSERT INTO stock_movements (product_id, kind, quantity, note, user_id) VALUES (?,?,?,?,?)')->execute([$l['product_id'], $doc['kind'] === 'sale' ? 'return' : 'out', abs($delta), 'Belge iptali ' . $doc['doc_no'], $uid]);
        }
        $pdo->commit();
        log_action('update', 'acc_docs', $id, ['cancel' => true]);
        flash('Belge iptal edildi' . ($mv ? ' ve stok geri alındı.' : '.'));
    }
    redirect(aurl("acc/doc/$kind/$id"));
}

/* ───────── Cariler ───────── */
function acc_parties_list(string $kind): void
{
    $rows = acc_q("SELECT p.id, p.name, p.phone, p.is_active, COALESCE(p.opening_balance,0)
        + COALESCE((SELECT SUM(d.total * d.rate) FROM acc_docs d WHERE d.party_id = p.id AND d.status = 'final'), 0)
        - " . acc_pay_sum_sql() . " AS bal,
        GREATEST(COALESCE((SELECT MAX(d.doc_date) FROM acc_docs d WHERE d.party_id = p.id AND d.status = 'final'), '0000-00-00'),
                 COALESCE((SELECT MAX(DATE(y.pay_at)) FROM acc_payments y WHERE y.party_id = p.id), '0000-00-00')) AS last_at
        FROM acc_parties p WHERE p.kind = ? ORDER BY p.name", [$kind])->fetchAll();
    $pending = acc_firms_pending();
    admin_view('acc_parties', compact('kind', 'rows', 'pending'));
}

function acc_party_statement(int $id, bool $full = false): void
{
    $p = acc_q('SELECT * FROM acc_parties WHERE id = ?', [$id])->fetch();
    if (!$p) { flash('Cari bulunamadı', 'err'); redirect(aurl('acc/parties/customer')); }
    $moves = [];
    foreach (acc_q("SELECT id, kind, doc_date d, doc_no, total*rate amt, currency, total, status FROM acc_docs WHERE party_id = ? AND status = 'final'", [$id])->fetchAll() as $r)
        $moves[] = ['d' => $r['d'], 'label' => ($r['kind'] === 'sale' ? 'Satış' : 'Alış') . ' – ' . $r['doc_no'], 'debit' => (float)$r['amt'], 'credit' => 0.0, 'link' => aurl('acc/doc/' . $r['kind'] . '/' . $r['id'])];
    foreach (acc_q('SELECT y.id, y.kind, y.method, y.pay_at d, y.amount*COALESCE(y.rate,1) amt, y.note, a.name account FROM acc_payments y LEFT JOIN acc_accounts a ON a.id = y.account_id WHERE y.party_id = ?', [$id])->fetchAll() as $r) {
        $red = acc_pay_reduces($p['kind'], $r['kind']);
        $moves[] = ['d' => $r['d'], 'label' => acc_pay_label($r) . ($r['account'] ? ' – ' . $r['account'] : '') . ($r['note'] ? ' (' . $r['note'] . ')' : ''), 'debit' => $red ? 0.0 : (float)$r['amt'], 'credit' => $red ? (float)$r['amt'] : 0.0, 'link' => null];
    }
    usort($moves, function ($a, $b) { return strcmp((string)$a['d'], (string)$b['d']); });
    $bal = (float)$p['opening_balance'];
    foreach ($moves as &$m) { $bal += $m['debit'] - $m['credit']; $m['bal'] = $bal; }
    unset($m);
    if (!$full) { acc_party_profile($p, $bal); return; }
}

function acc_balances_page(): void
{
    $cust = array_values(array_filter(acc_balances('customer'), function ($r) { return abs($r['bal']) > 0.005; }));
    $supp = array_values(array_filter(acc_balances('supplier'), function ($r) { return abs($r['bal']) > 0.005; }));
    admin_view('acc_balances', compact('cust', 'supp'));
}

/* ───────── Cari kartı (Yeni / Düzenle) ───────── */
function acc_party_currencies(): array
{
    return ['TRY' => 'TL', 'USD' => 'USD', 'EUR' => 'EUR', 'GBP' => 'GBP', 'CHF' => 'CHF', 'AZN' => 'AZN', 'MKD' => 'MKD', 'TMT' => 'TMT', 'GEL' => 'GEL', 'RUB' => 'RUB', 'BGN' => 'BGN', 'CAD' => 'CAD', 'SAR' => 'SAR', 'AED' => 'AED', 'IQD' => 'IQD'];
}

function acc_party_edit(string $kind, string $idArg): void
{
    $id = $idArg === 'new' ? 0 : (int)$idArg;
    $p = $id ? acc_q('SELECT * FROM acc_parties WHERE id = ? AND kind = ?', [$id, $kind])->fetch() : null;
    if ($id && !$p) { flash('Cari bulunamadı.', 'err'); redirect(aurl('acc/parties/' . $kind)); }
    $back = aurl('acc/parties/' . $kind);
    $errors = []; $branches = [];
    if ($p) $branches = acc_q('SELECT name, address, code FROM acc_party_branches WHERE party_id = ? ORDER BY id', [$id])->fetchAll();
    $val = $p ?: ['name' => '', 'currency' => 'TRY', 'is_active' => 1, 'tax_free' => 0, 'opening_balance' => '', 'image' => ''];

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $in = $_POST;
        $num = function ($k) use ($in) { $v = trim((string)($in[$k] ?? '')); if ($v === '') return null; $v = str_replace(' ', '', $v); if (strpos($v, ',') !== false) $v = str_replace(',', '.', str_replace('.', '', $v)); return (float)$v; };
        $str = function ($k) use ($in) { $v = trim((string)($in[$k] ?? '')); return $v === '' ? null : $v; };
        $name = trim((string)($in['name'] ?? ''));
        if ($name === '') $errors['name'] = 'İsim / unvan zorunludur.';
        $cur = $in['currency'] ?? 'TRY'; if (!isset(acc_party_currencies()[$cur])) $cur = 'TRY';
        $image = $p['image'] ?? null;
        if (!empty($in['image__remove'])) $image = null;
        if (!empty($_FILES['image']['tmp_name']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
            $ext = strtolower(pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION));
            if (!in_array($ext, ['jpg', 'jpeg', 'png', 'webp', 'gif'], true)) $errors['image'] = 'Sadece JPG, PNG, WEBP veya GIF yükleyebilirsiniz.';
            elseif ($_FILES['image']['size'] > 5 * 1024 * 1024) $errors['image'] = 'Dosya en fazla 5 MB olabilir.';
            else {
                $dir = ROOT . '/uploads/acc_parties'; if (!is_dir($dir)) mkdir($dir, 0775, true);
                $fn = (slugify($name) ?: 'cari') . '-' . bin2hex(random_bytes(3)) . '.' . ($ext === 'jpeg' ? 'jpg' : $ext);
                if (move_uploaded_file($_FILES['image']['tmp_name'], "$dir/$fn")) $image = base_path() . '/uploads/acc_parties/' . $fn;
            }
        }
        $bn = $in['branch_name'] ?? []; $ba = $in['branch_addr'] ?? []; $bc = $in['branch_code'] ?? []; $branches = [];
        foreach ((array)$bn as $i => $b) if (trim((string)$b) !== '') $branches[] = ['name' => trim($b), 'address' => trim((string)($ba[$i] ?? '')), 'code' => trim((string)($bc[$i] ?? ''))];
        $agents = array_values(array_filter(array_map('intval', (array)($in['agents'] ?? []))));
        $data = [
            'name' => $name, 'image' => $image, 'email' => $str('email'), 'phone' => $str('phone'), 'phone2' => $str('phone2'), 'other_contact' => $str('other_contact'),
            'contact_person' => $str('contact_person'), 'address' => $str('address'), 'delivery_address' => $str('delivery_address'),
            'tax_office' => $str('tax_office'), 'tax_no' => $str('tax_no'), 'tax_free' => empty($in['tax_free']) ? 0 : 1, 'bank_info' => $str('bank_info'),
            'currency' => $cur, 'risk_limit' => $num('risk_limit'), 'due_days' => $num('due_days') === null ? null : (int)$num('due_days'), 'discount' => $num('discount'),
            'opening_balance' => $num('opening_balance') ?? 0, 'class1' => (int)($in['class1'] ?? 0) ?: null, 'class2' => (int)($in['class2'] ?? 0) ?: null,
            'code' => $str('code'), 'agents' => $agents ? json_encode($agents) : null, 'note' => $str('note'), 'is_active' => empty($in['is_inactive']) ? 1 : 0,
        ];
        if (!$errors) {
            $pdo = db(); $pdo->beginTransaction();
            try {
                if ($id) {
                    $sets = implode(', ', array_map(function ($k) { return "`$k` = ?"; }, array_keys($data)));
                    $pdo->prepare("UPDATE acc_parties SET $sets WHERE id = ?")->execute(array_merge(array_values($data), [$id]));
                } else {
                    $data['kind'] = $kind;
                    $cols = implode(', ', array_map(function ($k) { return "`$k`"; }, array_keys($data)));
                    $pdo->prepare("INSERT INTO acc_parties ($cols) VALUES (" . implode(',', array_fill(0, count($data), '?')) . ')')->execute(array_values($data));
                    $id = (int)$pdo->lastInsertId();
                }
                $pdo->prepare('DELETE FROM acc_party_branches WHERE party_id = ?')->execute([$id]);
                foreach ($branches as $b) $pdo->prepare('INSERT INTO acc_party_branches (party_id, name, address, code) VALUES (?,?,?,?)')->execute([$id, $b['name'], $b['address'] ?: null, $b['code'] ?: null]);
                $pdo->commit();
                log_action($p ? 'update' : 'create', 'acc_parties', $id);
                flash(($kind === 'customer' ? 'Müşteri' : 'Tedarikçi') . ' kaydedildi.');
                redirect($back);
            } catch (Throwable $e) { $pdo->rollBack(); $errors['_'] = 'Kaydedilemedi: ' . $e->getMessage(); }
        }
        $val = array_merge($val, $in, ['image' => $image, 'is_active' => empty($in['is_inactive']) ? 1 : 0, 'tax_free' => empty($in['tax_free']) ? 0 : 1]);
    }
    $classes = [1 => [], 2 => []];
    foreach (acc_q('SELECT id, slot, name FROM acc_party_classes ORDER BY name')->fetchAll() as $c) $classes[(int)$c['slot']][] = $c;
    $users = []; try { $users = acc_q("SELECT id, TRIM(CONCAT(COALESCE(first_name,''),' ',COALESCE(last_name,''))) n FROM users WHERE role IN ('admin','staff') ORDER BY first_name")->fetchAll(PDO::FETCH_KEY_PAIR); } catch (Throwable $e) {}
    $sel = $val['agents'] ?? null; $sel = is_string($sel) ? (json_decode($sel, true) ?: []) : (array)$sel;
    admin_view('acc_party_form', compact('kind', 'id', 'val', 'errors', 'branches', 'classes', 'users', 'sel', 'back'));
}

/** VKN/TCKN ile kayıtlı cari arama (JSON) */
function acc_party_find(): void
{
    header('Content-Type: application/json; charset=utf-8');
    $q = preg_replace('/\D/', '', (string)($_GET['q'] ?? ''));
    if (strlen($q) < 10) { echo json_encode(['ok' => false, 'msg' => 'Vergi no 10, TC kimlik no 11 haneli olmalıdır.']); exit; }
    $r = acc_q('SELECT id, kind, name FROM acc_parties WHERE REPLACE(tax_no, " ", "") = ? LIMIT 1', [$q])->fetch();
    echo json_encode($r ? ['ok' => true, 'found' => true, 'name' => $r['name'], 'url' => aurl('acc/party-edit/' . $r['kind'] . '/' . $r['id'])] : ['ok' => true, 'found' => false]);
    exit;
}

/** Yeni sınıflandırma ekle (JSON) */
function acc_class_add(): void
{
    header('Content-Type: application/json; charset=utf-8');
    $slot = (int)($_POST['slot'] ?? 1) === 2 ? 2 : 1; $name = trim((string)($_POST['name'] ?? ''));
    if ($name === '') { echo json_encode(['ok' => false]); exit; }
    acc_q('INSERT IGNORE INTO acc_party_classes (slot, name) VALUES (?,?)', [$slot, mb_substr($name, 0, 120)]);
    $id = (int)acc_q('SELECT id FROM acc_party_classes WHERE slot = ? AND name = ?', [$slot, $name])->fetchColumn();
    echo json_encode(['ok' => true, 'id' => $id, 'name' => $name]); exit;
}

/* ───────── Hazır firma listesini içe aktar (app/data/firmalar.json) ───────── */
function acc_firms_file(): string { return ROOT . '/app/data/firmalar.json'; }

/** Aktarılmayı bekleyen firma sayısı [müşteri, tedarikçi] */
function acc_firms_pending(): array
{
    $f = acc_firms_file();
    if (!is_file($f)) return [0, 0];
    $rows = json_decode((string)file_get_contents($f), true) ?: [];
    $have = array_flip(acc_q("SELECT code FROM acc_parties WHERE code LIKE 'CARI-%'")->fetchAll(PDO::FETCH_COLUMN));
    $c = [0, 0];
    foreach ($rows as $r) if (!isset($have['CARI-' . $r[0]])) $c[$r[7] === 'supplier' ? 1 : 0]++;
    return $c;
}

function acc_import_firms(): void
{
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') redirect(aurl('acc/parties/customer'));
    $f = acc_firms_file();
    $rows = is_file($f) ? (json_decode((string)file_get_contents($f), true) ?: []) : [];
    if (!$rows) { flash('Firma listesi dosyası (app/data/firmalar.json) bulunamadı.', 'err'); redirect(aurl('acc/parties/customer')); }
    $pdo = db();
    $have = array_flip($pdo->query("SELECT code FROM acc_parties WHERE code LIKE 'CARI-%'")->fetchAll(PDO::FETCH_COLUMN));
    $cls = [];
    foreach ($pdo->query('SELECT id, slot, name FROM acc_party_classes')->fetchAll() as $c) $cls[$c['slot'] . '|' . $c['name']] = (int)$c['id'];
    $ins = $pdo->prepare('INSERT INTO acc_parties (kind, name, phone, city, address, code, class1, is_active, note, opening_balance) VALUES (?,?,?,?,?,?,?,?,?,0)');
    $added = [0, 0];
    $pdo->beginTransaction();
    try {
        foreach ($rows as [$no, $name, $act, $phone, $ilce, $sehir, $tip, $kind]) {
            if (isset($have['CARI-' . $no]) || $name === '') continue;
            $cid = null;
            if ($tip !== '') {
                if (!isset($cls['1|' . $tip])) { $pdo->prepare('INSERT IGNORE INTO acc_party_classes (slot, name) VALUES (1, ?)')->execute([$tip]); $cls['1|' . $tip] = (int)$pdo->query('SELECT id FROM acc_party_classes WHERE slot = 1 AND name = ' . $pdo->quote($tip))->fetchColumn(); }
                $cid = $cls['1|' . $tip];
            }
            $addr = trim(($ilce ?: '') . ($ilce && $sehir ? ' / ' : '') . ($sehir ?: ''));
            $ins->execute([$kind, $name, $phone, $sehir, $addr ?: null, 'CARI-' . $no, $cid, $act ? 1 : 0, $tip === 'RİSKLİ CARİLER' ? 'Riskli cari olarak işaretlenmişti.' : null]);
            $added[$kind === 'supplier' ? 1 : 0]++;
        }
        $pdo->commit();
    } catch (Throwable $e) { $pdo->rollBack(); flash('İçe aktarma başarısız: ' . $e->getMessage(), 'err'); redirect(aurl('acc/parties/customer')); }
    log_action('import', 'acc_parties', null, ['customers' => $added[0], 'suppliers' => $added[1]]);
    flash($added[0] . ' müşteri ve ' . $added[1] . ' tedarikçi eklendi.');
    redirect(aurl('acc/parties/customer'));
}


/* ───────── Cari detay ekranı (BizimHesap benzeri) ───────── */
function acc_party_profile(array $p, float $bal): void
{
    $id = (int)$p['id'];
    $isCust = $p['kind'] === 'customer';
    $mainKind = $isCust ? 'sale' : 'purchase'; $otherKind = $isCust ? 'purchase' : 'sale';
    $docs = function ($kind) use ($id) {
        return acc_q('SELECT d.id, d.kind, d.doc_no, d.doc_date, d.total, d.currency, d.status, d.note, (SELECT COUNT(*) FROM acc_doc_lines l WHERE l.doc_id = d.id) n FROM acc_docs d WHERE d.party_id = ? AND d.kind = ? ORDER BY d.doc_date DESC, d.id DESC LIMIT 12', [$id, $kind])->fetchAll();
    };
    $mainDocs = $docs($mainKind); $otherDocs = $docs($otherKind);
    $props = $isCust ? $docs('proposal') : [];
    $turnover = (float)acc_q("SELECT COALESCE(SUM(total*rate),0) FROM acc_docs WHERE party_id = ? AND kind = ? AND status = 'final'", [$id, $mainKind])->fetchColumn();
    $pays = acc_q('SELECT y.id, y.kind, y.method, y.pay_at, y.amount, y.rate, y.note, a.name account, a.currency FROM acc_payments y LEFT JOIN acc_accounts a ON a.id = y.account_id WHERE y.party_id = ? ORDER BY y.pay_at DESC, y.id DESC LIMIT 12', [$id])->fetchAll();
    $hasMoves = (int)acc_q('SELECT (SELECT COUNT(*) FROM acc_docs WHERE party_id = ?) + (SELECT COUNT(*) FROM acc_payments WHERE party_id = ?)', [$id, $id])->fetchColumn() > 0;
    $class = ''; if (!empty($p['class1'])) $class = (string)acc_q('SELECT name FROM acc_party_classes WHERE id = ?', [(int)$p['class1']])->fetchColumn();
    admin_view('acc_party_view', compact('p', 'bal', 'isCust', 'mainKind', 'otherKind', 'mainDocs', 'otherDocs', 'props', 'turnover', 'pays', 'hasMoves', 'class'));
}

function acc_party_note(): void
{
    header('Content-Type: application/json; charset=utf-8');
    $id = (int)($_POST['id'] ?? 0); $note = trim((string)($_POST['note'] ?? ''));
    if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !$id) { echo json_encode(['ok' => false]); exit; }
    acc_q('UPDATE acc_parties SET note = ? WHERE id = ?', [$note === '' ? null : mb_substr($note, 0, 2000), $id]);
    echo json_encode(['ok' => true]); exit;
}

function acc_party_delete(): void
{
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') redirect(aurl('acc'));
    $id = (int)($_POST['id'] ?? 0);
    $p = acc_q('SELECT * FROM acc_parties WHERE id = ?', [$id])->fetch();
    if (!$p) redirect(aurl('acc'));
    $back = aurl('acc/parties/' . $p['kind']);
    $n = (int)acc_q('SELECT (SELECT COUNT(*) FROM acc_docs WHERE party_id = ?) + (SELECT COUNT(*) FROM acc_payments WHERE party_id = ?)', [$id, $id])->fetchColumn();
    if ($n > 0) { flash('Bu carinin işlem kayıtları var, silinemez. Kartını "Pasif" yaparak listeden kaldırabilirsiniz.', 'err'); redirect(aurl('acc/party/' . $id)); }
    acc_q('DELETE FROM acc_party_branches WHERE party_id = ?', [$id]);
    acc_q('DELETE FROM acc_parties WHERE id = ?', [$id]);
    log_action('delete', 'acc_parties', $id);
    flash('Cari silindi.'); redirect($back);
}


/* ───────── Teklifler ───────── */
function acc_proposals_list(): void
{
    $period = $_GET['period'] ?? 'year';
    $showCancel = !empty($_GET['cancelled']);
    $where = ["d.kind = 'proposal'"]; $p = [];
    if (!$showCancel) $where[] = "d.status <> 'cancelled'";
    if ($period === 'month') $where[] = 'd.doc_date >= CURDATE() - INTERVAL 1 MONTH';
    elseif ($period === 'year') $where[] = 'd.doc_date >= CURDATE() - INTERVAL 1 YEAR';
    $rows = acc_q('SELECT d.id, d.doc_date, d.doc_no, d.total, d.currency, d.status, d.due_date, p.name party_name FROM acc_docs d LEFT JOIN acc_parties p ON p.id = d.party_id WHERE ' . implode(' AND ', $where) . ' ORDER BY d.doc_date DESC, d.id DESC LIMIT 1000', $p)->fetchAll();
    $customers = acc_q("SELECT id, name FROM acc_parties WHERE kind = 'customer' AND is_active = 1 ORDER BY name")->fetchAll(PDO::FETCH_KEY_PAIR);
    admin_view('acc_proposals', compact('rows', 'period', 'showCancel', 'customers'));
}

/** Yeni müşteriye teklif: müşteriyi hızlıca açar ve teklif formuna yönlendirir */
function acc_party_quick(): void
{
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') redirect(aurl('acc/docs/proposal'));
    $name = trim((string)($_POST['name'] ?? '')); $phone = trim((string)($_POST['phone'] ?? ''));
    if ($name === '') { flash('Müşteri adı / ünvanı yazın.', 'err'); redirect(aurl('acc/docs/proposal')); }
    db()->prepare("INSERT INTO acc_parties (kind, name, phone, is_active, opening_balance) VALUES ('customer', ?, ?, 1, 0)")->execute([mb_substr($name, 0, 191), $phone !== '' ? mb_substr($phone, 0, 40) : null]);
    $id = (int)db()->lastInsertId();
    log_action('create', 'acc_parties', $id);
    redirect(aurl('acc/doc/proposal/new?party_id=' . $id));
}

/** Müşteri ekranından Aras Kargo gönderisi oluşturur */
function acc_aras_send(): void
{
    $pid = (int)($_POST['party_id'] ?? 0);
    $back = $pid ? aurl('acc/party/' . $pid) : aurl('acc');
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') redirect($back);
    $ac = aras_config();
    $file = ($_POST['cargo_type'] ?? '') === 'file';
    $branch = ($_POST['delivery'] ?? '') === 'branch'; $sms = ($_POST['sms'] ?? '') === 'yes';
    $cod = ($_POST['cod'] ?? '') === 'yes';
    $desc = ($file ? 'Dosya/Zarf' : 'Koli') . ($branch ? ' - subeden teslim' : '') . ($sms ? ' - aliciya SMS' : '');
    $d = ['code' => aras_code('party', $pid), 'name' => $_POST['name'] ?? '', 'phone' => $_POST['phone'] ?? '', 'city' => $_POST['city'] ?? '', 'town' => $_POST['town'] ?? '', 'address' => $_POST['address'] ?? '',
        'pieces' => $file ? 1 : ($_POST['pieces'] ?? 1), 'weight' => $file ? 0.5 : ($ac['weight'] ?: 1), 'payor' => $cod ? '1' : ($_POST['payor'] ?? '1'), 'desc' => $desc, 'source' => 'party', 'ref_id' => $pid,
        'cod' => $cod, 'cod_amount' => $_POST['cod_amount'] ?? '', 'cod_type' => $_POST['cod_type'] ?? '0',
        'cargo_type' => $file ? 'file' : 'parcel', 'delivery' => $branch ? 'branch' : 'address', 'sms' => $sms ? 'yes' : 'no', 'cod_flag' => $cod ? 'yes' : 'no',
        's_city' => $_POST['s_city'] ?? '', 's_town' => $_POST['s_town'] ?? '', 's_phone' => $_POST['s_phone'] ?? ''];
    [$ok, $msg, , $lid] = aras_send($d);
    if ($ok) { log_action('create', 'aras_shipments', $lid); flash($msg); redirect($lid ? aurl('acc/aras-label/' . $lid) : $back); }
    flash($msg, 'err');
    redirect($back);
}

/** Kargo etiketleri (Aras "dummy etiket" şablonu, parça başına bir etiket) */
function acc_aras_label(int $id): void
{
    $row = acc_q('SELECT * FROM aras_shipments WHERE id = ?', [$id])->fetch();
    if (!$row) { flash('Kargo kaydı bulunamadı.', 'err'); redirect(aurl('acc/aras-list')); }
    $d = json_decode((string)$row['payload'], true) ?: [];
    if (!empty($d['cod_flag'])) $d['cod'] = $d['cod_flag'] === 'yes';
    admin_view('acc_aras_label', compact('row', 'd'));
}

/** Aras gönderi listesi: durum, takip no, iptal, destek bilgisi */
function acc_aras_list(): void
{
    $rows = acc_q('SELECT * FROM aras_shipments ORDER BY id DESC LIMIT 200')->fetchAll();
    admin_view('acc_aras_list', compact('rows'));
}

/** Durum sorgula / iptal et / tümünü yenile (POST) */
function acc_aras_action(string $what, int $id): void
{
    $back = aurl('acc/aras-list');
    if (!empty($_POST['back_order'])) $back = aurl('orders/' . (int)$_POST['back_order']);
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') redirect($back);
    if ($what === 'status') { [$ok, $msg] = aras_refresh($id); }
    elseif ($what === 'cancel') { [$ok, $msg] = aras_cancel($id); if ($ok) log_action('update', 'aras_shipments', $id, ['cancel' => true]); }
    else {
        $rows = acc_q("SELECT id, integration_code FROM aras_shipments WHERE ok = 1 AND cancelled = 0 AND (status_code IS NULL OR status_code <> 6) AND created_at >= NOW() - INTERVAL 60 DAY ORDER BY id DESC LIMIT 50")->fetchAll();
        if (!$rows) { flash('Sorgulanacak aktif gönderi yok.'); redirect($back); }
        [$ok, $res] = aras_query(array_column($rows, 'integration_code'));
        if (!$ok) { flash($res, 'err'); redirect($back); }
        $n = 0;
        foreach ($res as $r) {
            $code = (string)($r['MUSTERI_OZEL_KODU'] ?? ''); if ($code === '') continue;
            $sc = (int)($r['DURUM_KODU'] ?? 0); $txt = trim((string)($r['DURUMU'] ?? '')) ?: aras_status_label($sc); $trk = trim((string)($r['KARGO_TAKIP_NO'] ?? ''));
            acc_q('UPDATE aras_shipments SET tracking_no = ?, status_code = ?, status_text = ?, checked_at = NOW() WHERE integration_code = ? AND ok = 1', [$trk ?: null, $sc ?: null, mb_substr($txt, 0, 160), $code]);
            if ($trk !== '') acc_q('UPDATE shipments SET tracking_no = ?' . ($sc === 6 ? ', delivered_at = NOW()' : '') . ' WHERE tracking_no = ?', [$trk, $code]);
            $n++;
        }
        $ok = true; $msg = $n . ' gönderinin durumu güncellendi.' . ($n < count($rows) ? ' Diğerleri henüz şubeye teslim edilmemiş (irsaliye oluşmamış).' : '');
    }
    flash($msg, $ok ? 'ok' : 'err');
    redirect($back);
}
