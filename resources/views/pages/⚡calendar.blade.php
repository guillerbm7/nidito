<?php

use App\Models\CalendarEntry;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Livewire\Attributes\On;
use Livewire\Component;

new class extends Component
{
    public string $weekStart = '';

    public string $selectedDate = '';

    public string $viewMode = 'week';

    public string $currentMonth = '';

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
    public function refreshEntries(): void {}

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

    {{-- =========================================================================
         CALENDAR GRID - Main calendar area
         ========================================================================= --}}
    <div class="flex-1 flex flex-col overflow-hidden min-h-0">

        {{-- Header: Title, navigation, view mode toggle --}}
        <div class="flex flex-wrap items-center justify-between gap-3 px-4 py-3 lg:px-7 lg:py-5 bg-surface-primary border-b border-border">
            <div class="flex items-center gap-4">
                <h2 class="font-serif text-2xl text-text-primary capitalize">
                    {{
                        $viewMode === 'month'
                            ? Carbon::createFromFormat('Y-m', $currentMonth)->locale('es')->isoFormat('MMMM YYYY')
                            : Carbon::parse($weekStart)->locale('es')->isoFormat('MMMM YYYY')
                    }}
                </h2>
                <div class="flex gap-1">
                    <button wire:click="previousPeriod"
                            class="w-8 h-8 rounded-lg border border-border-input text-text-muted hover:bg-surface-tertiary transition-colors flex items-center justify-center text-sm">
                        ‹
                    </button>
                    <button wire:click="nextPeriod"
                            class="w-8 h-8 rounded-lg border border-border-input text-text-muted hover:bg-surface-tertiary transition-colors flex items-center justify-center text-sm">
                        ›
                    </button>
                </div>
            </div>

            <div class="flex border border-border-input rounded-lg overflow-hidden">
                <button wire:click="showWeek"
                        @class([
                            'px-3 py-1.5 text-xs transition-colors',
                            'bg-text-primary text-surface-secondary' => $viewMode === 'week',
                            'text-text-muted hover:bg-surface-tertiary' => $viewMode !== 'week',
                        ])>
                    Semana
                </button>
                <button wire:click="showMonth"
                        @class([
                            'px-3 py-1.5 text-xs transition-colors',
                            'bg-text-primary text-surface-secondary' => $viewMode === 'month',
                            'text-text-muted hover:bg-surface-tertiary' => $viewMode !== 'month',
                        ])>
                    Mes
                </button>
            </div>
        </div>

        @php
            $dayNames = ['lun', 'mar', 'mié', 'jue', 'vie', 'sáb', 'dom'];
        @endphp
        <div class="flex-1 min-h-0 overflow-auto bg-surface-secondary">
            <div class="min-w-[700px] min-h-full flex flex-col">

                {{-- Day names header --}}
                <div class="grid grid-cols-7 bg-surface-primary border-b border-border">
                    @foreach($dayNames as $dayName)
                        <div class="flex flex-col items-center py-3">
                            <span class="text-[10px] uppercase tracking-widest text-text-subtle">
                                {{ $dayName }}
                            </span>
                        </div>
                    @endforeach
                </div>

                {{-- Calendar cells --}}
                <div @class([
                    'grid grid-cols-7 divide-x divide-border',
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
                        'calendar-cell',
                        'min-h-[150px] lg:min-h-[120px]' => $viewMode === 'week',
                        'min-h-[120px]' => $viewMode === 'month',
                        'calendar-cell-selected' => $isSelected,
                        'calendar-cell-default' => !$isSelected,
                        'calendar-cell-outside' => $isOutsideMonth,
                    ])
                    wire:click="selectDay('{{ $dateStr }}')">

                    {{-- Day number badge --}}
                    <div class="mb-1">
                        <div @class([
                            'day-badge-today' => $isToday,
                            'day-badge-default' => !$isToday,
                        ])>
                            {{ $day->day }}
                        </div>
                    </div>

                    {{-- Entry items --}}
                    @forelse($entriesToShow as $entry)
                        <div @class([
                            'relative rounded-lg px-2.5 py-1.5 border-l-2 cursor-pointer hover:opacity-80 transition-opacity group',
                            'entry-lunch' => $entry->type === 'lunch',
                            'entry-dinner' => $entry->type === 'dinner',
                            'entry-task' => $entry->type === 'task',
                            'entry-event' => $entry->type === 'event',
                        ])>
                            {{-- Edit button --}}
                            <button wire:click.stop="editModal('{{ $entry->id }}')"
                                    class="absolute top-1 right-5 opacity-0 group-hover:opacity-100 transition-opacity text-text-subtle hover:text-accent-purple p-0.5">
                                <svg xmlns="http://www.w3.org/2000/svg" class="w-3 h-3" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                    <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/>
                                    <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/>
                                </svg>
                            </button>

                            {{-- Delete button --}}
                            <button wire:click.stop="deleteModal('{{ $entry->id }}')"
                                    class="absolute top-1 right-1 opacity-0 group-hover:opacity-100 transition-opacity text-text-subtle hover:text-accent-purple p-0.5">
                                <svg xmlns="http://www.w3.org/2000/svg" class="w-3 h-3" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                    <polyline points="3 6 5 6 21 6"></polyline>
                                    <path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path>
                                    <line x1="10" y1="11" x2="10" y2="17"></line>
                                    <line x1="14" y1="11" x2="14" y2="17"></line>
                                </svg>
                            </button>

                            <p class="text-[11.5px] font-medium text-text-primary truncate pr-4">{{ $entry->title }}</p>

                            @if($entry->assigned_to && isset($users[$entry->assigned_to]))
                                <p class="text-[10px] mt-0.5" data-user-color style="--user-color: {{ $users[$entry->assigned_to]->avatar_color }}">
                                    {{ $users[$entry->assigned_to]->name }}
                                </p>
                            @endif

                            @if($entry->recipe_url)
                                <a href="{{ $entry->recipe_url }}" target="_blank"
                                   class="text-[10px] text-accent-purple-light hover:underline">🔗 receta</a>
                            @endif
                        </div>
                    @empty
                    @endforelse

                    {{-- Hidden entries count (month view) --}}
                    @if($hiddenCount > 0)
                        <p class="text-[10px] text-text-subtle px-1">+{{ $hiddenCount }} más</p>
                    @endif

                    {{-- Add entry button (week view) --}}
                    @if($viewMode === 'week')
                    <button wire:click.stop="openModal('{{ $dateStr }}')"
                            class="mt-auto btn-add">
                        Añadir entrada
                    </button>
                    @endif
                </div>
                @endforeach
                </div>
            </div>
        </div>
    </div>

    {{-- =========================================================================
         DETAIL PANEL - Selected day info
         ========================================================================= --}}
    <div class="w-full lg:w-60 bg-surface-primary border-t lg:border-t-0 lg:border-l border-border flex flex-col items-center lg:items-stretch text-center lg:text-left p-4 lg:p-6 flex-shrink-0">

        {{-- Selected date --}}
        <h3 class="font-serif text-base text-text-primary capitalize">
            {{ Carbon::parse($selectedDate)->locale('es')->isoFormat('dddd D') }}
        </h3>
        <p class="text-xs text-text-subtle capitalize mb-5">
            {{ Carbon::parse($selectedDate)->locale('es')->isoFormat('MMMM YYYY') }}
        </p>

        @php
            $lunch  = $selectedEntries->firstWhere('type', 'lunch');
            $dinner = $selectedEntries->firstWhere('type', 'dinner');
            $tasks  = $selectedEntries->whereIn('type', ['task', 'event']);
        @endphp

        {{-- Meals section --}}
        <div class="mb-4 w-full">
            <p class="text-[10px] uppercase tracking-widest text-text-label mb-2">Comidas</p>
            <div class="flex items-start justify-center lg:justify-start gap-2 mb-2">
                <div class="w-2 h-2 rounded-full bg-entry-lunch-dot mt-1.5 flex-shrink-0"></div>
                <div class="text-center lg:text-left">
                    <p class="text-[12.5px] text-text-entry">{{ $lunch?->title ?? 'Sin añadir' }}</p>
                    <p class="text-[11px] text-text-subtle">Comida</p>
                </div>
            </div>
            <div class="flex items-start justify-center lg:justify-start gap-2">
                <div class="w-2 h-2 rounded-full bg-entry-dinner-dot mt-1.5 flex-shrink-0"></div>
                <div class="text-center lg:text-left">
                    <p class="text-[12.5px] text-text-entry">{{ $dinner?->title ?? 'Sin añadir' }}</p>
                    <p class="text-[11px] text-text-subtle">Cena</p>
                </div>
            </div>
        </div>

        {{-- Events and tasks section --}}
        <div class="mb-4 w-full">
            <p class="text-[10px] uppercase tracking-widest text-text-label mb-2">Eventos y tareas</p>
            @forelse($tasks as $entry)
                <div class="flex items-start justify-center lg:justify-start gap-2 mb-2">
                    <div @class([
                        'w-2 h-2 rounded-full mt-1.5 flex-shrink-0',
                        'bg-entry-task-dot' => $entry->type === 'task',
                        'bg-entry-event-dot' => $entry->type === 'event',
                    ])></div>
                    <div class="text-center lg:text-left">
                        <p class="text-[12.5px] text-text-entry">{{ $entry->title }}</p>
                        @if($entry->assigned_to && isset($users[$entry->assigned_to]))
                            <p class="text-[11px] mt-0.5" data-user-color style="--user-color: {{ $users[$entry->assigned_to]->avatar_color }}">
                                {{ $users[$entry->assigned_to]->name }}
                            </p>
                        @endif
                    </div>
                </div>
            @empty
                <p class="text-xs text-text-placeholder italic">Sin eventos ni tareas.</p>
            @endforelse
        </div>

        {{-- Add new entry button --}}
        <button wire:click="openModal('{{ $selectedDate }}')"
                class="mt-auto w-full py-2 border border-dashed border-border-dashed rounded-lg text-xs text-text-subtle hover:border-accent-purple-light hover:text-accent-purple transition-colors">
            + Nueva entrada
        </button>
    </div>

    {{-- Entry modal component --}}
    <livewire:pages::entry-modal />
</div>
