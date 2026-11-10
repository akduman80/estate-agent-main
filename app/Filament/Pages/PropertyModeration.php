<?php

namespace App\Filament\Pages;

use App\Enums\UserRole;
use App\Models\PropertyComment;
use App\Models\Setting;
use App\Services\PropertyInteractions;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

class PropertyModeration extends Page implements Tables\Contracts\HasTable
{
    use Tables\Concerns\InteractsWithTable;

    protected static ?string $navigationIcon = 'heroicon-o-chat-bubble-left-right';

    protected static ?string $navigationGroup = 'İçerik';

    protected static ?string $navigationLabel = 'Yorum ve Etkileşim Yönetimi';

    protected static ?string $title = 'Yorum ve Etkileşim Yönetimi';

    protected static string $view = 'filament.pages.property-moderation';

    public static function canAccess(): bool
    {
        return auth()->user()?->role === UserRole::SUPERVISOR;
    }

    public function setPremoderation(bool $enabled): void
    {
        abort_unless(static::canAccess(), 403);
        DB::transaction(function () use ($enabled) {
            Setting::updateOrCreate(['key' => 'property_premoderation'], ['value' => $enabled]);
            DB::table('property_moderation_logs')->insert(['user_id' => auth()->id(), 'action' => 'premoderation', 'reason' => $enabled ? 'Ön moderasyon açıldı.' : 'Ön moderasyon kapatıldı.', 'created_at' => now(), 'updated_at' => now()]);
        });
        Setting::clearCache('property_premoderation');
        Notification::make()->title('Moderasyon ayarı kaydedildi.')->success()->send();
    }

    public function table(Table $table): Table
    {
        return $table->query(PropertyComment::query()->with(['user', 'listing', 'post'])->withCount('reports'))
            ->defaultSort('created_at', 'desc')
            ->columns([
                Tables\Columns\TextColumn::make('id')->sortable(),
                Tables\Columns\TextColumn::make('kind')->label('Tür')->formatStateUsing(fn ($state) => ['comment' => 'Haber yorumu', 'reply' => 'Haber yanıtı', 'question' => 'İlan sorusu', 'answer' => 'İlan cevabı'][$state] ?? $state),
                Tables\Columns\TextColumn::make('user.name')->label('Üye')->searchable(),
                Tables\Columns\TextColumn::make('body')->label('İçerik')->wrap()->limit(250)->searchable(),
                Tables\Columns\TextColumn::make('status')->label('Durum')->badge(),
                Tables\Columns\TextColumn::make('reports_count')->label('Şikâyetler')->sortable(),
                Tables\Columns\TextColumn::make('created_at')->label('Tarih')->dateTime('d.m.Y H:i')->sortable(),
            ])->filters([
                Tables\Filters\SelectFilter::make('status')->label('Durum')->options(['pending' => 'Bekleyen', 'approved' => 'Onaylı', 'rejected' => 'Reddedilen', 'deleted' => 'Kaldırılan']),
                Tables\Filters\SelectFilter::make('kind')->label('İçerik türü')->options(['comment' => 'Haber yorumu', 'reply' => 'Haber yanıtı', 'question' => 'İlan sorusu', 'answer' => 'İlan cevabı']),
                Tables\Filters\Filter::make('reported')->label('Şikâyet edilenler')->query(fn ($q) => $q->has('reports')),
                Tables\Filters\Filter::make('blocked')->label('Engellenen üyelerin içerikleri')->query(fn ($q) => $q->whereIn('user_id', DB::table('property_interaction_preferences')->where('blocked', true)->select('user_id'))),
            ])->actions([
                ...collect(['approved' => 'Onayla', 'rejected' => 'Reddet', 'deleted' => 'Kaldır'])->map(fn ($label, $state) => Tables\Actions\Action::make($state)->label($label)->visible(fn ($record) => $record->status !== 'deleted')->form([Textarea::make('reason')->label('Gerekçe')->required()->minLength(3)->maxLength(500)])->action(function ($record, array $data) use ($state) {
                    app(PropertyInteractions::class)->moderate($record, $state, $data['reason']);
                }))->values()->all(),
                Tables\Actions\Action::make('block')->label('Üye engelini değiştir')->requiresConfirmation()->form([Textarea::make('reason')->label('Gerekçe')->required()->minLength(3)->maxLength(500)])->action(function ($record, array $data) {
                    abort_unless(static::canAccess() && $record->user_id && $record->user_id !== auth()->id(), 403);
                    DB::transaction(function () use ($record, $data) {
                        $pref = DB::table('property_interaction_preferences')->where('user_id', $record->user_id)->first();
                        DB::table('property_interaction_preferences')->updateOrInsert(['user_id' => $record->user_id], ['blocked' => ! ($pref?->blocked ?? false), 'updated_at' => now()]);
                        DB::table('property_moderation_logs')->insert(['user_id' => auth()->id(), 'comment_id' => $record->id, 'action' => 'block-toggle', 'reason' => $data['reason'], 'created_at' => now(), 'updated_at' => now()]);
                    });
                }),
            ])->bulkActions([
                Tables\Actions\BulkAction::make('approve')->label('Seçilenleri onayla')->requiresConfirmation()->form([Textarea::make('reason')->label('Gerekçe')->required()->minLength(3)->maxLength(500)])->action(function (Collection $records, array $data) {
                    abort_unless(static::canAccess() && $records->count() <= 100, 403);
                    DB::transaction(function () use ($records, $data) {
                        foreach ($records as $record) {
                            if ($record->status === 'pending') {
                                app(PropertyInteractions::class)->moderate($record, 'approved', $data['reason']);
                            }
                        }
                    });
                })->deselectRecordsAfterCompletion(),
            ]);
    }
}
