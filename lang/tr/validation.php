<?php

return [
    'accepted' => ':attribute kabul edilmelidir.',
    'array' => ':attribute bir dizi olmalıdır.',
    'confirmed' => ':attribute tekrarı eşleşmiyor.',
    'digits' => ':attribute :digits haneli olmalıdır.',
    'email' => ':attribute geçerli bir e-posta adresi olmalıdır.',
    'in' => 'Seçilen :attribute geçersiz.',
    'max' => [
        'numeric' => ':attribute en fazla :max olabilir.',
        'string' => ':attribute en fazla :max karakter olmalıdır.',
        'array' => ':attribute en fazla :max öğe içermelidir.',
    ],
    'min' => [
        'numeric' => ':attribute en az :min olmalıdır.',
        'string' => ':attribute en az :min karakter olmalıdır.',
        'array' => ':attribute en az :min öğe içermelidir.',
    ],
    'nullable' => ':attribute boş bırakılabilir.',
    'numeric' => ':attribute sayı olmalıdır.',
    'regex' => ':attribute biçimi geçersiz.',
    'required' => ':attribute alanı zorunludur.',
    'string' => ':attribute metin olmalıdır.',
    'unique' => 'Bu :attribute daha önce kullanılmış.',

    'password' => [
        'letters' => ':attribute en az bir harf içermelidir.',
        'mixed' => ':attribute en az bir büyük ve bir küçük harf içermelidir.',
        'numbers' => ':attribute en az bir rakam içermelidir.',
        'symbols' => ':attribute en az bir özel karakter içermelidir.',
        'uncompromised' => 'Girilen :attribute bir veri sızıntısında tespit edilmiştir. Lütfen farklı bir :attribute seçin.',
    ],

    'attributes' => [
        'account_type' => 'üyelik türü',
        'address' => 'işletme adresi',
        'city' => 'il',
        'district' => 'ilçe',
        'email' => 'e-posta adresi',
        'first_name' => 'ad',
        'identity_number' => 'T.C. kimlik numarası',
        'last_name' => 'soyad',
        'license_number' => 'yetki belgesi numarası',
        'neighborhood' => 'mahalle / köy',
        'office_name' => 'işletme / ofis adı',
        'password' => 'şifre',
        'phone' => 'cep telefonu',
        'tax_number' => 'vergi numarası',
        'tax_office' => 'vergi dairesi',
    ],
];
