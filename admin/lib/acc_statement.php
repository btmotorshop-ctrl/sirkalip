<?php
/* Ön Muhasebe › Cari Hesap Ekstresi (BizimHesap "Ekstre" ve "Detaylı Ekstre").
 * Tarih aralığı + Devir bakiye satırı; Müşteri: satış = Borç, tahsilat = Alacak.
 * Tedarikçi: alış = Alacak, ödeme = Borç. Bakiye pozitifse açık hesap (müşteri bize / biz tedarikçiye borçluyuz). */

function acc_st_n($v): string { return number_format((float)$v, 2, ',', '.'); }

function acc_st_qty($q): string
{
    $s = rtrim(rtrim(number_format((float)$q, 3, ',', ''), '0'), ',');
    return $s . ' Ad';
}

/** Ekstre verisi: devir bakiye, hareket satırları (isteğe bağlı kalemlerle), toplamlar */
function acc_statement_build(array $p, string $from, string $to, bool $detail): array
{
    $id = (int)$p['id'];
    $isCust = $p['kind'] === 'customer';
    $all = [];
    foreach (acc_q("SELECT id, kind, doc_no, doc_date d, due_date v, currency, rate, total*rate amt FROM acc_docs WHERE party_id = ? AND status = 'final'", [$id])->fetchAll() as $r) {
        $cur = $r['currency'] ?? 'TRY';
        $desc = ($r['kind'] === 'sale' ? 'Satış' : 'Alış') . ' No:' . $r['doc_no'];
        if ($cur !== 'TRY' && (float)$r['rate'] > 0) $desc .= ' 1 ' . $cur . ' = ' . number_format((float)$r['rate'], 5, ',', '.') . ' TL';
        $all[] = ['o' => 0, 'id' => (int)$r['id'], 'd' => $r['d'], 'v' => $r['v'] ?: $r['d'], 'type' => $r['kind'] === 'sale' ? 'Satış' : 'Alış', 'no' => (string)$r['doc_no'], 'desc' => $desc,
            'pay' => '', 'eff' => (float)$r['amt'], 'doc' => (int)$r['id'], 'rate' => (float)$r['rate'], 'link' => aurl('acc/doc/' . $r['kind'] . '/' . $r['id'])];
    }
    foreach (acc_q('SELECT y.id, y.kind, y.method, DATE(y.pay_at) d, y.due_date v, y.amount*COALESCE(y.rate,1) amt, y.note, a.name account FROM acc_payments y LEFT JOIN acc_accounts a ON a.id = y.account_id WHERE y.party_id = ?', [$id])->fetchAll() as $r) {
        $red = acc_pay_reduces($p['kind'], $r['kind']);
        $all[] = ['o' => 1, 'id' => (int)$r['id'], 'd' => $r['d'], 'v' => $r['v'] ?: $r['d'], 'type' => acc_pay_label($r), 'no' => '', 'desc' => (string)$r['note'],
            'pay' => ($r['method'] ?? 'cash') === 'cash' ? (string)$r['account'] : '', 'eff' => $red ? -(float)$r['amt'] : (float)$r['amt'], 'doc' => 0, 'rate' => 1.0, 'link' => ''];
    }
    usort($all, function ($a, $b) { return [$a['d'], $a['o'], $a['id']] <=> [$b['d'], $b['o'], $b['id']]; });

    $bal = (float)$p['opening_balance'];
    $rows = [];
    foreach ($all as $m) {
        if ($m['d'] < $from) { $bal += $m['eff']; continue; }
        if ($m['d'] > $to) continue;
        $rows[] = $m;
    }
    $devir = round($bal, 2);
    $debit = 0.0; $credit = 0.0;
    // Devir bakiye: müşteride (+) Borç, (−) Alacak; tedarikçide (+) Alacak, (−) Borç
    $dBorc = 0.0; $dAlacak = 0.0;
    if ($isCust) { if ($devir > 0) $dBorc = $devir; elseif ($devir < 0) $dAlacak = -$devir; }
    else { if ($devir > 0) $dAlacak = $devir; elseif ($devir < 0) $dBorc = -$devir; }
    $debit += $dBorc; $credit += $dAlacak;

    $lines = [];
    if ($detail) {
        $ids = array_values(array_unique(array_filter(array_column($rows, 'doc'))));
        if ($ids) foreach (acc_q('SELECT doc_id, name, qty, unit_price, line_total FROM acc_doc_lines WHERE doc_id IN (' . implode(',', array_map('intval', $ids)) . ') ORDER BY id')->fetchAll() as $l) $lines[$l['doc_id']][] = $l;
    }
    $out = [];
    foreach ($rows as $m) {
        $eff = $m['eff'];
        $increase = $eff >= 0;
        $isDebit = $isCust ? $increase : !$increase;     // Borç sütunu mu?
        $m['borc'] = $isDebit ? abs($eff) : 0.0; $m['alacak'] = $isDebit ? 0.0 : abs($eff);
        $debit += $m['borc']; $credit += $m['alacak'];
        $bal += $eff; $m['bal'] = round($bal, 2);
        $m['lines'] = [];
        foreach (($lines[$m['doc']] ?? []) as $l) $m['lines'][] = ['name' => $l['name'], 'qty' => (float)$l['qty'], 'price' => (float)$l['unit_price'] * $m['rate'], 'total' => (float)$l['line_total'] * $m['rate']];
        $out[] = $m;
    }
    return ['devir' => $devir, 'dBorc' => $dBorc, 'dAlacak' => $dAlacak, 'rows' => $out, 'debit' => round($debit, 2), 'credit' => round($credit, 2), 'bal' => round($bal, 2)];
}

