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
<div class="flex h-full">

    {{-- ÁREA PRINCIPAL --}}
    <div class="flex-1 flex flex-col overflow-hidden">

        {{-- Cabecera --}}
        <div class="flex items-center justify-between px-7 py-5 bg-[#FFFEFB] border-b border-[#EAE8E2]">
            <div class="flex items-center gap-4">
                <h2 class="font-serif text-2xl text-[#2C2A26] capitalize">
                    {{ Carbon::parse($weekStart)->locale('es')->isoFormat('MMMM YYYY') }}
                </h2>
                <div class="flex gap-1">
                    <button wire:click="previousWeek"
                            class="w-8 h-8 rounded-lg border border-[#E0DDD6] text-[#7A756D] hover:bg-[#F2EFE8] transition-colors flex items-center justify-center text-sm">
                        ‹
                    </button>
                    <button wire:click="nextWeek"
                            class="w-8 h-8 rounded-lg border border-[#E0DDD6] text-[#7A756D] hover:bg-[#F2EFE8] transition-colors flex items-center justify-center text-sm">
                        ›
                    </button>
                </div>
            </div>
            <div class="flex border border-[#E0DDD6] rounded-lg overflow-hidden">
                <button wire:click="showWeek" class="px-3 py-1.5 text-xs bg-[#2C2A26] text-[#F7F5F0]">Semana</button>
                <button wire:click="showMonth" class="px-3 py-1.5 text-xs text-[#7A756D] hover:bg-[#F2EFE8] transition-colors">Mes</button>
            </div>
        </div>

        {{-- Fila de días --}}
        <div class="grid grid-cols-7 bg-[#FFFEFB] border-b border-[#EAE8E2]">
            @php $weekDay = Carbon::parse($weekStart)->locale('es') @endphp
            @for ($i = 0; $i <= 6; $i++)
                @php
                    $isToday    = $weekDay->isToday();
                    $isSelected = $weekDay->toDateString() === $selectedDate;
                @endphp
                <div class="flex flex-col items-center py-3 cursor-pointer"
                     wire:click="selectDay('{{ $weekDay->toDateString() }}')">
                    <span class="text-[10px] uppercase tracking-widest text-[#A09B92] mb-1">
                        {{ substr($weekDay->locale('es')->format('l'), 0, 3) }}
                    </span>
                    <div @class([
                        'w-9 h-9 flex items-center justify-center rounded-full font-serif text-lg transition-colors',
                        'bg-[#5B52C4] text-white'           => $isToday,
                        'bg-[#EDE8FF] text-[#5B52C4]'       => $isSelected && !$isToday,
                        'text-[#3D3A35] hover:bg-[#F2EFE8]' => !$isToday && !$isSelected,
                    ])>
                        {{ $weekDay->isoFormat('D') }}
                    </div>
                </div>
                @php $weekDay = Carbon::parse($weekDay)->addDay()->locale('es') @endphp
            @endfor
        </div>

        {{-- Celdas --}}
        @php
            $entriesByDate = collect([
                now()->toDateString() => collect([
                    (object)['title' => 'Pasta carbonara', 'type' => 'lunch',  'recipe_url' => 'https://youtube.com', 'assigned_to' => null, 'notes' => null],
                    (object)['title' => 'Limpiar baño',    'type' => 'task',   'recipe_url' => null, 'assigned_to' => 1, 'notes' => null],
                ]),
                now()->addDay()->toDateString() => collect([
                    (object)['title' => 'Pizza casera', 'type' => 'dinner', 'recipe_url' => null, 'assigned_to' => null, 'notes' => null],
                ]),
            ]);
            $selectedEntries = $entriesByDate->get($selectedDate, collect());
        @endphp

        <div class="grid grid-cols-7 flex-1 overflow-y-auto bg-[#F7F5F0] divide-x divide-[#EAE8E2]">
            @for ($i = 0; $i <= 6; $i++)
                @php
                    $weekDay    = Carbon::parse($weekStart)->addDays($i)->locale('es');
                    $dateStr    = $weekDay->toDateString();
                    $dayEntries = $entriesByDate->get($dateStr, collect());
                    $isSelected = $dateStr === $selectedDate;
                @endphp

                <div @class([
                    'min-h-[120px] p-2 flex flex-col gap-1.5 transition-colors cursor-pointer',
                    'bg-[#FDFCF8]' => $isSelected,
                    'bg-[#F7F5F0]' => !$isSelected,
                ]) wire:click="selectDay('{{ $dateStr }}')">

                    @forelse($dayEntries as $entry)
                        <div @class([
                            'rounded-lg px-2.5 py-1.5 border-l-2 cursor-pointer hover:opacity-80 transition-opacity',
                            'bg-[#FFF3E8] border-[#E8894A]' => $entry->type === 'lunch',
                            'bg-[#EDE8FF] border-[#7B6FD4]' => $entry->type === 'dinner',
                            'bg-[#E8F5EE] border-[#4AAD7E]' => $entry->type === 'task',
                            'bg-[#E8F1FF] border-[#4A82E8]' => $entry->type === 'event',
                        ])>
                            <p class="text-[11.5px] font-medium text-[#2C2A26] truncate">{{ $entry->title }}</p>
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
                        <button wire:click.stop="openModal()"
                                class="mt-auto text-[11px] text-[#7A756D] bg-[#FFFEFB] border border-[#E0DDD6] hover:border-[#5B52C4] hover:text-[#5B52C4] rounded-md py-1.5 transition-colors w-full text-center">
                            + Añadir entrada
                        </button>
                    @endforelse

                </div>
            @endfor
        </div>

    </div>

    {{-- PANEL DETALLE --}}
    <div class="w-60 bg-[#FFFEFB] border-l border-[#EAE8E2] flex flex-col p-6 flex-shrink-0">

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
        <div class="mb-4">
            <p class="text-[10px] uppercase tracking-widest text-[#B5B0A6] mb-2">Comidas</p>
            <div class="flex items-start gap-2 mb-2">
                <div class="w-2 h-2 rounded-full bg-[#E8894A] mt-1.5 flex-shrink-0"></div>
                <div>
                    <p class="text-[12.5px] text-[#3D3A35]">{{ $lunch?->title ?? 'Sin añadir' }}</p>
                    <p class="text-[11px] text-[#A09B92]">Comida</p>
                </div>
            </div>
            <div class="flex items-start gap-2">
                <div class="w-2 h-2 rounded-full bg-[#7B6FD4] mt-1.5 flex-shrink-0"></div>
                <div>
                    <p class="text-[12.5px] text-[#3D3A35]">{{ $dinner?->title ?? 'Sin añadir' }}</p>
                    <p class="text-[11px] text-[#A09B92]">Cena</p>
                </div>
            </div>
        </div>

        {{-- Eventos y tareas --}}
        <div class="mb-4">
            <p class="text-[10px] uppercase tracking-widest text-[#B5B0A6] mb-2">Eventos y tareas</p>
            @forelse($tasks as $entry)
                <div class="flex items-start gap-2 mb-2">
                    <div @class([
                        'w-2 h-2 rounded-full mt-1.5 flex-shrink-0',
                        'bg-[#4AAD7E]' => $entry->type === 'task',
                        'bg-[#4A82E8]' => $entry->type === 'event',
                    ])></div>
                    <div>
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

        <button wire:click="openModal()"
                class="mt-auto w-full py-2 border border-dashed border-[#D8D4CC] rounded-lg text-xs text-[#A09B92] hover:border-[#7B6FD4] hover:text-[#5B52C4] transition-colors">
            + Nueva entrada
        </button>

    </div>

</div>