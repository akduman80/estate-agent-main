<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ProjectResource\Pages;
use App\Models\Project;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Str;

class ProjectResource extends Resource
{
    protected static ?string $model = Project::class;

    protected static ?string $navigationIcon = 'heroicon-o-building-office-2';

    protected static ?string $navigationLabel = 'Projeler';

    protected static ?string $modelLabel = 'Proje';

    protected static ?string $pluralModelLabel = 'Projeler';

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make('Proje bilgileri')->schema([
                Forms\Components\TextInput::make('title')->label('Proje adı')->required()->maxLength(255)
                    ->live(onBlur: true)->afterStateUpdated(fn ($state, callable $set) => $set('slug', Str::slug($state))),
                Forms\Components\TextInput::make('slug')->label('URL')->required()->unique(ignoreRecord: true),
                Forms\Components\TextInput::make('developer_name')->label('Müteahhit / geliştirici')->required()->maxLength(255),
                Forms\Components\Select::make('status')->label('Proje durumu')->options([
                    'planning' => 'Planlanıyor',
                    'construction' => 'Devam ediyor',
                    'completed' => 'Tamamlandı',
                ])->default('construction')->required(),
                Forms\Components\TextInput::make('city')->label('İl'),
                Forms\Components\TextInput::make('district')->label('İlçe'),
                Forms\Components\TextInput::make('neighborhood')->label('Mahalle'),
                Forms\Components\TextInput::make('latitude')->label('Enlem')->numeric(),
                Forms\Components\TextInput::make('longitude')->label('Boylam')->numeric(),
                Forms\Components\FileUpload::make('cover_image')->label('Kapak görseli')->image()->disk('public')
                    ->directory('projects')->imageEditor()->imageCropAspectRatio('4:3')->columnSpanFull(),
                Forms\Components\Textarea::make('summary')->label('Kısa açıklama')->rows(3)->columnSpanFull(),
                Forms\Components\RichEditor::make('content')->label('Proje detayları')->columnSpanFull(),
            ])->columns(2),
            Forms\Components\Section::make('Yayın')->schema([
                Forms\Components\Toggle::make('is_featured')->label('Ana sayfada öne çıkar')->default(false),
                Forms\Components\Toggle::make('is_published')->label('Yayında')->default(false),
                Forms\Components\DateTimePicker::make('published_at')->label('Yayın tarihi')->seconds(false),
            ])->columns(3),
            Forms\Components\Section::make('Mobil uygulama bağlantısı')->schema([
                Forms\Components\TextInput::make('mobile_project_uuid')->label('Kalıcı mobil proje UUID')->disabled(),
                Forms\Components\TextInput::make('mobile_local_project_id')->label('Eski yerel proje ID')->disabled(),
            ])->columns(2)->collapsed(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table->columns([
            Tables\Columns\ImageColumn::make('cover_image')->label('Görsel')->disk('public'),
            Tables\Columns\TextColumn::make('title')->label('Proje')->searchable()->sortable(),
            Tables\Columns\TextColumn::make('developer_name')->label('Müteahhit')->searchable(),
            Tables\Columns\TextColumn::make('status')->label('Durum')->badge(),
            Tables\Columns\IconColumn::make('is_featured')->label('Öne çıkan')->boolean(),
            Tables\Columns\IconColumn::make('is_published')->label('Yayında')->boolean(),
        ])->actions([Tables\Actions\EditAction::make()])
            ->bulkActions([Tables\Actions\BulkActionGroup::make([Tables\Actions\DeleteBulkAction::make()])]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListProjects::route('/'),
            'create' => Pages\CreateProject::route('/create'),
            'edit' => Pages\EditProject::route('/{record}/edit'),
        ];
    }
}