function acc_statement_page(int $id, bool $detail): void
{
    $p = acc_q('SELECT * FROM acc_parties WHERE id = ?', [$id])->fetch();
    if (!$p) { flash('Cari bulunamadı', 'err'); redirect(aurl('acc/parties/customer')); }
    $isCust = $p['kind'] === 'customer';
    $to = acc_pay_date($_GET['to'] ?? '', true) ?: date('Y-m-d');
    $from = acc_pay_date($_GET['from'] ?? '', true) ?: date('Y-m-d', strtotime($to . ' -1 month'));
    if ($from > $to) { $t = $from; $from = $to; $to = $t; }
    $st = acc_statement_build($p, $from, $to, $detail);

    if (($_GET['export'] ?? '') === 'csv') {
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="ekstre-' . $id . '-' . date('Y-m-d') . '.csv"');
        $o = fopen('php://output', 'w'); fwrite($o, "\xEF\xBB\xBF");
        $n = function ($v) { return $v ? acc_st_n($v) : ''; };
        fputcsv($o, [$p['name'] . ' HESAP EKSTRESİ', acc_date($from) . ' - ' . acc_date($to)], ';');
        fputcsv($o, $detail ? ['Tarih', 'Vade', 'Hareket', 'Belge No', 'Açıklama', 'Miktar', 'Ödeme', 'Fiyat', 'Borç', 'Alacak', 'Bakiye'] : ['Tarih', 'Vade', 'Hareket', 'Açıklama', 'Ödeme', 'Borç', 'Alacak', 'Bakiye'], ';');
        $dv = $detail ? [acc_date($from), '', '', '', 'Devir bakiye', '', '', '', $n($st['dBorc']), $n($st['dAlacak']), acc_st_n($st['devir'])]
                      : [acc_date($from), acc_date($from), '', 'Devir bakiye', '', $n($st['dBorc']), $n($st['dAlacak']), acc_st_n($st['devir'])];
        fputcsv($o, $dv, ';');
        foreach ($st['rows'] as $m) {
            fputcsv($o, $detail ? [acc_date($m['d']), acc_date($m['v']), $m['type'], $m['no'], $m['desc'], '', $m['pay'], '', $n($m['borc']), $n($m['alacak']), acc_st_n($m['bal'])]
                                : [acc_date($m['d']), acc_date($m['v']), $m['type'], $m['desc'], $m['pay'], $n($m['borc']), $n($m['alacak']), acc_st_n($m['bal'])], ';');
            foreach ($m['lines'] as $l) fputcsv($o, ['', '', '', '', $l['name'], acc_st_qty($l['qty']), '', acc_st_n($l['price']), acc_st_n($l['total']), '', ''], ';');
        }
        fputcsv($o, $detail ? ['', '', '', '', '', '', '', 'TOPLAM', acc_st_n($st['debit']), acc_st_n($st['credit']), acc_st_n($st['bal'])] : ['', '', '', '', 'TOPLAM', acc_st_n($st['debit']), acc_st_n($st['credit']), acc_st_n($st['bal'])], ';');
        fclose($o); exit;
    }
    admin_view('acc_statement', compact('p', 'st', 'from', 'to', 'detail', 'isCust'));
}
