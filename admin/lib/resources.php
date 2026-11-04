<?php
/**
 * Genel CRUD kaynakları. Her kaynak: table, title, fields, list (listede görünen alanlar), opsiyonel where/defaults/readonly.
 * Alan tipleri: text, textarea, html, number, money, bool, select, date, datetime, color, password, email
 */
function opts_query(string $sql): array
{
    $o = [];
    foreach (db()->query($sql)->fetchAll(PDO::FETCH_NUM) as $r) $o[$r[0]] = $r[1];
    return $o;
}

function icon_options(): array
{
    return ['pickup' => 'Pickup (dişli)', 'knotter' => 'Knotter', 'gearbox' => 'Şanzıman', 'needle' => 'İğne', 'chamber' => 'Balya Odası', 'knife' => 'Bıçak', 'gear' => 'Dişli', 'bearing' => 'Rulman', 'spring' => 'Yay', 'shaft' => 'Mil', 'bolt' => 'Cıvata', 'other' => 'Diğer'];
}

function resources(): array
{
    $all = resources_base() + acc_resources();
    if (!loc_ready()) return $all;
    foreach ($all as $k => $r) {
        if (empty($r['fields'])) continue;
        $new = [];
        foreach ($r['fields'] as $f => $spec) {
            $new[$f] = $spec;
            if (substr($f, -3) !== '_en') continue;
            $base = substr($f, 0, -3);
            foreach (['de' => 'DE', 'fr' => 'FR', 'ru' => 'RU', 'ar' => 'AR'] as $l => $L) {
                $s2 = $spec; $s2[0] = preg_match('/\(EN\)/', $spec[0]) ? str_replace('(EN)', '(' . $L . ')', $spec[0]) : $spec[0] . ' (' . $L . ')';
                $s2 = array_values(array_filter($s2, function ($x, $i) { return $i < 2 || !is_string($x) || $x !== 'required'; }, ARRAY_FILTER_USE_BOTH));
                $new[$base . '_' . $l] = $s2;
            }
        }
        $all[$k]['fields'] = $new;
    }
    return $all;
}

