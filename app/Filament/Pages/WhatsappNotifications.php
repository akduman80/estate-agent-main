<?php
namespace App\Filament\Pages;

use App\Enums\UserRole;
use App\Models\{WhatsappSetting, WhatsappOutbox};
use App\Services\EvolutionClient;
use App\Services\WhatsappNotifications as Notifications;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Pages\Page;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;

class WhatsappNotifications extends Page
{
    protected static ?string $navigationIcon='heroicon-o-chat-bubble-left-right';
    protected static ?string $navigationGroup='Sistem';
    protected static ?string $navigationLabel='WhatsApp Bildirimleri';
    protected static ?string $title='WhatsApp Bildirimleri';
    protected static string $view='filament.pages.whatsapp-notifications';
    public ?array $data=[];
    public ?string $qr=null;

    public static function canAccess(): bool { return auth()->user()?->role === UserRole::SUPERVISOR; }
    private function guardAction(string $action, int $max=5): void
    {
        abort_unless(static::canAccess(),403);
        $key='whatsapp:'.$action.':'.auth()->id();
        abort_if(RateLimiter::tooManyAttempts($key,$max),429);
        RateLimiter::hit($key,60);
    }
    public function mount(): void
    {
        abort_unless(static::canAccess(),403);
        $this->form->fill(WhatsappSetting::current()->only(['enabled','members_enabled','listings_enabled','number']));
    }
    public function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Toggle::make('enabled')->label('Yönetici bildirimleri açık'),
            Forms\Components\Toggle::make('members_enabled')->label('Yeni üyelik bildirimi'),
            Forms\Components\Toggle::make('listings_enabled')->label('İlan onay talebi bildirimi'),
            Forms\Components\TextInput::make('number')->label('Alıcı WhatsApp numarası')->placeholder('905XXXXXXXXX')
                ->helperText('Ülke koduyla yalnız rakam girin. Değişiklikten sonraki yeni olaylar bu alıcıya gider; bekleyen kayıtların alıcısı değişmez.')
                ->required()->regex('/^[1-9][0-9]{9,14}$/D')->maxLength(15),
        ])->statePath('data');
    }
    public function save(): void
    {
        $this->guardAction('save');
        WhatsappSetting::current()->update($this->form->getState());
        Notification::make()->title('Bildirim ayarları kaydedildi')->success()->send();
    }
    public function checkConnection(): void
    {
        $this->guardAction('check');
        WhatsappSetting::current()->update(['connection_state'=>app(EvolutionClient::class)->state(), 'checked_at'=>now()]);
    }
    public function testMessage(): void
    {
        $this->guardAction('test',1);
        $settings=WhatsappSetting::current();
        if (!$settings->enabled || $settings->connection_state!=='open') {
            Notification::make()->title('Önce bildirimleri açın, hesabı bağlayın ve bağlantıyı kontrol edin.')->warning()->send();
            return;
        }
        app(Notifications::class)->record('test',(string)Str::uuid(),['Ziyade Emlak | Yönetici test bildirimi','Tarih: '.now('Europe/Istanbul')->format('d.m.Y H:i')]);
        Notification::make()->title('Test bildirimi kuyruğa alındı')->success()->send();
    }
    public function connect(): void
    {
        $this->guardAction('connect',2);
        $this->qr=null;
        try {
            $client=app(EvolutionClient::class);
            $r=$client->request('GET','/instance/connect/'.$client->instancePath());
            $qr=$r->json('base64');
            if ($r->successful() && is_string($qr) && preg_match('#^data:image/png;base64,[A-Za-z0-9+/=]+$#D',$qr)) $this->qr=$qr;
        } catch (\Throwable) {}
        if (!$this->qr) Notification::make()->title('QR alınamadı; bağlantıyı kontrol edin. Açık oturum varsa yeniden QR gerekmez.')->warning()->send();
    }
    public function retry(int $id): void
    {
        $this->guardAction('retry',3);
        // Only explicit rate-limit failures are known not to have been accepted.
        WhatsappOutbox::whereKey($id)->where('status','failed')->where('error_code','rate_limited')
            ->where('attempts','<',8)->update(['status'=>'retry','available_at'=>now()]);
    }
    public function records()
    {
        abort_unless(static::canAccess(),403);
        return WhatsappOutbox::latest('id')->limit(30)->get();
    }
    public function connection(): WhatsappSetting
    {
        abort_unless(static::canAccess(),403);
        return WhatsappSetting::current();
    }
}
