<?php

namespace App\Filament\Resources;

use App\Enums\AccountStatus;
use App\Enums\BusinessApprovalStatus;
use App\Filament\Resources\BusinessProfileResource\Pages;
use App\Mail\AccountDecisionMail;
use App\Models\BusinessProfile;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Mail;

class BusinessProfileResource extends Resource
{
    protected static ?string $model = BusinessProfile::class;

    protected static ?string $navigationIcon = 'heroicon-o-building-office-2';

    protected static ?string $navigationLabel = 'Emlak İşletmeleri';

    protected static ?string $modelLabel = 'Emlak İşletmesi';

    protected static ?string $pluralModelLabel = 'Emlak İşletmeleri';

    public static function form(Form $form): Form
    {
        return $form->schema([
            TextInput::make('office_name')->label('Ofis Adı')->disabled(),
            TextInput::make('license_number')->label('Yetki Belgesi No')->disabled(),
            TextInput::make('tax_office')->label('Vergi Dairesi')->disabled(),
            TextInput::make('city')->label('İl')->disabled(),
            TextInput::make('district')->label('İlçe')->disabled(),
            TextInput::make('neighborhood')->label('Mahalle / Köy')->disabled(),
            RichEditor::make('address')->label('Adres')->disabled()->columnSpanFull(),
            RichEditor::make('review_note')->label('İnceleme Notu')->columnSpanFull(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table->columns([
            Tables\Columns\TextColumn::make('office_name')->label('İşletme')->searchable(),
            Tables\Columns\TextColumn::make('user.name')->label('Yetkili'),
            Tables\Columns\TextColumn::make('user.email')->label('E-posta'),
            Tables\Columns\TextColumn::make('license_number')->label('Yetki Belgesi'),
            Tables\Columns\TextColumn::make('approval_status')->label('Durum')->badge(),
            Tables\Columns\TextColumn::make('created_at')->label('Başvuru')->dateTime('d.m.Y H:i'),
        ])->filters([
            Tables\Filters\SelectFilter::make('approval_status')->label('Durum')->options([
                'pending' => 'Onay Bekliyor', 'approved' => 'Onaylı', 'rejected' => 'Reddedildi', 'suspended' => 'Askıda',
            ]),
        ])->actions([
            Tables\Actions\EditAction::make()->label('İncele'),
            Tables\Actions\Action::make('approve')->label('Onayla')->color('success')->icon('heroicon-o-check-circle')
                ->requiresConfirmation()->visible(fn (BusinessProfile $record): bool => $record->approval_status !== BusinessApprovalStatus::APPROVED)
                ->action(function (BusinessProfile $record): void {
                    $record->update(['approval_status' => BusinessApprovalStatus::APPROVED, 'reviewed_by' => auth()->id(), 'reviewed_at' => now()]);
                    $record->user->update(['account_status' => AccountStatus::ACTIVE]);
                    Mail::to($record->user->email)->send(new AccountDecisionMail($record->user, 'onaylandı'));
                    Notification::make()->success()->title('İşletme onaylandı')->send();
                }),
            Tables\Actions\Action::make('reject')->label('Reddet')->color('danger')->icon('heroicon-o-x-circle')
                ->form([RichEditor::make('note')->label('Ret gerekçesi')->required()])
                ->visible(fn (BusinessProfile $record): bool => $record->approval_status === BusinessApprovalStatus::PENDING)
                ->action(function (BusinessProfile $record, array $data): void {
                    $record->update(['approval_status' => BusinessApprovalStatus::REJECTED, 'review_note' => $data['note'], 'reviewed_by' => auth()->id(), 'reviewed_at' => now()]);
                    $record->user->update(['account_status' => AccountStatus::REJECTED]);
                    Mail::to($record->user->email)->send(new AccountDecisionMail($record->user, 'reddedildi', $data['note']));
                    Notification::make()->warning()->title('İşletme reddedildi')->send();
                }),
        ]);
    }

    public static function getPages(): array
    {
        return ['index' => Pages\ListBusinessProfiles::route('/'), 'edit' => Pages\EditBusinessProfile::route('/{record}/edit')];
    }

    public static function canCreate(): bool
    {
        return false;
    }
}