function resources_base(): array
{
    $seo = ['seo_title' => ['SEO Başlık', 'text'], 'seo_description' => ['SEO Açıklama', 'textarea']];
    $cats = function () { return opts_query('SELECT id, name_tr FROM categories ORDER BY sort_order'); };
    $brands = function () { return opts_query('SELECT id, name FROM brands ORDER BY sort_order'); };
    $prods = function () { return opts_query('SELECT id, CONCAT(code, " - ", name_tr) FROM products WHERE deleted_at IS NULL ORDER BY code'); };
    $bool = ['1' => 'Evet', '0' => 'Hayır'];

    $userFields = [
        'first_name' => ['Ad', 'text', 'required'], 'last_name' => ['Soyad', 'text', 'required'],
        'email' => ['E-posta', 'email', 'required'], 'phone' => ['Telefon', 'text'],
        'password' => ['Şifre (değiştirmek için doldurun)', 'password'],
    ];

    return [
        'categories' => ['table' => 'categories', 'title' => 'Kategori Yönetimi', 'order' => 'sort_order',
            'fields' => ['name_tr' => ['Kategori Adı (TR)', 'text', 'required'], 'name_en' => ['Kategori Adı (EN)', 'text'], 'slug' => ['URL (boşsa otomatik)', 'slug', 'name_tr'],
                'parent_id' => ['Üst Kategori', 'select', $cats], 'desc_tr' => ['Kısa Açıklama (TR)', 'text'], 'desc_en' => ['Kısa Açıklama (EN)', 'text'],
                'icon' => ['Simge (görsel yoksa)', 'select', 'icon_options'], 'image' => ['Kategori Görseli', 'image', 'name_tr', 'Önerilen: beyaz veya şeffaf arka plan, kare (ör. 800×800 px)'], 'sort_order' => ['Sıra', 'number'], 'is_active' => ['Aktif', 'bool']] + $seo,
            'list' => ['image', 'name_tr', 'slug', 'parent_id', 'sort_order', 'is_active'],
            'list_view' => 'categories_list', 'form_view' => 'categories_form'],
        'brands' => ['table' => 'brands', 'title' => 'Marka Yönetimi', 'order' => 'sort_order',
            'fields' => ['name' => ['Marka Adı', 'text', 'required'], 'slug' => ['URL (boşsa otomatik)', 'slug', 'name'], 'logo' => ['Marka Logosu', 'image', 'name', 'Önerilen: şeffaf arka planlı PNG veya SVG, yatay logo (ör. 400×160 px)'],
                'sort_order' => ['Sıra', 'number'], 'is_popular' => ['Popüler Markalarda Göster', 'bool'], 'is_active' => ['Aktif', 'bool']] + $seo,
            'list' => ['logo', 'name', 'slug', 'sort_order', 'is_popular', 'is_active']],
        'machine_models' => ['table' => 'machine_models', 'title' => 'Makine Modelleri', 'order' => 'brand_id, name',
            'fields' => ['brand_id' => ['Marka', 'select', $brands, 'required'], 'name' => ['Model Adı', 'text', 'required']],
            'list' => ['brand_id', 'name'], 'filters' => ['brand_id']],
        'suppliers' => ['table' => 'suppliers', 'title' => 'Tedarikçi Firmalar',
            'fields' => ['name' => ['Firma Adı', 'text', 'required'], 'phone' => ['Telefon', 'text'], 'email' => ['E-posta', 'email']],
            'list' => ['name', 'phone', 'email']],
        'tags' => ['table' => 'tags', 'title' => 'Etiket Yönetimi',
            'fields' => ['name' => ['Etiket', 'text', 'required'], 'color' => ['Renk', 'color']], 'list' => ['name', 'color']],
        'spec_keys' => ['table' => 'spec_keys', 'title' => 'Teknik Detaylar', 'order' => 'sort_order',
            'fields' => ['name_tr' => ['Özellik (TR)', 'text', 'required'], 'name_en' => ['Özellik (EN)', 'text'], 'unit' => ['Birim', 'text'],
                'is_filterable' => ['Filtrede Göster', 'bool'], 'sort_order' => ['Sıra', 'number']],
            'list' => ['name_tr', 'name_en', 'unit', 'is_filterable', 'sort_order']],
        'product_tabs' => ['table' => 'product_tabs', 'title' => 'Ürün Detay Sekmeleri', 'order' => 'sort_order',
            'fields' => ['product_id' => ['Ürün (boşsa tüm ürünlerde)', 'select', $prods], 'title_tr' => ['Sekme Başlığı (TR)', 'text', 'required'], 'title_en' => ['Sekme Başlığı (EN)', 'text'],
                'content_tr' => ['İçerik (TR)', 'html'], 'content_en' => ['İçerik (EN)', 'html'], 'sort_order' => ['Sıra', 'number']],
            'list' => ['title_tr', 'product_id', 'sort_order']],
        'reviews' => ['table' => 'reviews', 'title' => 'Ürün Yorumları', 'order' => 'id DESC',
            'fields' => ['product_id' => ['Ürün', 'select', $prods, 'required'], 'name' => ['Ad Soyad', 'text'], 'rating' => ['Puan (1-5)', 'number'],
                'comment' => ['Yorum', 'textarea'], 'is_approved' => ['Onaylı', 'bool']],
            'list' => ['product_id', 'name', 'rating', 'is_approved', 'created_at'], 'filters' => ['is_approved']],
        'questions' => ['table' => 'questions', 'title' => 'Ürün Soru & Cevap', 'order' => 'id DESC',
            'fields' => ['product_id' => ['Ürün', 'select', $prods, 'required'], 'name' => ['Ad Soyad', 'text'], 'question' => ['Soru', 'textarea', 'required'],
                'answer' => ['Cevap', 'textarea'], 'is_published' => ['Yayında', 'bool']],
            'list' => ['product_id', 'name', 'question', 'is_published', 'created_at']],
        'stock_movements' => ['table' => 'stock_movements', 'title' => 'Stok Hareketleri', 'order' => 'id DESC',
            'fields' => ['product_id' => ['Ürün', 'select', $prods, 'required'], 'kind' => ['Hareket', 'select', ['in' => 'Giriş', 'out' => 'Çıkış', 'adjust' => 'Sayım Düzeltme', 'order' => 'Sipariş', 'return' => 'İade']],
                'quantity' => ['Adet', 'number', 'required'], 'note' => ['Açıklama', 'text']],
            'list' => ['product_id', 'kind', 'quantity', 'note', 'created_at'], 'after_save' => 'apply_stock_movement', 'no_edit' => true],
        'stock_alerts' => ['table' => 'stock_alerts', 'title' => 'Stok Alarm Hatırlatma', 'where' => "kind = 'stock'", 'defaults' => ['kind' => 'stock'], 'order' => 'id DESC',
            'fields' => ['product_id' => ['Ürün', 'select', $prods, 'required'], 'email' => ['E-posta', 'email', 'required'], 'notified_at' => ['Bildirildi', 'datetime']],
            'list' => ['product_id', 'email', 'notified_at', 'created_at']],
        'price_alerts' => ['table' => 'stock_alerts', 'title' => 'Fiyat Alarm Hatırlatma', 'where' => "kind = 'price'", 'defaults' => ['kind' => 'price'], 'order' => 'id DESC',
            'fields' => ['product_id' => ['Ürün', 'select', $prods, 'required'], 'email' => ['E-posta', 'email', 'required'], 'notified_at' => ['Bildirildi', 'datetime']],
            'list' => ['product_id', 'email', 'notified_at', 'created_at']],

        'payments' => ['table' => 'payments', 'title' => 'Ödeme Kayıtları', 'order' => 'id DESC', 'readonly' => true,
            'fields' => ['order_id' => ['Sipariş ID', 'number'], 'provider' => ['Ödeme Sağlayıcı', 'text'], 'amount' => ['Tutar', 'money'], 'status' => ['Durum', 'text'], 'transaction_id' => ['İşlem No', 'text']],
            'list' => ['order_id', 'provider', 'amount', 'status', 'transaction_id', 'created_at']],
        'payments_failed' => ['table' => 'payments', 'title' => 'Hatalı Ödeme Kayıtları', 'where' => "status = 'failed'", 'order' => 'id DESC', 'readonly' => true,
            'fields' => ['order_id' => ['Sipariş ID', 'number'], 'provider' => ['Sağlayıcı', 'text'], 'amount' => ['Tutar', 'money'], 'transaction_id' => ['İşlem No', 'text']],
            'list' => ['order_id', 'provider', 'amount', 'transaction_id', 'created_at']],
        'transfer_notices' => ['table' => 'transfer_notices', 'title' => 'Havale / EFT Bildirimleri', 'order' => 'id DESC',
            'fields' => ['order_id' => ['Sipariş ID', 'number', 'required'], 'bank_account_id' => ['Banka', 'select', function () { return opts_query('SELECT id, bank_name FROM bank_accounts'); }],
                'sender_name' => ['Gönderen', 'text'], 'amount' => ['Tutar', 'money', 'required'], 'note' => ['Not', 'textarea'], 'is_approved' => ['Onaylandı', 'bool']],
            'list' => ['order_id', 'sender_name', 'amount', 'is_approved', 'created_at']],
        'return_requests' => ['table' => 'return_requests', 'title' => 'İade Talepleri', 'order' => 'id DESC',
            'fields' => ['order_id' => ['Sipariş ID', 'number', 'required'], 'quantity' => ['Adet', 'number'], 'reason' => ['İade Nedeni', 'textarea', 'required'],
                'status' => ['Durum', 'select', ['pending' => 'Bekliyor', 'approved' => 'Onaylandı', 'rejected' => 'Reddedildi', 'refunded' => 'İade Edildi']], 'refund_amount' => ['İade Tutarı', 'money']],
            'list' => ['order_id', 'reason', 'status', 'refund_amount', 'created_at'], 'filters' => ['status']],

        'pages' => ['table' => 'pages', 'title' => 'Sayfa Yönetimi', 'where' => "kind = 'page'", 'defaults' => ['kind' => 'page'],
            'fields' => ['title_tr' => ['Başlık (TR)', 'text', 'required'], 'title_en' => ['Başlık (EN)', 'text'], 'slug' => ['URL', 'slug', 'title_tr'],
                'content_tr' => ['İçerik (TR)', 'html'], 'content_en' => ['İçerik (EN)', 'html'], 'image' => ['Görsel URL', 'text'], 'is_active' => ['Aktif', 'bool']] + $seo,
            'list' => ['title_tr', 'slug', 'is_active', 'updated_at']],
        'articles' => ['table' => 'pages', 'title' => 'Haber / Teknik Bilgiler', 'where' => "kind = 'article'", 'defaults' => ['kind' => 'article'],
            'fields' => ['title_tr' => ['Başlık (TR)', 'text', 'required'], 'title_en' => ['Başlık (EN)', 'text'], 'slug' => ['URL', 'slug', 'title_tr'],
                'content_tr' => ['İçerik (TR)', 'html'], 'content_en' => ['İçerik (EN)', 'html'], 'image' => ['Görsel URL', 'text'], 'is_active' => ['Aktif', 'bool']] + $seo,
            'list' => ['title_tr', 'slug', 'is_active', 'updated_at']],
        'contracts' => ['table' => 'pages', 'title' => 'Sözleşmeler', 'where' => "kind = 'contract'", 'defaults' => ['kind' => 'contract'],
            'fields' => ['title_tr' => ['Başlık (TR)', 'text', 'required'], 'title_en' => ['Başlık (EN)', 'text'], 'slug' => ['URL', 'slug', 'title_tr'],
                'content_tr' => ['Metin (TR)', 'html'], 'content_en' => ['Metin (EN)', 'html'], 'is_active' => ['Aktif', 'bool']],
            'list' => ['title_tr', 'slug', 'is_active', 'updated_at']],
        'contact_messages' => ['table' => 'contact_messages', 'title' => 'Müşteri Hizmetleri – Gelen Mesajlar', 'order' => 'id DESC', 'filters' => ['is_read'],
            'fields' => ['name' => ['Ad Soyad', 'text'], 'email' => ['E-posta', 'email'], 'phone' => ['Telefon', 'text'], 'subject' => ['Konu', 'text'], 'message' => ['Mesaj', 'textarea'], 'is_read' => ['Okundu', 'bool']],
            'list' => ['name', 'email', 'phone', 'subject', 'message', 'is_read', 'created_at']],
        'faqs' => ['table' => 'faqs', 'title' => 'Sıkça Sorulan Sorular', 'order' => 'sort_order',
            'fields' => ['question_tr' => ['Soru (TR)', 'text', 'required'], 'question_en' => ['Soru (EN)', 'text'], 'answer_tr' => ['Cevap (TR)', 'textarea'], 'answer_en' => ['Cevap (EN)', 'textarea'], 'sort_order' => ['Sıra', 'number'], 'is_active' => ['Aktif', 'bool']],
            'list' => ['question_tr', 'sort_order', 'is_active']],
        'banners' => ['table' => 'banners', 'title' => 'Ana Sayfa Banner Yönetimi', 'order' => 'position, sort_order',
            'fields' => ['locale' => ['Dil (bu banner hangi dilde görünsün?)', 'select', ['all' => 'Tüm diller (varsayılan)', 'tr' => 'Yalnızca Türkçe', 'en' => 'Yalnızca İngilizce', 'de' => 'Yalnızca Almanca', 'fr' => 'Yalnızca Fransızca', 'ru' => 'Yalnızca Rusça', 'ar' => 'Yalnızca Arapça']],
                'position' => ['Konum', 'select', ['home_hero' => 'Ana Sayfa Slider', 'home_promo' => 'Ana Sayfa Kampanya Kutusu', 'home_finder' => 'Parça Bulucu Banner (masaüstü 2170×725, mobil ayrı)', 'home_finder_logo' => 'Parça Bulucu Kutusu Görseli (456×320)', 'category_top' => 'Kategori Üstü']],
                'title_tr' => ['Başlık (TR)', 'text'], 'title_en' => ['Başlık (EN)', 'text'], 'text_tr' => ['Metin (TR)', 'textarea'], 'text_en' => ['Metin (EN)', 'textarea'],
                'image' => ['Görsel (masaüstü)', 'image', 'title_tr'], 'mobile_image' => ['Görsel (mobil)', 'image', 'title_tr'], 'link' => ['Bağlantı', 'text'],
                'sort_order' => ['Sıra', 'number'], 'starts_at' => ['Başlangıç', 'datetime'], 'ends_at' => ['Bitiş', 'datetime'], 'is_active' => ['Aktif', 'bool']],
            'list' => ['position', 'locale', 'title_tr', 'sort_order', 'is_active']],
        'menu_items' => ['table' => 'menu_items', 'title' => 'Menü Yönetimi', 'order' => 'location, sort_order',
            'fields' => ['location' => ['Konum', 'select', ['header' => 'Üst Menü', 'footer_1' => 'Footer Kurumsal', 'footer_2' => 'Footer Yardım', 'topbar' => 'Üst Bar']],
                'label_tr' => ['Başlık (TR)', 'text', 'required'], 'label_en' => ['Başlık (EN)', 'text'], 'href' => ['Bağlantı', 'text', 'required'], 'sort_order' => ['Sıra', 'number']],
            'list' => ['location', 'label_tr', 'href', 'sort_order'], 'filters' => ['location']],
        'menu_header' => ['table' => 'menu_items', 'title' => 'Header Menüsü', 'where' => "location = 'header'", 'defaults' => ['location' => 'header'], 'order' => 'sort_order',
            'fields' => ['label_tr' => ['Başlık (TR)', 'text', 'required'], 'label_en' => ['Başlık (EN)', 'text'], 'href' => ['Bağlantı', 'text', 'required'], 'sort_order' => ['Sıra', 'number']],
            'list' => ['label_tr', 'href', 'sort_order']],
        'menu_footer' => ['table' => 'menu_items', 'title' => 'Footer Menüsü', 'where' => "location IN ('footer_1','footer_2')", 'order' => 'location, sort_order',
            'fields' => ['location' => ['Sütun', 'select', ['footer_1' => 'Kurumsal', 'footer_2' => 'Yardım']], 'label_tr' => ['Başlık (TR)', 'text', 'required'], 'label_en' => ['Başlık (EN)', 'text'], 'href' => ['Bağlantı', 'text', 'required'], 'sort_order' => ['Sıra', 'number']],
            'list' => ['location', 'label_tr', 'href', 'sort_order']],
        'email_templates' => ['table' => 'email_templates', 'title' => 'Sistem Mail Tasarımları',
            'fields' => ['key' => ['Şablon Anahtarı (ör. order_created)', 'text', 'required'], 'subject_tr' => ['Konu (TR)', 'text'], 'body_tr' => ['İçerik (TR)', 'html'], 'subject_en' => ['Konu (EN)', 'text'], 'body_en' => ['İçerik (EN)', 'html']],
            'list' => ['key', 'subject_tr']],
        'redirects' => ['table' => 'redirects', 'title' => '301 Yönlendirmeleri',
            'fields' => ['from_path' => ['Eski Adres (ör. /eski-urun)', 'text', 'required'], 'to_path' => ['Yeni Adres', 'text', 'required']],
            'list' => ['from_path', 'to_path', 'hits']],
        'not_found_logs' => ['table' => 'not_found_logs', 'title' => '404 Link Yönetimi', 'order' => 'hits DESC', 'readonly' => true,
            'fields' => ['path' => ['Adres', 'text'], 'hits' => ['Tıklanma', 'number']], 'list' => ['path', 'hits', 'last_seen_at']],
        'search_terms' => ['table' => 'search_terms', 'title' => 'Arama Yapılan Kelimeler', 'order' => 'hits DESC', 'readonly' => true,
            'fields' => ['term' => ['Kelime', 'text']], 'list' => ['term', 'hits', 'result_count']],
        'activity_logs' => ['table' => 'activity_logs', 'title' => 'İşlem Kayıtları', 'order' => 'id DESC', 'readonly' => true,
            'fields' => ['user_id' => ['Kullanıcı', 'number'], 'action' => ['İşlem', 'text']], 'list' => ['user_id', 'action', 'entity', 'entity_id', 'created_at']],

        'members' => ['table' => 'users', 'title' => 'Üye Yönetimi', 'where' => "role = 'customer'", 'defaults' => ['role' => 'customer'], 'order' => 'id DESC', 'search' => ['first_name', 'last_name', 'email', 'phone'],
            'fields' => $userFields + ['group_id' => ['Üye / Bayi Grubu', 'select', function () { return opts_query('SELECT id, name FROM customer_groups'); }],
                'company_name' => ['Firma Adı', 'text'], 'tax_office' => ['Vergi Dairesi', 'text'], 'tax_number' => ['Vergi No / TC', 'text'],
                'email_opt_in' => ['E-posta İzni', 'bool'], 'sms_opt_in' => ['SMS İzni', 'bool'], 'is_active' => ['Aktif', 'bool']],
            'list' => ['first_name', 'last_name', 'email', 'phone', 'group_id', 'is_active', 'created_at']],
        'email_list' => ['table' => 'users', 'title' => 'E-mail Listesi', 'where' => 'email_opt_in = 1', 'readonly' => true,
            'fields' => ['email' => ['E-posta', 'email']], 'list' => ['first_name', 'last_name', 'email', 'created_at']],
        'sms_list' => ['table' => 'users', 'title' => 'SMS Listesi', 'where' => 'sms_opt_in = 1', 'readonly' => true,
            'fields' => ['phone' => ['Telefon', 'text']], 'list' => ['first_name', 'last_name', 'phone', 'created_at']],
        'customer_groups' => ['table' => 'customer_groups', 'title' => 'Üye & Bayi Grupları',
            'fields' => ['name' => ['Grup Adı', 'text', 'required'], 'discount_rate' => ['İskonto Oranı (%)', 'number'], 'is_dealer' => ['Bayi Grubu', 'bool']],
            'list' => ['name', 'discount_rate', 'is_dealer']],
        'admins' => ['table' => 'users', 'title' => 'Kullanıcı Yönetimi (Yöneticiler)', 'where' => "role IN ('admin','staff')", 'defaults' => ['role' => 'staff'],
            'fields' => $userFields + ['role' => ['Rol', 'select', ['admin' => 'Yönetici (tam yetki)', 'staff' => 'Personel']], 'is_active' => ['Aktif', 'bool']],
            'list' => ['first_name', 'last_name', 'email', 'role', 'last_login_at', 'is_active']],

        'campaigns' => ['table' => 'campaigns', 'title' => 'Kampanyalar', 'order' => 'id DESC', 'filters' => ['kind'],
            'fields' => ['name' => ['Kampanya Adı', 'text', 'required'],
                'kind' => ['Kampanya Tipi', 'select', ['product' => 'Ürüne / Kategoriye Özel İndirim', 'cart_total' => 'Sepette İndirim (tutar üstü)', 'shipping' => 'Kargo Kampanyası', 'payment_type' => 'Ödeme Tipi (ör. havalede indirim)', 'quantity_range' => 'Adet Aralığı', 'buy_x_pay_y' => 'X Al Y Öde', 'gift_product' => 'Hediye Ürün', 'price_range' => 'Fiyat Aralığı', 'x_items_fixed' => 'X Ürün Tek Fiyat']],
                'discount_type' => ['İndirim Türü', 'select', ['percent' => 'Yüzde (%)', 'amount' => 'Tutar (TL)']], 'discount_value' => ['İndirim Değeri', 'number', 'required'],
                'scope' => ['Uygulanacak Ürünler', 'select', ['all' => 'Tüm Ürünler', 'categories' => 'Seçili Kategoriler', 'brands' => 'Seçili Markalar', 'products' => 'Seçili Ürünler']],
                'scope_ids' => ['Seçili ID\'ler (virgülle, ör. 1,4,7)', 'idlist'], 'rules' => ['Kurallar (min. tutar, ödeme tipi, adet aralığı)', 'json'],
                'banner_image' => ['Banner Görsel URL', 'text'], 'starts_at' => ['Başlangıç', 'datetime'], 'ends_at' => ['Bitiş', 'datetime'], 'is_active' => ['Aktif', 'bool']],
            'list' => ['name', 'kind', 'discount_type', 'discount_value', 'starts_at', 'ends_at', 'is_active']],
        'coupons' => ['table' => 'coupons', 'title' => 'Hediye Çeki / Kupon Yönetimi', 'order' => 'id DESC',
            'fields' => ['code' => ['Kupon Kodu', 'text', 'required'], 'discount_type' => ['İndirim Türü', 'select', ['amount' => 'Tutar (TL)', 'percent' => 'Yüzde (%)']],
                'discount_value' => ['İndirim Değeri', 'number', 'required'], 'min_cart_total' => ['Min. Sepet Tutarı', 'money'], 'usage_limit' => ['Kullanım Limiti', 'number'],
                'starts_at' => ['Başlangıç', 'datetime'], 'ends_at' => ['Bitiş', 'datetime'], 'is_active' => ['Aktif', 'bool']],
            'list' => ['code', 'discount_type', 'discount_value', 'used_count', 'usage_limit', 'ends_at', 'is_active']],

        'shipping_companies' => ['table' => 'shipping_companies', 'title' => 'Kargo Yönetimi',
            'fields' => ['name' => ['Kargo Firması', 'text', 'required'], 'code' => ['Kod', 'text', 'required'], 'fee' => ['Kargo Ücreti', 'money'], 'tracking_url' => ['Takip URL (takip no için {no})', 'text'], 'is_active' => ['Aktif', 'bool']],
            'list' => ['name', 'code', 'fee', 'is_active']],
        'bank_accounts' => ['table' => 'bank_accounts', 'title' => 'Banka Hesap Bilgileri',
            'fields' => ['bank_name' => ['Banka', 'text', 'required'], 'account_holder' => ['Hesap Sahibi', 'text', 'required'], 'iban' => ['IBAN', 'text', 'required'], 'is_active' => ['Aktif', 'bool']],
            'list' => ['bank_name', 'account_holder', 'iban', 'is_active']],
        'currency_rates' => ['table' => 'currency_rates', 'title' => 'Döviz Kurları', 'pk' => 'code', 'order' => 'code',
            'fields' => ['code' => ['Döviz Kodu (USD, EUR)', 'text', 'required'], 'rate' => ['Kur (TL)', 'number', 'required']],
            'list' => ['code', 'rate', 'updated_at']],
    ];
}

/** Stok hareketi kaydedilince ürün stoğunu güncelle */
function apply_stock_movement(array $data): void
{
    $q = (int)$data['quantity'];
    if (in_array($data['kind'], ['out', 'order'], true)) $q = -abs($q);
    if ($data['kind'] === 'adjust') {
        db()->prepare('UPDATE products SET stock = ? WHERE id = ?')->execute([abs($q), $data['product_id']]);
    } else {
        db()->prepare('UPDATE products SET stock = GREATEST(0, stock + ?) WHERE id = ?')->execute([$q, $data['product_id']]);
    }
}
