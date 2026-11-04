<?php
/* Ön Muhasebe › Ayarlar › Teklif ve Özel Şablonlar
   Teklif / satış / alış belgelerinin yazdırma (PDF) şablonları. Tablo ilk kullanımda kendiliğinden oluşur. */

const ACCT_KINDS = ['proposal' => 'Teklif şablonu', 'sale' => 'Satış şablonu', 'purchase' => 'Alış şablonu'];
const ACCT_LANGS = ['tr' => 'Türkçe', 'en' => 'English', 'de' => 'Deutsch', 'fr' => 'Français', 'ru' => 'Русский'];
const ACCT_SIZES = ['a4' => 'A4', 'a5l' => 'A5 Yatay', 'a5p' => 'A5 Dikey', 't80' => '80mm Termal', 't58' => '58mm Termal'];

function acct_ensure(): void
{
    static $done = false; if ($done) return; $done = true;
    db()->exec("CREATE TABLE IF NOT EXISTS acc_doc_templates (
        id INT AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(120) NOT NULL,
        kind VARCHAR(10) NOT NULL DEFAULT 'proposal',
        lang VARCHAR(5) NOT NULL DEFAULT 'tr',
        title VARCHAR(190) NOT NULL DEFAULT '',
        page_size VARCHAR(8) NOT NULL DEFAULT 'a4',
        head_name VARCHAR(255) NULL,
        head_info TEXT NULL,
        intro TEXT NULL,
        footer TEXT NULL,
        opts LONGTEXT NULL,
        created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_turkish_ci");
    if ((int)db()->query('SELECT COUNT(*) FROM acc_doc_templates')->fetchColumn() === 0) {
        $g = (array)setting('general', []);
        $name = (string)($g['siteName'] ?? 'Firma Adı');
        $info = trim(implode("\n", array_filter([(string)($g['address'] ?? ''), !empty($g['phone']) ? 'Tel: ' . $g['phone'] : '', !empty($g['email']) ? 'E-posta: ' . $g['email'] : ''])));
        $st = db()->prepare('INSERT INTO acc_doc_templates (name, kind, lang, title, page_size, head_name, head_info, intro, footer, opts) VALUES (?,?,?,?,?,?,?,?,?,?)');
        foreach ([
            ['Teklif Formu', 'proposal', 'TEKLİF', 'Yapmış olduğumuz görüşmeler sonrasında hazırladığımız fiyat teklifimizi değerlendirmenize sunarız.'],
            ['Satış Bilgi Notu', 'sale', 'SATIŞ BİLGİ NOTU', ''],
            ['Alış Bilgi Notu', 'purchase', 'ALIŞ BİLGİ NOTU', ''],
        ] as [$n, $k, $title, $intro]) $st->execute([$n, $k, 'tr', $title, 'a4', $name, $info, $intro, '', json_encode(acct_default_opts())]);
    }
}

/** Seçenek tanımları: grup => [anahtar => [etiket, [değer => metin], varsayılan]] */
function acct_meta(): array
{
    $gor = ['1' => 'görünsün', '0' => 'görünmesin'];
    $yn = function (string $x) use ($gor) { return ['1' => "$x görünsün", '0' => "$x görünmesin"]; };
    return [
        'Genel Ayarlar' => [
            'show_due' => ['Geçerlilik / vade tarihi', $yn('Geçerlilik / vade tarihi'), '1'],
            'show_logo' => ['Logo', $yn('Logo'), '1'],
            'show_address' => ['Müşteri adresi', $yn('Müşteri adresi'), '1'],
            'totals' => ['Dip toplam bloğu', ['full' => 'Dip toplam bloğu görünsün', 'total' => 'Sadece toplam tutar görünsün', 'none' => 'Dip toplam bloğu görünmesin'], 'full'],
            'show_note' => ['Belge açıklaması', $yn('Açıklama'), '1'],
            'show_balance' => ['Müşterinin güncel bakiyesi', $yn('Güncel bakiye'), '0'],
        ],
        'Kolon Ayarları' => [
            'col_disc_rate' => ['İndirim oranı', ['1' => 'Satırlarda indirim oranı görünsün', '0' => 'Satırlarda indirim oranı görünmesin'], '0'],
            'col_price' => ['Birim fiyat', ['1' => 'Satırlarda birim fiyat görünsün', '0' => 'Satırlarda birim fiyat görünmesin'], '1'],
            'price_vat' => ['Birim fiyat KDV', ['excl' => 'Birim fiyat KDV hariç görünsün', 'incl' => 'Birim fiyat KDV dahil görünsün'], 'excl'],
            'total_vat' => ['Satır toplamı KDV', ['incl' => 'Satır toplamı KDV dahil görünsün', 'excl' => 'Satır toplamı KDV hariç görünsün'], 'excl'],
            'price_mode' => ['Fiyat türü', ['disc' => 'İndirimli fiyat görünsün', 'list' => 'Liste fiyatı görünsün', 'both' => 'Liste ve indirimli fiyat görünsün'], 'list'],
            'col_vat' => ['KDV oranı', ['1' => 'Satırlarda KDV oranı görünsün', '0' => 'Satırlarda KDV oranı görünmesin'], '1'],
            'col_total' => ['Satır tutarı', ['1' => 'Satırlarda toplam tutar görünsün', '0' => 'Satırlarda toplam tutar görünmesin'], '1'],
            'col_image' => ['Ürün resmi', ['1' => 'Satırlarda ürün resmi görünsün', '0' => 'Satırlarda ürün resmi görünmesin'], '0'],
            'col_code' => ['Ürün kodu', ['1' => 'Satırlarda ürün kodu görünsün', '0' => 'Satırlarda ürün kodu görünmesin'], '1'],
            'col_barcode' => ['Barkod', ['0' => 'Barkod görünmesin', '1' => 'Barkod rakamı görünsün'], '0'],
        ],
    ];
}
function acct_default_opts(): array
{
    $o = []; foreach (acct_meta() as $g) foreach ($g as $k => [, , $def]) $o[$k] = $def;
    return $o;
}
function acct_opts(array $t): array
{
    $o = acct_default_opts();
    $j = json_decode((string)($t['opts'] ?? ''), true);
    if (is_array($j)) foreach ($j as $k => $v) if (isset($o[$k])) $o[$k] = (string)$v;
    return $o;
}
function acct_clean_opts(array $in): array
{
    $o = acct_default_opts();
    foreach (acct_meta() as $g) foreach ($g as $k => [, $choices]) if (isset($in[$k]) && isset($choices[(string)$in[$k]])) $o[$k] = (string)$in[$k];
    return $o;
}

function acct_dict(string $lang): array
{
    $d = [
        'tr' => ['date' => 'Tarih', 'valid' => 'Geçerlilik', 'due' => 'Vade', 'no' => 'No', 'desc' => 'Açıklama', 'qty' => 'Miktar', 'price' => 'Fiyat', 'list' => 'Liste Fiyatı', 'disc_price' => 'İnd. Fiyat', 'disc_rate' => 'İnd. %', 'vat' => 'KDV (%)', 'amt' => 'Tutar', 'excl' => 'KDV Hariç', 'incl' => 'KDV Dahil', 'code' => 'Kod', 'barcode' => 'Barkod', 'gross' => 'Brüt', 'discount' => 'İndirim', 'net' => 'Net', 'vat_total' => 'KDV', 'grand' => 'Toplam', 'balance' => 'Güncel Bakiye', 'note' => 'Açıklama', 'to' => 'Sayın', 'print' => 'Yazdır / PDF Kaydet'],
        'en' => ['date' => 'Date', 'valid' => 'Valid until', 'due' => 'Due date', 'no' => 'No', 'desc' => 'Description', 'qty' => 'Qty', 'price' => 'Price', 'list' => 'List price', 'disc_price' => 'Disc. price', 'disc_rate' => 'Disc. %', 'vat' => 'VAT (%)', 'amt' => 'Amount', 'excl' => 'excl. VAT', 'incl' => 'incl. VAT', 'code' => 'Code', 'barcode' => 'Barcode', 'gross' => 'Gross', 'discount' => 'Discount', 'net' => 'Net', 'vat_total' => 'VAT', 'grand' => 'Total', 'balance' => 'Current balance', 'note' => 'Note', 'to' => 'To', 'print' => 'Print / Save as PDF'],
        'de' => ['date' => 'Datum', 'valid' => 'Gültig bis', 'due' => 'Fällig', 'no' => 'Nr.', 'desc' => 'Beschreibung', 'qty' => 'Menge', 'price' => 'Preis', 'list' => 'Listenpreis', 'disc_price' => 'Rabattpreis', 'disc_rate' => 'Rabatt %', 'vat' => 'MwSt (%)', 'amt' => 'Betrag', 'excl' => 'netto', 'incl' => 'brutto', 'code' => 'Code', 'barcode' => 'Barcode', 'gross' => 'Brutto', 'discount' => 'Rabatt', 'net' => 'Netto', 'vat_total' => 'MwSt', 'grand' => 'Gesamt', 'balance' => 'Aktueller Saldo', 'note' => 'Anmerkung', 'to' => 'An', 'print' => 'Drucken / PDF'],
        'fr' => ['date' => 'Date', 'valid' => 'Valable jusqu\'au', 'due' => 'Échéance', 'no' => 'N°', 'desc' => 'Description', 'qty' => 'Qté', 'price' => 'Prix', 'list' => 'Prix catalogue', 'disc_price' => 'Prix remisé', 'disc_rate' => 'Remise %', 'vat' => 'TVA (%)', 'amt' => 'Montant', 'excl' => 'HT', 'incl' => 'TTC', 'code' => 'Code', 'barcode' => 'Code-barres', 'gross' => 'Brut', 'discount' => 'Remise', 'net' => 'Net', 'vat_total' => 'TVA', 'grand' => 'Total', 'balance' => 'Solde actuel', 'note' => 'Note', 'to' => 'À', 'print' => 'Imprimer / PDF'],
        'ru' => ['date' => 'Дата', 'valid' => 'Действительно до', 'due' => 'Срок оплаты', 'no' => '№', 'desc' => 'Описание', 'qty' => 'Кол-во', 'price' => 'Цена', 'list' => 'Прайс-цена', 'disc_price' => 'Цена со скидкой', 'disc_rate' => 'Скидка %', 'vat' => 'НДС (%)', 'amt' => 'Сумма', 'excl' => 'без НДС', 'incl' => 'с НДС', 'code' => 'Код', 'barcode' => 'Штрихкод', 'gross' => 'Брутто', 'discount' => 'Скидка', 'net' => 'Нетто', 'vat_total' => 'НДС', 'grand' => 'Итого', 'balance' => 'Текущий баланс', 'note' => 'Примечание', 'to' => 'Кому', 'print' => 'Печать / PDF'],
    ];
    return $d[$lang] ?? $d['en'];
}

function acct_page(string $size): array
{
    return [
        'a4' => ['A4 portrait', 794], 'a5l' => ['A5 landscape', 794], 'a5p' => ['A5 portrait', 559],
        't80' => ['80mm 200mm', 302], 't58' => ['58mm 200mm', 220],
    ][$size] ?? ['A4 portrait', 794];
}

function acct_fallback(string $kind): array
{
    return ['id' => 0, 'name' => 'Varsayılan', 'kind' => $kind, 'lang' => 'tr', 'title' => $kind === 'proposal' ? 'TEKLİF' : ($kind === 'sale' ? 'SATIŞ BİLGİ NOTU' : 'ALIŞ BİLGİ NOTU'), 'page_size' => 'a4',
        'head_name' => (string)(((array)setting('general', []))['siteName'] ?? ''), 'head_info' => '', 'intro' => '', 'footer' => '', 'opts' => json_encode(acct_default_opts())];
}
function acct_for_kind(string $kind): array
{
    acct_ensure();
    return acc_q('SELECT * FROM acc_doc_templates WHERE kind = ? ORDER BY id', [$kind])->fetchAll();
}

function acct_css(): string
{
    return <<<'CSS'
.pp{box-sizing:border-box;background:#fff;color:#1f2937;font:13px/1.45 "Inter","Segoe UI",Arial,sans-serif;padding:34px 38px;margin:0 auto;box-shadow:0 2px 14px rgba(15,23,42,.12)}
.pp *{box-sizing:border-box}
.pp-top{display:flex;gap:18px;align-items:flex-start;justify-content:space-between}
.pp-logo img{max-height:90px;max-width:200px;object-fit:contain}
.pp-firm{text-align:right}
.pp-firm h1{font-size:18px;margin:0 0 4px;font-weight:800;letter-spacing:-.01em}
.pp-firm div{font-size:12px;color:#4b5563}
.pp-title{text-align:center;font-size:20px;font-weight:800;letter-spacing:.06em;margin:22px 0 14px;color:#374151}
.pp-meta{display:flex;justify-content:space-between;gap:20px;margin-bottom:12px}
.pp-party b{display:block;font-size:14px}
.pp-party span{display:block;font-size:12px;color:#4b5563}
.pp-meta table{font-size:12.5px}
.pp-meta td{padding:1px 0 1px 14px}
.pp-meta td:first-child{color:#6b7280;text-align:right}
.pp-meta td:last-child{font-weight:600}
.pp-intro{border:1px dashed #cbd5e1;border-radius:6px;padding:8px 10px;margin:8px 0 14px;font-size:12.5px}
.pp-t{width:100%;border-collapse:collapse;font-size:12.5px}
.pp-t th{background:#f3f4f6;text-align:left;font-size:11.5px;padding:8px 8px;border-bottom:1px solid #d1d5db}
.pp-t td{padding:8px 8px;border-bottom:1px solid #eceff3;vertical-align:top}
.pp-t .r{text-align:right;white-space:nowrap}
.pp-t small{display:block;color:#6b7280;font-size:11px;margin-top:2px}
.pp-t img,.pp-ph{width:42px;height:42px;object-fit:contain;border-radius:5px;background:#f3f4f6;display:block}
.pp-sum{margin:12px 0 0 auto;width:270px;font-size:12.5px}
.pp-sum div{display:flex;justify-content:space-between;padding:3px 0}
.pp-sum .g{border-top:1px solid #9ca3af;margin-top:4px;padding-top:6px;font-size:15px;font-weight:800}
.pp-note,.pp-foot{margin-top:14px;font-size:12px;white-space:normal}
.pp-foot{border-top:1px solid #e5e7eb;padding-top:10px;color:#374151}
.pp-bal{margin-top:10px;font-size:12.5px;text-align:right}
CSS;
}

/** Şablon + belge verisinden kâğıt HTML'i üretir (önizleme ve yazdırma ortak kullanır). */
function acct_paper(array $t, array $doc, array $lines, array $party, ?float $balance = null): string
{
    $o = acct_opts($t); $d = acct_dict($t['lang'] ?? 'tr'); $cur = $doc['currency'] ?? 'TRY';
    $kind = $doc['kind'] ?? ($t['kind'] ?? 'proposal'); $isProp = $kind === 'proposal';
    $m = function ($v) use ($cur) { return acc_money($v, $cur); };
    $nl = function ($s) { return nl2br(e((string)$s)); };
    $num = function ($v) { return rtrim(rtrim(number_format((float)$v, 3, ',', '.'), '0'), ','); };
    [, $width] = acct_page($t['page_size'] ?? 'a4');

    // Satır hesapları
    $rows = []; $gross = 0.0; $discSum = 0.0;
    foreach ($lines as $l) {
        $qty = (float)$l['qty']; $price = (float)$l['unit_price']; $vat = (float)$l['vat_rate']; $disc = (float)($l['discount'] ?? 0);
        $g = $qty * $price; $net = $g - $disc; $k = 1 + $vat / 100;
        $unitDisc = $qty > 0 ? $price - $disc / $qty : $price;
        $gross += $g; $discSum += $disc;
        $rows[] = ['l' => $l, 'qty' => $qty, 'price' => $price, 'unitDisc' => $unitDisc, 'discRate' => $g > 0 ? $disc / $g * 100 : 0, 'vat' => $vat, 'net' => $net, 'k' => $k];
    }
    $discSum += (float)($doc['discount_total'] ?? 0);
    $vatTotal = (float)($doc['vat_total'] ?? 0); $grand = (float)($doc['total'] ?? 0);
    $netSum = $gross - $discSum;

    // Başlık bloğu
    $logo = '';
    if ($o['show_logo'] === '1') {
        $dz = (array)setting('design', []); $src = !empty($dz['logo']) ? media_url($dz['logo']) : asset('img/logo-sm.png');
        $logo = '<div class="pp-logo"><img src="' . e($src) . '" alt=""></div>';
    }
    $h = '<div class="pp" style="width:' . (int)$width . 'px;max-width:100%"><div class="pp-top">' . ($logo ?: '<div></div>');
    $h .= '<div class="pp-firm"><h1>' . e($t['head_name'] ?? '') . '</h1><div>' . $nl($t['head_info'] ?? '') . '</div></div></div>';
    $title = trim((string)($t['title'] ?? '')); if ($title !== '') $h .= '<div class="pp-title">' . e($title) . '</div>';

    // Cari + tarih bilgileri
    $h .= '<div class="pp-meta"><div class="pp-party"><b>' . e($party['name'] ?? '—') . '</b>';
    if ($o['show_address'] === '1') {
        $addr = trim((string)($party['address'] ?? '') . ' ' . (!empty($party['city']) ? $party['city'] : ''));
        if ($addr !== '') $h .= '<span>' . e($addr) . '</span>';
        $tx = trim((string)($party['tax_office'] ?? '') . ' ' . (!empty($party['tax_no']) ? 'V.N: ' . $party['tax_no'] : ''));
        if ($tx !== '') $h .= '<span>' . e($tx) . '</span>';
    }
    $h .= '</div><table><tr><td>' . e($d['date']) . ':</td><td>' . e(acc_date($doc['doc_date'] ?? date('Y-m-d'))) . '</td></tr>';
    if ($o['show_due'] === '1' && !empty($doc['due_date'])) $h .= '<tr><td>' . e($isProp ? $d['valid'] : $d['due']) . ':</td><td>' . e(acc_date($doc['due_date'])) . '</td></tr>';
    $h .= '<tr><td>' . e($d['no']) . ':</td><td>' . e($doc['doc_no'] ?? '') . '</td></tr></table></div>';

    if (trim((string)($t['intro'] ?? '')) !== '') $h .= '<div class="pp-intro">' . $nl($t['intro']) . '</div>';

    // Kalem tablosu
    $vatNote = function (string $mode) use ($d) { return ' <small style="display:inline">(' . e($mode === 'incl' ? $d['incl'] : $d['excl']) . ')</small>'; };
    $head = []; if ($o['col_image'] === '1') $head[] = '<th style="width:54px"></th>';
    $head[] = '<th>' . e($d['desc']) . '</th><th class="r">' . e($d['qty']) . '</th>';
    $showList = $o['col_price'] === '1' && in_array($o['price_mode'], ['list', 'both'], true);
    $showDisc = $o['col_price'] === '1' && in_array($o['price_mode'], ['disc', 'both'], true);
    if ($showList) $head[] = '<th class="r">' . e($o['price_mode'] === 'both' ? $d['list'] : $d['price']) . $vatNote($o['price_vat']) . '</th>';
    if ($showDisc) $head[] = '<th class="r">' . e($o['price_mode'] === 'both' ? $d['disc_price'] : $d['price']) . $vatNote($o['price_vat']) . '</th>';
    if ($o['col_disc_rate'] === '1') $head[] = '<th class="r">' . e($d['disc_rate']) . '</th>';
    if ($o['col_vat'] === '1') $head[] = '<th class="r">' . e($d['vat']) . '</th>';
    if ($o['col_total'] === '1') $head[] = '<th class="r">' . e($d['amt']) . $vatNote($o['total_vat']) . '</th>';

    $h .= '<table class="pp-t"><thead><tr>' . implode('', $head) . '</tr></thead><tbody>';
    foreach ($rows as $r) {
        $l = $r['l']; $vp = function ($x) use ($r, $o) { return $o['price_vat'] === 'incl' ? $x * $r['k'] : $x; };
        $h .= '<tr>';
        if ($o['col_image'] === '1') $h .= '<td>' . (!empty($l['_img']) ? '<img src="' . e(media_url($l['_img'])) . '" alt="">' : '<span class="pp-ph"></span>') . '</td>';
        $sub = [];
        if ($o['col_code'] === '1' && !empty($l['_code'])) $sub[] = e($d['code']) . ': ' . e($l['_code']);
        if ($o['col_barcode'] === '1' && !empty($l['_barcode'])) $sub[] = e($d['barcode']) . ': ' . e($l['_barcode']);
        if (!empty($l['description'])) $sub[] = e($l['description']);
        $h .= '<td>' . e($l['name']) . ($sub ? '<small>' . implode('<br>', $sub) . '</small>' : '') . '</td><td class="r">' . $num($r['qty']) . ' ' . e($l['unit'] ?? '') . '</td>';
        if ($showList) $h .= '<td class="r">' . $m($vp($r['price'])) . '</td>';
        if ($showDisc) $h .= '<td class="r">' . $m($vp($r['unitDisc'])) . '</td>';
        if ($o['col_disc_rate'] === '1') $h .= '<td class="r">%' . $num(round($r['discRate'], 2)) . '</td>';
        if ($o['col_vat'] === '1') $h .= '<td class="r">%' . $num($r['vat']) . '</td>';
        if ($o['col_total'] === '1') $h .= '<td class="r"><b>' . $m($o['total_vat'] === 'incl' ? $r['net'] * $r['k'] : $r['net']) . '</b></td>';
        $h .= '</tr>';
    }
    if (!$rows) $h .= '<tr><td colspan="9" style="text-align:center;color:#9ca3af;padding:20px">—</td></tr>';
    $h .= '</tbody></table>';

    // Dip toplam
    if ($o['totals'] === 'full') {
        $h .= '<div class="pp-sum"><div><span>' . e($d['gross']) . '</span><span>' . $m($gross) . '</span></div>';
        if ($discSum > 0) $h .= '<div><span>' . e($d['discount']) . '</span><span>-' . $m($discSum) . '</span></div>';
        $h .= '<div><span>' . e($d['net']) . '</span><span>' . $m($netSum) . '</span></div><div><span>' . e($d['vat_total']) . '</span><span>' . $m($vatTotal) . '</span></div>';
        $h .= '<div class="g"><span>' . e($d['grand']) . '</span><span>' . $m($grand) . '</span></div></div>';
    } elseif ($o['totals'] === 'total') {
        $h .= '<div class="pp-sum"><div class="g"><span>' . e($d['grand']) . '</span><span>' . $m($grand) . '</span></div></div>';
    }
    if ($o['show_balance'] === '1' && $balance !== null) $h .= '<div class="pp-bal">' . e($d['balance']) . ': <b>' . acc_money($balance) . '</b></div>';
    if ($o['show_note'] === '1' && trim((string)($doc['note'] ?? '')) !== '') $h .= '<div class="pp-note"><b>' . e($d['note']) . ':</b> ' . $nl($doc['note']) . '</div>';
    if (trim((string)($t['footer'] ?? '')) !== '') $h .= '<div class="pp-foot">' . $nl($t['footer']) . '</div>';
    return $h . '</div>';
}

/** Önizleme için örnek belge verisi */
function acct_sample(string $kind): array
{
    $doc = ['kind' => $kind, 'doc_no' => ($kind === 'proposal' ? 'TKL' : ($kind === 'sale' ? 'SAT' : 'ALS')) . date('Y') . '000123', 'doc_date' => date('Y-m-d'), 'due_date' => date('Y-m-d', strtotime('+30 days')),
        'currency' => 'TRY', 'rate' => 1, 'discount_total' => 0, 'note' => 'Belge açıklaması buraya gelir.'];
    $lines = [
        ['name' => 'Örnek ürün A', 'qty' => 10, 'unit_price' => 30, 'vat_rate' => 20, 'discount' => 30, 'unit' => 'Ad', 'description' => 'Ürün kartındaki ilave açıklama buraya gelir.', '_code' => 'ORN-001', '_barcode' => '8690000000011'],
        ['name' => 'Örnek ürün B', 'qty' => 2, 'unit_price' => 125, 'vat_rate' => 20, 'discount' => 0, 'unit' => 'Ad', '_code' => 'ORN-002', '_barcode' => '8690000000028'],
    ];
    $net = 300 - 30 + 250; $vat = round($net * 0.2, 2); $doc['subtotal'] = $net; $doc['vat_total'] = $vat; $doc['total'] = $net + $vat;
    $party = ['name' => 'Örnek Ticaret Ltd. Şti.', 'address' => 'Örnek mah. Örnek sok. No:1', 'city' => 'İSTANBUL', 'tax_office' => 'Örnek V.D.', 'tax_no' => '1234567890'];
    return [$doc, $lines, $party];
}

function acct_from_post(?array $old = null): array
{
    $kind = isset(ACCT_KINDS[$_POST['kind'] ?? '']) ? $_POST['kind'] : 'proposal';
    $lang = isset(ACCT_LANGS[$_POST['lang'] ?? '']) ? $_POST['lang'] : 'tr';
    $size = isset(ACCT_SIZES[$_POST['page_size'] ?? '']) ? $_POST['page_size'] : 'a4';
    $s = function ($k, $n) { return mb_substr(trim((string)($_POST[$k] ?? '')), 0, $n); };
    return ['id' => (int)($old['id'] ?? 0), 'name' => $s('name', 120), 'kind' => $kind, 'lang' => $lang, 'title' => $s('title', 190), 'page_size' => $size,
        'head_name' => $s('head_name', 255), 'head_info' => $s('head_info', 2000), 'intro' => $s('intro', 3000), 'footer' => $s('footer', 3000),
        'opts' => json_encode(acct_clean_opts((array)($_POST['opt'] ?? [])))];
}

function acc_templates_route(array $seg): void
{
    acct_ensure();
    $a = $seg[1] ?? 'templates';
    if ($a === 'template-preview') { acct_preview(); return; }
    if ($a === 'templates') { acct_list(); return; }
    $id = $seg[2] ?? 'new'; $act = $seg[3] ?? '';
    if ($id === 'new') { acct_edit(null); return; }
    $id = (int)$id;
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && $act === 'delete') {
        db()->prepare('DELETE FROM acc_doc_templates WHERE id = ?')->execute([$id]);
        log_action('delete', 'acc_doc_templates', $id); flash('Şablon silindi.'); redirect(aurl('acc/templates'));
    }
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && $act === 'copy') {
        $t = acc_q('SELECT * FROM acc_doc_templates WHERE id = ?', [$id])->fetch();
        if ($t) {
            db()->prepare('INSERT INTO acc_doc_templates (name, kind, lang, title, page_size, head_name, head_info, intro, footer, opts) VALUES (?,?,?,?,?,?,?,?,?,?)')
                ->execute([mb_substr($t['name'] . ' (kopya)', 0, 120), $t['kind'], $t['lang'], $t['title'], $t['page_size'], $t['head_name'], $t['head_info'], $t['intro'], $t['footer'], $t['opts']]);
            $nid = (int)db()->lastInsertId(); log_action('create', 'acc_doc_templates', $nid); flash('Şablon kopyalandı.'); redirect(aurl('acc/template/' . $nid));
        }
        redirect(aurl('acc/templates'));
    }
    acct_edit($id);
}

function acct_list(): void
{
    $rows = acc_q('SELECT * FROM acc_doc_templates ORDER BY FIELD(kind, "proposal", "sale", "purchase"), id')->fetchAll();
    admin_view('acc_templates', compact('rows'));
}

function acct_edit(?int $id): void
{
    $t = $id ? acc_q('SELECT * FROM acc_doc_templates WHERE id = ?', [$id])->fetch() : null;
    if ($id && !$t) { flash('Şablon bulunamadı.', 'err'); redirect(aurl('acc/templates')); }
    if (!$t) { $t = acct_fallback($_GET['kind'] ?? 'proposal'); $t['id'] = 0; $t['name'] = ''; $g = (array)setting('general', []); $t['head_name'] = (string)($g['siteName'] ?? ''); }
    $errors = [];
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $n = acct_from_post($t);
        if ($n['name'] === '') $errors['name'] = 'Şablon adı zorunludur.';
        $t = $n + $t;
        if (!$errors) {
            if ($id) {
                db()->prepare('UPDATE acc_doc_templates SET name=?, kind=?, lang=?, title=?, page_size=?, head_name=?, head_info=?, intro=?, footer=?, opts=? WHERE id=?')
                    ->execute([$n['name'], $n['kind'], $n['lang'], $n['title'], $n['page_size'], $n['head_name'], $n['head_info'], $n['intro'], $n['footer'], $n['opts'], $id]);
                log_action('update', 'acc_doc_templates', $id);
            } else {
                db()->prepare('INSERT INTO acc_doc_templates (name, kind, lang, title, page_size, head_name, head_info, intro, footer, opts) VALUES (?,?,?,?,?,?,?,?,?,?)')
                    ->execute([$n['name'], $n['kind'], $n['lang'], $n['title'], $n['page_size'], $n['head_name'], $n['head_info'], $n['intro'], $n['footer'], $n['opts']]);
                log_action('create', 'acc_doc_templates', (int)db()->lastInsertId());
            }
            flash('Şablon kaydedildi.'); redirect(aurl('acc/templates'));
        }
    }
    [$doc, $lines, $party] = acct_sample($t['kind']);
    $paper = acct_paper($t, $doc, $lines, $party);
    admin_view('acc_template_form', compact('t', 'errors', 'paper', 'id'));
}

function acct_preview(): void
{
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') { http_response_code(405); exit; }
    $n = acct_from_post(); [$doc, $lines, $party] = acct_sample($n['kind']);
    header('Content-Type: text/html; charset=utf-8'); echo acct_paper($n, $doc, $lines, $party); exit;
}

/** Belgeyi seçilen şablonla yazdırılabilir (PDF'e kaydedilebilir) sayfa olarak göster */
function acc_doc_print(int $id, string $kind): void
{
    acct_ensure();
    $doc = acc_q('SELECT * FROM acc_docs WHERE id = ?', [$id])->fetch();
    if (!$doc) { flash('Belge bulunamadı', 'err'); redirect(aurl("acc/docs/$kind")); }
    $kind = $doc['kind'];
    $lines = acc_q('SELECT * FROM acc_doc_lines WHERE doc_id = ? ORDER BY id', [$id])->fetchAll();
    foreach ($lines as &$l) {
        if (!empty($l['product_id'])) {
            $p = acc_q('SELECT code, barcode FROM products WHERE id = ?', [$l['product_id']])->fetch();
            if ($p) { $l['_code'] = $p['code']; $l['_barcode'] = $p['barcode']; }
            $im = acc_q('SELECT url FROM product_images WHERE product_id = ? ORDER BY is_main DESC, sort_order, id LIMIT 1', [$l['product_id']])->fetchColumn();
            if ($im) $l['_img'] = $im;
        }
    }
    unset($l);
    $party = $doc['party_id'] ? (acc_q('SELECT * FROM acc_parties WHERE id = ?', [$doc['party_id']])->fetch() ?: []) : [];
    if (!$party) $party = ['name' => 'Perakende'];
    $tpls = acct_for_kind($kind); $t = null;
    $want = (int)($_GET['tpl'] ?? 0);
    foreach ($tpls as $x) if ((int)$x['id'] === $want) $t = $x;
    if (!$t) $t = $tpls[0] ?? acct_fallback($kind);
    $balance = null;
    if ($doc['party_id'] && acct_opts($t)['show_balance'] === '1') {
        foreach (acc_balances(($party['kind'] ?? 'customer') === 'supplier' ? 'supplier' : 'customer') as $b) if ((int)$b['id'] === (int)$doc['party_id']) $balance = (float)$b['bal'];
    }
    $paper = acct_paper($t, $doc, $lines, $party, $balance);
    [$pcss] = acct_page($t['page_size']); $d = acct_dict($t['lang']);
    header('Content-Type: text/html; charset=utf-8');
    echo '<!doctype html><html lang="' . e($t['lang']) . '"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><title>' . e($doc['doc_no']) . '</title><style>'
        . 'body{margin:0;background:#e5e7eb;font-family:Inter,Segoe UI,Arial,sans-serif}.bar{position:sticky;top:0;background:#111827;color:#fff;padding:10px 16px;display:flex;gap:10px;align-items:center;font-size:13px}'
        . '.bar button,.bar a{background:#e30613;color:#fff;border:0;border-radius:7px;padding:8px 14px;font-weight:700;cursor:pointer;text-decoration:none;font-size:13px}.bar a{background:#374151}.wrap{padding:20px 10px}'
        . acct_css() . '@media print{body{background:#fff}.bar{display:none}.wrap{padding:0}.pp{box-shadow:none;padding:0}@page{size:' . $pcss . ';margin:12mm}}</style></head><body>'
        . '<div class="bar"><button onclick="window.print()">' . e($d['print']) . '</button><a href="' . e(aurl("acc/doc/$kind/$id")) . '">←</a><span style="opacity:.7">' . e($t['name']) . '</span></div><div class="wrap">' . $paper . '</div></body></html>';
    exit;
}

/** Çeki listesi (packing list): düzenlenebilir koli / kg alanlarıyla yazdırılabilir sayfa */
function acc_doc_packing(int $id, string $kind): void
{
    $doc = acc_q('SELECT * FROM acc_docs WHERE id = ?', [$id])->fetch();
    if (!$doc) { flash('Belge bulunamadı', 'err'); redirect(aurl("acc/docs/$kind")); }
    $kind = $doc['kind'];
    $lines = acc_q('SELECT l.*, p.code pcode, p.barcode pbar FROM acc_doc_lines l LEFT JOIN products p ON p.id = l.product_id WHERE l.doc_id = ? ORDER BY l.id', [$id])->fetchAll();
    $party = $doc['party_id'] ? (acc_q('SELECT * FROM acc_parties WHERE id = ?', [$doc['party_id']])->fetch() ?: []) : [];
    $g = (array)setting('general', []);
    $fmt = function ($q) { return rtrim(rtrim(number_format((float)$q, 3, ',', '.'), '0'), ','); };
    $rows = '';
    foreach ($lines as $i => $l) {
        $rows .= '<tr><td>' . ($i + 1) . '</td><td>' . e($l['pcode'] ?? '') . '</td><td class="l">' . e($l['name']) . '</td>'
            . '<td class="q" data-v="' . (float)$l['qty'] . '">' . $fmt($l['qty']) . '</td><td>' . e($l['unit']) . '</td>'
            . '<td><input class="n" data-k="box" inputmode="decimal" value="1"></td>'
            . '<td><input class="n" data-k="net" inputmode="decimal" placeholder="0"></td>'
            . '<td><input class="n" data-k="gross" inputmode="decimal" placeholder="0"></td></tr>';
    }
    header('Content-Type: text/html; charset=utf-8');
    ?><!doctype html><html lang="tr"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><title>Çeki Listesi <?= e($doc['doc_no']) ?></title>
<style>
body{margin:0;background:#e5e7eb;font-family:Inter,Segoe UI,Arial,sans-serif;color:#111827}
.bar{position:sticky;top:0;background:#111827;color:#fff;padding:10px 16px;display:flex;gap:10px;align-items:center;font-size:13px}
.bar button,.bar a{background:#e30613;color:#fff;border:0;border-radius:7px;padding:8px 14px;font-weight:700;cursor:pointer;text-decoration:none;font-size:13px}.bar a{background:#374151}
.pp{width:210mm;max-width:100%;box-sizing:border-box;margin:20px auto;background:#fff;padding:14mm;box-shadow:0 2px 12px rgba(0,0,0,.15);font-size:12px}
h1{margin:0;font-size:22px;letter-spacing:.04em}.top{display:flex;justify-content:space-between;gap:16px;border-bottom:3px solid #111827;padding-bottom:10px;margin-bottom:12px}
.box{border:1px solid #cbd5e1;border-radius:6px;padding:8px 10px;line-height:1.5}.two{display:grid;grid-template-columns:1fr 1fr;gap:10px;margin-bottom:12px}
table{width:100%;border-collapse:collapse}th{background:#111827;color:#fff;padding:7px 6px;font-size:11px;text-align:center}td{border-bottom:1px solid #e2e8f0;padding:6px;text-align:center}td.l{text-align:left}
tfoot td{font-weight:800;border-top:2px solid #111827;background:#f1f5f9}
input.n{width:62px;border:1px solid #cbd5e1;border-radius:4px;padding:4px;text-align:right;font:inherit}
.f{width:100%;border:0;border-bottom:1px dashed #94a3b8;font:inherit;padding:2px 0;background:transparent}
.sign{display:grid;grid-template-columns:1fr 1fr;gap:40px;margin-top:40px;text-align:center}.sign div{border-top:1px solid #111827;padding-top:6px}
@media print{body{background:#fff}.bar{display:none}.pp{box-shadow:none;margin:0;padding:0;width:auto}input.n,.f{border-color:transparent}input::placeholder{color:transparent}@page{size:A4;margin:12mm}}
</style></head><body>
<div class="bar"><button onclick="window.print()">Yazdır</button><a href="<?= e(aurl("acc/doc/$kind/$id")) ?>">←</a><span style="opacity:.7">Çeki Listesi · koli ve kg alanlarını doldurun, otomatik kaydedilir</span></div>
<div class="pp">
  <div class="top"><div><h1>ÇEKİ LİSTESİ</h1><div style="color:#64748b">PACKING LIST</div></div>
    <div style="text-align:right"><b><?= e($g['siteName'] ?? '') ?></b><br><?= e($g['address'] ?? '') ?><br><?= e(trim(($g['phone'] ?? '') . ' ' . ($g['email'] ?? ''))) ?></div></div>
  <div class="two">
    <div class="box"><b>Alıcı</b><br><?= e($party['name'] ?? 'Perakende') ?><br><?= e($party['address'] ?? '') ?> <?= e($party['city'] ?? '') ?><br><?= e($party['phone'] ?? '') ?></div>
    <div class="box"><b>Belge No:</b> <?= e($doc['doc_no']) ?><br><b>Tarih:</b> <?= acc_date($doc['doc_date']) ?><br>
      <b>Taşıyıcı / Plaka:</b> <input class="f" data-s="carrier" style="width:55%"><br><b>Varış:</b> <input class="f" data-s="dest" style="width:75%"></div>
  </div>
  <table><thead><tr><th>#</th><th>Ürün Kodu</th><th style="text-align:left">Ürün / Cinsi</th><th>Miktar</th><th>Birim</th><th>Koli</th><th>Net Kg</th><th>Brüt Kg</th></tr></thead>
    <tbody><?= $rows ?></tbody>
    <tfoot><tr><td colspan="3" style="text-align:right">TOPLAM</td><td id="tq">0</td><td></td><td id="tb">0</td><td id="tn">0</td><td id="tg">0</td></tr></tfoot></table>
  <div style="margin-top:12px">Not: <input class="f" data-s="note" style="width:92%"></div>
  <div class="sign"><div>Gönderen</div><div>Teslim Alan</div></div>
</div>
<script>
(function(){var key='packing_<?= (int)$id ?>',st={};try{st=JSON.parse(localStorage.getItem(key)||'{}')}catch(e){}
var inp=[].slice.call(document.querySelectorAll('input.n,input.f'));
function num(v){v=String(v||'').replace(',','.');var n=parseFloat(v);return isNaN(n)?0:n}
function fmt(n){return (Math.round(n*1000)/1000).toString().replace('.',',')}
inp.forEach(function(el,i){if(st[i]!==undefined)el.value=st[i];el.addEventListener('input',function(){calc();save()})});
function save(){try{var o={};inp.forEach(function(el,i){o[i]=el.value});localStorage.setItem(key,JSON.stringify(o))}catch(e){}}
function calc(){var q=0,b=0,n=0,g=0;[].forEach.call(document.querySelectorAll('tbody tr'),function(r){q+=parseFloat(r.querySelector('.q').dataset.v)||0;b+=num(r.querySelector('[data-k=box]').value);n+=num(r.querySelector('[data-k=net]').value);g+=num(r.querySelector('[data-k=gross]').value)});
tq.textContent=fmt(q);tb.textContent=fmt(b);tn.textContent=fmt(n);tg.textContent=fmt(g)}
calc();})();
</script></body></html><?php
    exit;
}
