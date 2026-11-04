<?php
/* Ön Muhasebe › Nakit Yönetimi › Hesaplarım (BizimHesap "Hesaplarım"):
 * Kasa / Banka / POS / Kredi Kartı / Şirket Ortakları / Veresiye hesapları, hesap hareketleri,
 * hesaba para girişi-çıkışı ve hesaplar arası transfer. */

function acc_account_kinds(): array
{
    return [
        'cash'    => ['Kasa', 'Kasa Tanımları', 'Nakit paranızı tuttuğunuz kasaları buradan yönetirsiniz.'],
        'bank'    => ['Banka Hesabı', 'Banka Hesapları', 'Banka hesaplarınızın güncel bakiyesi.'],
        'pos'     => ['POS Hesabı', 'POS Hesapları', 'Kredi kartı ile yaptığınız tahsilatlar POS hesabına işlenir; bankaya geçen tutarı hesaplar arası transfer ile banka hesabınıza aktarırsınız.'],
        'card'    => ['Kredi Kartı', 'Kredi Kartları', 'Şirket kredi kartlarınız. Kartla yapılan ödemeler kart hesabından düşülür.'],
        'partner' => ['Ortaklar Hesabı', 'Şirket Ortakları Hesapları', 'Şirket ortaklarının şirketle olan nakit alacak / borç hesapları.'],
        'credit'  => ['Veresiye Hesabı', 'Veresiye Hesapları', 'Veresiye (açık hesap) çalıştığınız yerlerle olan hesaplarınız.'],
    ];
}

function acc_account_colors(): array
{
    return ['' => ['Beyaz', '#ffffff'], 'g' => ['Yeşil', '#d9f2e3'], 'b' => ['Mavi', '#d8e8fb'], 'o' => ['Turuncu', '#fde3c4'], 'n' => ['Lacivert', '#c9d3ea'], 'r' => ['Kırmızı', '#fbd5d5']];
}

function acc_account_cur(string $c): string { return $c === 'TRY' ? 'TL' : $c; }

/** Hesap bakiyesi (hesabın kendi para biriminde) */
function acc_account_balance(array $a): float
{
    $id = (int)$a['id'];
    $in = (float)acc_q("SELECT COALESCE(SUM(amount),0) FROM acc_payments WHERE account_id = ? AND kind = 'in'", [$id])->fetchColumn();
    $out = (float)acc_q("SELECT COALESCE(SUM(amount),0) FROM acc_payments WHERE account_id = ? AND kind = 'out'", [$id])->fetchColumn();
    $exp = (float)acc_q('SELECT COALESCE(SUM(amount),0) FROM acc_expenses WHERE account_id = ? AND is_paid = 1', [$id])->fetchColumn();
    $cin = (float)acc_q("SELECT COALESCE(SUM(amount),0) FROM acc_cash_moves WHERE account_id = ? AND kind = 'in'", [$id])->fetchColumn();
    $cout = (float)acc_q("SELECT COALESCE(SUM(amount),0) FROM acc_cash_moves WHERE account_id = ? AND kind = 'out'", [$id])->fetchColumn();
    return round((float)$a['opening_balance'] + $in - $out - $exp + $cin - $cout, 2);
}

function acc_accounts_page(): void
{
    $kinds = acc_account_kinds();
    $rows = acc_q('SELECT * FROM acc_accounts ORDER BY is_active DESC, name')->fetchAll();
    foreach ($rows as &$r) $r['balance'] = acc_account_balance($r);
    unset($r);
    admin_view('acc_accounts', compact('kinds', 'rows'));
}

