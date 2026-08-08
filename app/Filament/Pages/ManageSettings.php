<?php

namespace App\Filament\Pages;

use App\Enums\UserRole;
use App\Models\Setting;
use App\Models\User;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Pages\Page;
use Filament\Notifications\Notification;

class ManageSettings extends Page
{
    protected static ?string $navigationIcon  = 'heroicon-o-cog-6-tooth';
    protected static ?string $navigationGroup = 'Sistem';
    protected static ?string $navigationLabel = 'Site Ayarları';
    protected static ?string $title           = 'Site Genel Ayarları';

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
            'options_type'              => ['Satılık', 'Kiralık'],
            'options_category'          => ['Daire', 'Müstakil Ev', 'Arsa', 'Tarla', 'İşyeri'],
            'options_room_count'        => ['1+0', '1+1', '2+1', '3+1', '4+1'],
            'options_heating_type'      => ['Kombi (Doğalgaz)', 'Klima', 'Merkezi Sistem', 'Sobalı'],
            'options_building_age'      => ['0 (Yeni)', '1-5 Arası', '6-10 Arası', '11-15 Arası', '16-20 Arası', '21 ve üzeri'],
            'options_furniture_status'  => ['Eşyalı', 'Eşyasız'],
            'options_zoning_status'     => ['Tarla', 'Konut İmarlı', 'Ticari İmarlı', 'Sanayi İmarlı', 'Bağ & Bahçe', 'Zeytinlik'],
            'options_deed_status'       => ['Hisseli Tapu', 'Müstakil Parsel', 'Tahsis Tapu', 'Zilliyet'],
            'options_owner_nationality' => ['T.C. Vatandaşı', 'Yabancı'],
            'options_from_who'          => ['Emlak Ofisinden', 'Sahibinden', 'İnşaat Firmasından'],
            'options_credit_eligibility'=> ['Evet', 'Hayır', 'Bilinmiyor'],
            'options_swap_option'       => ['Evet', 'Hayır'],
        ];
    }

    public function mount(): void
    {
        // 1. Veritabanındaki tüm ayarları çekiyoruz[cite: 6]
        $settings = Setting::pluck('value', 'key')->toArray(); //[cite: 6]
        $defaults = $this->getDefaultOptionSettings();

        // 2. Veritabanında boş olan/henüz kaydedilmemiş anahtarlara varsayılan dizileri atıyoruz
        foreach ($defaults as $key => $defaultValues) {
            if (!isset($settings[$key]) || empty($settings[$key])) {
                $settings[$key] = $defaultValues;
            }
        }

        // 3. Formu dolduruyoruz[cite: 6]
        $this->form->fill($settings); //[cite: 6]
    }

    public function form(Form $form): Form
    {
        $defaults = $this->getDefaultOptionSettings();

        return $form
            ->schema([
                Forms\Components\Tabs::make('Settings')
                    ->tabs([
                        // --- TAB 1: SLIDER AYARLARI ---[cite: 15]
                        Forms\Components\Tabs\Tab::make('Ana Sayfa Slider') //[cite: 15]
                            ->icon('heroicon-o-photo') //[cite: 15]
                            ->schema([
                                Forms\Components\TextInput::make('home_slider_title') //[cite: 15]
                                    ->label('Slider Ana Başlığı') //[cite: 15]
                                    ->default('PROPERTY SEARCHING JUST GOT SO EASY') //[cite: 15]
                                    ->placeholder('PROPERTY SEARCHING JUST GOT SO EASY') //[cite: 15]
                                    ->columnSpanFull(), //[cite: 15]

                                Forms\Components\Repeater::make('home_sliders') //[cite: 15]
                                    ->label('Slider Görselleri') //[cite: 15]
                                    ->schema([
                                        Forms\Components\FileUpload::make('image') //[cite: 15]
                                            ->label('Slider Görseli') //[cite: 15]
                                            ->image() //[cite: 15]
                                            ->directory('site-sliders') //[cite: 15]
                                            ->required() //[cite: 15]
                                            ->imageEditor() //[cite: 15]
                                            ->imageCropAspectRatio('1740:1000') //[cite: 15]
                                            ->imageResizeTargetWidth('1740') //[cite: 15]
                                            ->imageResizeTargetHeight('1000') //[cite: 15]
                                            ->imageResizeMode('cover') //[cite: 15]
                                            ->helperText('İdeal Görsel Boyutu: 1740x1000 px.'), //[cite: 15]

                                        Forms\Components\TextInput::make('title')->label('Slider Başlığı'), //[cite: 15]
                                        Forms\Components\TextInput::make('subtitle')->label('Alt Açıklama Metni'), //[cite: 15]
                                        Forms\Components\TextInput::make('link')->label('Görsel Linki (Opsiyonel)'), //[cite: 15]
                                    ])
                                    ->collapsible() //[cite: 15]
                                    ->cloneable() //[cite: 15]
                                    ->defaultItems(1) //[cite: 15]
                                    ->addActionLabel('Yeni Slider Ekle') //[cite: 15]
                                    ->columnSpanFull(), //[cite: 15]
                            ]),

                        // --- TAB 2: GENEL BİLGİLER ---[cite: 15]
                        Forms\Components\Tabs\Tab::make('Genel Bilgiler') //[cite: 15]
                            ->icon('heroicon-o-building-office') //[cite: 15]
                            ->schema([
                                Forms\Components\FileUpload::make('site_logo')->label('Site Logosu')->image()->directory('site'), //[cite: 15]
                                Forms\Components\TextInput::make('site_phone')->label('Telefon Numarası'), //[cite: 15]
                                Forms\Components\TextInput::make('site_email')->label('E-posta Adresi'), //[cite: 15]
                                Forms\Components\Textarea::make('eids_text')->label('EİDS / Onaylı Taşınmaz Metni')->rows(2)->columnSpanFull(), //[cite: 15]
                            ]),

                        // --- TAB 3: İLAN SEÇENEKLERİ ---[cite: 15]
                        Forms\Components\Tabs\Tab::make('İlan Seçenekleri') //[cite: 15]
                            ->icon('heroicon-o-list-bullet') //[cite: 15]
                            ->schema([
                                Forms\Components\TagsInput::make('options_type')
                                    ->label('İlan Tipi Seçenekleri')
                                    ->default($defaults['options_type']),

                                Forms\Components\TagsInput::make('options_category')
                                    ->label('Gayrimenkul Türü Seçenekleri')
                                    ->default($defaults['options_category']),

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
                    ->columnSpanFull(), //[cite: 15]
            ])
            ->statePath('data'); //[cite: 15]
    }

    public function save(): void
    {
        $data = $this->form->getState(); //[cite: 15]

        foreach ($data as $key => $value) { //[cite: 15]
            Setting::updateOrCreate( //[cite: 15]
                ['key' => $key], //[cite: 15]
                ['value' => $value] //[cite: 15]
            );

            Setting::clearCache($key); //[cite: 15]
        }

        Notification::make() //[cite: 15]
            ->title('Ayarlar başarıyla kaydedildi ve önbellek yenilendi.') //[cite: 15]
            ->success() //[cite: 15]
            ->send(); //[cite: 15]
    }
}
