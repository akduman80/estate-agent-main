<?php

namespace App\Filament\Forms;

use App\Models\Listing;
use App\Services\PhotoListingSuggestion;
use Filament\Forms\Components\Actions;
use Filament\Forms\Components\Actions\Action;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Get;
use Filament\Forms\Set;

class PhotoSuggestionFields
{
    public static function make(): array
    {
        return [
            Toggle::make('photo_consent')->label('Seçtiğim ilk üç fotoğrafın Google Gemini hizmetine gönderilmesini onaylıyorum.')->helperText('Fotoğraflarda kişisel bilgi bulunmamalıdır. Küçültülmüş kopyalar analiz edilir; mevcut fotoğraflar değiştirilmez. Google hizmet koşulları ve veri işleme politikaları geçerlidir: https://ai.google.dev/gemini-api/terms. Analiz isteğinde saklama kapalıdır; sağlayıcının güvenlik kayıtları kendi koşullarına tabidir.')->dehydrated(false),
            Actions::make([
                Action::make('photoSuggestion')->label('Fotoğraftan İlan Hazırla')->icon('heroicon-o-sparkles')->action(function (Get $get, Set $set, ?Listing $record) {
                    $result = app(PhotoListingSuggestion::class)->suggest((string) $get('category'), (array) $get('uploaded_images'), (bool) $get('photo_consent'), $record);
                    $set('photo_suggestion_title', $result['title']);
                    $set('photo_suggestion_description', $result['description']);
                }),
            ])->columnSpanFull(),
            Textarea::make('photo_suggestion_title')->label('Önerilen başlık — kontrol edin')->dehydrated(false)->maxLength(150)->visible(fn (Get $get) => filled($get('photo_suggestion_title'))),
            Textarea::make('photo_suggestion_description')->label('Önerilen açıklama — kontrol edin')->dehydrated(false)->rows(5)->maxLength(4000)->helperText('Metrekare, oda sayısı, tapu, imar, fiyat, adres, yapı yaşı ve teknik bilgileri kendi belgelerinizle doğrulayın.')->visible(fn (Get $get) => filled($get('photo_suggestion_description'))),
            Actions::make([
                Action::make('applyPhotoSuggestion')->label('Kontrol ettim, başlık ve açıklamaya uygula')->requiresConfirmation()->modalDescription('Mevcut başlık ve açıklama bu öneriyle değiştirilir. İlan otomatik kaydedilmez veya yayınlanmaz.')->visible(fn (Get $get) => filled($get('photo_suggestion_title')) && filled($get('photo_suggestion_description')))->action(function (Get $get, Set $set) {
                    $set('title', mb_substr(strip_tags((string) $get('photo_suggestion_title')), 0, 150));
                    $set('description', '<p>'.nl2br(e(mb_substr(strip_tags((string) $get('photo_suggestion_description')), 0, 4000))).'</p>');
                }),
            ])->columnSpanFull(),
        ];
    }
}
