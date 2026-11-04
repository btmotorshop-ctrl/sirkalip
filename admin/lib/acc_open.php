<?php
/* Ön Muhasebe › Açık Hesap raporu (BizimHesap "Açık Hesap Raporu").
 * İşaretli bakiye: (+) cari bize borçlu = varlık · (−) biz cariye borçluyuz = borç.
 * Müşteri bakiyesi olduğu gibi, tedarikçi bakiyesi (biz borçluyuz = +) ters çevrilerek alınır;
 * böylece alacak fişiyle eksiye düşen müşteri de "Borçlar"da görünür. */

function acc_signed_balances(): array
{
    $rows = [];
    foreach (['customer' => 1, 'supplier' => -1] as $kind => $sign) {
        foreach (acc_balances($kind) as $r) {
            $r['kind'] = $kind;
            $r['sbal'] = round($sign * (float)$r['bal'], 2);
            $rows[] = $r;
        }
    }
    return $rows;
}

/** Ana sayfa özeti: [varlık (bize borçlular), borç (biz borçluyuz)] – ikisi de pozitif sayı */
function acc_open_totals(): array
{
    $asset = 0.0; $debt = 0.0;
    foreach (acc_signed_balances() as $r) { if ($r['sbal'] > 0.004) $asset += $r['sbal']; elseif ($r['sbal'] < -0.004) $debt += -$r['sbal']; }
    return [$asset, $debt];
}

function acc_open_accounts(): void
{
    if (isset($_GET['credit'])) $_GET['type'] = 'A'; elseif (isset($_GET['debit'])) $_GET['type'] = 'B';
    $type = ($_GET['type'] ?? 'B') === 'A' ? 'A' : 'B';            // B = Borçlular, A = Alacaklılar
    $who = in_array($_GET['who'] ?? '0', ['customer', 'supplier'], true) ? $_GET['who'] : '0';
    $rows = [];
    foreach (acc_signed_balances() as $r) {
        if ($who !== '0' && $r['kind'] !== $who) continue;
        if ($type === 'B' ? $r['sbal'] <= 0.004 : $r['sbal'] >= -0.004) continue;
        $rows[] = $r;
    }
    usort($rows, function ($a, $b) use ($type) { return $type === 'B' ? $b['sbal'] <=> $a['sbal'] : $a['sbal'] <=> $b['sbal']; });
    $total = 0.0; foreach ($rows as $r) $total += $r['sbal'];

    if (($_GET['export'] ?? '') === 'csv') {
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="acik-hesap-' . ($type === 'B' ? 'borclular' : 'alacaklilar') . '-' . date('Y-m-d') . '.csv"');
        $o = fopen('php://output', 'w'); fwrite($o, "\xEF\xBB\xBF");
        fputcsv($o, ['İsim / Unvan', 'Hesap Tipi', 'Açık Bakiyesi (TL)', 'Telefon'], ';');
        foreach ($rows as $r) fputcsv($o, [$r['name'], $r['kind'] === 'customer' ? 'Müşteri' : 'Tedarikçi', number_format($r['sbal'], 2, ',', ''), $r['phone']], ';');
        fputcsv($o, ['TOPLAM', '', number_format($total, 2, ',', ''), ''], ';');
        fclose($o); exit;
    }
    admin_view('acc_open_accounts', compact('rows', 'total', 'type', 'who'));
}
