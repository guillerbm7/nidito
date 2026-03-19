<?php

use Livewire\Component;
use Carbon\Carbon;
use App\Models\CalendarEntry;
use App\Models\User;

new class extends Component
{
    public string $weekStart    = '';
    public string $selectedDate = '';

    public function mount(): void
    {
        $this->weekStart    = Carbon::now()->startOfWeek()->toDateString();
        $this->selectedDate = Carbon::now()->toDateString();
    }

    public function previousWeek(): void
    {
        $this->weekStart = Carbon::parse($this->weekStart)->subWeek()->toDateString();
    }

    public function nextWeek(): void
    {
        $this->weekStart = Carbon::parse($this->weekStart)->addWeek()->toDateString();
    }

    public function selectDay(string $date): void
    {
        $this->selectedDate = $date;
    }

    public function with(): array
    {
        $weekEnd         = Carbon::parse($this->weekStart)->endOfWeek()->toDateString();
        $entries         = CalendarEntry::whereBetween('date', [$this->weekStart, $weekEnd])
                             ->orderBy('type')
                             ->get();
        $entriesByDate   = $entries->groupBy('date');
        $selectedEntries = $entriesByDate->get($this->selectedDate, collect());
        $users           = User::all()->keyBy('id');

        return [
            'entriesByDate'   => $entriesByDate,
            'selectedEntries' => $selectedEntries,
            'users'           => $users,
        ];
    }
};
?>

<div>

    {{ Carbon::parse($weekStart)->locale('es')->isoFormat('DD MMMM YYYY') }}
    {{ $weekStart }}
    <button wire:click="previousWeek">< anterior</button>
    <button wire:click="nextWeek">siguiente ></button>
        
</div>
