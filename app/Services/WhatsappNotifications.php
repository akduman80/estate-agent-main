<?php
namespace App\Services;

use App\Models\{User, Listing, WhatsappSetting, WhatsappOutbox};
use App\Filament\Resources\UserResource;
use App\Filament\Portal\Resources\ListingResource;
use Illuminate\Support\Str;

class WhatsappNotifications
{
    public function member(User $user): void
    {
        if (! $user->email_verified_at) return;
        $this->record('member', (string)$user->id, [
            'Ziyade Emlak | Yeni Üye', 'Ad Soyad: '.$user->name, 'Üye No: '.$user->id,
            'Tarih: '.now('Europe/Istanbul')->format('d.m.Y H:i'),
            'Üyeyi incele: '.$this->absolute(UserResource::getUrl('edit', ['record'=>$user], panel:'admin')),
        ]);
    }

    public function listing(Listing $listing): void
    {
        $this->record('listing', $listing->id.':'.Str::uuid(), array_filter([
            'Ziyade Emlak | İlan Onayı Bekliyor', 'İlan No: '.$listing->id,
            filled($listing->title) ? 'Başlık: '.$listing->title : null,
            $listing->user ? 'Üye: '.$listing->user->name : null,
            ($location = collect([$listing->city_id, $listing->district_id])->filter()->implode(' / ')) ? 'Konum: '.$location : null,
            $listing->price !== null ? 'Fiyat: '.number_format((float)$listing->price, 2, ',', '.').' '.$listing->currency : null,
            'Tarih: '.now('Europe/Istanbul')->format('d.m.Y H:i'),
            'İlanı incele: '.$this->absolute(ListingResource::getUrl('edit', ['record'=>$listing], panel:'admin')),
        ]));
    }

    public function record(string $type, string $id, array $lines): ?WhatsappOutbox
    {
        $settings = WhatsappSetting::current();
        if (! $settings->enabled || ! preg_match('/^[1-9][0-9]{9,14}$/D', $settings->number)) return null;
        if (($type==='member' && !$settings->members_enabled) || ($type==='listing' && !$settings->listings_enabled)) return null;
        return WhatsappOutbox::firstOrCreate(['event_type'=>$type, 'event_id'=>$id, 'recipient'=>$settings->number],
            ['payload'=>['text'=>implode("\n", $lines)], 'available_at'=>now()]);
    }

    private function absolute(string $url): string
    {
        return rtrim(config('whatsapp.site_url'), '/').'/'.ltrim(parse_url($url, PHP_URL_PATH), '/');
    }
}
