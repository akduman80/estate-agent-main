<?php

namespace App\Filament\Resources;

use App\Filament\Resources\LegalDocumentResource\Pages;
use App\Models\LegalDocument;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class LegalDocumentResource extends Resource
{
    protected static ?string $model = LegalDocument::class;

    protected static ?string $navigationIcon = 'heroicon-o-document-text';

    protected static ?string $navigationLabel = 'Sözleşme ve Politikalar';

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\TextInput::make('title')->label('Başlık')->required(),
            Forms\Components\TextInput::make('slug')->required(),
            Forms\Components\TextInput::make('version')->label('Sürüm')->required(),
            Forms\Components\RichEditor::make('content')->label('Metin')->required()->columnSpanFull(),
            Forms\Components\Toggle::make('is_required')->label('Kayıtta zorunlu'),
            Forms\Components\Toggle::make('is_active')->label('Aktif'),
            Forms\Components\DateTimePicker::make('published_at')->label('Yayın Tarihi'),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table->columns([
            Tables\Columns\TextColumn::make('title')->label('Belge'),
            Tables\Columns\TextColumn::make('version')->label('Sürüm'),
            Tables\Columns\IconColumn::make('is_required')->label('Zorunlu')->boolean(),
            Tables\Columns\IconColumn::make('is_active')->label('Aktif')->boolean(),
            Tables\Columns\TextColumn::make('published_at')->label('Yayın')->dateTime('d.m.Y H:i'),
        ])->actions([Tables\Actions\EditAction::make()]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListLegalDocuments::route('/'),
            'create' => Pages\CreateLegalDocument::route('/create'),
            'edit' => Pages\EditLegalDocument::route('/{record}/edit'),
        ];
    }
}