/** Yeni hesap / Güncelle (modal POST) */
function acc_account_save(): void
{
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') redirect(aurl('acc/accounts'));
    $id = (int)($_POST['id'] ?? 0);
    $kinds = acc_account_kinds();
    $name = trim((string)($_POST['name'] ?? ''));
    $color = (string)($_POST['color'] ?? '');
    if (!isset(acc_account_colors()[$color])) $color = '';
    $no = trim((string)($_POST['account_no'] ?? '')); if ($no === '') $no = null;
    $limRaw = trim((string)($_POST['credit_limit'] ?? ''));
    $limit = $limRaw === '' ? null : acc_pay_dec($limRaw);
    $wanted = acc_pay_dec($_POST['balance'] ?? '0');
    if ($name === '') { flash('Hesap tanımı (adı) zorunludur.', 'err'); redirect(aurl('acc/accounts')); }

    if ($id) {
        $a = acc_q('SELECT * FROM acc_accounts WHERE id = ?', [$id])->fetch();
        if (!$a) { flash('Hesap bulunamadı.', 'err'); redirect(aurl('acc/accounts')); }
        // "Güncel Bakiye": istenen bakiyeye göre açılış bakiyesini ayarla
        $open = round((float)$a['opening_balance'] + ($wanted - acc_account_balance($a)), 2);
        acc_q('UPDATE acc_accounts SET name = ?, color = ?, account_no = ?, credit_limit = ?, opening_balance = ?, is_active = ? WHERE id = ?',
            [$name, $color, $no, $limit, $open, empty($_POST['inactive']) ? 1 : 0, $id]);
        log_action('update', 'acc_accounts', $id);
        flash('Hesap güncellendi.'); redirect(aurl('acc/account/' . $id));
    }
    $kind = (string)($_POST['kind'] ?? 'bank'); if (!isset($kinds[$kind])) $kind = 'bank';
    $cur = (string)($_POST['currency'] ?? 'TRY'); if (!isset(acc_party_currencies()[$cur])) $cur = 'TRY';
    acc_q('INSERT INTO acc_accounts (name, kind, currency, color, account_no, credit_limit, opening_balance, is_active) VALUES (?,?,?,?,?,?,?,1)',
        [$name, $kind, $cur, $color, $no, $limit, $wanted]);
    $nid = (int)db()->lastInsertId();
    log_action('create', 'acc_accounts', $nid);
    flash($kinds[$kind][0] . ' eklendi.'); redirect(aurl('acc/accounts'));
}

/** Hesaba para girişi / çıkışı / hesaplar arası transfer (modal POST) */
function acc_account_move_post(): void
{
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') redirect(aurl('acc/accounts'));
    $id = (int)($_POST['account_id'] ?? 0);
    $a = acc_q('SELECT * FROM acc_accounts WHERE id = ?', [$id])->fetch();
    $back = aurl('acc/account/' . $id);
    if (!$a) { flash('Hesap bulunamadı.', 'err'); redirect(aurl('acc/accounts')); }
    $type = (string)($_POST['type'] ?? 'in');
    $amount = round(acc_pay_dec($_POST['amount'] ?? ''), 2);
    $date = acc_pay_date($_POST['date'] ?? '');
    $note = trim((string)($_POST['note'] ?? '')); $note = $note === '' ? null : mb_substr($note, 0, 250);
    if ($amount <= 0) { flash('Tutar sıfırdan büyük olmalıdır.', 'err'); redirect($back); }
    $by = (string)(admin_user()['name'] ?? '');

    if ($type === 'in' || $type === 'out') {
        acc_q('INSERT INTO acc_cash_moves (account_id, kind, amount, move_at, note, created_by) VALUES (?,?,?,?,?,?)', [$id, $type, $amount, $date, $note, $by]);
        log_action('create', 'acc_cash_moves', (int)db()->lastInsertId());
        flash($type === 'in' ? 'Hesaba para girişi kaydedildi.' : 'Hesaptan para çıkışı kaydedildi.'); redirect($back);
    }
    if ($type === 'transfer') {
        $to = acc_q('SELECT * FROM acc_accounts WHERE id = ? AND id <> ?', [(int)($_POST['to_id'] ?? 0), $id])->fetch();
        if (!$to) { flash('Hedef hesabı seçin.', 'err'); redirect($back); }
        $toAmount = $to['currency'] === $a['currency'] ? $amount : round(acc_pay_dec($_POST['to_amount'] ?? ''), 2);
        if ($toAmount <= 0) $toAmount = $amount;
        $pdo = db(); $pdo->beginTransaction();
        try {
            $label = 'Transfer';
            acc_q('INSERT INTO acc_cash_moves (account_id, kind, amount, move_at, note, created_by, peer_id) VALUES (?,?,?,?,?,?,?)', [$id, 'out', $amount, $date, $note, $by, (int)$to['id']]);
            $o = (int)$pdo->lastInsertId();
            acc_q('INSERT INTO acc_cash_moves (account_id, kind, amount, move_at, note, created_by, peer_id, link_id) VALUES (?,?,?,?,?,?,?,?)', [(int)$to['id'], 'in', $toAmount, $date, $note, $by, $id, $o]);
            $i = (int)$pdo->lastInsertId();
            acc_q('UPDATE acc_cash_moves SET link_id = ? WHERE id = ?', [$i, $o]);
            $pdo->commit();
        } catch (Throwable $e) { $pdo->rollBack(); flash('Transfer kaydedilemedi.', 'err'); redirect($back); }
        log_action('create', 'acc_cash_moves', $o, ['transfer' => $to['id']]);
        flash('Transfer yapıldı: ' . $a['name'] . ' → ' . $to['name']); redirect($back);
    }
    redirect($back);
}

