<x-filament-panels::page>
    <div class="flex flex-wrap gap-3">
        <p>Ön moderasyon: {{ \App\Models\Setting::get('property_premoderation', true) ? 'Açık' : 'Kapalı' }}. Düzenlenen içerikler her zaman yeniden onaya alınır.</p>
        <x-filament::button wire:click="setPremoderation(true)">Ön moderasyonu aç</x-filament::button>
        <x-filament::button wire:click="setPremoderation(false)" color="gray">Ön moderasyonu kapat</x-filament::button>
    </div>
    <p>Haber beğenileri: {{ \Illuminate\Support\Facades\DB::table('property_likes')->whereNotNull('post_id')->count() }} · İlan beğenileri: {{ \Illuminate\Support\Facades\DB::table('property_likes')->whereNotNull('listing_id')->count() }} · Bekleyen: {{ \App\Models\PropertyComment::where('status','pending')->count() }} · Engellenen üyeler: {{ \Illuminate\Support\Facades\DB::table('property_interaction_preferences')->where('blocked',true)->count() }}</p>
    {{ $this->table }}
    <x-filament::section heading="Son moderasyon işlemleri">
        @foreach(\Illuminate\Support\Facades\DB::table('property_moderation_logs')->orderByDesc('id')->limit(30)->get() as $log)<p>{{ $log->created_at }} · Yetkili #{{ $log->user_id }} · İçerik #{{ $log->comment_id }} · {{ $log->action }} · {{ $log->reason }}</p>@endforeach
    </x-filament::section>
</x-filament-panels::page>
