<?php
/* Ön Muhasebe › Tahsilat / Ödeme (BizimHesap tarzı): nakit-kart-banka, bakiye düzeltme, borç-alacak fişi, cari virman.
 *
 * Yön kuralı (acc_payments.kind):  'in'  = cari ALACAKLANIR (tahsilat, alacak fişi)  → müşteri bakiyesi düşer, tedarikçi bakiyesi artar
 *                                   'out' = cari BORÇLANIR  (ödeme, borç fişi)        → müşteri bakiyesi artar, tedarikçi bakiyesi düşer
 * Bakiye (+): müşteri için "müşteri bize borçlu", tedarikçi için "biz tedarikçiye borçluyuz".
 * method: cash (kasa/banka hareketi) | receipt (borç-alacak fişi) | balance (bakiye düzeltme) | virement (cari virman); hesapsız olanlarda account_id = 0.
 */

/** Cari bakiyesine etki eden ödeme toplamı (TL, bakiyeyi DÜŞÜREN yön pozitif). Sorguda cari tablosu "p" takma adıyla olmalı. */
function acc_pay_sum_sql(): string
{
    return "COALESCE((SELECT SUM(y.amount * COALESCE(y.rate,1) * IF(y.kind = IF(p.kind = 'customer','in','out'), 1, -1)) FROM acc_payments y WHERE y.party_id = p.id), 0)";
}

/** Tek carinin güncel bakiyesi (TL) */
function acc_party_balance(int $id): float
{
    $sql = "SELECT COALESCE(p.opening_balance,0)
        + COALESCE((SELECT SUM(d.total * d.rate) FROM acc_docs d WHERE d.party_id = p.id AND d.status = 'final'), 0)
        - " . acc_pay_sum_sql() . " FROM acc_parties p WHERE p.id = ?";
    return round((float)acc_q($sql, [$id])->fetchColumn(), 2);
}

/** Bakiyeyi yükseltmek/düşürmek için gereken hareket yönü */
function acc_pay_kind_for(string $partyKind, string $effect): string
{
    $drop = $partyKind === 'customer' ? 'in' : 'out';
    return $effect === 'drop' ? $drop : ($drop === 'in' ? 'out' : 'in');
}

/** Hareketin cari bakiyesini düşürüp düşürmediği (ekstrede alacak sütunu) */
function acc_pay_reduces(string $partyKind, string $kind): bool
{
    return $kind === ($partyKind === 'customer' ? 'in' : 'out');
}

function acc_pay_label(array $y): string
{
    $m = $y['method'] ?? 'cash'; $in = ($y['kind'] ?? 'in') === 'in';
    if ($m === 'receipt') return $in ? 'Alacak Fişi' : 'Borç Fişi';
    if ($m === 'balance') return 'Bakiye Düzeltme';
    if ($m === 'virement') return 'Cari Virman';
    return $in ? 'Tahsilat' : 'Ödeme';
}

function acc_pay_dec($v): float
{
    $v = str_replace(' ', '', trim((string)$v));
    if ($v === '') return 0.0;
    if (strpos($v, ',') !== false) $v = str_replace(',', '.', str_replace('.', '', $v));
    elseif (preg_match('/^-?\d{1,3}(\.\d{3})+$/', $v)) $v = str_replace('.', '', $v);   // 20.000 = yirmi bin
    return (float)$v;
}

function acc_pay_date($v, bool $nullable = false): ?string
{
    $v = trim((string)$v);
    if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $v) && strtotime($v)) return $v;
    if (preg_match('/^(\d{2})\.(\d{2})\.(\d{4})$/', $v, $m) && checkdate((int)$m[2], (int)$m[1], (int)$m[3])) return "$m[3]-$m[2]-$m[1]";
    return $nullable ? null : date('Y-m-d');
}

