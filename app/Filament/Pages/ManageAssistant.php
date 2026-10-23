<?php

namespace App\Filament\Pages;

use App\Enums\UserRole;
use App\Models\Setting;
use App\Services\Assistant\KnowledgeTool;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Support\Facades\Cache;

class ManageAssistant extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-chat-bubble-left-right';

    protected static ?string $navigationGroup = 'Sistem';

    protected static ?string $navigationLabel = 'ZYD Emlak Asistanı';

    protected static ?string $title = 'ZYD Emlak Asistanı';

    protected static string $view = 'filament.pages.manage-assistant';

    public ?array $data = [];

    public static function canAccess(): bool
    {
        return auth()->user()?->role === UserRole::SUPERVISOR;
    }

    public function mount(): void
    {
        abort_unless(static::canAccess(), 403);
        $this->form->fill([
            'sources' => Setting::get('assistant_sources', app(KnowledgeTool::class)->defaults()),
            'enabled' => Setting::get('assistant_enabled', true),
            'welcome' => Setting::get('assistant_welcome', 'ZYDEMLAK’a hoş geldiniz. Ben ZYD Emlak Asistanı. Size nasıl yardımcı olabilirim?'),
            'daily_limit' => Setting::get('assistant_daily_limit', 200),
            'tools' => Setting::get('assistant_tools', ['search', 'compare', 'open', 'details', 'documents', 'create', 'calculator', 'help']),
            'quick_actions' => Setting::get('assistant_quick_actions', ['Satılık ev ara', 'Kiralık ev ara', 'Arsa ara', 'İlanları karşılaştır', 'Sözleşme hazırla', 'Tapu masrafı hesapla', 'Kira artışı hesapla', 'Emlak hakkında soru sor']),
        ]);
    }

    public function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Toggle::make('enabled')->label('Asistanı etkinleştir')->helperText('Sunucu ASSISTANT_ENABLED ayarı da açık olmalıdır.'),
            Forms\Components\Textarea::make('welcome')->label('Karşılama mesajı')->required()->maxLength(1500),
            Forms\Components\TextInput::make('daily_limit')->label('Oturum başına günlük istek sınırı')->numeric()->integer()->minValue(1)->maxValue(2000)->required(),
            Forms\Components\CheckboxList::make('tools')->label('Etkin araçlar')->options(['search' => 'İlan arama', 'compare' => 'Karşılaştırma', 'open' => 'İlan açma', 'details' => 'İlan detayları', 'documents' => 'Belgeler', 'create' => 'İlan ekranına yönlendirme', 'calculator' => 'Hesaplama araçlarına yönlendirme', 'help' => 'Kullanım yardımı']),
            Forms\Components\TagsInput::make('quick_actions')->label('Hızlı işlem komutları')->placeholder('Komut yazıp Enter’a basın')->nestedRecursiveRules(['string', 'max:120'])->rules(['array', 'max:12']),
            Forms\Components\Repeater::make('sources')->label('Kontrol edilmiş resmî bilgi kaynakları')->addActionLabel('Kaynak ekle')->maxItems(12)->schema([
                Forms\Components\TextInput::make('title')->label('Kaynak başlığı')->required()->maxLength(160),
                Forms\Components\TextInput::make('url')->label('HTTPS kaynak bağlantısı')->required()->url()->maxLength(1500)->rules([fn () => function ($attribute, $value, $fail) {
                    $host = parse_url($value, PHP_URL_HOST);
                    if (parse_url($value, PHP_URL_SCHEME) !== 'https' || ! is_string($host) || ! preg_match('/(^|\.)gov\.tr$/i', $host)) {
                        $fail('Yalnız HTTPS kullanan resmî gov.tr kaynakları eklenebilir.');
                    }
                }]),
                Forms\Components\DatePicker::make('checked_at')->label('İçeriği kontrol ettiğiniz tarih')->required()->maxDate(now()),
            ])->helperText('Bağlantılar kaynak yönlendirmesidir. Oran hesaplamaları ayrı teyit ve geçerlilik kontrolü kullanır.'),
        ])->statePath('data');
    }

    public function save(): void
    {
        abort_unless(static::canAccess(), 403);
        $data = $this->form->getState();
        foreach (['enabled', 'welcome', 'daily_limit', 'tools', 'quick_actions', 'sources'] as $key) {
            Setting::updateOrCreate(['key' => 'assistant_'.$key], ['value' => $data[$key] ?? []]);
            Setting::clearCache('assistant_'.$key);
        }
        Notification::make()->title('Asistan ayarları kaydedildi')->success()->send();
    }

    public function counts(): array
    {
        return ['helpful' => (int) Cache::get('assistant:feedback:helpful:'.now()->format('Y-m-d'), 0), 'unhelpful' => (int) Cache::get('assistant:feedback:unhelpful:'.now()->format('Y-m-d'), 0), 'requests' => (int) Cache::get('assistant:requests:'.now()->format('Y-m-d'), 0), 'errors' => (int) Cache::get('assistant:errors:'.now()->format('Y-m-d'), 0)];
    }

    public function geminiCounts(): array
    {
        $day = now()->format('Y-m-d');
        $out = [];
        foreach (['calls','errors','input','output','thought','reserved'] as $name) $out[$name] = (int) Cache::get('assistant:gemini:'.$name.':'.$day, 0);
        $in = config('assistant.gemini.input_usd_per_million'); $output = config('assistant.gemini.output_usd_per_million');
        $out['estimated_usd'] = is_numeric($in) && is_numeric($output) && $in >= 0 && $output >= 0
            ? round(($out['input'] * $in + ($out['output'] + $out['thought']) * $output) / 1000000, 6) : null;
        return $out;
    }
}
