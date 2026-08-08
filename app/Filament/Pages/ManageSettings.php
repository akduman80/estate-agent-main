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

    public function mount(): void
    {
        $settings = Setting::pluck('value', 'key')->toArray();
        $this->form->fill($settings);
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Tabs::make('Settings')
                                     ->tabs([
                                         // --- TAB 1: SLIDER AYARLARI ---
                                         Forms\Components\Tabs\Tab::make('Ana Sayfa Slider')
                                                                  ->icon('heroicon-o-photo')
                                                                  ->schema([
                                                                      Forms\Components\TextInput::make('home_slider_title')
                                                                                                ->label('Slider Ana Başlığı')
                                                                                                ->default('PROPERTY SEARCHING JUST GOT SO EASY')
                                                                                                ->placeholder('PROPERTY SEARCHING JUST GOT SO EASY')
                                                                                                ->columnSpanFull(),
                                                                      Forms\Components\Repeater::make('home_sliders')
                                                                                               ->label('Slider Görselleri')
                                                                                               ->schema([
                                                                                                   Forms\Components\FileUpload::make('image')
                                                                                                                              ->label('Slider Görseli')
                                                                                                                              ->image()
                                                                                                                              ->directory('site-sliders')
                                                                                                                              ->required()
                                                                                                                              ->imageEditor() // Görsel düzenleyiciyi aktif eder
                                                                                                                              ->imageCropAspectRatio('1740:1000') // Kullanıcı kırparken tam 1740x1000 oranına kilitler
                                                                                                                              ->imageResizeTargetWidth('1740')   // Resim kaydedilirken otomatik genişliği 1740px yapar
                                                                                                                              ->imageResizeTargetHeight('1000')  // Resim kaydedilirken otomatik yüksekliği 1000px yapar
                                                                                                                              ->imageResizeMode('cover')         // Resmi oranları bozmadan tam kırparak boyutlandırır
                                                                                                                              ->helperText('İdeal Görsel Boyutu: 1740x1000 px. Yüklenen görsel bu boyutlara göre otomatik kırpılacaktır.'),

                                                                                                   Forms\Components\TextInput::make('title')
                                                                                                                             ->label('Slider Başlığı')
                                                                                                                             ->placeholder('PROPERTY SEARCHING JUST GOT SO EASY'),

                                                                                                   Forms\Components\TextInput::make('subtitle')
                                                                                                                             ->label('Alt Açıklama Metni')
                                                                                                                             ->placeholder('Ticaret Bakanlığı EİDS güvencesiyle...'),

                                                                                                   Forms\Components\TextInput::make('link')
                                                                                                                             ->label('Görsel Linki (Opsiyonel)')
                                                                                                                             ->placeholder('https://...'),
                                                                                               ])
                                                                                               ->collapsible()
                                                                                               ->cloneable()
                                                                                               ->defaultItems(1)
                                                                                               ->addActionLabel('Yeni Slider Ekle')
                                                                                               ->columnSpanFull(),
                                                                  ]),

                                         // --- TAB 2: GENEL & İLETİŞİM ---
                                         Forms\Components\Tabs\Tab::make('Genel Bilgiler')
                                                                  ->icon('heroicon-o-building-office')
                                                                  ->schema([
                                                                      Forms\Components\FileUpload::make('site_logo')
                                                                                                 ->label('Site Logosu')
                                                                                                 ->image()
                                                                                                 ->directory('site')
                                                                                                 ->helperText('Önerilen Boyut: 250x60 px (PNG/WebP şeffaf arka plan).'),

                                                                      Forms\Components\TextInput::make('site_phone')
                                                                                                ->label('Telefon Numarası')
                                                                                                ->placeholder('+90 (850) 000 00 00'),

                                                                      Forms\Components\TextInput::make('site_email')
                                                                                                ->label('E-posta Adresi')
                                                                                                ->placeholder('info@estate.com'),

                                                                      Forms\Components\Textarea::make('eids_text')
                                                                                               ->label('EİDS / Onaylı Taşınmaz Metni')
                                                                                               ->rows(2)
                                                                                               ->columnSpanFull(),
                                                                  ]),
                                         // app/Filament/Pages/ManageSettings.php

                                         Forms\Components\Tabs\Tab::make('İlan Seçenekleri')
                                                                  ->icon('heroicon-o-list-bullet')
                                                                  ->schema([
                                                                      // 1. İlan Tipi
                                                                      Forms\Components\TagsInput::make('options_type')
                                                                                                ->label('İlan Tipi Seçenekleri')
                                                                                                ->placeholder('Mevcut: Satılık, Kiralık (Yeni eklemek için yazıp Enter\'a basın)')
                                                                                                ->default(['Satılık', 'Kiralık']),

                                                                      // 2. İmar Durumu
                                                                      Forms\Components\TagsInput::make('options_zoning_status')
                                                                                                ->label('İmar Durumu Seçenekleri')
                                                                                                ->placeholder('Mevcut: Tarla, Konut İmarlı, Ticari İmarlı, Sanayi İmarlı, Bağ & Bahçe, Zeytinlik')
                                                                                                ->default(['Tarla', 'Konut İmarlı', 'Ticari İmarlı', 'Sanayi İmarlı', 'Bağ & Bahçe', 'Zeytinlik']),

                                                                      // 3. Tapu Durumu
                                                                      Forms\Components\TagsInput::make('options_deed_status')
                                                                                                ->label('Tapu Durumu Seçenekleri')
                                                                                                ->placeholder('Mevcut: Hisseli Tapu, Müstakil Parsel, Tahsis Tapu, Zilliyet')
                                                                                                ->default(['Hisseli Tapu', 'Müstakil Parsel', 'Tahsis Tapu', 'Zilliyet']),

                                                                      // 4. Gayrimenkul Sahibi Uyruğu
                                                                      Forms\Components\TagsInput::make('options_owner_nationality')
                                                                                                ->label('Gayrimenkul Sahibi Seçenekleri')
                                                                                                ->placeholder('Mevcut: T.C. Vatandaşı, Yabancı')
                                                                                                ->default(['T.C. Vatandaşı', 'Yabancı']),

                                                                      // 5. Kimden
                                                                      Forms\Components\TagsInput::make('options_from_who')
                                                                                                ->label('Kimden Seçenekleri')
                                                                                                ->placeholder('Mevcut: Emlak Ofisinden, Sahibinden, İnşaat Firmasından')
                                                                                                ->default(['Emlak Ofisinden', 'Sahibinden', 'İnşaat Firmasından']),

                                                                      // 6. Krediye Uygunluk
                                                                      Forms\Components\TagsInput::make('options_credit_eligibility')
                                                                                                ->label('Krediye Uygunluk Seçenekleri')
                                                                                                ->placeholder('Mevcut: Evet, Hayır, Bilinmiyor')
                                                                                                ->default(['Evet', 'Hayır', 'Bilinmiyor']),

                                                                      // 7. Takaslı
                                                                      Forms\Components\TagsInput::make('options_swap_option')
                                                                                                ->label('Takas Seçenekleri')
                                                                                                ->placeholder('Mevcut: Evet, Hayır')
                                                                                                ->default(['Evet', 'Hayır']),

                                                                      // 8. İl Listesi
                                                                      Forms\Components\TagsInput::make('options_cities')
                                                                                                ->label('İl Seçenekleri')
                                                                                                ->placeholder('Mevcut: Osmaniye, İstanbul, Ankara')
                                                                                                ->default(['Osmaniye', 'İstanbul', 'Ankara']),

                                                                      // 9. İlçe Listesi
                                                                      Forms\Components\TagsInput::make('options_districts')
                                                                                                ->label('İlçe Seçenekleri')
                                                                                                ->placeholder('Mevcut: Sumbas, Kadirli, Kadıköy, Beşiktaş, Çankaya')
                                                                                                ->default(['Sumbas', 'Kadirli', 'Kadıköy', 'Beşiktaş', 'Çankaya']),
                                                                  ]),
                                     ])
                                     ->columnSpanFull(),
            ])
            ->statePath('data');
    }

    public function save(): void
    {
        $data = $this->form->getState();

        foreach ($data as $key => $value) {
            Setting::updateOrCreate(
                ['key' => $key],
                ['value' => $value]
            );

            // Redis üzerindeki eski cache'i siliyoruz
            Setting::clearCache($key);
        }

        Notification::make()
                    ->title('Ayarlar başarıyla kaydedildi ve önbellek (Redis) yenilendi.')
                    ->success()
                    ->send();
    }
}
