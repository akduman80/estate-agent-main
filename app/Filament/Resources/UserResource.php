<?php

namespace App\Filament\Resources;

use App\Enums\AccountStatus;
use App\Enums\BusinessApprovalStatus;
use App\Enums\UserRole;
use App\Filament\Portal\Resources\ListingResource;
use App\Filament\Resources\UserResource\Pages;
use App\Models\User;
use App\Rules\TurkishIdentityNumber;
use App\Services\ListingQuotaService;
use App\Services\TurkeyAddressService;
use Filament\Forms;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Forms\Set;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Validation\Rules\Password;

class UserResource extends Resource
{
    protected static ?string $model = User::class;

    protected static ?string $navigationIcon = 'heroicon-o-users';

    protected static ?string $navigationLabel = 'Üyeler';

    protected static ?string $modelLabel = 'Üye';

    protected static ?string $pluralModelLabel = 'Üyeler';

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make('Hesap bilgileri')
                ->schema([
                    Forms\Components\Grid::make(['default' => 1, 'md' => 2])->schema([
                        TextInput::make('first_name')->label('Ad')->required()->maxLength(100),
                        TextInput::make('last_name')->label('Soyad')->required()->maxLength(100),
                        TextInput::make('email')
                            ->label('E-posta')
                            ->email()
                            ->required()
                            ->maxLength(255)
                            ->unique(ignoreRecord: true),
                        TextInput::make('phone')
                            ->label('Cep telefonu')
                            ->tel()
                            ->required(fn (Get $get): bool => in_array(static::roleValue($get('role')), [UserRole::INDIVIDUAL->value, UserRole::AGENT->value], true))
                            ->maxLength(24),
                        Forms\Components\Select::make('role')
                            ->label('Üyelik türü')
                            ->options([
                                UserRole::INDIVIDUAL->value => 'Bireysel',
                                UserRole::AGENT->value => 'Emlak işletmesi',
                                UserRole::EDITOR->value => 'Editör',
                                UserRole::SUPERVISOR->value => 'Supervisor',
                            ])
                            ->required()
                            ->live(),
                        Forms\Components\Select::make('account_status')
                            ->label('Hesap durumu')
                            ->options([
                                AccountStatus::ACTIVE->value => 'Aktif',
                                AccountStatus::PENDING_EMAIL->value => 'E-posta doğrulaması bekliyor',
                                AccountStatus::PENDING_APPROVAL->value => 'Supervisor onayı bekliyor',
                                AccountStatus::REJECTED->value => 'Reddedildi',
                                AccountStatus::SUSPENDED->value => 'Askıda',
                            ])
                            ->default(AccountStatus::ACTIVE->value)
                            ->required(),
                        TextInput::make('password')
                            ->label(fn (string $operation): string => $operation === 'create' ? 'Şifre' : 'Yeni şifre')
                            ->password()
                            ->revealable()
                            ->required(fn (string $operation): bool => $operation === 'create')
                            ->rule(Password::min(10)->letters()->mixedCase()->numbers())
                            ->dehydrated(fn (?string $state): bool => filled($state))
                            ->afterStateHydrated(fn (TextInput $component) => $component->state(null))
                            ->helperText(fn (string $operation): string => $operation === 'create'
                                ? 'En az 10 karakter; büyük-küçük harf ve rakam içermeli.'
                                : 'Boş bırakırsanız mevcut şifre değişmez.'),
                        TextInput::make('listing_limit_override')
                            ->label('Özel aktif ilan limiti')
                            ->numeric()
                            ->minValue(0)
                            ->maxValue(10000)
                            ->nullable()
                            ->visible(fn (Get $get): bool => in_array(static::roleValue($get('role')), [UserRole::INDIVIDUAL->value, UserRole::AGENT->value], true))
                            ->helperText('Boş bırakılırsa bireysel üyede 10, emlakçıda 100 kullanılır.'),
                    ]),
                    Forms\Components\Grid::make(['default' => 1, 'md' => 3])->schema([
                        Forms\Components\Toggle::make('email_verified')
                            ->label('E-posta doğrulandı')
                            ->default(true),
                        Forms\Components\Toggle::make('phone_verified')
                            ->label('Telefon doğrulandı')
                            ->default(false)
                            ->visible(fn (Get $get): bool => in_array(static::roleValue($get('role')), [UserRole::INDIVIDUAL->value, UserRole::AGENT->value], true)),
                        Forms\Components\Toggle::make('two_factor_enabled')
                            ->label('SMS ile iki aşamalı giriş')
                            ->default(false)
                            ->visible(fn (Get $get): bool => in_array(static::roleValue($get('role')), [UserRole::INDIVIDUAL->value, UserRole::AGENT->value], true)),
                    ]),
                ]),