function acc_account_move_delete(): void
{
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') redirect(aurl('acc/accounts'));
    $id = (int)($_POST['id'] ?? 0);
    $m = acc_q('SELECT * FROM acc_cash_moves WHERE id = ?', [$id])->fetch();
    $back = aurl('acc/account/' . (int)($m['account_id'] ?? 0));
    if (!$m) { flash('Kayıt bulunamadı.', 'err'); redirect(aurl('acc/accounts')); }
    $ids = [$id]; if (!empty($m['link_id'])) $ids[] = (int)$m['link_id'];
    db()->exec('DELETE FROM acc_cash_moves WHERE id IN (' . implode(',', array_map('intval', $ids)) . ')');
    log_action('delete', 'acc_cash_moves', $id);
    flash('Hesap hareketi silindi.'); redirect($back);
}

/** Hesap detayı: hareketler (en yeni üstte), Borç = hesaba giriş, Alacak = hesaptan çıkış */
function acc_account_moves(int $id): void
{
    $a = acc_q('SELECT * FROM acc_accounts WHERE id = ?', [$id])->fetch();
    if (!$a) { flash('Hesap bulunamadı', 'err'); redirect(aurl('acc/accounts')); }
    $moves = [];
    foreach (acc_q('SELECT y.id, y.kind, y.pay_at d, y.amount, y.note, p.name party, p.id pid FROM acc_payments y LEFT JOIN acc_parties p ON p.id = y.party_id WHERE y.account_id = ?', [$id])->fetchAll() as $r)
        $moves[] = ['k' => 'p' . $r['id'], 'd' => $r['d'], 'label' => $r['kind'] === 'in' ? 'Tahsilat' : 'Ödeme', 'user' => '', 'who' => (string)$r['party'], 'pid' => (int)$r['pid'], 'note' => (string)$r['note'],
            'in' => $r['kind'] === 'in' ? (float)$r['amount'] : 0.0, 'out' => $r['kind'] === 'out' ? (float)$r['amount'] : 0.0, 'cm' => 0];
    foreach (acc_q('SELECT m.*, o.name peer FROM acc_cash_moves m LEFT JOIN acc_accounts o ON o.id = m.peer_id WHERE m.account_id = ?', [$id])->fetchAll() as $r)
        $moves[] = ['k' => 'c' . $r['id'], 'd' => $r['move_at'], 'label' => $r['peer_id'] ? ($r['kind'] === 'in' ? 'Transfer Girişi' : 'Transfer Çıkışı') : ($r['kind'] === 'in' ? 'Para Girişi' : 'Para Çıkışı'),
            'user' => (string)$r['created_by'], 'who' => (string)($r['peer'] ?? ''), 'pid' => 0, 'note' => (string)$r['note'],
            'in' => $r['kind'] === 'in' ? (float)$r['amount'] : 0.0, 'out' => $r['kind'] === 'out' ? (float)$r['amount'] : 0.0, 'cm' => (int)$r['id']];
    foreach (acc_q('SELECT id, exp_at d, amount, category, description FROM acc_expenses WHERE account_id = ? AND is_paid = 1', [$id])->fetchAll() as $r)
        $moves[] = ['k' => 'e' . $r['id'], 'd' => $r['d'], 'label' => 'Masraf', 'user' => '', 'who' => '', 'pid' => 0, 'note' => (string)($r['description'] ?: $r['category']), 'in' => 0.0, 'out' => (float)$r['amount'], 'cm' => 0];
    usort($moves, function ($x, $y) { return strcmp((string)$x['d'] . $x['k'], (string)$y['d'] . $y['k']); });
    $bal = (float)$a['opening_balance'];
    foreach ($moves as &$m) { $bal += $m['in'] - $m['out']; $m['bal'] = round($bal, 2); }
    unset($m);
    $moves = array_reverse($moves);
    $others = acc_q('SELECT id, name, currency FROM acc_accounts WHERE id <> ? AND is_active = 1 ORDER BY name', [$id])->fetchAll();
    admin_view('acc_account', compact('a', 'moves', 'bal', 'others'));
}
