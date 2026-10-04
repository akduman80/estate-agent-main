<?php

namespace Tests\Feature;

use App\Models\Listing;
use App\Models\User;
use App\Support\ListingDecimal;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class ListingDecimalPrecisionTest extends TestCase
{
    use RefreshDatabase;

    public function test_decimal_values_survive_create_update_and_public_display(): void
    {
        $values = [
            'price' => '1234567.89123',
            'square_meters' => '123.4567',
            'net_square_meters' => '98.7654',
            'deposit_price' => '4567.89123',
            'dues_price' => '345.6789',
        ];
        $listing = Listing::withoutEvents(fn () => Listing::create(array_merge([
            'user_id' => User::factory()->create()->id,
            'title' => 'Hassasiyet testi',
            'slug' => 'hassasiyet-testi',
            'city_id' => 'İstanbul',
            'district_id' => 'Kadıköy',
            'eids_status' => 'verified',
            'publication_status' => 'approved',
            'is_active' => true,
        ], $values)));

        $listing->refresh();
        foreach ($values as $field => $value) {
            $this->assertSame($value, $listing->$field);
        }

        $response = $this->get(route('listings.show', $listing))->assertOk();
        foreach ($values as $value) {
            $response->assertSee(ListingDecimal::format($value));
        }
        $this->get(route('listings.index'))->assertOk()->assertSee('1.234.567,89123');

        $listing->forceFill(['price' => '7654321.12345'])->saveQuietly();
        $this->assertSame('7654321.12345', $listing->fresh()->price);
    }

    public function test_large_precise_numbers_are_formatted_without_float_conversion(): void
    {
        $this->assertSame('123.456.789.012.345.678,1234567890123456789', ListingDecimal::format('123456789012345678.1234567890123456789'));
        $this->assertSame('1.234', ListingDecimal::format('1234.0000'));
        $this->assertSame('0,0001', ListingDecimal::format('0.0001'));
    }

    public function test_excess_precision_is_rejected_instead_of_rounded(): void
    {
        $this->expectException(ValidationException::class);
        new Listing(['price' => '1.'.str_repeat('1', 31)]);
    }
}