            Forms\Components\Section::make('Konum ve kimlik bilgileri')
                ->visible(fn (Get $get): bool => in_array(static::roleValue($get('role')), [UserRole::INDIVIDUAL->value, UserRole::AGENT->value], true))
                ->schema([
                    Forms\Components\Grid::make(['default' => 1, 'md' => 3])->schema([
                        Forms\Components\Select::make('city')
                            ->label('İl')
                            ->options(TurkeyAddressService::getCities())
                            ->searchable()
                            ->live()
                            ->afterStateUpdated(function (Set $set): void {
                                $set('district', null);
                                $set('neighborhood', null);
                            })
                            ->required(),
                        Forms\Components\Select::make('district')
                            ->label('İlçe')
                            ->options(fn (Get $get): array => TurkeyAddressService::getDistricts($get('city')))
                            ->searchable()
                            ->live()
                            ->afterStateUpdated(fn (Set $set) => $set('neighborhood', null))
                            ->required(),
                        Forms\Components\Select::make('neighborhood')
                            ->label('Mahalle / Köy')
                            ->options(fn (Get $get): array => TurkeyAddressService::getNeighborhoods($get('city'), $get('district')))
                            ->searchable()
                            ->required(),
                    ]),
                    TextInput::make('identity_number')
                        ->label('Yeni T.C. Kimlik Numarası')
                        ->password()
                        ->revealable()
                        ->length(11)
                        ->rules(fn (Get $get): array => static::roleValue($get('role')) === UserRole::INDIVIDUAL->value ? [new TurkishIdentityNumber] : [])
                        ->required(fn (string $operation, Get $get, ?User $record): bool => static::roleValue($get('role')) === UserRole::INDIVIDUAL->value
                            && ($operation === 'create' || blank($record?->identity_number_hash)))
                        ->dehydrated(fn (?string $state): bool => filled($state))
                        ->afterStateHydrated(fn (TextInput $component) => $component->state(null))
                        ->visible(fn (Get $get): bool => static::roleValue($get('role')) === UserRole::INDIVIDUAL->value)
                        ->helperText(fn (?User $record): string => $record?->identity_number_hash
                            ? 'Güvenlik nedeniyle mevcut numara gösterilmez. Boş bırakırsanız değişmez.'
                            : '11 haneli geçerli T.C. Kimlik Numarası girin.'),
                ]),

