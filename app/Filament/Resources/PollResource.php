<?php

namespace App\Filament\Resources;

use App\Filament\Resources\PollResource\Pages;
use App\Models\Poll;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class PollResource extends Resource
{
    protected static ?string $model = Poll::class;

    protected static ?string $navigationIcon = 'heroicon-o-chart-bar-square';

    protected static ?string $navigationGroup = 'İçerik Yönetimi';

    protected static ?string $navigationLabel = 'Anketler';

    protected static ?string $modelLabel = 'Anket';

    protected static ?string $pluralModelLabel = 'Anketler';

    public static function form(Form $form): Form
    {
        return $form->schema([
            TextInput::make('title')
                ->label('Anket Başlığı')
                ->required()
                ->maxLength(255)
                ->columnSpanFull(),
            RichEditor::make('question')
                ->label('Soru')
                ->required()
                ->columnSpanFull(),
            Grid::make(['default' => 1, 'md' => 2])->schema([
                Select::make('status')
                    ->label('Durum')
                    ->options([
                        'draft' => 'Taslak',
                        'active' => 'Yayında',
                        'closed' => 'Kapalı',
                    ])
                    ->default('draft')
                    ->helperText('Bir anketi yayına aldığınızda daha önce yayında olan anket otomatik kapanır.')
                    ->required(),
                Select::make('results_visibility')
                    ->label('Sonuçları Göster')
                    ->options([
                        'always' => 'Her zaman',
                        'after_vote' => 'Oy verdikten sonra',
                    ])
                    ->default('after_vote')
                    ->required(),
                DateTimePicker::make('starts_at')->label('Başlangıç Tarihi')->seconds(false),
                DateTimePicker::make('ends_at')->label('Bitiş Tarihi')->seconds(false)->after('starts_at'),
            ])->columnSpanFull(),
            Repeater::make('options')
                ->label('Seçenekler')
                ->relationship()
                ->schema([
                    TextInput::make('text')->label('Seçenek')->required()->maxLength(255),
                    TextInput::make('sort_order')->label('Sıra')->numeric()->default(0)->required(),
                ])
                ->columns(2)
                ->minItems(2)
                ->defaultItems(2)
                ->reorderableWithButtons()
                ->addActionLabel('Seçenek ekle')
                ->columnSpanFull(),
        ])->columns(2);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('title')->label('Başlık')->searchable()->limit(45),
                Tables\Columns\TextColumn::make('question')->label('Soru')->formatStateUsing(fn ($state) => strip_tags($state ?? ''))->limit(60),
                Tables\Columns\TextColumn::make('status')
                    ->label('Durum')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'active' => 'Yayında',
                        'closed' => 'Kapalı',
                        default => 'Taslak',
                    })
                    ->color(fn (string $state): string => match ($state) {
                        'active' => 'success',
                        'closed' => 'gray',
                        default => 'warning',
                    }),
                Tables\Columns\TextColumn::make('options_sum_votes_count')
                    ->label('Oy')
                    ->sum('options', 'votes_count')
                    ->numeric(),
                Tables\Columns\TextColumn::make('starts_at')->label('Başlangıç')->dateTime('d.m.Y H:i')->placeholder('-'),
                Tables\Columns\TextColumn::make('ends_at')->label('Bitiş')->dateTime('d.m.Y H:i')->placeholder('-'),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([])
            ->defaultSort('created_at', 'desc');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListPolls::route('/'),
            'create' => Pages\CreatePoll::route('/create'),
            'edit' => Pages\EditPoll::route('/{record}/edit'),
        ];
    }
}
