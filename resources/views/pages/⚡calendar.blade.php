<?php

use Livewire\Component;
use Carbon\Carbon;
use App\Models\CalendarEntry;
use App\Models\User;
use Livewire\Attributes\On;
use Illuminate\Support\Collection;

new class extends Component
{
    public string $weekStart = '';
    public string $selectedDate = '';
    public string $viewMode = 'week'; // week | month
    public string $currentMonth = ''; // Y-m

    public function mount(): void
    {
        $today = Carbon::now();

        $this->weekStart = $today->copy()->startOfWeek()->toDateString();
        $this->selectedDate = $today->toDateString();
        $this->currentMonth = $today->format('Y-m');
    }

    public function showWeek(): void
    {
        $this->viewMode = 'week';
        $this->weekStart = Carbon::parse($this->selectedDate)->startOfWeek()->toDateString();
    }

    public function showMonth(): void
    {
        $this->viewMode = 'month';
        $this->currentMonth = Carbon::parse($this->selectedDate)->format('Y-m');
    }

    public function previousPeriod(): void
    {
        if ($this->viewMode === 'week') {
            $this->weekStart = Carbon::parse($this->weekStart)->subWeek()->toDateString();
            return;
        }

        $this->currentMonth = Carbon::createFromFormat('Y-m', $this->currentMonth)->subMonth()->format('Y-m');
    }

    public function nextPeriod(): void
    {
        if ($this->viewMode === 'week') {
            $this->weekStart = Carbon::parse($this->weekStart)->addWeek()->toDateString();
            return;
        }

        $this->currentMonth = Carbon::createFromFormat('Y-m', $this->currentMonth)->addMonth()->format('Y-m');
    }

    public function selectDay(string $date): void
    {
        $this->selectedDate = $date;

        // Mantener sincronizados los estados para navegación más intuitiva.
        $selected = Carbon::parse($date);
        $this->weekStart = $selected->copy()->startOfWeek()->toDateString();
        $this->currentMonth = $selected->format('Y-m');
    }

    public function openModal(string $date): void
    {
        $this->dispatch('open-entry-modal', date: $date);
    }

    public function editModal($entryId): void
    {
        $this->dispatch('open-entry-modal-edit', entryId: $entryId);
    }

    public function deleteModal($entryId): void
    {
        $this->dispatch('entry-modal-delete', entryId: $entryId);
    }

    private function visibleDates(): Collection
    {
        if ($this->viewMode === 'week') {
            $start = Carbon::parse($this->weekStart)->startOfDay();

            return collect(range(0, 6))
                ->map(fn ($i) => $start->copy()->addDays($i)->toDateString());
        }

        $month = Carbon::createFromFormat('Y-m', $this->currentMonth);
        $start = $month->copy()->startOfMonth()->startOfWeek();
        $end = $month->copy()->endOfMonth()->endOfWeek();

        $days = [];
        for ($d = $start->copy(); $d->lte($end); $d->addDay()) {
            $days[] = $d->toDateString();
        }

        return collect($days);
    }

    #[On('entry-saved')]
    public function refreshEntries(): void
    {
        // Intencionalmente vacío: fuerza re-render.
    }

    public function with(): array
    {
        $visibleDates = $this->visibleDates();

        $rangeStart = $visibleDates->first();
        $rangeEnd = $visibleDates->last();

        $entries = CalendarEntry::query()
            ->whereBetween('date', [$rangeStart, $rangeEnd])
            ->orderBy('date')
            ->orderBy('type')
            ->get();

        $entriesByDate = $entries->groupBy('date');
        $selectedEntries = $entriesByDate->get($this->selectedDate, collect());
        $users = User::all()->keyBy('id');

        return [
            'visibleDates' => $visibleDates,
            'entriesByDate' => $entriesByDate,
            'selectedEntries' => $selectedEntries,
            'users' => $users,
        ];
    }
};
?>