/** Ana tahsilat / ödeme sayfası (Nakit - Kredi Kartı - Banka) */
function acc_payment_form(): void
{
    $pid = (int)($_GET['party_id'] ?? $_POST['party_id'] ?? 0);
    $p = acc_q('SELECT * FROM acc_parties WHERE id = ?', [$pid])->fetch();
    if (!$p) { flash('Cari bulunamadı', 'err'); redirect(aurl('acc/parties/customer')); }
    $isCust = $p['kind'] === 'customer';
    $accounts = acc_q("SELECT * FROM acc_accounts WHERE is_active = 1 ORDER BY FIELD(kind,'cash','bank','pos','card','partner','credit'), name")->fetchAll();
    foreach ($accounts as &$a) { $a['bal'] = acc_account_balance($a); $a['xrate'] = acc_rate($a['currency']); }
    unset($a);
    $err = '';
    $f = ['type' => (($_GET['type'] ?? '') === 'in' || ($_GET['type'] ?? '') === 'out') ? $_GET['type'] : ($isCust ? 'in' : 'out'),
        'date' => date('Y-m-d'), 'note' => '', 'account_id' => 0, 'amount' => '', 'rate' => '', 'effective' => ''];
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $f = ['type' => ($_POST['type'] ?? '') === 'out' ? 'out' : 'in', 'date' => acc_pay_date($_POST['date'] ?? ''), 'note' => mb_substr(trim((string)($_POST['note'] ?? '')), 0, 250),
            'account_id' => (int)($_POST['account_id'] ?? 0), 'amount' => trim((string)($_POST['amount'] ?? '')), 'rate' => trim((string)($_POST['rate'] ?? '')), 'effective' => trim((string)($_POST['effective'] ?? ''))];
        $acc = acc_q('SELECT * FROM acc_accounts WHERE id = ? AND is_active = 1', [$f['account_id']])->fetch();
        $amount = acc_pay_dec($f['amount']); $rate = acc_pay_dec($f['rate']); $eff = acc_pay_dec($f['effective']);
        if (!$acc) $err = 'Lütfen bir Kasa/Hesap seçin.';
        elseif ($amount <= 0) $err = 'Lütfen tutarı girin.';
        else {
            if ($acc['currency'] === 'TRY') $rate = 1.0;
            elseif ($rate <= 0) $rate = $eff > 0 ? $eff / $amount : acc_rate($acc['currency']);
            acc_q('INSERT INTO acc_payments (pay_at, kind, party_id, account_id, amount, rate, note, method) VALUES (?,?,?,?,?,?,?,?)',
                [$f['date'] . ' ' . date('H:i:s'), $f['type'], $pid, (int)$acc['id'], $amount, $rate, $f['note'] !== '' ? $f['note'] : null, 'cash']);
            log_action('create', 'acc_payments', (int)db()->lastInsertId());
            flash(($f['type'] === 'in' ? 'Tahsilat' : 'Ödeme') . ' kaydedildi.');
            redirect(aurl('acc/party/' . $pid));
        }
    }
    $bal = acc_party_balance($pid);
    admin_view('acc_payment_form', compact('p', 'isCust', 'accounts', 'f', 'err', 'bal'));
}

/** Carinin tüm ödemeleri */
function acc_party_payments(int $id): void
{
    $p = acc_q('SELECT * FROM acc_parties WHERE id = ?', [$id])->fetch();
    if (!$p) { flash('Cari bulunamadı', 'err'); redirect(aurl('acc/parties/customer')); }
    $rows = acc_q('SELECT y.*, a.name account, a.currency FROM acc_payments y LEFT JOIN acc_accounts a ON a.id = y.account_id WHERE y.party_id = ? ORDER BY y.pay_at DESC, y.id DESC', [$id])->fetchAll();
    admin_view('acc_payments_list', compact('p', 'rows'));
}

