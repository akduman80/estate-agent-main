<?php

namespace App\Livewire;

use App\Services\RentIncreaseCalculator;
use App\Support\RentSummaryPdf;
use Livewire\Component;

class RentCalculator extends Component
{
    public string $rent = '';

    public string $kind = 'home';

    public string $renewal_date = '';

    public string $contract_rate = '';

    public bool $special = false;

    public ?array $result = null;

    public function mount(): void
    {
        $this->renewal_date = now()->toDateString();
    }

    public function updated(): void
    {
        $this->result = null;
    }

    public function calculate(): void
    {
        $this->result = null;
        $data = $this->validate(['rent' => ['required', 'numeric', 'min:0.01', 'max:1000000000', 'decimal:0,2'], 'kind' => ['required', 'in:home,workplace'], 'renewal_date' => ['required', 'date_format:Y-m-d'], 'contract_rate' => ['nullable', 'numeric', 'min:0', 'max:999', 'decimal:0,2'], 'special' => ['boolean']]);
        if ($this->special) {
            $this->addError('special', 'Beş yılı aşan, dövizli veya özel koşullu sözleşmeler için otomatik kesin kira hesabı sunulmuyor. Hukuk uzmanına danışın.');

            return;
        }
        $this->result = app(RentIncreaseCalculator::class)->calculate($data);
    }

    public function render()
    {
        return view('livewire.rent-calculator')->layout('components.layouts.modern', ['title' => 'Kira Artışı Hesapla | ZYDEMLAK']);
    }

    public function download()
    {
        // Recompute from validated inputs; a client-supplied result is never trusted.
        $this->calculate();
        if (! $this->result) {
            return null;
        }
        $pdf = app(RentSummaryPdf::class)->make($this->result);

        return response()->streamDownload(fn () => print ($pdf), 'kira-artisi-'.$this->result['date'].'.pdf', ['Content-Type' => 'application/pdf', 'Cache-Control' => 'private, no-store']);
    }
}
