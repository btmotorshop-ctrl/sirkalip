<?php
/**
 * Yönetim paneli menüsü (sadeleştirilmiş).
 * Her grup: [başlık, ikon, grubun kendi rotası|null, [[alt başlık, rota, 'arama anahtar kelimeleri'(ops.)], ...]]
 * Rota: 'dashboard' | 'products' | 'orders' | 'settings/x' | 'reports/x' | 'r/<kaynak>' (genel CRUD)
 * Orijinal menü yedeği: yerel/menu.php.orijinal
 */
function admin_menu(): array
{
    return [
        ['Anasayfa', 'home', 'dashboard', []],

        ['Siparişler', 'clipboard-list', null, [
            ['Tüm Siparişler', 'orders', 'sipariş liste'],
            ['Ödeme Bekleyenler', 'orders?payment=waiting', 'ödeme kaydı yok bekleyen'],
            ['Havale / EFT Bildirimleri', 'r/transfer_notices', 'havale eft dekont'],
            ['İade Talepleri', 'r/return_requests', 'iade'],
            ['İade Ödemeleri', 'orders?payment=refunded', 'iade ödeme'],
            ['İptal Edilenler', 'orders?status=cancelled', 'iptal'],
            ['Silinen Siparişler', 'orders?deleted=1', 'silinen çöp'],
            ['Ödeme Sonucu Dönmeyenler', 'r/payments', 'ödeme sonuç'],
            ['Hatalı Ödeme Kayıtları', 'r/payments_failed', 'hata ödeme'],
        ]],

        ['Ürünler', 'package', null, [
            ['Ürün Yönetimi', 'products', 'ürün liste ekle fiyat'],
            ['Kategoriler', 'r/categories', 'kategori'],
            ['Markalar', 'r/brands', 'marka'],
            ['Makine Modelleri', 'r/machine_models', 'model makine'],
            ['Tedarikçiler', 'r/suppliers', 'tedarikçi firma'],
            ['Etiketler', 'r/tags', 'etiket'],
            ['Teknik Detaylar', 'r/spec_keys', 'özellik teknik'],
            ['Ürün Detay Sekmeleri', 'r/product_tabs', 'sekme'],
            ['Ürün Detay Ayarları', 'settings/product', ''],
            ['Ürün Birleştirme', 'products/merge', 'birleştir set paket kit birleştirme'],
            ['Toplu Ürün İşlemleri', 'products/bulk', 'toplu excel fiyat'],
            ['Anasayfa Ürün Sıralama', 'products/sort', 'sıralama vitrin'],
            ['Geri Dönüşüm Kutusu', 'products?deleted=1', 'silinen çöp kutusu'],
        ]],

        ['Stok ve Kontrol', 'boxes', null, [
            ['Stok Yönetimi', 'stock', 'stok depo'],
            ['Kritik Stok', 'stock?critical=1', 'kritik azalan'],
            ['Stok Hareketleri', 'r/stock_movements', 'hareket'],
            ['Stok Alarm Hatırlatma', 'r/stock_alerts', 'alarm bekleyen'],
            ['Fiyat Alarm Hatırlatma', 'r/price_alerts', 'alarm fiyat'],
            ['Pasif Ürünler', 'products?active=0', 'pasif kapalı'],
            ['Açıklaması Eksik Ürünler', 'products?missing=desc', 'açıklamasız eksik'],
            ['Resmi Eksik Ürünler', 'products?missing=image', 'resimsiz görselsiz eksik'],
            ['SEO Bilgisi Eksik Ürünler', 'products?missing=seo', 'meta eksik'],
        ]],

        ['Müşteriler', 'users', null, [
            ['Üyeler', 'r/members', 'üye müşteri'],
            ['Üye ve Bayi Grupları', 'r/customer_groups', 'bayi grup'],
            ['Ürün Yorumları', 'r/reviews', 'yorum'],
            ['Soru ve Cevaplar', 'r/questions', 'soru cevap'],
            ['Gelen Mesajlar', 'r/contact_messages?is_spam=0', 'mesaj iletişim müşteri hizmetleri'],
            ['Spam Kutusu', 'r/contact_messages?is_spam=1', 'spam bot istenmeyen mesaj'],
            ['E-posta Listesi', 'r/email_list', 'mail bülten'],
            ['SMS Listesi', 'r/sms_list', 'sms'],
        ]],

        ['Kampanyalar', 'percent', null, [
            ['Tüm Kampanyalar', 'r/campaigns', 'kampanya indirim'],
            ['Kupon / Hediye Çeki', 'r/coupons', 'kupon hediye çeki indirim kodu'],
            ['Ana Sayfa Bannerları', 'r/banners', 'banner slider görsel'],
        ]],

        ['İçerik ve Tasarım', 'image', null, [
            ['Sayfalar', 'r/pages', 'sayfa hakkımızda'],
            ['Sıkça Sorulan Sorular', 'r/faqs', 'sss soru'],
            ['Haberler / Blog', 'r/articles', 'haber blog makale'],
            ['Menü Yönetimi', 'r/menu_items', 'site menüsü'],
            ['Header Tasarımı', 'r/menu_header', 'üst başlık'],
            ['Footer Tasarımı', 'r/menu_footer', 'alt'],
            ['Tasarım Ayarları', 'settings/design', 'renk logo tema'],
            ['Sabit Site İçerikleri', 'settings/content', 'içerik metin'],
            ['Sözleşmeler', 'r/contracts', 'sözleşme kvkk'],
            ['Sistem Mail Tasarımları', 'r/email_templates', 'mail şablon'],
            ['Çerez Yönetimi', 'settings/cookies', 'çerez'],
            ['Script Yönetimi', 'settings/scripts', 'script analytics kod'],
        ]],

        ['SEO', 'search', null, [
            ['Toplu Kategori SEO', 'seo/categories', ''],
            ['Toplu Marka SEO', 'seo/brands', ''],
            ['Toplu Ürün SEO', 'seo/products', ''],
            ['Toplu Sayfa SEO', 'seo/pages', ''],
            ['XML Sitemap', 'settings/sitemap', 'sitemap'],
            ['301 Yönlendirmeleri', 'r/redirects', 'yönlendirme'],
            ['404 Link Kayıtları', 'r/not_found_logs', 'bulunamayan hata'],
        ]],

        ['Ön Muhasebe', 'banknote', 'acc', [
            ['Ön Muhasebe Ana Sayfa', 'acc', ''],
            ['Satışlar', 'acc/docs/sale', 'satış fatura'],
            ['Alışlar', 'acc/docs/purchase', 'alış fatura'],
            ['Müşteriler (Cari)', 'acc/parties/customer', 'cari müşteri'],
            ['Tedarikçiler (Cari)', 'acc/parties/supplier', 'cari tedarikçi'],
            ['Tahsilatlar', 'r/acc_collections', ''],
            ['Ödemeler', 'r/acc_payouts', ''],
            ['Masraflar', 'r/acc_expenses', 'gider'],
            ['Kasa / Banka Hesapları', 'acc/accounts', 'kasa banka hesap'],
            ['Hesap Tanımları', 'r/acc_accounts', ''],
            ['Cari Bakiye Raporu', 'acc/balances', 'bakiye borç alacak'],
        ]],

        ['Raporlar', 'chart-bar', null, [
            ['Satış Raporu', 'reports/sales', 'satış ciro'],
            ['Ürün Bazlı', 'reports/products', ''],
            ['Kategori Bazlı', 'reports/categories', ''],
            ['Marka Bazlı', 'reports/brands', ''],
            ['İl Bazlı', 'reports/cities', 'şehir'],
            ['Ödeme Tipine Göre', 'reports/payment', ''],
            ['Kaynağına Göre', 'reports/source', 'kaynak'],
            ['Saatlik Satışlar', 'reports/hourly', 'saat'],
            ['Ürün Kârlılığı', 'reports/profit', 'kâr'],
            ['En Çok Satan Ürünler', 'reports/bestsellers', 'çok satan'],
            ['En Çok Sipariş Veren Üyeler', 'reports/topcustomers', 'sadık müşteri'],
            ['Arama Yapılan Kelimeler', 'r/search_terms', 'arama'],
            ['İşlem Kayıtları', 'r/activity_logs', 'log'],
            ['Ziyaretçi Detayları', 'visitors', 'ziyaretçi ip ülke tarayıcı'],
            ['Ürün Görüntüleme', 'visitors?type=product', 'ürün görüntüleme hangi ürün'],
        ]],

        ['Ayarlar', 'settings', null, [
            ['Genel Ayarlar', 'settings/general', 'firma telefon adres'],
            ['Teklif ve Özel Şablonlar', 'acc/templates', 'teklif şablon yazdır pdf fatura'],
            ['Kargo Firmaları', 'r/shipping_companies', 'kargo'],
            ['Kargo Ayarları', 'settings/shipping', 'kargo ücret'],
            ['Aras Kargo Entegrasyonu', 'settings/aras', 'aras'],
            ['Döviz Kurları', 'r/currency_rates', 'dolar euro kur'],
            ['E-posta Hesapları', 'settings/email', 'smtp mail'],
            ['Ödeme Ayarları', 'settings/payment', ''],
            ['Sanal POS', 'settings/pos', 'pos kredi kartı'],
            ['Banka Hesapları', 'r/bank_accounts', 'iban'],
            ['WhatsApp Ayarları', 'settings/whatsapp', ''],
            ['Dil ve Otomatik Çeviri', 'translate', 'dil çeviri'],
            ['Çeviri Ayarları', 'settings/translate', ''],
            ['Kullanıcılar', 'r/admins', 'yönetici kullanıcı yetki şifre'],
        ]],
    ];
}

/** Menüde olmayan sayfalar için başlık bul */
function menu_title_for(string $route): ?string
{
    foreach (admin_menu() as [$label, , $r, $children]) {
        if ($r === $route) return $label;
        foreach ($children as $c) if ($c[1] === $route) return $c[0];
    }
    return null;
}
