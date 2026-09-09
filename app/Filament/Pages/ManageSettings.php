<?php

namespace App\Filament\Pages;

use App\Enums\UserRole;
use App\Models\Setting;
use App\Models\User;
use App\Services\DatabaseMailConfigurator;
use App\Services\IletiMerkeziPhoneOtpSender;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Validation\ValidationException;

class ManageSettings extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-cog-6-tooth';

    protected static ?string $navigationGroup = 'Sistem';

    protected static ?string $navigationLabel = 'Site Ayarları';

    protected static ?string $title = 'Site Genel Ayarları';

    // View dosyasının yolunu da bağımsız sayfalara uygun güncelledik
    protected static string $view = 'filament.pages.manage-settings';

    public ?array $data = [];

    /**
     * Sadece SUPERVISOR (Admin) rolüne sahip kullanıcılar görebilir ve erişebilir.
     */
    public static function canAccess(): bool
    {
        /** @var User $user */
        $user = auth()->user();

        return $user && ($user->role === UserRole::SUPERVISOR || $user->role === UserRole::SUPERVISOR->value);
    }

    /**
     * Varsayılan seçenek matrisimiz.
     * Veritabanında henüz kayıt yoksa bu değerler otomatik yüklenecek.
     */
    protected function getDefaultOptionSettings(): array
    {
        return [
            'options_type' => ['Satılık', 'Kiralık'],
            'options_category' => ['Daire', 'Müstakil Ev', 'Arsa', 'Tarla', 'İşyeri'],
            'options_subcategory' => ['Standart', 'Lüks', 'Müstakil', 'Ticari', 'Yatırımlık'],
            'options_room_count' => ['1+0', '1+1', '2+1', '3+1', '4+1'],
            'options_heating_type' => ['Kombi (Doğalgaz)', 'Klima', 'Merkezi Sistem', 'Sobalı'],
            'options_building_age' => ['0 (Yeni)', '1-5 Arası', '6-10 Arası', '11-15 Arası', '16-20 Arası', '21 ve üzeri'],
            'options_furniture_status' => ['Eşyalı', 'Eşyasız'],
            'options_usage_status' => ['Boş', 'Kiracılı', 'Mülk Sahibi', 'Yapım Aşamasında'],
            'options_facade' => ['Kuzey', 'Güney', 'Doğu', 'Batı'],
            'options_zoning_status' => ['Tarla', 'Konut İmarlı', 'Ticari İmarlı', 'Sanayi İmarlı', 'Bağ & Bahçe', 'Zeytinlik'],
            'options_deed_status' => ['Hisseli Tapu', 'Müstakil Parsel', 'Tahsis Tapu', 'Zilliyet'],
            'options_owner_nationality' => ['T.C. Vatandaşı', 'Yabancı'],
            'options_from_who' => ['Emlak Ofisinden', 'Sahibinden', 'İnşaat Firmasından'],
            'options_credit_eligibility' => ['Evet', 'Hayır', 'Bilinmiyor'],
            'options_swap_option' => ['Evet', 'Hayır'],
        ];
    }

    protected function getDefaultContactSettings(): array
    {
        return [
            'contact_page_title' => 'İletişim',
            'contact_company_name' => 'Zyd Emlak',
            'site_address' => 'Istanbul, Turkiye',
            'contact_map_query' => 'Istanbul, Turkiye',
            'contact_phone_text' => 'İlanlar, randevu ve danışmanlık talepleriniz için bize ulaşabilirsiniz.',
            'contact_email_text' => 'Mesajınızı e-posta ile iletebilir veya aşağıdaki iletişim formunu kullanabilirsiniz.',
            'contact_form_title' => 'İletişim formu',
            'contact_submit_text' => 'Mesaj gönder',
        ];
    }

    protected function getDefaultDocumentSettings(): array
    {
        return [
            'user_documents' => [],
        ];
    }

    protected function getDefaultSecuritySettings(): array
    {
        return [
            'smtp_enabled' => false,
            'smtp_host' => '',
            'smtp_port' => 587,
            'smtp_scheme' => 'auto',
            'smtp_username' => '',
            'smtp_from_address' => '',
            'smtp_from_name' => 'ZYDEMLAK',
            'email_otp_enabled' => false,
            'email_otp_ttl_minutes' => 5,
            'email_otp_max_attempts' => 5,
            'email_otp_lock_minutes' => 15,
            'email_otp_resend_seconds' => 60,
            'email_otp_hourly_limit' => 5,
            'phone_otp_enabled' => false,
            'sms_sender' => 'ILHAN AKDMN',
            'sms_iys_enabled' => null,
            'sms_iys_list' => 'BIREYSEL',
        ];
    }

    protected function getDefaultMarketSettings(): array
    {
        return [
            'rent_increase_rate' => 31.79,
            'rent_increase_period' => 'Eylül 2026',
            'rent_increase_updated_at' => now()->toDateString(),
        ];
    }

    public function mount(): void
    {
        // 1. Veritabanındaki tüm ayarları çekiyoruz[cite: 6]
        $settings = Setting::pluck('value', 'key')->toArray(); // [cite: 6]
        $defaults = array_merge(
            $this->getDefaultOptionSettings(),
            $this->getDefaultContactSettings(),
            $this->getDefaultDocumentSettings(),
            $this->getDefaultSecuritySettings(),
            $this->getDefaultMarketSettings(),
        );

        // 2. Veritabanında boş olan/henüz kaydedilmemiş anahtarlara varsayılan dizileri atıyoruz
        foreach ($defaults as $key => $defaultValues) {
            if (! isset($settings[$key]) || empty($settings[$key])) {
                $settings[$key] = $defaultValues;
            }
        }

        // Kayıtlı gizli değerler hiçbir zaman forma geri yazılmaz.
        $settings['smtp_password'] = null;
        $settings['sms_api_key'] = null;
        $settings['sms_api_hash'] = null;

        // 3. Formu dolduruyoruz[cite: 6]
        $this->form->fill($settings); // [cite: 6]
    }

    public function form(Form $form): Form
    {
        $defaults = $this->getDefaultOptionSettings();

        return $form
            ->schema([
                Forms\Components\Tabs::make('Settings')
                    ->tabs([
                        // --- TAB 1: SLIDER AYARLARI ---[cite: 15]
                        Forms\Components\Tabs\Tab::make('Ana Sayfa Slider') // [cite: 15]
                            ->icon('heroicon-o-photo') // [cite: 15]
                            ->schema([
                                Forms\Components\TextInput::make('home_slider_title') // [cite: 15]
                                    ->label('Slider Ana Başlığı') // [cite: 15]
                                    ->default('PROPERTY SEARCHING JUST GOT SO EASY') // [cite: 15]
                                    ->placeholder('PROPERTY SEARCHING JUST GOT SO EASY') // [cite: 15]
                                    ->columnSpanFull(), // [cite: 15]

                                Forms\Components\Repeater::make('home_sliders') // [cite: 15]
                                    ->label('Slider Görselleri') // [cite: 15]
                                    ->schema([
                                        Forms\Components\FileUpload::make('image') // [cite: 15]
                                            ->label('Slider Görseli') // [cite: 15]
                                            ->image() // [cite: 15]
                                            ->directory('site-sliders') // [cite: 15]
                                            ->required() // [cite: 15]
                                            ->imageEditor() // [cite: 15]
                                            ->imageCropAspectRatio('1740:1000') // [cite: 15]
                                            ->imageResizeTargetWidth('1740') // [cite: 15]
                                            ->imageResizeTargetHeight('1000') // [cite: 15]
                                            ->imageResizeMode('cover') // [cite: 15]
                                            ->helperText('İdeal Görsel Boyutu: 1740x1000 px.'), // [cite: 15]

                                        Forms\Components\TextInput::make('title')->label('Slider Başlığı'), // [cite: 15]
                                        Forms\Components\TextInput::make('subtitle')->label('Alt Açıklama Metni'), // [cite: 15]
                                        Forms\Components\TextInput::make('link')->label('Görsel Linki (Opsiyonel)'), // [cite: 15]
                                    ])
                                    ->collapsible() // [cite: 15]
                                    ->cloneable() // [cite: 15]
                                    ->defaultItems(1) // [cite: 15]
                                    ->addActionLabel('Yeni Slider Ekle') // [cite: 15]
                                    ->columnSpanFull(), // [cite: 15]
                            ]),

                        // --- TAB 2: GENEL BİLGİLER ---[cite: 15]
                        Forms\Components\Tabs\Tab::make('Genel Bilgiler') // [cite: 15]
                            ->icon('heroicon-o-building-office') // [cite: 15]
                            ->schema([
                                Forms\Components\FileUpload::make('site_logo')
                                    ->label('Site Logosu')
                                    ->image()
                                    ->live()
                                    ->disk('public')
                                    ->visibility('public')
                                    ->directory('site')
                                    ->acceptedFileTypes(['image/jpeg', 'image/png', 'image/webp'])
                                    ->maxSize(5120)
                                    ->imageResizeMode('contain')
                                    ->imageResizeTargetWidth('1000')
                                    ->imageResizeTargetHeight('650')
                                    ->imageResizeUpscale(false)
                                    ->afterStateUpdated(fn (Forms\Components\FileUpload $component) => $this->saveLogoImmediately($component))
                                    ->helperText('PNG, JPG veya WEBP (en fazla 5 MB). Logo yüklenince otomatik kaydedilir ve açık renkli arka planı sitede kaldırılır.'), // [cite: 15]
                                Forms\Components\TextInput::make('site_phone')->label('Telefon Numarası'), // [cite: 15]
                                Forms\Components\TextInput::make('site_email')->label('E-posta Adresi'), // [cite: 15]
                                Forms\Components\Textarea::make('eids_text')->label('EİDS / Onaylı Taşınmaz Metni')->rows(2)->columnSpanFull(), // [cite: 15]
                            ]),

                        Forms\Components\Tabs\Tab::make('İletişim Sayfası')
                            ->icon('heroicon-o-envelope')
                            ->schema([
                                Forms\Components\TextInput::make('contact_page_title')
                                    ->label('Sayfa Başlığı')
                                    ->default('İletişim')
                                    ->required(),

                                Forms\Components\TextInput::make('contact_company_name')
                                    ->label('Firma / Marka Adı')
                                    ->default('Zyd Emlak')
                                    ->required(),

                                Forms\Components\Textarea::make('site_address')
                                    ->label('Adres')
                                    ->default('Istanbul, Turkiye')
                                    ->rows(3)
                                    ->columnSpanFull(),

                                Forms\Components\TextInput::make('contact_map_query')
                                    ->label('Harita Arama Metni')
                                    ->default('Istanbul, Turkiye')
                                    ->helperText('Boş bırakılırsa adres bilgisiyle harita oluşturulur.')
                                    ->columnSpanFull(),

                                Forms\Components\Textarea::make('contact_phone_text')
                                    ->label('Telefon Açıklaması')
                                    ->default('İlanlar, randevu ve danışmanlık talepleriniz için bize ulaşabilirsiniz.')
                                    ->rows(2)
                                    ->columnSpanFull(),

                                Forms\Components\Textarea::make('contact_email_text')
                                    ->label('E-posta Açıklaması')
                                    ->default('Mesajınızı e-posta ile iletebilir veya aşağıdaki iletişim formunu kullanabilirsiniz.')
                                    ->rows(2)
                                    ->columnSpanFull(),

                                Forms\Components\TextInput::make('contact_form_title')
                                    ->label('Form Başlığı')
                                    ->default('İletişim formu'),

                                Forms\Components\TextInput::make('contact_submit_text')
                                    ->label('Form Buton Yazısı')
                                    ->default('Mesaj gönder'),
                            ])
                            ->columns(2),

                        Forms\Components\Tabs\Tab::make('Kullanıcı Dokümanları')
                            ->icon('heroicon-o-document-arrow-down')
                            ->schema([
                                Forms\Components\Repeater::make('user_documents')
                                    ->label('Header Dokümanları')
                                    ->schema([
                                        Forms\Components\TextInput::make('title')
                                            ->label('Doküman Adı')
                                            ->placeholder('Kira Kontratı')
                                            ->required()
                                            ->maxLength(100),

                                        Forms\Components\FileUpload::make('file')
                                            ->label('Dosya')
                                            ->disk('public')
                                            ->directory('user-documents')
                                            ->acceptedFileTypes([
                                                'application/pdf',
                                                'application/msword',
                                                'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
                                                'application/vnd.ms-excel',
                                                'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                                                'image/jpeg',
                                                'image/png',
                                            ])
                                            ->downloadable()
                                            ->openable()
                                            ->required()
                                            ->helperText('PDF, Word, Excel veya görsel dosyaları yükleyebilirsiniz.'),

                                        Forms\Components\TextInput::make('sort')
                                            ->label('Sıralama')
                                            ->numeric()
                                            ->default(0),

                                        Forms\Components\Toggle::make('is_active')
                                            ->label('Yayında')
                                            ->default(true),
                                    ])
                                    ->columns(2)
                                    ->collapsible()
                                    ->cloneable()
                                    ->reorderable()
                                    ->defaultItems(0)
                                    ->addActionLabel('Yeni Doküman Ekle')
                                    ->columnSpanFull(),
                            ]),

                        Forms\Components\Tabs\Tab::make('Kira ve Piyasa')
                            ->icon('heroicon-o-chart-bar')
                            ->schema([
                                Forms\Components\TextInput::make('rent_increase_rate')
                                    ->label('Kira artış oranı (%)')
                                    ->numeric()
                                    ->minValue(0)
                                    ->maxValue(999)
                                    ->step(0.01)
                                    ->placeholder('31,79')
                                    ->helperText('Boş bırakılırsa kira artış kartı ana sayfada gösterilmez.'),
                                Forms\Components\TextInput::make('rent_increase_period')
                                    ->label('Dönem')
                                    ->placeholder('Eylül 2026')
                                    ->maxLength(80),
                                Forms\Components\DatePicker::make('rent_increase_updated_at')
                                    ->label('Son güncelleme tarihi'),
                                Forms\Components\Placeholder::make('market_source_info')
                                    ->label('Canlı piyasa kaynakları')
                                    ->content('Dolar ve Euro TCMB günlük gösterge kurlarından; gram altın Gold API XAU/USD fiyatı ve TCMB dolar satış kuru üzerinden hesaplanır. Servis verisi alınamazsa ilgili değer gösterilmez.')
                                    ->columnSpanFull(),
                            ])
                            ->columns(3),

                        Forms\Components\Tabs\Tab::make('OTP ve Güvenlik')
                            ->icon('heroicon-o-shield-check')
                            ->schema([
                                Forms\Components\Placeholder::make('otp_status_info')
                                    ->label('Canlı servis durumu')
                                    ->content(function (): string {
                                        $mailReady = app(DatabaseMailConfigurator::class)->hasStoredConfiguration();
                                        $smsReady = IletiMerkeziPhoneOtpSender::isConfigured();

                                        return sprintf(
                                            'SMTP ayarları: %s · SMS servisi: %s',
                                            $mailReady ? 'Kayıtlı' : 'Eksik',
                                            $smsReady ? 'Hazır' : 'Hazır değil (log sürücüsü)',
                                        );
                                    })
                                    ->helperText('SMTP parolası ile SMS API Key/Hash değerleri şifreli saklanır ve kayıttan sonra tekrar gösterilmez.')
                                    ->columnSpanFull(),

                                Forms\Components\Toggle::make('smtp_enabled')
                                    ->label('SMTP e-posta servisini aktif et')
                                    ->helperText('Aktif ettiğinizde aşağıda SMTP bağlantı alanları açılır.')
                                    ->default(false)
                                    ->live()
                                    ->inline(false)
                                    ->columnSpanFull(),

                                Forms\Components\Section::make('SMTP bağlantı bilgileri')
                                    ->description('Bilgileri e-posta hizmeti sağlayıcınızdan alabilirsiniz.')
                                    ->schema([
                                        Forms\Components\TextInput::make('smtp_host')
                                            ->label('SMTP sunucusu')
                                            ->placeholder('smtp.example.com')
                                            ->required()
                                            ->maxLength(255),

                                        Forms\Components\TextInput::make('smtp_port')
                                            ->label('SMTP portu')
                                            ->numeric()
                                            ->minValue(1)
                                            ->maxValue(65535)
                                            ->default(587)
                                            ->required(),

                                        Forms\Components\Select::make('smtp_scheme')
                                            ->label('Bağlantı güvenliği')
                                            ->options([
                                                'auto' => 'Otomatik / STARTTLS (genellikle 587)',
                                                'smtps' => 'SMTPS / SSL (genellikle 465)',
                                            ])
                                            ->default('auto')
                                            ->required(),

                                        Forms\Components\TextInput::make('smtp_username')
                                            ->label('SMTP kullanıcı adı')
                                            ->autocomplete(false)
                                            ->required()
                                            ->maxLength(255),

                                        Forms\Components\TextInput::make('smtp_password')
                                            ->label('SMTP parolası')
                                            ->password()
                                            ->revealable()
                                            ->autocomplete('new-password')
                                            ->dehydrated(fn ($state): bool => filled($state))
                                            ->helperText(Setting::getSecret('smtp_password')
                                                ? 'Kayıtlı parola korunur. Değiştirmek için yeni parolayı yazın.'
                                                : 'İlk kurulumda parola zorunludur.'),

                                        Forms\Components\TextInput::make('smtp_from_address')
                                            ->label('Gönderen e-posta adresi')
                                            ->email()
                                            ->placeholder('noreply@zydemlak.com')
                                            ->required()
                                            ->maxLength(255),

                                        Forms\Components\TextInput::make('smtp_from_name')
                                            ->label('Gönderen adı')
                                            ->default('ZYDEMLAK')
                                            ->required()
                                            ->maxLength(255),
                                    ])
                                    ->visible(fn ($get): bool => (bool) $get('smtp_enabled'))
                                    ->columns(2)
                                    ->columnSpanFull(),

                                Forms\Components\Toggle::make('email_otp_enabled')
                                    ->label('E-posta OTP sistemini aktif et')
                                    ->helperText('Aktif olduğunda üyelik, giriş ve hassas hesap değişikliklerinde 6 haneli e-posta kodu zorunlu olur.')
                                    ->default(false)
                                    ->live()
                                    ->visible(fn ($get): bool => (bool) $get('smtp_enabled'))
                                    ->inline(false)
                                    ->columnSpanFull(),

                                Forms\Components\Section::make('E-posta OTP kuralları')
                                    ->schema([
                                        Forms\Components\TextInput::make('email_otp_ttl_minutes')
                                            ->label('Kod geçerlilik süresi (dakika)')
                                            ->numeric()->minValue(1)->maxValue(30)->required(),

                                        Forms\Components\TextInput::make('email_otp_max_attempts')
                                            ->label('En fazla hatalı deneme')
                                            ->numeric()->minValue(3)->maxValue(10)->required(),

                                        Forms\Components\TextInput::make('email_otp_lock_minutes')
                                            ->label('Hatalı deneme kilidi (dakika)')
                                            ->numeric()->minValue(1)->maxValue(120)->required(),

                                        Forms\Components\TextInput::make('email_otp_resend_seconds')
                                            ->label('Yeniden gönderme bekleme süresi (saniye)')
                                            ->numeric()->minValue(15)->maxValue(600)->required(),

                                        Forms\Components\TextInput::make('email_otp_hourly_limit')
                                            ->label('Saatlik gönderim sınırı')
                                            ->numeric()->minValue(1)->maxValue(20)->required(),
                                    ])
                                    ->visible(fn ($get): bool => (bool) $get('smtp_enabled') && (bool) $get('email_otp_enabled'))
                                    ->columns(2)
                                    ->columnSpanFull(),

                                Forms\Components\Toggle::make('phone_otp_enabled')
                                    ->label('Telefon OTP sistemini aktif et')
                                    ->helperText('Aktif olduğunda EİDS kimlik işleminden önce telefon doğrulaması zorunlu olur.')
                                    ->default(false)
                                    ->live()
                                    ->inline(false),

                                Forms\Components\Section::make('İleti Merkezi SMS bağlantısı')
                                    ->description('API Key ve Hash şifrelenerek saklanır. Kayıtlı değerler ekranda tekrar gösterilmez.')
                                    ->schema([
                                        Forms\Components\TextInput::make('sms_api_key')
                                            ->label('API Key')
                                            ->password()
                                            ->revealable()
                                            ->autocomplete('new-password')
                                            ->dehydrated(fn ($state): bool => filled($state))
                                            ->helperText(Setting::getSecret('sms_api_key')
                                                ? 'Kayıtlı API Key korunur. Değiştirmek için yeni değeri yazın.'
                                                : 'İlk aktivasyonda zorunludur.'),

                                        Forms\Components\TextInput::make('sms_api_hash')
                                            ->label('API Hash')
                                            ->password()
                                            ->revealable()
                                            ->autocomplete('new-password')
                                            ->dehydrated(fn ($state): bool => filled($state))
                                            ->helperText(Setting::getSecret('sms_api_hash')
                                                ? 'Kayıtlı API Hash korunur. Değiştirmek için yeni değeri yazın.'
                                                : 'İlk aktivasyonda zorunludur.'),

                                        Forms\Components\TextInput::make('sms_sender')
                                            ->label('Onaylı SMS başlığı')
                                            ->placeholder('ILHAN AKDMN')
                                            ->maxLength(11)
                                            ->helperText('İleti Merkezi panelindeki onaylı başlığı büyük/küçük harf ve boşluklarıyla birebir yazın.'),

                                        Forms\Components\Select::make('sms_iys_enabled')
                                            ->label('OTP mesajında İYS sorgusu')
                                            ->placeholder('Seçiniz')
                                            ->options([
                                                '0' => 'Hayır (İYS sorgusu yapılmasın)',
                                                '1' => 'Evet (İYS sorgusu yapılsın)',
                                            ])
                                            ->live()
                                            ->helperText('Ticari iletilerde İYS sorgusu yasal olarak zorunludur. OTP kullanımınıza uygun seçimi siz yapmalısınız.'),

                                        Forms\Components\Select::make('sms_iys_list')
                                            ->label('İYS alıcı listesi')
                                            ->options([
                                                'BIREYSEL' => 'Bireysel',
                                                'TACIR' => 'Tacir',
                                            ])
                                            ->default('BIREYSEL')
                                            ->visible(fn ($get): bool => (string) $get('sms_iys_enabled') === '1'),
                                    ])
                                    ->columns(2)
                                    ->columnSpanFull(),
                            ])
                            ->columns(2),

                        // --- TAB 3: İLAN SEÇENEKLERİ ---[cite: 15]
                        Forms\Components\Tabs\Tab::make('İlan Seçenekleri') // [cite: 15]
                            ->icon('heroicon-o-list-bullet') // [cite: 15]
                            ->schema([
                                Forms\Components\TagsInput::make('options_type')
                                    ->label('İlan Tipi Seçenekleri')
                                    ->default($defaults['options_type']),

                                Forms\Components\TagsInput::make('options_category')
                                    ->label('Gayrimenkul Türü Seçenekleri')
                                    ->default($defaults['options_category']),

                                Forms\Components\TagsInput::make('options_subcategory')
                                    ->label('Alt Kategori Seçenekleri')
                                    ->default($defaults['options_subcategory']),

                                Forms\Components\TagsInput::make('options_room_count')
                                    ->label('Oda Sayısı Seçenekleri')
                                    ->default($defaults['options_room_count']),

                                Forms\Components\TagsInput::make('options_heating_type')
                                    ->label('Isıtma Tipi Seçenekleri')
                                    ->default($defaults['options_heating_type']),

                                Forms\Components\TagsInput::make('options_building_age')
                                    ->label('Bina Yaşı Seçenekleri')
                                    ->default($defaults['options_building_age']),

                                Forms\Components\TagsInput::make('options_furniture_status')
                                    ->label('Eşya Durumu Seçenekleri')
                                    ->default($defaults['options_furniture_status']),

                                Forms\Components\TagsInput::make('options_usage_status')
                                    ->label('Kullanım Durumu Seçenekleri')
                                    ->default($defaults['options_usage_status']),

                                Forms\Components\TagsInput::make('options_facade')
                                    ->label('Cephe Seçenekleri')
                                    ->default($defaults['options_facade']),

                                Forms\Components\TagsInput::make('options_zoning_status')
                                    ->label('İmar Durumu Seçenekleri (Arsa/Tarla için)')
                                    ->default($defaults['options_zoning_status']),

                                Forms\Components\TagsInput::make('options_deed_status')
                                    ->label('Tapu Durumu Seçenekleri')
                                    ->default($defaults['options_deed_status']),

                                Forms\Components\TagsInput::make('options_owner_nationality')
                                    ->label('Gayrimenkul Sahibi Seçenekleri')
                                    ->default($defaults['options_owner_nationality']),

                                Forms\Components\TagsInput::make('options_from_who')
                                    ->label('Kimden Seçenekleri')
                                    ->default($defaults['options_from_who']),

                                Forms\Components\TagsInput::make('options_credit_eligibility')
                                    ->label('Krediye Uygunluk Seçenekleri')
                                    ->default($defaults['options_credit_eligibility']),

                                Forms\Components\TagsInput::make('options_swap_option')
                                    ->label('Takas Seçenekleri')
                                    ->default($defaults['options_swap_option']),
                            ]),
                    ])
                    ->columnSpanFull(), // [cite: 15]
            ])
            ->statePath('data'); // [cite: 15]
    }

    public function saveLogoImmediately(Forms\Components\FileUpload $component): void
    {
        $component->saveUploadedFiles();

        $logoPath = collect($component->getState())
            ->first(fn ($file): bool => is_string($file) && filled($file));

        if (! $logoPath) {
            return;
        }

        Setting::updateOrCreate(
            ['key' => 'site_logo'],
            ['value' => $logoPath],
        );
        Setting::clearCache('site_logo');

        Notification::make()
            ->title('Logo kaydedildi ve sitede kullanıma alındı.')
            ->success()
            ->send();
    }

    public function save(): void
    {
        $data = $this->form->getState(); // [cite: 15]

        // SMTP kapatıldığında gizlenen OTP anahtarı önceki değerinde açık kalmamalıdır.
        if (! ($data['smtp_enabled'] ?? false)) {
            $data['email_otp_enabled'] = false;
        }

        if (($data['smtp_enabled'] ?? false) && blank($data['smtp_password'] ?? null) && blank(Setting::getSecret('smtp_password'))) {
            throw ValidationException::withMessages([
                'data.smtp_password' => 'SMTP servisini ilk kez aktif ederken parola girmelisiniz.',
            ]);
        }

        if ($data['phone_otp_enabled'] ?? false) {
            $requiredSmsFields = [
                'sms_api_key' => 'API Key',
                'sms_api_hash' => 'API Hash',
                'sms_sender' => 'Onaylı SMS başlığı',
                'sms_iys_enabled' => 'İYS tercihi',
            ];

            foreach ($requiredSmsFields as $key => $label) {
                $hasValue = in_array($key, ['sms_api_key', 'sms_api_hash'], true)
                    ? filled($data[$key] ?? null) || filled(Setting::getSecret($key))
                    : filled($data[$key] ?? null) || (string) ($data[$key] ?? '') === '0';

                if (! $hasValue) {
                    throw ValidationException::withMessages([
                        "data.{$key}" => "Telefon OTP için {$label} alanı zorunludur.",
                    ]);
                }
            }

            if ((string) ($data['sms_iys_enabled'] ?? '') === '1' && blank($data['sms_iys_list'] ?? null)) {
                throw ValidationException::withMessages([
                    'data.sms_iys_list' => 'İYS sorgusu açıkken alıcı listesi zorunludur.',
                ]);
            }
        }

        foreach ($data as $key => $value) { // [cite: 15]
            if (in_array($key, ['smtp_password', 'sms_api_key', 'sms_api_hash'], true)) {
                if (filled($value)) {
                    Setting::putSecret($key, $value);
                }

                continue;
            }

            Setting::updateOrCreate( // [cite: 15]
                ['key' => $key], // [cite: 15]
                ['value' => $value] // [cite: 15]
            );

            Setting::clearCache($key); // [cite: 15]
        }

        app(DatabaseMailConfigurator::class)->apply();

        Notification::make() // [cite: 15]
            ->title('Ayarlar başarıyla kaydedildi ve önbellek yenilendi.') // [cite: 15]
            ->success() // [cite: 15]
            ->send(); // [cite: 15]
    }
}