/** Bakiye düzeltme / borç-alacak fişi / cari virman */
function acc_party_adjust(): void
{
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') redirect(aurl('acc'));
    $pid = (int)($_POST['party_id'] ?? 0);
    $p = acc_q('SELECT * FROM acc_parties WHERE id = ?', [$pid])->fetch();
    if (!$p) { flash('Cari bulunamadı', 'err'); redirect(aurl('acc/parties/customer')); }
    $back = aurl('acc/party/' . $pid);
    $type = (string)($_POST['type'] ?? '');
    $date = acc_pay_date($_POST['date'] ?? ''); $due = acc_pay_date($_POST['due_date'] ?? '', true);
    $note = mb_substr(trim((string)($_POST['note'] ?? '')), 0, 250);
    $ts = $date . ' ' . date('H:i:s');
    $ins = 'INSERT INTO acc_payments (pay_at, kind, party_id, account_id, amount, rate, note, method, due_date, link_id) VALUES (?,?,?,0,?,1,?,?,?,?)';
    $pdo = db();

    if ($type === 'balance') {
        $new = acc_pay_dec($_POST['balance'] ?? ''); $cur = acc_party_balance($pid); $diff = round($new - $cur, 2);
        if (abs($diff) < 0.01) { flash('Bakiye zaten bu tutarda.'); redirect($back); }
        acc_q($ins, [date('Y-m-d H:i:s'), acc_pay_kind_for($p['kind'], $diff > 0 ? 'rise' : 'drop'), $pid, abs($diff),
            ($note !== '' ? $note . ' · ' : '') . 'Bakiye düzeltme: ' . acc_money($cur) . ' → ' . acc_money($new), 'balance', null, null]);
        log_action('create', 'acc_payments', (int)$pdo->lastInsertId(), 'bakiye düzeltme');
        flash('Bakiye güncellendi.'); redirect($back);
    }

    $amount = acc_pay_dec($_POST['amount'] ?? '');
    if ($amount <= 0) { flash('Lütfen tutarı girin.', 'err'); redirect($back); }

    if ($type === 'receipt') {
        $kind = ($_POST['rtype'] ?? '1') === '2' ? 'out' : 'in';
        acc_q($ins, [$ts, $kind, $pid, $amount, $note !== '' ? $note : null, 'receipt', $due, null]);
        log_action('create', 'acc_payments', (int)$pdo->lastInsertId(), 'borç-alacak fişi');
        flash(($kind === 'in' ? 'Alacak' : 'Borç') . ' fişi kaydedildi.'); redirect($back);
    }

    if ($type === 'virement') {
        $oid = (int)($_POST['other_id'] ?? 0);
        $o = $oid && $oid !== $pid ? acc_q('SELECT id, name FROM acc_parties WHERE id = ?', [$oid])->fetch() : null;
        if (!$o) { flash('Virman için başka bir cari seçin.', 'err'); redirect($back); }
        $thisKind = ($_POST['vtype'] ?? '1') === '2' ? 'out' : 'in'; $otherKind = $thisKind === 'in' ? 'out' : 'in';
        $pdo->beginTransaction();
        try {
            acc_q($ins, [$ts, $thisKind, $pid, $amount, 'Virman – ' . $o['name'] . ($note !== '' ? ' · ' . $note : ''), 'virement', $due, null]);
            $a = (int)$pdo->lastInsertId();
            acc_q($ins, [$ts, $otherKind, $oid, $amount, 'Virman – ' . $p['name'] . ($note !== '' ? ' · ' . $note : ''), 'virement', $due, $a]);
            $b = (int)$pdo->lastInsertId();
            acc_q('UPDATE acc_payments SET link_id = ? WHERE id = ?', [$b, $a]);
            $pdo->commit();
        } catch (Throwable $e) { if ($pdo->inTransaction()) $pdo->rollBack(); flash('Virman kaydedilemedi: ' . $e->getMessage(), 'err'); redirect($back); }
        log_action('create', 'acc_payments', $a, 'cari virman');
        flash('Cari virman kaydedildi.'); redirect($back);
    }
    flash('Geçersiz işlem.', 'err'); redirect($back);
}

/** Ödeme / fiş silme (virmanda karşı kayıt da silinir) */
function acc_payment_delete(): void
{
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') redirect(aurl('acc'));
    $id = (int)($_POST['id'] ?? 0);
    $y = acc_q('SELECT * FROM acc_payments WHERE id = ?', [$id])->fetch();
    if (!$y) { flash('Kayıt bulunamadı', 'err'); redirect(aurl('acc')); }
    $back = $y['party_id'] ? aurl('acc/party/' . (int)$y['party_id']) : aurl('acc');
    if (($_POST['back'] ?? '') === 'list' && $y['party_id']) $back = aurl('acc/payments/' . (int)$y['party_id']);
    $ids = [$id];
    if (!empty($y['link_id'])) $ids[] = (int)$y['link_id'];
    $in = implode(',', array_map('intval', $ids));
    db()->exec("DELETE FROM acc_payments WHERE id IN ($in)");
    log_action('delete', 'acc_payments', $id);
    flash(acc_pay_label($y) . ' kaydı silindi.'); redirect($back);
}