<div class="flex h-full flex-col lg:flex-row">
    {{-- ÁREA PRINCIPAL --}}
    <div class="flex-1 flex flex-col overflow-hidden min-h-0">
        {{-- Cabecera --}}
        <div class="flex flex-wrap items-center justify-between gap-3 px-4 py-3 lg:px-7 lg:py-5 bg-[#FFFEFB] border-b border-[#EAE8E2]">
            <div class="flex items-center gap-4">
                <h2 class="font-serif text-2xl text-[#2C2A26] capitalize">
                    {{
                        $viewMode === 'month'
                            ? Carbon::createFromFormat('Y-m', $currentMonth)->locale('es')->isoFormat('MMMM YYYY')
                            : Carbon::parse($weekStart)->locale('es')->isoFormat('MMMM YYYY')
                    }}
                </h2>
                <div class="flex gap-1">
                    <button wire:click="previousPeriod"
                            class="w-8 h-8 rounded-lg border border-[#E0DDD6] text-[#7A756D] hover:bg-[#F2EFE8] transition-colors flex items-center justify-center text-sm">
                        ‹
                    </button>
                    <button wire:click="nextPeriod"
                            class="w-8 h-8 rounded-lg border border-[#E0DDD6] text-[#7A756D] hover:bg-[#F2EFE8] transition-colors flex items-center justify-center text-sm">
                        ›
                    </button>
                </div>
            </div>

            <div class="flex border border-[#E0DDD6] rounded-lg overflow-hidden">
                <button wire:click="showWeek"
                        @class([
                            'px-3 py-1.5 text-xs transition-colors',
                            'bg-[#2C2A26] text-[#F7F5F0]' => $viewMode === 'week',
                            'text-[#7A756D] hover:bg-[#F2EFE8]' => $viewMode !== 'week',
                        ])>
                    Semana
                </button>

                <button wire:click="showMonth"
                        @class([
                            'px-3 py-1.5 text-xs transition-colors',
                            'bg-[#2C2A26] text-[#F7F5F0]' => $viewMode === 'month',
                            'text-[#7A756D] hover:bg-[#F2EFE8]' => $viewMode !== 'month',
                        ])>
                    Mes
                </button>
            </div>
        </div>

        {{-- Encabezado de días --}}
        @php
            $dayNames = ['lun', 'mar', 'mié', 'jue', 'vie', 'sáb', 'dom'];
        @endphp
        <div class="flex-1 min-h-0 overflow-auto bg-[#F7F5F0]">
            <div class="min-w-[700px] min-h-full flex flex-col">
                <div class="grid grid-cols-7 bg-[#FFFEFB] border-b border-[#EAE8E2]">
                    @foreach($dayNames as $dayName)
                        <div class="flex flex-col items-center py-3">
                            <span class="text-[10px] uppercase tracking-widest text-[#A09B92]">
                                {{ $dayName }}
                            </span>
                        </div>
                    @endforeach
                </div>

                {{-- Celdas --}}
                <div @class([
                    'grid grid-cols-7 divide-x divide-[#EAE8E2]',
                    'flex-1 overflow-y-auto' => $viewMode === 'week',
                ])>
                @foreach($visibleDates as $dateStr)
                @php
                    $day = Carbon::parse($dateStr)->locale('es');
                    $dayEntries = $entriesByDate->get($dateStr, collect());
                    $isSelected = $dateStr === $selectedDate;
                    $isToday = $day->isToday();
                    $isOutsideMonth = $viewMode === 'month' && $day->format('Y-m') !== $currentMonth;
                    $prioritizedEntries = $dayEntries
                        ->sortBy(fn ($entry) => match ($entry->type) {
                            'lunch' => 0,
                            'dinner' => 1,
                            default => 2,
                        })
                        ->values();
                    $entriesToShow = $viewMode === 'month'
                        ? $prioritizedEntries->take(2)
                        : $prioritizedEntries;
                    $hiddenCount = $viewMode === 'month'
                        ? max(0, $prioritizedEntries->count() - 2)
                        : 0;
                @endphp

                <div @class([
                        'p-2 flex flex-col gap-1.5 transition-colors cursor-pointer',
                        'min-h-[150px] lg:min-h-[120px]' => $viewMode === 'week',
                        'min-h-[120px]' => $viewMode === 'month',
                        'bg-[#FDFCF8]' => $isSelected,
                        'bg-[#F7F5F0]' => !$isSelected,
                        'opacity-55' => $isOutsideMonth,
                    ])
                    wire:click="selectDay('{{ $dateStr }}')">

                    {{-- Número de día --}}
                    <div class="mb-1">
                        <div @class([
                            'w-7 h-7 flex items-center justify-center rounded-full text-[12px] font-medium',
                            'bg-[#5B52C4] text-white' => $isToday,
                            'text-[#7A756D]' => !$isToday,
                        ])>
                            {{ $day->day }}
                        </div>
                    </div>

                    {{-- Entradas del día --}}
                    @forelse($entriesToShow as $entry)
                        <div @class([
                            'relative rounded-lg px-2.5 py-1.5 border-l-2 cursor-pointer hover:opacity-80 transition-opacity group',
                            'bg-[#FFF3E8] border-[#E8894A]' => $entry->type === 'lunch',
                            'bg-[#EDE8FF] border-[#7B6FD4]' => $entry->type === 'dinner',
                            'bg-[#E8F5EE] border-[#4AAD7E]' => $entry->type === 'task',
                            'bg-[#E8F1FF] border-[#4A82E8]' => $entry->type === 'event',
                        ])>
                            <button wire:click.stop="editModal('{{ $entry->id }}')"
                                    class="absolute top-1 right-5 opacity-0 group-hover:opacity-100 transition-opacity text-[#A09B92] hover:text-[#5B52C4] p-0.5">
                                <svg xmlns="http://www.w3.org/2000/svg" class="w-3 h-3" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                    <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/>
                                    <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/>
                                </svg>
                            </button>

                            <button wire:click.stop="deleteModal('{{ $entry->id }}')"
                                    class="absolute top-1 right-1 opacity-0 group-hover:opacity-100 transition-opacity text-[#A09B92] hover:text-[#5B52C4] p-0.5">
                                <svg xmlns="http://www.w3.org/2000/svg" class="w-3 h-3" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                    <polyline points="3 6 5 6 21 6"></polyline>
                                    <path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path>
                                    <line x1="10" y1="11" x2="10" y2="17"></line>
                                    <line x1="14" y1="11" x2="14" y2="17"></line>
                                </svg>
                            </button>

                            <p class="text-[11.5px] font-medium text-[#2C2A26] truncate pr-4">{{ $entry->title }}</p>

                            @if($entry->assigned_to && isset($users[$entry->assigned_to]))
                                <p class="text-[10px] mt-0.5" style="color: {{ $users[$entry->assigned_to]->avatar_color }}">
                                    {{ $users[$entry->assigned_to]->name }}
                                </p>
                            @endif

                            @if($entry->recipe_url)
                                <a href="{{ $entry->recipe_url }}" target="_blank"
                                   class="text-[10px] text-[#7B6FD4] hover:underline">🔗 receta</a>
                            @endif
                        </div>
                    @empty
                    @endforelse
                    @if($hiddenCount > 0)
                        <p class="text-[10px] text-[#A09B92] px-1">+{{ $hiddenCount }} más</p>
                    @endif
                    @if($viewMode === 'week')
                    <button wire:click.stop="openModal('{{ $dateStr }}')"
                            class="mt-auto text-[11px] text-[#7A756D] bg-[#FFFEFB] border border-[#E0DDD6] hover:border-[#5B52C4] hover:text-[#5B52C4] rounded-md py-1.5 transition-colors w-full text-center">
                        Añadir entrada
                    </button>
                    @endif
                </div>
                @endforeach
                </div>
            </div>
        </div>
    </div>

    {{-- PANEL DETALLE --}}
    <div class="w-full lg:w-60 bg-[#FFFEFB] border-t lg:border-t-0 lg:border-l border-[#EAE8E2] flex flex-col items-center lg:items-stretch text-center lg:text-left p-4 lg:p-6 flex-shrink-0">
        <h3 class="font-serif text-base text-[#2C2A26] capitalize">
            {{ Carbon::parse($selectedDate)->locale('es')->isoFormat('dddd D') }}
        </h3>
        <p class="text-xs text-[#A09B92] capitalize mb-5">
            {{ Carbon::parse($selectedDate)->locale('es')->isoFormat('MMMM YYYY') }}
        </p>

        @php
            $lunch  = $selectedEntries->firstWhere('type', 'lunch');
            $dinner = $selectedEntries->firstWhere('type', 'dinner');
            $tasks  = $selectedEntries->whereIn('type', ['task', 'event']);
        @endphp

        {{-- Comidas --}}
        <div class="mb-4 w-full">
            <p class="text-[10px] uppercase tracking-widest text-[#B5B0A6] mb-2">Comidas</p>
            <div class="flex items-start justify-center lg:justify-start gap-2 mb-2">
                <div class="w-2 h-2 rounded-full bg-[#E8894A] mt-1.5 flex-shrink-0"></div>
                <div class="text-center lg:text-left">
                    <p class="text-[12.5px] text-[#3D3A35]">{{ $lunch?->title ?? 'Sin añadir' }}</p>
                    <p class="text-[11px] text-[#A09B92]">Comida</p>
                </div>
            </div>
            <div class="flex items-start justify-center lg:justify-start gap-2">
                <div class="w-2 h-2 rounded-full bg-[#7B6FD4] mt-1.5 flex-shrink-0"></div>
                <div class="text-center lg:text-left">
                    <p class="text-[12.5px] text-[#3D3A35]">{{ $dinner?->title ?? 'Sin añadir' }}</p>
                    <p class="text-[11px] text-[#A09B92]">Cena</p>
                </div>
            </div>
        </div>

        {{-- Eventos y tareas --}}
        <div class="mb-4 w-full">
            <p class="text-[10px] uppercase tracking-widest text-[#B5B0A6] mb-2">Eventos y tareas</p>
            @forelse($tasks as $entry)
                <div class="flex items-start justify-center lg:justify-start gap-2 mb-2">
                    <div @class([
                        'w-2 h-2 rounded-full mt-1.5 flex-shrink-0',
                        'bg-[#4AAD7E]' => $entry->type === 'task',
                        'bg-[#4A82E8]' => $entry->type === 'event',
                    ])></div>
                    <div class="text-center lg:text-left">
                        <p class="text-[12.5px] text-[#3D3A35]">{{ $entry->title }}</p>
                        @if($entry->assigned_to && isset($users[$entry->assigned_to]))
                            <p class="text-[11px] mt-0.5" style="color: {{ $users[$entry->assigned_to]->avatar_color }}">
                                {{ $users[$entry->assigned_to]->name }}
                            </p>
                        @endif
                    </div>
                </div>
            @empty
                <p class="text-xs text-[#C0BAB0] italic">Sin eventos ni tareas.</p>
            @endforelse
        </div>

        <button wire:click="openModal('{{ $selectedDate }}')"
                class="mt-auto w-full py-2 border border-dashed border-[#D8D4CC] rounded-lg text-xs text-[#A09B92] hover:border-[#7B6FD4] hover:text-[#5B52C4] transition-colors">
            + Nueva entrada
        </button>
    </div>

    <livewire:pages::entry-modal />
</div>