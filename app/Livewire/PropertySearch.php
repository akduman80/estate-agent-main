<?php

namespace App\Livewire;

use App\Models\Listing;
use App\Models\Setting;
use App\Services\TurkeyAddressService;
use Livewire\Component;
use Livewire\WithPagination;

class PropertySearch extends Component
{
    use WithPagination;

    // Filtre Değişkenleri
    public $search = '';

    public $city_id = '';

    public $type = '';

    public $zoning_status = ''; // İmar Durumu filtresi eklemek isterseniz

    public $min_price = '';

    public $max_price = '';

    public $min_area = '';

    public $max_area = '';

    public $features = []; // Checkbox özellikleri

    protected $paginationTheme = 'bootstrap';

    // Filtre değiştiğinde sayfalamayı 1'e sıfırla
    public function updated($propertyName)
    {
        $this->resetPage();
    }

    public function render()
    {
        $listings = Listing::query()
            ->with(['images', 'eidsVerification'])
            ->where('is_active', true)
            ->when($this->search, fn ($q) => $q->where('title', 'like', "%{$this->search}%"))
            ->when($this->city_id, fn ($q) => $q->where('city_id', $this->city_id))
            ->when($this->type, fn ($q) => $q->where('type', $this->type))
            ->when($this->zoning_status, fn ($q) => $q->where('zoning_status', $this->zoning_status))
            ->when($this->min_price !== '' && $this->min_price !== null, fn ($q) => $q->where('price', '>=', $this->min_price))
            ->when($this->max_price !== '' && $this->max_price !== null, fn ($q) => $q->where('price', '<=', $this->max_price))
            ->when($this->min_area !== '' && $this->min_area !== null, fn ($q) => $q->where('square_meters', '>=', $this->min_area))
            ->when($this->max_area !== '' && $this->max_area !== null, fn ($q) => $q->where('square_meters', '<=', $this->max_area))
            ->latest()
            ->paginate(12);

        // Tüm Türkiye şehirleri
        $cities = TurkeyAddressService::getCities();

        // Manage Settings'te dinamik tanımladığınız ilan tipleri (Satılık, Kiralık vs.)
        $typeOptions = Setting::get('options_type', ['Satılık', 'Kiralık']);

        return view('livewire.property-search', [
            'listings' => $listings,
            'cities' => $cities,
            'typeOptions' => $typeOptions,
        ]);
    }
}
