<?php
/** Basit şema güncellemeleri: mevcut veritabanlarına yeni tabloları ekler. */
function run_migrations(): void
{
    $pdo = db();
    $v = (int)($pdo->query("SELECT JSON_UNQUOTE(value) FROM settings WHERE `key` = 'schema_version'")->fetchColumn() ?: 0);
    $migrations = [
        1 => [
            "CREATE TABLE IF NOT EXISTS contact_messages (id INT AUTO_INCREMENT PRIMARY KEY, name VARCHAR(120) NOT NULL, email VARCHAR(191) NOT NULL, phone VARCHAR(30), subject VARCHAR(191), message TEXT NOT NULL, is_read TINYINT(1) DEFAULT 0, created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP) DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_turkish_ci",
            "CREATE TABLE IF NOT EXISTS faqs (id INT AUTO_INCREMENT PRIMARY KEY, question_tr VARCHAR(255) NOT NULL, question_en VARCHAR(255), answer_tr TEXT, answer_en TEXT, sort_order INT DEFAULT 0, is_active TINYINT(1) DEFAULT 1) DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_turkish_ci",
        ],
        2 => ['seed_content'],
        3 => ['seed_images'],
        4 => ['seed_location'],
        5 => ['use_3d_images'],
        6 => ['set_whatsapp'],
        7 => ['seed_hero_banner'],
        8 => ['seed_brand_logos'],
        9 => ["CREATE TABLE IF NOT EXISTS page_visits (d DATE PRIMARY KEY, visitors INT NOT NULL DEFAULT 0, pageviews INT NOT NULL DEFAULT 0, product_views INT NOT NULL DEFAULT 0) DEFAULT CHARSET=utf8mb4"],
        10 => ["enable_card_payment"],
        11 => ["ALTER TABLE orders ADD COLUMN IF NOT EXISTS vat_total DECIMAL(12,2) NOT NULL DEFAULT 0 AFTER shipping_fee", "import_catalog_2026"],
        12 => ['seed_finder_banner'],
        13 => ['seed_brands_2026'],
        14 => ['add_locale_columns'],   // Almanca, Fransızca, Rusça, Arapça içerik sütunları
        15 => ['apply_shipped_translations'],   // Hazır (dosyayla gelen) ürün/kategori/sayfa çevirileri
        16 => ["ALTER TABLE banners ADD COLUMN IF NOT EXISTS locale VARCHAR(8) NOT NULL DEFAULT 'all'"],   // Banner dil seçimi
        17 => ['create_accounting_tables'],   // Ön Muhasebe (cari, belge, hesap, masraf)
        18 => ['acc_party_columns'],   // Cari kartı: iletişim, vergi, risk limiti, şubeler, sınıflandırma
        19 => ['acc_doc_columns'],
        20 => ["CREATE TABLE IF NOT EXISTS aras_shipments (id INT AUTO_INCREMENT PRIMARY KEY, source VARCHAR(10), ref_id INT NULL, integration_code VARCHAR(40), receiver VARCHAR(191), city VARCHAR(60), town VARCHAR(60), pieces INT NOT NULL DEFAULT 1, ok TINYINT(1) NOT NULL DEFAULT 0, message VARCHAR(255), created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP, KEY aras_src (source, ref_id)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4"],   // Aras Kargo gönderi günlüğü
        21 => ["ALTER TABLE aras_shipments ADD COLUMN IF NOT EXISTS payload TEXT NULL"],
        22 => ["ALTER TABLE aras_shipments ADD COLUMN IF NOT EXISTS tracking_no VARCHAR(40) NULL", "ALTER TABLE aras_shipments ADD COLUMN IF NOT EXISTS status_code INT NULL", "ALTER TABLE aras_shipments ADD COLUMN IF NOT EXISTS status_text VARCHAR(160) NULL",
               "ALTER TABLE aras_shipments ADD COLUMN IF NOT EXISTS checked_at DATETIME NULL", "ALTER TABLE aras_shipments ADD COLUMN IF NOT EXISTS cancelled TINYINT(1) NOT NULL DEFAULT 0",
               "ALTER TABLE aras_shipments ADD COLUMN IF NOT EXISTS method VARCHAR(30) NULL", "ALTER TABLE aras_shipments ADD COLUMN IF NOT EXISTS env VARCHAR(5) NULL",
               "ALTER TABLE aras_shipments ADD COLUMN IF NOT EXISTS req MEDIUMTEXT NULL", "ALTER TABLE aras_shipments ADD COLUMN IF NOT EXISTS res MEDIUMTEXT NULL"],   // Aras: takip no, son durum, iptal, istek/yanıt günlüğü   // Kargo fişi için gönderi ayrıntıları   // Belge: saat, sevk/sipariş/irsaliye bilgisi, fatura altı iskonto; satır: birim, iskonto, açıklama
        23 => ["ALTER TABLE acc_payments ADD COLUMN IF NOT EXISTS method VARCHAR(12) NOT NULL DEFAULT 'cash'", "ALTER TABLE acc_payments ADD COLUMN IF NOT EXISTS due_date DATE NULL", "ALTER TABLE acc_payments ADD COLUMN IF NOT EXISTS link_id INT NULL"],   // Tahsilat/Ödeme: fiş türü (cash/receipt/balance/virement), vade, virman karşı kaydı
        24 => ["ALTER TABLE acc_accounts ADD COLUMN IF NOT EXISTS color VARCHAR(4) NOT NULL DEFAULT ''", "ALTER TABLE acc_accounts ADD COLUMN IF NOT EXISTS account_no VARCHAR(60) NULL", "ALTER TABLE acc_accounts ADD COLUMN IF NOT EXISTS credit_limit DECIMAL(14,2) NULL",
               "CREATE TABLE IF NOT EXISTS acc_cash_moves (id INT AUTO_INCREMENT PRIMARY KEY, account_id INT NOT NULL, kind VARCHAR(4) NOT NULL, amount DECIMAL(14,2) NOT NULL, move_at DATE NOT NULL, note VARCHAR(255) NULL, created_by VARCHAR(120) NULL, peer_id INT NULL, link_id INT NULL, created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP, KEY acc_cm_acc (account_id)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4"],   // Hesaplarım: etiket rengi, hesap no, kart limiti; hesaba para girişi/çıkışı/transfer
        25 => ["CREATE TABLE IF NOT EXISTS visitor_logs (
                    id BIGINT AUTO_INCREMENT PRIMARY KEY,
                    ip VARCHAR(45) NOT NULL,
                    country VARCHAR(60) NULL,
                    city VARCHAR(100) NULL,
                    device_type VARCHAR(10) NOT NULL DEFAULT 'desktop',
                    browser VARCHAR(50) NULL,
                    os VARCHAR(50) NULL,
                    referer VARCHAR(500) NULL,
                    page_url VARCHAR(500) NOT NULL,
                    page_type VARCHAR(20) NOT NULL DEFAULT 'page',
                    product_id INT NULL,
                    session_id VARCHAR(64) NULL,
                    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
                    KEY vl_date (created_at),
                    KEY vl_ip (ip),
                    KEY vl_product (product_id),
                    KEY vl_page_type (page_type)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4"],   // Detaylı ziyaretçi takip tablosu
        26 => ["ALTER TABLE contact_messages ADD COLUMN IF NOT EXISTS is_spam TINYINT(1) NOT NULL DEFAULT 0"],   // İletişim formu: spam/bot işareti
        27 => [   // Yeni ürün kategorileri (varsa dokunmaz)
            "INSERT INTO categories (slug, name_tr, desc_tr, icon, sort_order, is_active) VALUES ('tabla-tupleri', 'Tabla Tüpleri', 'Balya makinesi tabla tüpleri ve akümülatörler', 'box', 50, 1) ON DUPLICATE KEY UPDATE name_tr = name_tr",
            "INSERT INTO categories (slug, name_tr, desc_tr, icon, sort_order, is_active) VALUES ('saft-catal-istavroz-aksami', 'Şaft Çatal İstavroz Aksamı', 'Şaft, çatal ve istavroz aksamı', 'link2', 51, 1) ON DUPLICATE KEY UPDATE name_tr = name_tr",
            "INSERT INTO categories (slug, name_tr, desc_tr, icon, sort_order, is_active) VALUES ('zincir-aksami', 'Zincir Aksamı', 'Zincir ve zincir aksamı', 'settings2', 52, 1) ON DUPLICATE KEY UPDATE name_tr = name_tr",
            "INSERT INTO categories (slug, name_tr, desc_tr, icon, sort_order, is_active) VALUES ('burc-aksami', 'Burç Aksamı', 'Gallignani burç aksamı', 'circle-dot', 53, 1) ON DUPLICATE KEY UPDATE name_tr = name_tr",
            "INSERT INTO categories (slug, name_tr, desc_tr, icon, sort_order, is_active) VALUES ('muhtelif-parcalar', 'Muhtelif Parçalar', 'Muhtelif yedek parçalar', 'puzzle', 54, 1) ON DUPLICATE KEY UPDATE name_tr = name_tr",
        ],
        28 => ['seed_new_products_2026'],   // Tabla Tüpleri + Muhtelif Parçalar ürünleri
        29 => ['seed_zincir_2026'],   // Zincir Aksamı ürünleri
    ];
    $done = $v;
    foreach ($migrations as $ver => $stmts) {
        if ($ver <= $v) continue;
        foreach ($stmts as $s) { if (function_exists($s)) $s(); else $pdo->exec($s); }
        $pdo->prepare("INSERT INTO settings (`key`, value) VALUES ('schema_version', ?) ON DUPLICATE KEY UPDATE value = VALUES(value)")->execute([json_encode($ver)]);
        $done = $ver;
    }
    $GLOBALS['LOC_READY'] = $done >= 14;
}

/** *_en ile biten her metin sütunu için *_de, *_fr, *_ru, *_ar sütunlarını ekler (ürün, kategori, sayfa, SSS, banner vb.) */
function add_locale_columns(): void
{
    $pdo = db();
    $rows = $pdo->query("SELECT TABLE_NAME t, COLUMN_NAME c, COLUMN_TYPE ty FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND COLUMN_NAME LIKE '%\\_en' AND DATA_TYPE IN ('varchar','text','mediumtext','longtext')")->fetchAll(PDO::FETCH_ASSOC);
    foreach ($rows as $r) {
        $base = substr($r['c'], 0, -3);
        foreach (['de', 'fr', 'ru', 'ar'] as $l) {
            $pdo->exec('ALTER TABLE `' . $r['t'] . '` ADD COLUMN IF NOT EXISTS `' . $base . '_' . $l . '` ' . $r['ty'] . ' NULL');
        }
    }
}

/** Temel sayfalar, SSS ve örnek teknik makaleler (yoksa ekler) */
function seed_content(): void
{
    $pdo = db();
    $pages = [
        ['page', 'hakkimizda', 'Hakkımızda', 'About Us', '<p><b>SIR KALIP</b>, balya makineleri ve tarım makineleri için orijinal ve muadil yedek parça tedarik eden bir firmadır. Gallignani, Claas, Welger, New Holland, Cicoria başta olmak üzere birçok markaya ait pickup, knotter, iğne, rulman, zincir, dişli ve yay gruplarında geniş stok ile hizmet veriyoruz.</p><p>Amacımız; hasat sezonunda makinenizin durmaması için doğru parçayı en hızlı şekilde kapınıza ulaştırmak. Uzman ekibimiz parça seçiminde teknik destek sağlar; parçanın fotoğrafını WhatsApp üzerinden göndermeniz yeterli.</p><h3>Neden SIR KALIP?</h3><ul><li>Geniş stok, aynı gün kargo</li><li>Orijinal ölçülere uygun üretim</li><li>Uzman teknik destek</li><li>Güvenli ödeme ve kolay iade</li></ul>'],
        ['page', 'kargo', 'Kargo ve Teslimat', 'Shipping & Delivery', '<p>Saat 14:00\'e kadar verilen siparişler aynı gün kargoya teslim edilir. Teslimat süresi bulunduğunuz bölgeye göre 1-3 iş günüdür.</p><p>Belirlenen tutarın üzerindeki siparişlerde kargo ücretsizdir. Kargo firmaları: Yurtiçi, MNG, Aras, Sürat Kargo.</p>'],
        ['page', 'iade', 'İade ve Değişim', 'Returns & Exchanges', '<p>Satın aldığınız ürünleri teslim tarihinden itibaren <b>14 gün</b> içinde, kullanılmamış ve orijinal ambalajında olmak kaydıyla iade edebilirsiniz.</p><p>İade talebinizi Hesabım › Siparişlerim sayfasından ya da bizimle iletişime geçerek oluşturabilirsiniz. Montajı yapılmış ürünlerde iade kabul edilmez.</p>'],
        ['contract', 'mesafeli-satis', 'Mesafeli Satış Sözleşmesi', 'Distance Sales Agreement', '<p>Bu sözleşme, 6502 sayılı Tüketicinin Korunması Hakkında Kanun ve Mesafeli Sözleşmeler Yönetmeliği hükümleri gereğince düzenlenmiştir. <i>Sözleşme metni yönetim panelinden (Tasarım › Sözleşmeler) düzenlenebilir.</i></p>'],
        ['contract', 'on-bilgilendirme', 'Ön Bilgilendirme Formu', 'Preliminary Information Form', '<p><i>Ön bilgilendirme formu yönetim panelinden düzenlenebilir.</i></p>'],
        ['contract', 'kvkk', 'KVKK Aydınlatma Metni', 'Privacy Notice', '<p>6698 sayılı Kişisel Verilerin Korunması Kanunu kapsamında kişisel verileriniz; siparişlerinizin işlenmesi, teslimatı ve yasal yükümlülüklerin yerine getirilmesi amacıyla işlenmektedir. <i>Metin yönetim panelinden düzenlenebilir.</i></p>'],
        ['contract', 'cerez', 'Çerez Politikası', 'Cookie Policy', '<p>Sitemizde deneyiminizi iyileştirmek için zorunlu ve analitik çerezler kullanılmaktadır.</p>'],
        ['article', 'pickup-disli-montaji', 'Pickup Dişlisi Nasıl Değiştirilir?', 'How to Replace a Pickup Gear', '<p>Pickup dişlisi değişiminden önce makineyi kapatın ve kuyruk milini ayırın.</p><ol><li>Koruma kapağını sökün.</li><li>Zinciri gevşetin ve dişliyi mil üzerinden çıkarın.</li><li>Yeni dişliyi takın, kama yuvasını kontrol edin.</li><li>Zinciri gerin ve kapağı takın.</li></ol><p>Dişli ile birlikte tırmık yaylarını da kontrol etmenizi öneririz.</p>'],
        ['article', 'knotter-bakimi', 'Knotter (Bağlama) Sistemi Bakım Rehberi', 'Knotter Maintenance Guide', '<p>Knotter sistemi balya makinesinin en hassas bölümüdür. Sezon öncesi kanca, iplik diski ve bıçak ayarlarının kontrol edilmesi bağlama hatalarını büyük ölçüde azaltır.</p><ul><li>İplik diskini temizleyin, çapakları giderin.</li><li>Kanca ağzındaki aşınmayı kontrol edin.</li><li>Bıçağın keskinliğini kontrol edin.</li></ul>'],
        ['article', 'sezon-oncesi-kontrol', 'Sezon Öncesi Balya Makinesi Kontrol Listesi', 'Pre-season Baler Checklist', '<p>Hasat sezonuna başlamadan önce yapılacak basit kontroller, tarlada yaşanabilecek arızaların önüne geçer.</p><ol><li>Rulmanları ve yağlama noktalarını kontrol edin.</li><li>Zincir gerginliklerini ayarlayın.</li><li>Pickup tırmıklarındaki kırıkları değiştirin.</li><li>İğne ve knotter ayarlarını test edin.</li></ol>'],
    ];
    $st = $pdo->prepare('INSERT IGNORE INTO pages (kind, slug, title_tr, title_en, content_tr, is_active) VALUES (?,?,?,?,?,1)');
    foreach ($pages as $p) $st->execute($p);
    if (!(int)$pdo->query('SELECT COUNT(*) FROM faqs')->fetchColumn()) {
        $f = $pdo->prepare('INSERT INTO faqs (question_tr, answer_tr, sort_order) VALUES (?,?,?)');
        foreach ([
            ['Doğru parçayı nasıl bulabilirim?', 'Ana sayfadaki "Makinenize Göre Parça Bulun" alanından marka ve modelinizi seçin. Emin olamazsanız parçanın fotoğrafını WhatsApp hattımıza gönderin, uzman ekibimiz yardımcı olsun.'],
            ['Siparişim ne zaman kargoya verilir?', 'Saat 14:00\'e kadar verilen siparişler aynı gün kargoya teslim edilir.'],
            ['Hangi ödeme yöntemlerini kullanabilirim?', 'Kredi kartı, havale/EFT ve (aktifse) kapıda ödeme seçeneklerini kullanabilirsiniz.'],
            ['Ürünü iade edebilir miyim?', 'Kullanılmamış ürünleri 14 gün içinde iade edebilirsiniz. Detaylar için İade ve Değişim sayfasına bakın.'],
            ['Faturalı satış yapıyor musunuz?', 'Tüm siparişler faturalıdır. Kurumsal fatura için ödeme adımında firma bilgilerinizi girebilirsiniz.'],
        ] as $i => [$q, $a]) $f->execute([$q, $a, $i]);
    }
    // Örnek ürünlere teknik bilgi, uyumluluk, açıklama
    $pdo->exec("UPDATE products SET desc_tr = CONCAT('<p>', name_tr, ', balya makinelerinde güvenle kullanılan, orijinal ölçülere uygun olarak üretilmiş bir yedek parçadır. Yüksek dayanımlı malzemeden üretilmiştir ve zorlu tarla koşullarında uzun ömürlü performans sunar.</p><ul><li>Orijinal ölçülere uygun üretim</li><li>Isıl işlem görmüş malzeme</li><li>Kolay montaj</li><li>Stoktan hızlı teslimat</li></ul>') WHERE desc_tr IS NULL OR desc_tr = ''");
    $gen = $pdo->query("SELECT value FROM settings WHERE `key`='general'")->fetchColumn();
    $g = $gen ? json_decode($gen, true) : [];
    $g += ['siteName' => 'SIR KALIP', 'phone' => '0 (850) 302 76 75', 'email' => 'info@sirkalip.com', 'address' => 'Türkiye', 'workingHours' => 'Hafta içi 08:30 - 18:00, Cumartesi 09:00 - 14:00'];
    $pdo->prepare("INSERT INTO settings (`key`, value) VALUES ('general', ?) ON DUPLICATE KEY UPDATE value = VALUES(value)")->execute([json_encode($g, JSON_UNESCAPED_UNICODE)]);
    $pay = $pdo->query("SELECT value FROM settings WHERE `key`='payment'")->fetchColumn();
    $pp = $pay ? json_decode($pay, true) : [];
    $pp += ['card' => true, 'transfer' => true, 'cod' => true, 'transferDiscount' => 3, 'codFee' => 50];
    $pdo->prepare("INSERT INTO settings (`key`, value) VALUES ('payment', ?) ON DUPLICATE KEY UPDATE value = VALUES(value)")->execute([json_encode($pp)]);
    $sh = $pdo->query("SELECT value FROM settings WHERE `key`='shipping'")->fetchColumn();
    $ss = $sh ? json_decode($sh, true) : [];
    $ss += ['freeShippingLimit' => 2000, 'defaultFee' => 150];
    $pdo->prepare("INSERT INTO settings (`key`, value) VALUES ('shipping', ?) ON DUPLICATE KEY UPDATE value = VALUES(value)")->execute([json_encode($ss)]);
    if (!(int)$pdo->query('SELECT COUNT(*) FROM bank_accounts')->fetchColumn())
        $pdo->exec("INSERT INTO bank_accounts (bank_name, account_holder, iban) VALUES ('Ziraat Bankası', 'SIR KALIP TARIM MAKİNELERİ', 'TR00 0000 0000 0000 0000 0000 00')");
}

/** Tasarımdaki kategori ve ürün görsellerini ekler (boş olanlara) */
function seed_images(): void
{
    $pdo = db(); $b = base_path();
    foreach (glob(ROOT . '/assets/img/cat/*.jpg') as $f) {
        $slug = basename($f, '.jpg');
        $pdo->prepare("UPDATE categories SET image = ? WHERE slug = ? AND (image IS NULL OR image = '')")->execute([$b . '/assets/img/cat/' . basename($f), $slug]);
    }
    $files = glob(ROOT . '/assets/img/products/*.jpg'); usort($files, function ($a, $b) { return (strpos($a, '-detail') !== false) <=> (strpos($b, '-detail') !== false) ?: strcmp($a, $b); });
    foreach ($files as $f) {
        $name = basename($f, '.jpg'); $code = preg_replace('/-detail$/', '', $name);
        $st = $pdo->prepare('SELECT id FROM products WHERE code = ?'); $st->execute([$code]); $pid = $st->fetchColumn();
        if (!$pid) continue;
        $url = $b . '/assets/img/products/' . basename($f);
        $ex = $pdo->prepare('SELECT COUNT(*) FROM product_images WHERE product_id = ? AND url = ?'); $ex->execute([$pid, $url]);
        if ($ex->fetchColumn()) continue;
        $has = (int)$pdo->query("SELECT COUNT(*) FROM product_images WHERE product_id = $pid")->fetchColumn();
        $pdo->prepare("INSERT INTO product_images (product_id, url, kind, is_main, sort_order) VALUES (?, ?, 'photo', ?, ?)")->execute([$pid, $url, $has ? 0 : 1, $has]);
    }
}

/** İşletme adresi ve harita (Google Haritalar kaydına göre) */
function seed_location(): void
{
    $pdo = db();
    $g = json_decode((string)$pdo->query("SELECT value FROM settings WHERE `key`='general'")->fetchColumn(), true) ?: [];
    $g['address'] = 'Ferhatpaşa Mah. 23. Sk. No: 11, 34888 Ataşehir / İstanbul';
    if (empty($g['phone']) || strpos($g['phone'], '302 76 75') !== false) $g['phone'] = '0541 661 58 89';
    $g['mapLat'] = '40.9816443'; $g['mapLng'] = '29.1715067';
    $g['mapUrl'] = 'https://www.google.com/maps?cid=17703677301145039371';
    $pdo->prepare("INSERT INTO settings (`key`, value) VALUES ('general', ?) ON DUPLICATE KEY UPDATE value = VALUES(value)")->execute([json_encode($g, JSON_UNESCAPED_UNICODE)]);
}

/** Tasarımdan kesilen düşük çözünürlüklü görselleri yeni 3D render görsellerle değiştirir */
function use_3d_images(): void
{
    $pdo = db();
    foreach ($pdo->query("SELECT id, image FROM categories WHERE image LIKE '%/assets/img/cat/%.jpg' AND image NOT LIKE '%-3d.jpg'")->fetchAll() as $r) {
        $new = preg_replace('/\.jpg$/', '-3d.jpg', $r['image']);
        if (is_file(ROOT . substr($new, strpos($new, '/assets/')))) $pdo->prepare('UPDATE categories SET image = ? WHERE id = ?')->execute([$new, $r['id']]);
    }
    foreach ($pdo->query("SELECT id, url FROM product_images WHERE url LIKE '%/assets/img/products/%.jpg' AND url NOT LIKE '%-3d.jpg'")->fetchAll() as $r) {
        $new = preg_replace('/\.jpg$/', '-3d.jpg', $r['url']);
        if (is_file(ROOT . substr($new, strpos($new, '/assets/')))) $pdo->prepare('UPDATE product_images SET url = ? WHERE id = ?')->execute([$new, $r['id']]);
    }
}

/** WhatsApp hattı */
function set_whatsapp(): void
{
    $pdo = db();
    $w = json_decode((string)$pdo->query("SELECT value FROM settings WHERE `key`='whatsapp'")->fetchColumn(), true) ?: [];
    $w['number'] = '905416615889';
    $w += ['showButton' => true, 'message' => 'Merhaba, nasıl yardımcı olabiliriz?'];
    $pdo->prepare("INSERT INTO settings (`key`, value) VALUES ('whatsapp', ?) ON DUPLICATE KEY UPDATE value = VALUES(value)")->execute([json_encode($w, JSON_UNESCAPED_UNICODE)]);
}

/** Ana sayfa banner'ı (Banner Yönetimi'nden değiştirilebilir) */
function seed_hero_banner(): void
{
    $pdo = db(); $b = base_path();
    $pdo->prepare("INSERT INTO banners (position, title_tr, title_en, image, mobile_image, link, sort_order, is_active) VALUES ('home_hero', ?, ?, ?, ?, ?, 0, 1)")
        ->execute(['Tarım Makinalarınız İçin Güvenilir Yedek Parça Çözümü', 'Reliable Spare Parts for Your Agricultural Machinery', $b . '/assets/img/banner-ana.webp', $b . '/assets/img/banner-ana-m.webp', $b . '/kategori/balya-makinesi']);
}

/** Parça bulucu banner'ı ve kutu görseli (Banner Yönetimi'nden değiştirilebilir) */
function seed_finder_banner(): void
{
    $pdo = db(); $b = base_path();
    $has = function ($pos) use ($pdo) { $st = $pdo->prepare("SELECT COUNT(*) FROM banners WHERE position = ?"); $st->execute([$pos]); return (int)$st->fetchColumn() > 0; };
    if (!$has('home_finder'))
        $pdo->prepare("INSERT INTO banners (position, title_tr, title_en, image, mobile_image, link, sort_order, is_active) VALUES ('home_finder', ?, ?, ?, ?, ?, 0, 1)")
            ->execute(['Makinenize Göre Parça Bulun', 'Find Parts for Your Machine', $b . '/assets/img/finder-banner.webp', $b . '/assets/img/finder-banner-m.webp', $b . '/markalar']);
    if (!$has('home_finder_logo'))
        $pdo->prepare("INSERT INTO banners (position, title_tr, title_en, image, sort_order, is_active) VALUES ('home_finder_logo', ?, ?, ?, 0, 1)")
            ->execute(['Parça Bulucu Kutusu Görseli', 'Part Finder Image', $b . '/assets/img/finder-baler.jpg']);
}

/** Banner'lardaki marka logoları (eksik markaları da ekler) */
function seed_brand_logos(): void
{
    $pdo = db(); $b = base_path();
    $names = ['gaspardo' => 'Gaspardo', 'massey-ferguson' => 'Massey Ferguson', 'new-holland' => 'New Holland', 'john-deere' => 'John Deere', 'claas' => 'CLAAS', 'fendt' => 'Fendt', 'case-ih' => 'Case IH', 'deutz' => 'Deutz-Fahr', 'valtra' => 'Valtra', 'jcb' => 'JCB'];
    $order = 0;
    foreach ($names as $slug => $name) {
        $logo = $b . '/assets/img/brands/' . $slug . '.png';
        $st = $pdo->prepare('SELECT id, logo FROM brands WHERE slug = ?'); $st->execute([$slug]); $row = $st->fetch();
        if ($row) { if (empty($row['logo'])) $pdo->prepare('UPDATE brands SET logo = ?, is_popular = 1 WHERE id = ?')->execute([$logo, $row['id']]); }
        else $pdo->prepare('INSERT INTO brands (slug, name, logo, sort_order, is_popular, is_active) VALUES (?,?,?,?,1,1)')->execute([$slug, $name, $logo, 20 + $order]);
        $order++;
    }
}

function enable_card_payment(): void
{
    $pdo = db();
    $v = json_decode((string)$pdo->query("SELECT value FROM settings WHERE `key` = 'payment'")->fetchColumn(), true) ?: [];
    $v['card'] = true;
    $pdo->prepare("INSERT INTO settings (`key`, value) VALUES ('payment', ?) ON DUPLICATE KEY UPDATE value = VALUES(value)")->execute([json_encode($v, JSON_UNESCAPED_UNICODE)]);
    $p = json_decode((string)$pdo->query("SELECT value FROM settings WHERE `key` = 'pos'")->fetchColumn(), true) ?: [];
    $p += ['provider' => 'iyzico', 'iyzicoSandbox' => true, 'paytrTest' => true, 'maxInstallment' => 9];
    $pdo->prepare("INSERT INTO settings (`key`, value) VALUES ('pos', ?) ON DUPLICATE KEY UPDATE value = VALUES(value)")->execute([json_encode($p, JSON_UNESCAPED_UNICODE)]);
}

/** 2026 fiyat listesi: 251 ürün, 11 ürün grubu, görseller, OEM kodları, banka hesapları */
function import_catalog_2026(): void
{
    $pdo = db(); $b = base_path();
    $file = ROOT . '/app/data/catalog-2026.json';
    if (!is_file($file)) throw new RuntimeException('catalog-2026.json bulunamadı');
    $cat = json_decode(file_get_contents($file), true);
    if (!$cat) throw new RuntimeException('catalog-2026.json okunamadı');
    @set_time_limit(300); @ini_set('memory_limit', '512M');
    // Ürün görselleri tek paket halinde gelir: uploads/products/ klasörüne açılır
    $pack = ROOT . '/app/data/catalog-2026-images.json';
    if (is_file($pack)) {
        $dir = ROOT . '/uploads/products';
        if (!is_dir($dir) && !mkdir($dir, 0775, true)) throw new RuntimeException('uploads/products oluşturulamadı');
        foreach (json_decode(file_get_contents($pack), true) ?: [] as $name => $b64) {
            $name = basename($name);
            if (!preg_match('/^[A-Z0-9]+\.webp$/', $name)) continue;
            $bin = base64_decode($b64);
            if (!is_file("$dir/$name") || filesize("$dir/$name") !== strlen($bin)) file_put_contents("$dir/$name", $bin);
        }
    }

    // 1) Örnek (demo) ürünleri ve bağlı kayıtları kaldır
    $demo = ['AS50506', 'AS50275', 'AS50015', 'AS50320', 'AS50411', 'AS50630', 'AS50890', 'AS50712', 'AS77777'];
    $in = implode(',', array_fill(0, count($demo), '?'));
    $st = $pdo->prepare("SELECT id FROM products WHERE code IN ($in)"); $st->execute($demo);
    $ids = array_map('intval', $st->fetchAll(PDO::FETCH_COLUMN));
    if ($ids) {
        $idl = implode(',', $ids);
        foreach (['product_images', 'product_refs', 'product_specs', 'product_compat', 'product_tags', 'product_tabs', 'cart_items', 'favorites', 'stock_alerts', 'reviews', 'questions', 'stock_movements'] as $t) {
            try { $pdo->exec("DELETE FROM $t WHERE product_id IN ($idl)"); } catch (Throwable $e) {}
        }
        $pdo->exec("DELETE FROM product_related WHERE product_id IN ($idl) OR related_id IN ($idl)");
        $pdo->exec("DELETE FROM products WHERE id IN ($idl)");
    }
    // 2) Ürünü kalmayan örnek kategorileri kaldır
    $demoCats = ['pickup-grubu', 'knotter-grubu', 'sanziman-aktarma', 'igne-baglama', 'balya-odasi', 'bicaklar', 'zincir-disliler', 'rulmanlar', 'yaylar', 'mil-makaralar', 'civata-aksesuar', 'diger-parcalar', 'test-kategori-igne'];
    foreach ($demoCats as $slug) {
        $st = $pdo->prepare('SELECT c.id FROM categories c WHERE c.slug = ? AND NOT EXISTS (SELECT 1 FROM products p WHERE p.category_id = c.id) AND NOT EXISTS (SELECT 1 FROM categories k WHERE k.parent_id = c.id)');
        $st->execute([$slug]);
        if ($cid = $st->fetchColumn()) $pdo->prepare('DELETE FROM categories WHERE id = ?')->execute([$cid]);
    }
    // 3) Kategoriler (PDF ürün grupları)
    $catIds = [];
    foreach ($cat['categories'] as $i => $c) {
        $img = $b . '/uploads/products/' . $c['image'];
        $st = $pdo->prepare('SELECT id FROM categories WHERE slug = ?'); $st->execute([$c['slug']]);
        if ($id = $st->fetchColumn()) {
            $pdo->prepare('UPDATE categories SET name_tr = ?, name_en = ?, desc_tr = ?, image = ?, sort_order = ?, is_active = 1 WHERE id = ?')->execute([$c['name_tr'], $c['name_en'], $c['desc_tr'], $img, $i + 1, $id]);
        } else {
            $pdo->prepare('INSERT INTO categories (parent_id, slug, name_tr, name_en, desc_tr, image, sort_order, is_active, seo_title, seo_description) VALUES (NULL,?,?,?,?,?,?,1,?,?)')
                ->execute([$c['slug'], $c['name_tr'], $c['name_en'], $c['desc_tr'], $img, $i + 1, $c['name_tr'] . ' Yedek Parçaları', $c['desc_tr']]);
            $id = (int)$pdo->lastInsertId();
        }
        $catIds[$c['slug']] = (int)$id;
    }
    // 4) Markalar
    $brandSlugs = ['Gallignani' => 'gallignani', 'Paksan' => 'paksan', 'Elibol' => 'elibol', 'BCS' => 'bcs', 'CLAAS' => 'claas', 'Welger' => 'welger', 'John Deere' => 'john-deere', 'New Holland' => 'new-holland', 'Agromaster' => 'agromaster', 'Massey Ferguson' => 'massey-ferguson'];
    $brandIds = [];
    foreach ($brandSlugs as $name => $slug) {
        $st = $pdo->prepare('SELECT id FROM brands WHERE slug = ?'); $st->execute([$slug]);
        if (!($id = $st->fetchColumn())) {
            $pdo->prepare('INSERT INTO brands (slug, name, sort_order, is_popular, is_active) VALUES (?,?,?,1,1)')->execute([$slug, $name, 15]);
            $id = $pdo->lastInsertId();
        } else $pdo->prepare('UPDATE brands SET is_active = 1 WHERE id = ?')->execute([$id]);
        $brandIds[$name] = (int)$id;
    }
    // 5) Özellik anahtarları
    $spec = function (string $tr, string $en, int $sort) use ($pdo): int {
        $st = $pdo->prepare('SELECT id FROM spec_keys WHERE name_tr = ?'); $st->execute([$tr]);
        if ($id = $st->fetchColumn()) return (int)$id;
        $pdo->prepare('INSERT INTO spec_keys (name_tr, name_en, is_filterable, sort_order) VALUES (?,?,0,?)')->execute([$tr, $en, $sort]);
        return (int)$pdo->lastInsertId();
    };
    $kBrand = $spec('Uyumlu Marka', 'Compatible Brand', -3); $kOem = $spec('Orijinal Parça No', 'OEM Part No', -1); $kMat = $spec('Malzeme', 'Material', 0);
    $catNames = array_column($cat['categories'], 'name_tr', 'slug');
    // 6) Ürünler
    $ins = $pdo->prepare('INSERT INTO products (code, slug, name_tr, short_desc_tr, desc_tr, category_id, brand_id, price, currency, vat_rate, stock, critical_stock, desi, estimated_ship_days, is_active, is_featured, is_new, sort_order, seo_title, seo_description, published_at)
        VALUES (?,?,?,?,?,?,?,?,\'TRY\',20,100,5,1,2,1,?,?,?,?,?,NOW())');
    $upd = $pdo->prepare('UPDATE products SET name_tr = ?, short_desc_tr = ?, desc_tr = ?, category_id = ?, brand_id = ?, price = ?, is_featured = ?, is_new = ?, sort_order = ?, seo_title = ?, seo_description = ?, deleted_at = NULL WHERE id = ?');
    foreach ($cat['products'] as $p) {
        $cid = $catIds[$p['category_slug']]; $bid = $p['brand'] ? ($brandIds[$p['brand']] ?? null) : null;
        $st = $pdo->prepare('SELECT id FROM products WHERE code = ?'); $st->execute([$p['code']]);
        if ($id = $st->fetchColumn()) {
            $upd->execute([$p['name'], $p['short_desc_tr'], $p['desc_tr'], $cid, $bid, $p['price'], $p['is_featured'], $p['is_new'], $p['no'], $p['seo_title'], $p['seo_description'], $id]);
        } else {
            $slug = $p['slug'];
            $chk = $pdo->prepare('SELECT COUNT(*) FROM products WHERE slug = ?'); $chk->execute([$slug]);
            if ($chk->fetchColumn()) $slug .= '-' . substr(md5($p['code']), 0, 4);
            $ins->execute([$p['code'], $slug, $p['name'], $p['short_desc_tr'], $p['desc_tr'], $cid, $bid, $p['price'], $p['is_featured'], $p['is_new'], $p['no'], $p['seo_title'], $p['seo_description']]);
            $id = $pdo->lastInsertId();
        }
        $id = (int)$id;
        if ($p['img']) {
            $url = $b . '/uploads/products/' . $p['img'];
            $pdo->prepare('DELETE FROM product_images WHERE product_id = ? AND url LIKE ?')->execute([$id, '%/uploads/products/' . $p['img']]);
            $has = $pdo->query("SELECT COUNT(*) FROM product_images WHERE product_id = $id AND is_main = 1")->fetchColumn();
            $pdo->prepare("INSERT INTO product_images (product_id, url, kind, is_main, sort_order) VALUES (?, ?, 'photo', ?, 0)")->execute([$id, $url, $has ? 0 : 1]);
        }
        $pdo->exec("DELETE FROM product_refs WHERE product_id = $id AND kind = 'original'");
        foreach ($p['orj'] as $o) $pdo->prepare("INSERT INTO product_refs (product_id, kind, code) VALUES (?, 'original', ?)")->execute([$id, $o]);
        $pdo->exec("DELETE FROM product_specs WHERE product_id = $id AND spec_key_id IN ($kBrand, $kOem, $kMat)");
        $sp = $pdo->prepare('INSERT INTO product_specs (product_id, spec_key_id, value) VALUES (?,?,?)');
        if ($p['brand']) $sp->execute([$id, $kBrand, $p['brand']]);
        if ($p['orj']) $sp->execute([$id, $kOem, implode(', ', $p['orj'])]);
        if ($p['material']) $sp->execute([$id, $kMat, $p['material']]);
        if (!$pdo->query("SELECT COUNT(*) FROM stock_movements WHERE product_id = $id")->fetchColumn())
            $pdo->prepare("INSERT INTO stock_movements (product_id, kind, quantity, note) VALUES (?, 'in', 100, '2026 fiyat listesi ile açılış stoğu')")->execute([$id]);
    }
    // 7) Firma bilgileri ve banka hesapları (fiyat listesinden)
    $holder = 'SIR SAN TARIM MAKİNALARI YEDEK PARÇA SANAYİ VE TİCARET LİMİTED ŞİRKETİ';
    $pdo->exec("DELETE FROM bank_accounts WHERE iban LIKE 'TR00%'");
    foreach ([['Ziraat Bankası', 'TR33 0001 0008 3698 2496 1050 01'], ['Kuveyt Türk', 'TR76 0020 5000 0976 9155 1000 05']] as [$bank, $iban]) {
        $st = $pdo->prepare("SELECT COUNT(*) FROM bank_accounts WHERE REPLACE(iban, ' ', '') = ?"); $st->execute([str_replace(' ', '', $iban)]);
        if (!$st->fetchColumn()) $pdo->prepare('INSERT INTO bank_accounts (bank_name, account_holder, iban, is_active) VALUES (?,?,?,1)')->execute([$bank, $holder, $iban]);
    }
    $g = json_decode((string)$pdo->query("SELECT value FROM settings WHERE `key` = 'general'")->fetchColumn(), true) ?: [];
    if (empty($g['companyTitle'])) $g['companyTitle'] = 'SIR SAN Tarım Makinaları Yedek Parça San. ve Tic. Ltd. Şti.';
    if (empty($g['email']) || $g['email'] === 'info@sirkalip.com') $g['email'] = 'sirkalip@yandex.com';
    $g['pricesExcludeVat'] = true;
    $pdo->prepare("INSERT INTO settings (`key`, value) VALUES ('general', ?) ON DUPLICATE KEY UPDATE value = VALUES(value)")->execute([json_encode($g, JSON_UNESCAPED_UNICODE)]);
}

/** Balya makinesi markaları ve modelleri (Excel listesi, Ekim 2026). Logolar kaldırılır, markalar isimle gösterilir. */
function seed_brands_2026(): void
{
    $pdo = db();
    $list = [
        ['massey-ferguson', 'Massey Ferguson', ['MF1842S', '1840']],
        ['basak', 'Başak', ['BSK120', 'BSK220', 'BSK131']],
        ['fimaks', 'Fimaks', ['1600', '2690', '3690', '5190']],
        ['shaktiman', 'Shaktiman', ['165']],
        ['ozen-is', 'Özen İş', ['OZN05', 'OZN06', 'OZN04', 'OZN8050', 'OZN555']],
        ['naras', 'Naras', ['NBS', 'N120', 'N130', 'N131']],
        ['gallignani', 'Gallignani', ['5190', '6190', '7130', '5100', '2600', '5000', '2690', '3690', '5690', '7100', '6000', '6290', '6390', '6690', '7190']],
        ['paksan', 'Paksan', ['SÜPER8002', 'SÜPER8002E', 'SÜPER8002EDUAL2', 'GLOBALE', 'SÜPER YUNUS', 'SÜPER YUNUS 3 YABALI', 'SÜPER YUNUS DUAL2', 'HAMMER']],
        ['k-ertugrul', 'Kayhan Ertuğrul', ['KE520', 'KE555', 'KE565', 'KE595', 'KE690', 'KE790']],
        ['sipma', 'Sipma', ['PK4010', 'PK4000', 'Z224', 'Z224/1', 'Z224/2']],
        ['urmaksan', 'Ürmaksan', ['AGRİTÜRK', 'S190C', 'S190LCL', 'S190XLCL', 'S190XMLCL', 'S690MİNİ', 'S690WCB', 'S690WCS', 'S690WCB3', 'S190WC2W']],
        ['elibol', 'Elibol', ['TW03S', 'TW03', 'TW02']],
        ['case-ih', 'Case IH', ['SB200', 'SB300', 'SB500']],
        ['deutz', 'Deutz-Fahr', ['HB300', 'HD400', 'HD440']],
        ['rostselmash', 'Rostselmash', ['TUKAN1600']],
        ['cicoria', 'Cicoria', ['2749S', '2749S3']],
        ['harmak', 'Harmak', ['BLY02', 'BLY02R', 'BLY03']],
        ['anil-yataganli', 'Anıl Yatağanlı', ['AT-BMR2', 'AT-BMW2', 'AT-BMR3']],
        ['bcs', 'BCS', []],
        ['cokcanlar', 'Çokcanlar', ['ECLAAS55', 'WCLAAS55', 'Y.M.İ4320', 'TURBOGOLD7190', 'SÜPERGOLD7190', 'Y.M.İ4320XL']],
        ['agromaster', 'Agromaster', ['PK4010', 'Z511']],
        ['sonmezler', 'Sönmezler', ['9020', '9120', '9130', '9130İ']],
        ['akturk', 'Aktürk', ['AMS3000HB', 'AMS3000']],
        ['atlas', 'Atlas', ['PRO MC 2000', 'PRO MC 3000', 'PRO MC 3000 HB']],
        ['karatas', 'Karataş', ['K220', 'K230', 'K131E', 'K231', '0915', '0965']],
        ['claas', 'CLAAS', ['MARKANT40', 'MARKANT50', 'MARKANT55', 'MARKANT51', 'MARKANT60', 'MARKANT65']],
        ['tumosan', 'Tümosan', ['S190', 'S190L', 'S190LCL']],
        ['new-holland', 'New Holland', ['BC5040', 'BC5050HB', 'BC5060', 'BC5050']],
        ['turk-traktor', 'Türk Traktör', ['KB290', 'KB390']],
        ['john-deere', 'John Deere', ['328', '338', '348', '224', '332', '336', '342']],
        ['welger', 'Welger', ['LELY', 'AP41', 'AP45', 'AP52', 'AP530', 'AP630', 'AP730', 'AP830']],
        ['turkay', 'Türkay', []],
        ['sozsel-yataganli', 'Sözsel Yatağanlı', ['5390 SÜPER LONG', '5390', '2 İPLİ']],
        ['utm-tarim', 'UTM Tarım', ['TO602Z', 'TO603Z', 'TS603Z']],
        ['cinarmaksan', 'Çınarmaksan', ['MUTLU H 1560']],
        ['gaspardo', 'Gaspardo', ['MASCHIO', 'PISAGORL3', 'ARSİMET', 'PİTAGORA', 'PİTAGORA L', 'ARSİMET L']],
        ['hisarlar', 'Hisarlar', ['BM2142', 'BM2150', 'BM3150', 'BM3150H', 'BM2163', 'BM3163', 'BM3163H', 'HB3656']],
        ['freeman', 'Freeman', ['200', '270', '330', '370', '380', '385']],
        ['rivierre-casalis', 'Rivierre-Casalis', ['KR40TS', 'ER40EX', 'RC42', 'ER40C']],
        ['international', 'International', ['440', '445', '435', '47']],
        ['mccormick', 'McCormick', ['46', '45', '56', '47']],
        ['unia-kostka', 'Unia Kostka', ['Z511']],
        ['yanmar', 'Yanmar', ['YTAGRI', 'SOLİS']],
        ['agrizone', 'Agrizone', ['AZ984', 'AZ884']],
        ['preet', 'Preet', []],
        ['ks-agrotech', 'KS Agrotech', []],
        ['enorossi', 'Enorossi', ['ENOPACK800', 'ENOPACK900']],
        ['sitrex', 'Sitrex', ['M60', 'M61']],
        ['fortschritt', 'Fortschritt', ['K453', 'K430', '454', '530']],
        ['ford', 'Ford', ['532']],
        ['vicon', 'Vicon', ['SP451', 'SP471', 'SP481']],
    ];
    $sel = $pdo->prepare('SELECT id FROM brands WHERE slug = ?');
    $ins = $pdo->prepare('INSERT INTO brands (slug, name, logo, sort_order, is_popular, is_active) VALUES (?, ?, NULL, ?, 1, 1)');
    $upd = $pdo->prepare('UPDATE brands SET name = ?, logo = NULL, sort_order = ?, is_popular = 1, is_active = 1 WHERE id = ?');
    $mSel = $pdo->prepare('SELECT id, name FROM machine_models WHERE brand_id = ?');
    $mIns = $pdo->prepare('INSERT INTO machine_models (brand_id, name) VALUES (?, ?)');
    $mDel = $pdo->prepare('DELETE FROM machine_models WHERE id = ? AND NOT EXISTS (SELECT 1 FROM product_compat WHERE machine_model_id = ?)');
    $slugs = [];
    foreach ($list as $i => [$slug, $name, $models]) {
        $slugs[] = $slug;
        $sel->execute([$slug]); $id = $sel->fetchColumn();
        if ($id) $upd->execute([$name, $i, $id]);
        else { $ins->execute([$slug, $name, $i]); $id = $pdo->lastInsertId(); }
        $mSel->execute([$id]); $have = [];
        foreach ($mSel->fetchAll() as $m) {
            if (in_array($m['name'], $models, true)) $have[] = $m['name'];
            else $mDel->execute([$m['id'], $m['id']]);   // eski örnek modeller (ürüne bağlı değilse) silinir
        }
        foreach ($models as $m) if (!in_array($m, $have, true)) $mIns->execute([$id, $m]);
    }
    // Listede olmayan markalar (örn. traktör logoları) silinmez; ana sayfa şeridinden çıkarılır ve sona alınır
    $in = implode(',', array_fill(0, count($slugs), '?'));
    $pdo->prepare("UPDATE brands SET logo = NULL, is_popular = 0, sort_order = 900 WHERE slug NOT IN ($in)")->execute($slugs);
}


/**
 * app/data/translations.json içindeki hazır çevirileri veritabanına yazar.
 * Yalnızca BOŞ olan çeviri sütunlarını doldurur (elle yazılmış çevirilere dokunmaz) ve
 * yalnızca Türkçe metin dosyadaki kaynakla aynıysa uygular. Dönüş: yazılan alan sayısı.
 */
function apply_shipped_translations(): int
{
    $n = 0;
    try {
        $pdo = db();
        @set_time_limit(300);
        $pdo->exec("CREATE TABLE IF NOT EXISTS translation_meta (tbl VARCHAR(40) NOT NULL, row_id INT NOT NULL, field VARCHAR(40) NOT NULL, loc CHAR(2) NOT NULL, src_hash CHAR(32) NOT NULL, out_hash CHAR(32) NOT NULL, PRIMARY KEY (tbl, row_id, field, loc)) DEFAULT CHARSET=utf8mb4");
        $cols = [];
        foreach ($pdo->query("SELECT TABLE_NAME t, COLUMN_NAME c FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE()")->fetchAll(PDO::FETCH_ASSOC) as $r) $cols[$r['t'] . '.' . $r['c']] = 1;
        $locs = ['en', 'de', 'fr', 'ru', 'ar'];
        $meta = $pdo->prepare('INSERT INTO translation_meta (tbl, row_id, field, loc, src_hash, out_hash) VALUES (?,?,?,?,?,?) ON DUPLICATE KEY UPDATE src_hash = VALUES(src_hash), out_hash = VALUES(out_hash)');
        $file = ROOT . '/app/data/translations.json';
        $data = is_file($file) ? json_decode((string)file_get_contents($file), true) : null;
        foreach (($data['rows'] ?? []) as $row) {
            $t = $row['t']; $k = $row['k'];
            if (!isset($cols["$t.$k"])) continue;
            $st = $pdo->prepare("SELECT * FROM `$t` WHERE `$k` = ? LIMIT 1"); $st->execute([$row['v']]);
            $cur = $st->fetch(PDO::FETCH_ASSOC);
            if (!$cur) continue;
            $sets = []; $vals = []; $mrows = [];
            foreach ($row['f'] as $base => $fv) {
                $srcCol = $base . '_tr';
                if (!isset($cols["$t.$srcCol"]) || md5(trim((string)$cur[$srcCol])) !== $fv['h']) continue;
                foreach ($locs as $loc) {
                    $col = $base . '_' . $loc;
                    if (!isset($cols["$t.$col"]) || !isset($fv[$loc]) || trim((string)($cur[$col] ?? '')) !== '') continue;
                    $sets[] = "`$col` = ?"; $vals[] = $fv[$loc];
                    $mrows[] = [$t, (int)$cur['id'], $base, $loc, $fv['h'], md5($fv[$loc])];
                }
            }
            if (!$sets) continue;
            $vals[] = $cur['id'];
            $pdo->prepare("UPDATE `$t` SET " . implode(', ', $sets) . ' WHERE id = ?')->execute($vals);
            foreach ($mrows as $m) $meta->execute($m);
            $n += count($sets);
        }
        // Menü etiketleri: arayüz sözlüğündeki (lang/strings_*.php) İngilizce karşılıklarından
        if (isset($cols['menu_items.label_en'])) {
            foreach ($pdo->query('SELECT id, label_en FROM menu_items')->fetchAll(PDO::FETCH_ASSOC) as $m) {
                foreach (['de', 'fr', 'ru', 'ar'] as $loc) {
                    $col = 'label_' . $loc; $tr = lang_dict($loc)[$m['label_en']] ?? null;
                    if (!$tr || !isset($cols["menu_items.$col"])) continue;
                    $n += $pdo->prepare("UPDATE menu_items SET `$col` = ? WHERE id = ? AND (`$col` IS NULL OR `$col` = '')")->execute([$tr, $m['id']]) ? 1 : 0;
                }
            }
        }
    } catch (Throwable $e) {
        error_log('apply_shipped_translations: ' . $e->getMessage());
    }
    return $n;
}


/** Ön Muhasebe tabloları (BizimHesap benzeri): hesaplar, cariler, satış/alış belgeleri, tahsilat/ödeme, masraflar */
function create_accounting_tables(): void
{
    $pdo = db();
    $t = ' DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_turkish_ci';
    $pdo->exec("CREATE TABLE IF NOT EXISTS acc_accounts (id INT AUTO_INCREMENT PRIMARY KEY, name VARCHAR(120) NOT NULL, kind VARCHAR(12) NOT NULL DEFAULT 'bank', currency CHAR(3) NOT NULL DEFAULT 'TRY', opening_balance DECIMAL(14,2) NULL DEFAULT 0, is_active TINYINT(1) NOT NULL DEFAULT 1)" . $t);
    $pdo->exec("CREATE TABLE IF NOT EXISTS acc_parties (id INT AUTO_INCREMENT PRIMARY KEY, kind VARCHAR(10) NOT NULL DEFAULT 'customer', name VARCHAR(191) NOT NULL, tax_office VARCHAR(100), tax_no VARCHAR(20), phone VARCHAR(40), email VARCHAR(150), city VARCHAR(80), address VARCHAR(255), opening_balance DECIMAL(14,2) NULL DEFAULT 0, note TEXT, is_active TINYINT(1) NOT NULL DEFAULT 1, created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP, KEY acc_parties_kind (kind))" . $t);
    $pdo->exec("CREATE TABLE IF NOT EXISTS acc_docs (id INT AUTO_INCREMENT PRIMARY KEY, kind VARCHAR(10) NOT NULL, party_id INT NULL, doc_no VARCHAR(40), doc_date DATE NOT NULL, due_date DATE NULL, currency CHAR(3) NOT NULL DEFAULT 'TRY', rate DECIMAL(12,5) NOT NULL DEFAULT 1, subtotal DECIMAL(14,2) NOT NULL DEFAULT 0, vat_total DECIMAL(14,2) NOT NULL DEFAULT 0, total DECIMAL(14,2) NOT NULL DEFAULT 0, status VARCHAR(12) NOT NULL DEFAULT 'final', note TEXT, created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP, KEY acc_docs_kind (kind, doc_date), KEY acc_docs_party (party_id))" . $t);
    $pdo->exec("CREATE TABLE IF NOT EXISTS acc_doc_lines (id INT AUTO_INCREMENT PRIMARY KEY, doc_id INT NOT NULL, product_id INT NULL, name VARCHAR(255) NOT NULL, qty DECIMAL(12,3) NOT NULL DEFAULT 1, unit_price DECIMAL(14,2) NOT NULL DEFAULT 0, vat_rate DECIMAL(5,2) NOT NULL DEFAULT 0, line_total DECIMAL(14,2) NOT NULL DEFAULT 0, KEY acc_lines_doc (doc_id))" . $t);
    $pdo->exec("CREATE TABLE IF NOT EXISTS acc_payments (id INT AUTO_INCREMENT PRIMARY KEY, pay_at DATETIME NOT NULL, kind VARCHAR(4) NOT NULL, party_id INT NULL, account_id INT NOT NULL, amount DECIMAL(14,2) NOT NULL, rate DECIMAL(12,5) NULL, doc_id INT NULL, note VARCHAR(255), created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP, KEY acc_pay_party (party_id), KEY acc_pay_acc (account_id), KEY acc_pay_doc (doc_id))" . $t);
    $pdo->exec("CREATE TABLE IF NOT EXISTS acc_expenses (id INT AUTO_INCREMENT PRIMARY KEY, exp_at DATETIME NOT NULL, due_at DATETIME NULL, category VARCHAR(100) NOT NULL, description VARCHAR(255), doc_no VARCHAR(40), amount DECIMAL(14,2) NOT NULL, account_id INT NULL, is_paid TINYINT(1) NOT NULL DEFAULT 0, note TEXT, created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP, KEY acc_exp_date (exp_at))" . $t);
    if (!(int)$pdo->query('SELECT COUNT(*) FROM acc_accounts')->fetchColumn()) {
        $pdo->exec("INSERT INTO acc_accounts (name, kind, currency) VALUES ('TL Kasa', 'cash', 'TRY'), ('Banka TL Hesabı', 'bank', 'TRY')");
    }
}

/** Cari kartı genişletmesi (BizimHesap benzeri Yeni Müşteri formu) */
function acc_party_columns(): void
{
    $pdo = db();
    $t = ' DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_turkish_ci';
    $have = $pdo->query("SELECT COLUMN_NAME FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'acc_parties'")->fetchAll(PDO::FETCH_COLUMN);
    $add = [
        'image' => 'VARCHAR(255) NULL', 'phone2' => 'VARCHAR(40) NULL', 'other_contact' => 'TEXT NULL', 'contact_person' => 'VARCHAR(120) NULL',
        'delivery_address' => 'TEXT NULL', 'tax_free' => 'TINYINT(1) NOT NULL DEFAULT 0', 'bank_info' => 'TEXT NULL',
        'currency' => "VARCHAR(4) NOT NULL DEFAULT 'TRY'", 'risk_limit' => 'DECIMAL(14,2) NULL', 'due_days' => 'INT NULL', 'discount' => 'DECIMAL(5,2) NULL',
        'class1' => 'INT NULL', 'class2' => 'INT NULL', 'code' => 'VARCHAR(60) NULL', 'agents' => 'TEXT NULL',
    ];
    foreach ($add as $col => $def) if (!in_array($col, $have, true)) $pdo->exec("ALTER TABLE acc_parties ADD COLUMN `$col` $def");
    $pdo->exec('ALTER TABLE acc_parties MODIFY address TEXT NULL');
    $pdo->exec('CREATE TABLE IF NOT EXISTS acc_party_branches (id INT AUTO_INCREMENT PRIMARY KEY, party_id INT NOT NULL, name VARCHAR(150) NOT NULL, address TEXT NULL, code VARCHAR(60) NULL, KEY apb_party (party_id))' . $t);
    $pdo->exec('CREATE TABLE IF NOT EXISTS acc_party_classes (id INT AUTO_INCREMENT PRIMARY KEY, slot TINYINT NOT NULL, name VARCHAR(120) NOT NULL, UNIQUE KEY apc_slot_name (slot, name))' . $t);
}

/** Satış / alış belgesi genişletmesi (BizimHesap benzeri belge ekranı) */
function acc_doc_columns(): void
{
    $pdo = db();
    $cols = function ($t) use ($pdo) { return $pdo->query("SELECT COLUMN_NAME FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = '$t'")->fetchAll(PDO::FETCH_COLUMN); };
    $have = $cols('acc_docs');
    foreach (['doc_time' => 'TIME NULL', 'ship_date' => 'DATE NULL', 'order_date' => 'DATE NULL', 'order_no' => 'VARCHAR(40) NULL', 'waybill_no' => 'VARCHAR(40) NULL', 'discount_total' => 'DECIMAL(14,2) NOT NULL DEFAULT 0'] as $c => $d)
        if (!in_array($c, $have, true)) $pdo->exec("ALTER TABLE acc_docs ADD COLUMN `$c` $d");
    $have = $cols('acc_doc_lines');
    foreach (['unit' => "VARCHAR(10) NOT NULL DEFAULT 'Ad'", 'discount' => 'DECIMAL(14,2) NOT NULL DEFAULT 0', 'description' => 'TEXT NULL'] as $c => $d)
        if (!in_array($c, $have, true)) $pdo->exec("ALTER TABLE acc_doc_lines ADD COLUMN `$c` $d");
}

/** Yeni kategorilere ürün girişi (Tabla Tüpleri + Muhtelif Parçalar). Kodu olan atlanır; € fiyatlar TL'ye çevrilmiş haldedir. */
function seed_new_products_2026(): void
{
    $pdo = db();
    $cat = $pdo->query("SELECT slug, id FROM categories")->fetchAll(PDO::FETCH_KEY_PAIR);
    $brand = $pdo->query("SELECT slug, id FROM brands")->fetchAll(PDO::FETCH_KEY_PAIR);
    $slugify = function (string $s): string {
        $s = mb_strtolower(str_replace(['İ', 'I'], ['i', 'ı'], $s), 'UTF-8');
        $s = strtr($s, ['ç' => 'c', 'ğ' => 'g', 'ı' => 'i', 'ö' => 'o', 'ş' => 's', 'ü' => 'u', 'â' => 'a', 'î' => 'i', 'û' => 'u']);
        return trim(preg_replace('/[^a-z0-9]+/', '-', $s), '-');
    };
    // [code, name_tr, category_slug, brand_slug, price_TL, short_desc_tr]
    $items = [
        ['AS50026', 'Tabla Tüpü 55 Bar - 0,50 Lt (New Holland)', 'tabla-tupleri', 'new-holland', 2886.00, 'Orijinal No: 80398263'],
        ['AS50027', 'Tabla Tüpü 75 Bar - 0,50 Lt (New Holland)', 'tabla-tupleri', 'new-holland', 2886.00, 'Orijinal No: 80398269'],
        ['AS50903', 'Tabla Tüpü 80 Bar - 0,50 Lt (New Holland)', 'tabla-tupleri', 'new-holland', 2886.00, 'Orijinal No: 84018412 / 84971563'],
        ['AS50904', 'Tabla Tüpü 70 Bar - 0,50 Lt (New Holland)', 'tabla-tupleri', 'new-holland', 2886.00, 'Orijinal No: 84018325'],
        ['AS50905', 'Tabla Tüpü 85 Bar - 0,50 Lt (New Holland)', 'tabla-tupleri', 'new-holland', 2886.00, 'Orijinal No: 84017606'],
        ['AS50906', 'Tabla Tüpü 120 Bar - 0,50 Lt (New Holland)', 'tabla-tupleri', 'new-holland', 2886.00, 'Orijinal No: 84018365'],
        ['AS50907', 'Tabla Tüpü 250 Bar - 0,15 Lt (New Holland)', 'tabla-tupleri', 'new-holland', 1924.00, 'Orijinal No: 84453641'],
        ['AS10135', 'Tabla Tüpü 55 Bar - 0,75 Lt (John Deere)', 'tabla-tupleri', 'john-deere', 3126.50, 'Orijinal No: AZ22843'],
        ['AS10135A', 'Tabla Tüpü 75 Bar - 0,75 Lt (John Deere)', 'tabla-tupleri', 'john-deere', 3126.50, 'Orijinal No: AZ22843'],
        ['AMZ250047', 'Tabla Tüpü 50 Bar - 0,75 Lt (Laverda)', 'tabla-tupleri', '', 3126.50, 'Orijinal No: LA322718750'],
        ['AMZ250369', 'Tabla Tüpü - 0,75 Lt (Laverda)', 'tabla-tupleri', '', 3126.50, 'Orijinal No: LA323634950'],
        ['AS60628', 'Tabla Tüpü 100 Bar - 0,75 Lt (Claas)', 'tabla-tupleri', 'claas', 3126.50, 'Orijinal No: 055619.0'],
        ['AS30883Y', 'Bağlama Üstü Kapak Fanı Yuvarlak - Orijinal', 'muhtelif-parcalar', '', 3750.00, null],
        ['AS30883', 'Bağlama Üstü Kapak Fanı', 'muhtelif-parcalar', '', 1875.00, null],
        ['AMZ552169', 'Pervane - K.Ertuğrul', 'muhtelif-parcalar', '', 1875.00, null],
        ['AS30038', 'Sayaç Balya Makinesi İthal', 'muhtelif-parcalar', '', 247.20, null],
        ['AS30040', 'Sayaç (Yeşil)', 'muhtelif-parcalar', '', 824.00, null],
    ];
    $has = $pdo->prepare('SELECT 1 FROM products WHERE code = ?');
    $ins = $pdo->prepare('INSERT INTO products (code, name_tr, slug, category_id, brand_id, price, stock, vat_rate, short_desc_tr, is_active, published_at) VALUES (?,?,?,?,?,?,0,20,?,1,NOW())');
    foreach ($items as [$code, $name, $cslug, $bslug, $price, $sd]) {
        try {
            if (empty($cat[$cslug])) continue;
            $has->execute([$code]);
            if ($has->fetchColumn()) continue;   // kod zaten varsa atla
            $ins->execute([$code, $name, $slugify($name . '-' . $code), $cat[$cslug], ($bslug && isset($brand[$bslug])) ? $brand[$bslug] : null, $price, $sd]);
        } catch (Throwable $e) {
            error_log('seed_new_products_2026 ' . $code . ': ' . $e->getMessage());
        }
    }
}

/** Ürün satırlarını ekleyen ortak yardımcı. Satır: [code, name_tr, category_slug, brand_slug, price_TL, short_desc_tr]. Kodu olan atlanır. */
function sk_seed_products(array $items): void
{
    $pdo = db();
    $cat = $pdo->query("SELECT slug, id FROM categories")->fetchAll(PDO::FETCH_KEY_PAIR);
    $brand = $pdo->query("SELECT slug, id FROM brands")->fetchAll(PDO::FETCH_KEY_PAIR);
    $slugify = function (string $s): string {
        $s = mb_strtolower(str_replace(['İ', 'I'], ['i', 'ı'], $s), 'UTF-8');
        $s = strtr($s, ['ç' => 'c', 'ğ' => 'g', 'ı' => 'i', 'ö' => 'o', 'ş' => 's', 'ü' => 'u', 'â' => 'a', 'î' => 'i', 'û' => 'u']);
        return trim(preg_replace('/[^a-z0-9]+/', '-', $s), '-');
    };
    $has = $pdo->prepare('SELECT 1 FROM products WHERE code = ?');
    $ins = $pdo->prepare('INSERT INTO products (code, name_tr, slug, category_id, brand_id, price, stock, vat_rate, short_desc_tr, is_active, published_at) VALUES (?,?,?,?,?,?,0,20,?,1,NOW())');
    foreach ($items as [$code, $name, $cslug, $bslug, $price, $sd]) {
        try {
            if (empty($cat[$cslug])) continue;
            $has->execute([$code]);
            if ($has->fetchColumn()) continue;
            $ins->execute([$code, $name, $slugify($name . '-' . $code), $cat[$cslug], ($bslug && isset($brand[$bslug])) ? $brand[$bslug] : null, $price, $sd]);
        } catch (Throwable $e) {
            error_log('sk_seed_products ' . $code . ': ' . $e->getMessage());
        }
    }
}

/** Zincir Aksamı ürünleri ($ ve € fiyatlar TL'ye çevrilmiştir: USD=41,20 / EUR=48,10). */
function seed_zincir_2026(): void
{
    sk_seed_products([
        ['AS90407', 'ASA 40 Zincir (Eterno)', 'zincir-aksami', '', 385.22, null],
        ['AS90408', 'ASA 50 Zincir (Eterno)', 'zincir-aksami', '', 453.20, null],
        ['AS90409', 'ASA 60 Zincir (Eterno)', 'zincir-aksami', '', 679.80, null],
        ['AS90426', 'ASA 60H Takviyeli Zincir (Eterno)', 'zincir-aksami', '', 1030.00, null],
        ['AS90411', 'ASA 2040 Zincir (Eterno)', 'zincir-aksami', '', 362.56, null],
        ['AS90412', 'ASA 2050 Zincir (Eterno)', 'zincir-aksami', '', 473.80, null],
        ['AS90408A', 'ASA 50 Zincir (SKF)', 'zincir-aksami', '', 680.62, null],
        ['AS90409A', 'ASA 60 Zincir (SKF)', 'zincir-aksami', '', 974.03, null],
        ['AS90426A', 'ASA 60H Zincir (SKF)', 'zincir-aksami', '', 1361.23, null],
        ['AS90412A', 'ASA 2050 Zincir (SKF)', 'zincir-aksami', '', 702.26, null],
        ['AS90413', 'ASA 40 Zincir Kilidi (Eterno)', 'zincir-aksami', '', 49.44, null],
        ['AS90414', 'ASA 50 Zincir Kilidi (Eterno)', 'zincir-aksami', '', 49.44, null],
        ['AS90415', 'ASA 60 Zincir Kilidi (Eterno)', 'zincir-aksami', '', 49.44, null],
        ['AS90415A', 'ASA 60H Zincir Kilidi (Eterno)', 'zincir-aksami', '', 61.80, null],
        ['AS90417', 'ASA 2040 Zincir Kilidi (Eterno)', 'zincir-aksami', '', 49.44, null],
        ['AS90418', 'ASA 2050 Zincir Kilidi (Eterno)', 'zincir-aksami', '', 57.68, null],
        ['AS90419', 'ASA 40 Yarım Ek (Eterno)', 'zincir-aksami', '', 49.44, null],
        ['AS90420', 'ASA 50 Yarım Ek (Eterno)', 'zincir-aksami', '', 61.80, null],
        ['AS90421', 'ASA 60 Yarım Ek (Eterno)', 'zincir-aksami', '', 103.00, null],
        ['AS90423', 'ASA 2040 Yarım Ek (Eterno)', 'zincir-aksami', '', 103.00, null],
        ['AS90424', 'ASA 2050 Yarım Ek (Eterno)', 'zincir-aksami', '', 113.30, null],
    ]);
}
