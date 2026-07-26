<?php

namespace App\Livewire;

use App\Models\Listing;
use Illuminate\Support\Facades\Cache;
use Livewire\Component;
use Livewire\WithPagination;

class PropertySearch extends Component
{
    use WithPagination;

    // Filtre Değişkenleri
    public $search = '';
    public $city_id = '';
    public $type = '';
    public $min_price = 2000;
    public $max_price = 100000;
    public $min_area = 40;
    public $max_area = 12000;
    public $features = []; // Checkbox özellikleri

    public function updatingSearch()
    {
        $this->resetPage();
    }

    public function render()
    {
        $listings = Listing::query()
                           ->with(['images'])
                           ->where('is_active', true)
                           ->where('eids_status', 'verified')
                           ->when($this->search, fn($q) => $q->where('title', 'like', "%{$this->search}%"))
                           ->when($this->city_id, fn($q) => $q->where('city_id', $this->city_id))
                           ->when($this->type, fn($q) => $q->where('type', $this->type))
                           ->when($this->min_price, fn($q) => $q->where('price', '>=', $this->min_price))
                           ->when($this->max_price, fn($q) => $q->where('price', '<=', $this->max_price))
                           ->latest()
                           ->paginate(12);

        // Şehirler Redis'ten çekilir
        $cities = Cache::get('locations_cities', []);

        return view('livewire.property-search', [
            'listings' => $listings,
            'cities'   => $cities,
        ]);
    }
}
