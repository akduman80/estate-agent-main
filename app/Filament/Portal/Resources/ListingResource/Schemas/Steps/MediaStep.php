<?php

namespace App\Filament\Portal\Resources\ListingResource\Schemas\Steps;

use Filament\Forms\Components\Wizard;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\TextInput;
use App\Models\Listing;

class MediaStep
{
    public static function make(): Wizard\Step
    {
        return Wizard\Step::make('Görsel ve Video')
                          ->schema([
                              FileUpload::make('uploaded_images')
                                        ->label('İlan Fotoğrafları')
                                        ->multiple()
                                        ->image()
                                        ->directory('listings')
                                        ->maxFiles(30)
                                        ->maxSize(15360)
                                        ->reorderable()
                                        ->dehydrated(false)
                                        ->loadStateFromRelationshipsUsing(function (FileUpload $component, Listing $record) {
                                            $images = $record->images()->pluck('image_path')->toArray();
                                            $component->state($images);
                                        })
                                        ->saveRelationshipsUsing(function (Listing $record, $state) {
                                            if (is_array($state)) {
                                                $record->images()->delete();

                                                foreach (array_values($state) as $index => $path) {
                                                    $record->images()->create([
                                                        'image_path' => $path,
                                                        'is_main'    => $index === 0,
                                                        'sort_order' => $index,
                                                    ]);
                                                }
                                            }
                                        })
                                        ->columnSpanFull(),

                              TextInput::make('video_url')
                                       ->label('YouTube Video Linki')
                                       ->placeholder('https://www.youtube.com/watch?v=...')
                                       ->url(),
                          ]);
    }
}