            Forms\Components\Section::make('Emlak işletmesi bilgileri')
                ->visible(fn (Get $get): bool => static::roleValue($get('role')) === UserRole::AGENT->value)
                ->schema([
                    Forms\Components\Grid::make(['default' => 1, 'md' => 2])->schema([
                        TextInput::make('business_office_name')->label('Şirket / Ticari Unvan')->required()->maxLength(255),
                        TextInput::make('business_license_number')->label('Yetki Belgesi Numarası')->required()->maxLength(100),
                        TextInput::make('business_tax_office')->label('Vergi Dairesi')->required()->maxLength(150),
                        TextInput::make('business_tax_number')
                            ->label('Yeni Vergi Numarası')
                            ->password()
                            ->revealable()
                            ->length(10)
                            ->inputMode('numeric')
                            ->regex('/^[0-9]{10}$/')
                            ->required(fn (string $operation, ?User $record): bool => $operation === 'create' || blank($record?->businessProfile?->tax_number_hash))
                            ->dehydrated(fn (?string $state): bool => filled($state))
                            ->helperText(fn (?User $record): string => $record?->businessProfile?->tax_number_hash
                                ? 'Güvenlik nedeniyle mevcut numara gösterilmez. Boş bırakırsanız değişmez.'
                                : '10 haneli vergi numarası girin.'),
                        TextInput::make('business_phone')->label('İşletme telefonu')->tel()->maxLength(24),
                        Forms\Components\Select::make('business_approval_status')
                            ->label('İşletme onay durumu')
                            ->options([
                                BusinessApprovalStatus::APPROVED->value => 'Onaylı',
                                BusinessApprovalStatus::PENDING->value => 'Onay bekliyor',
                                BusinessApprovalStatus::REJECTED->value => 'Reddedildi',
                                BusinessApprovalStatus::SUSPENDED->value => 'Askıda',
                            ])
                            ->default(BusinessApprovalStatus::APPROVED->value)
                            ->required(),
                    ]),
                    Forms\Components\Textarea::make('business_address')
                        ->label('Açık iş yeri adresi')
                        ->required()
                        ->rows(3)
                        ->maxLength(1000)
                        ->columnSpanFull(),
                    Forms\Components\Textarea::make('business_review_note')
                        ->label('Supervisor inceleme notu')
                        ->rows(2)
                        ->maxLength(2000)
                        ->columnSpanFull(),
                ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table->columns([
            Tables\Columns\TextColumn::make('name')->label('Ad Soyad')->searchable(),
            Tables\Columns\TextColumn::make('email')->label('E-posta')->searchable(),
            Tables\Columns\TextColumn::make('phone')->label('Telefon'),
            Tables\Columns\TextColumn::make('role')->label('Üyelik Türü')->badge(),
            Tables\Columns\IconColumn::make('email_verified_at')->label('E-posta')
                ->getStateUsing(fn (User $record): bool => filled($record->email_verified_at))->boolean(),
            Tables\Columns\TextColumn::make('account_status')->label('Hesap Durumu')->badge(),
            Tables\Columns\TextColumn::make('listing_limit')
                ->label('Aktif İlan Limiti')
                ->getStateUsing(function (User $record): string {
                    $quota = app(ListingQuotaService::class);
                    $limit = $quota->effectiveLimit($record);

                    return $limit === null ? '-' : $quota->activeCount($record).' / '.$limit;
                }),
        ])->filters([
            Tables\Filters\SelectFilter::make('role')->options([
                UserRole::INDIVIDUAL->value => 'Bireysel', UserRole::AGENT->value => 'Emlak İşletmesi',
                UserRole::EDITOR->value => 'Editör', UserRole::SUPERVISOR->value => 'Yönetici',
            ]),
            Tables\Filters\SelectFilter::make('account_status')->options([
                'active' => 'Aktif', 'pending_email' => 'E-posta Bekliyor', 'pending_approval' => 'Onay Bekliyor',
                'rejected' => 'Reddedildi', 'suspended' => 'Askıda',
            ]),
        ])->actions([
            Tables\Actions\EditAction::make(),
            Tables\Actions\Action::make('setListingLimit')
                ->label('İlan Limitini Ayarla')
                ->icon('heroicon-o-adjustments-horizontal')
                ->visible(fn (User $record): bool => in_array($record->role, [UserRole::INDIVIDUAL, UserRole::AGENT], true))
                ->fillForm(fn (User $record): array => ['listing_limit_override' => $record->listing_limit_override])
                ->form([
                    TextInput::make('listing_limit_override')
                        ->label('Özel aktif ilan limiti')
                        ->numeric()
                        ->minValue(0)
                        ->maxValue(10000)
                        ->nullable()
                        ->helperText('Boş bırakılırsa bireysel üyede 10, emlakçıda 100 kullanılır.'),
                ])
                ->action(fn (User $record, array $data) => $record->update([
                    'listing_limit_override' => filled($data['listing_limit_override'])
                        ? (int) $data['listing_limit_override']
                        : null,
                ])),
            Tables\Actions\Action::make('createListingForUser')
                ->label('Kullanıcı Adına İlan Oluştur')
                ->icon('heroicon-o-plus-circle')
                ->visible(fn (User $record): bool => $record->account_status === AccountStatus::ACTIVE
                    && in_array($record->role, [UserRole::INDIVIDUAL, UserRole::AGENT], true))
                ->url(fn (User $record): string => ListingResource::getUrl('create', ['owner' => $record->id], panel: 'admin')),
            Tables\Actions\Action::make('suspend')->label('Askıya Al')->color('danger')->requiresConfirmation()
                ->visible(fn (User $record): bool => $record->role !== UserRole::SUPERVISOR && $record->account_status !== AccountStatus::SUSPENDED)
                ->action(fn (User $record) => $record->update(['account_status' => AccountStatus::SUSPENDED])),
            Tables\Actions\Action::make('activate')->label('Aktifleştir')->color('success')->requiresConfirmation()
                ->visible(fn (User $record): bool => $record->account_status === AccountStatus::SUSPENDED)
                ->action(fn (User $record) => $record->update(['account_status' => AccountStatus::ACTIVE])),
        ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListUsers::route('/'),
            'create' => Pages\CreateUser::route('/create'),
            'edit' => Pages\EditUser::route('/{record}/edit'),
        ];
    }

    private static function roleValue(mixed $role): ?string
    {
        return $role instanceof UserRole ? $role->value : $role;
    }
}
