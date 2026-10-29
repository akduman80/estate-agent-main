<?php

namespace App\Filament\Editor\Resources\PostResource\Pages;

use App\Filament\Editor\Resources\PostResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListPosts extends ListRecords
{
    protected static string $resource = PostResource::class;

    protected static ?string $title = 'Tüm Emlak Haberleri';

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()->label('Yeni Emlak Haberi Ekle'),
        ];
    }
}
