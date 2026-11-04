<?php
/** Ön Muhasebe genel CRUD kaynakları (cariler, hesaplar, tahsilat/ödeme, masraflar) */
function acc_resources(): array
{
    $cust = function () { return opts_query("SELECT id, name FROM acc_parties WHERE kind = 'customer' ORDER BY name"); };
    $supp = function () { return opts_query("SELECT id, name FROM acc_parties WHERE kind = 'supplier' ORDER BY name"); };
    $accs = function () { return opts_query("SELECT id, CONCAT(name, ' (', currency, ')') FROM acc_accounts WHERE is_active = 1 ORDER BY name"); };
    $party = function (string $label, callable $opts) {
        return [
            'name' => [$label, 'text', 'required'], 'tax_office' => ['Vergi Dairesi', 'text'], 'tax_no' => ['Vergi No / TC Kimlik No', 'text'],
            'phone' => ['Telefon', 'text'], 'email' => ['E-posta', 'email'], 'city' => ['Şehir', 'text'], 'address' => ['Adres', 'textarea'],
            'opening_balance' => ['Açılış Bakiyesi (TL) – pozitif: açık hesap bakiyesi', 'money'], 'note' => ['Not', 'textarea'], 'is_active' => ['Aktif', 'bool'],
        ];
    };
    $pay = function (string $kind, string $title, callable $opts) use ($accs) {
        return ['table' => 'acc_payments', 'title' => $title, 'where' => "kind = '$kind'", 'defaults' => ['kind' => $kind], 'order' => 'pay_at DESC, id DESC',
            'fields' => ['pay_at' => ['Tarih', 'date', 'required'], 'party_id' => [$kind === 'in' ? 'Müşteri' : 'Tedarikçi', 'select', $opts], 'account_id' => ['Kasa / Hesap', 'select', $accs, 'required'],
                'amount' => ['Tutar (hesabın para birimi)', 'money', 'required'], 'rate' => ['Kur (TL hesap için boş bırakın)', 'number'], 'note' => ['Açıklama', 'text']],
            'list' => ['pay_at', 'party_id', 'account_id', 'amount', 'note'], 'filters' => ['party_id', 'account_id'], 'search' => ['note']];
    };
    return [
        'acc_customers' => ['table' => 'acc_parties', 'title' => 'Müşteriler', 'where' => "kind = 'customer'", 'defaults' => ['kind' => 'customer'], 'order' => 'name',
            'fields' => $party('Ad / Unvan', $cust), 'list' => ['name', 'phone', 'city', 'is_active'], 'search' => ['name', 'phone', 'tax_no']],
        'acc_suppliers' => ['table' => 'acc_parties', 'title' => 'Tedarikçiler', 'where' => "kind = 'supplier'", 'defaults' => ['kind' => 'supplier'], 'order' => 'name',
            'fields' => $party('Tedarikçi Adı / Unvanı', $supp), 'list' => ['name', 'phone', 'city', 'is_active'], 'search' => ['name', 'phone', 'tax_no']],
        'acc_accounts' => ['table' => 'acc_accounts', 'title' => 'Hesap Tanımları (Kasa / Banka / POS)', 'order' => 'kind, name',
            'fields' => ['name' => ['Hesap Adı', 'text', 'required'],
                'kind' => ['Hesap Türü', 'select', ['cash' => 'Kasa', 'bank' => 'Banka Hesabı', 'pos' => 'POS Hesabı', 'card' => 'Kredi Kartı', 'partner' => 'Şirket Ortağı Hesabı', 'credit' => 'Veresiye Hesabı'], 'required'],
                'currency' => ['Para Birimi', 'select', ['TRY' => 'TL', 'USD' => 'USD', 'EUR' => 'EUR'], 'required'],
                'opening_balance' => ['Açılış Bakiyesi', 'money'], 'is_active' => ['Aktif', 'bool']],
            'list' => ['name', 'kind', 'currency', 'opening_balance', 'is_active']],
        'acc_collections' => $pay('in', 'Tahsilatlar', $cust),
        'acc_payouts' => $pay('out', 'Ödemeler', $supp),
        'acc_expenses' => ['table' => 'acc_expenses', 'title' => 'Masraflar', 'order' => 'exp_at DESC, id DESC',
            'fields' => ['exp_at' => ['İşlem Tarihi', 'date', 'required'], 'category' => ['Masraf Kalemi (ör. Kargo, Kira, Personel)', 'text', 'required'], 'description' => ['Açıklama', 'text'],
                'doc_no' => ['Belge No', 'text'], 'amount' => ['Tutar (TL)', 'money', 'required'], 'account_id' => ['Ödenen Hesap', 'select', $accs],
                'is_paid' => ['Ödendi', 'bool'], 'due_at' => ['Vade (ödenmediyse)', 'date'], 'note' => ['Not', 'textarea']],
            'list' => ['exp_at', 'category', 'description', 'amount', 'account_id', 'is_paid'], 'filters' => ['is_paid', 'account_id'], 'search' => ['category', 'description', 'doc_no']],
    ];
}
