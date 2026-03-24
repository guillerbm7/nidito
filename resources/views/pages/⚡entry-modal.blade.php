<?php

use Livewire\Component;
use Livewire\Attributes\Validate;
use Livewire\Attributes\On;
use Carbon\Carbon;
use App\Models\CalendarEntry;
use App\Models\User;

new class extends Component
{
    public bool $active = false;
    public bool $delete = false;
    public bool $edit = false;
    public ?int $entryId = null;
    #[Validate('required')]
    public string $title = '';
    #[Validate('required|date')]
    public string $date  = '';
    #[Validate('required')]
    public string $type  = '';
    public ?string $recipe_url = null;
    public ?string $notes = null;
    public ?int $assigned_to  = null;
    public ?CalendarEntry $entry = null;
   

    #[On('open-entry-modal')]
    public function openModal(string $date): void
    {
        $this->active = true;
        $this->date = $date;
    }

    #[On('open-entry-modal-edit')]
    public function editModal(string $entryId): void
    {   
        $this->active = true;
        $this->edit = true;
        $this->entryId = (int) $entryId;

        $this->entry = CalendarEntry::find($this->entryId);
        if(!$this->entry){
            $this->closeModal();
            return;
        }
        $this->title      = $this->entry->title;
        $this->type       = $this->entry->type;
        $this->date       = $this->entry->date;
        $this->notes      = $this->entry->notes ?? '';
        $this->recipe_url = $this->entry->recipe_url ?? '';
        $this->assigned_to = $this->entry->assigned_to;

    }

    public function closeModal(): void
    {
        $this->active = false;
        $this->reset();
    }

    public function saveModal(): void
    {   
        if($this->delete){
            
            $calendarEntry = CalendarEntry::find($this->entryId);
            if(!$calendarEntry){
                $this->closeModal();
                return;
            }
            $calendarEntry->delete();

            $this->closeModal();

            $this->dispatch('entry-saved');
            return;

        }
        if($this->type === 'lunch' || $this->type === 'dinner'){
            $exists = CalendarEntry::where('type', $this->type)
                ->where('date', $this->date)
                ->when($this->edit, fn($q) => $q->where('id', '!=', $this->entry->id))
                ->exists();

            if($exists) {
                $this->addError('type', 'Ya existe una entrada de ese tipo para este día.');
                return;
            }
        }
        
        $this->validate();

        if($this->edit){
            $calendarEntry = CalendarEntry::find($this->entryId);

            if(!$calendarEntry){
                $this->closeModal();
                return;
            }
            
            $calendarEntry->update([
                'assigned_to' => $this->assigned_to, 
                'title' => $this->title, 
                'type' => $this->type,
                'notes' => $this->notes,
                'recipe_url' => $this->recipe_url
            ] );

        }else{
            CalendarEntry::create([
                'created_by' => session('selected_user_id'), 
                'assigned_to' => $this->assigned_to, 
                'title' => $this->title, 
                'date' => $this->date, 
                'type' => $this->type,
                'notes' => $this->notes,
                'recipe_url' => $this->recipe_url
            ]);
        }
        
        $this->closeModal();

        $this->dispatch('entry-saved');
        
    }
    #[On('entry-modal-delete')]
    public function deleteModal($entryId){
        $this->active = true;
        $this->delete = true;
        $this->entryId = (int) $entryId;
        
    }

    public function updatedType(): void
    {
        $this->resetErrorBag('type');
    }

    public function with(): array
    {
        return [
            'users' => User::all(),
        ];
    }

};
?>
<div>
    
   
    
    @if($active && !$delete)
        {{-- Fondo oscuro --}}
        <div class="fixed inset-0 bg-black/40 z-40"
             wire:click="closeModal">
        </div>

        {{-- Tarjeta del modal --}}
        <div class="fixed top-1/2 left-1/2 -translate-x-1/2 -translate-y-1/2 z-50
                    w-[calc(100%-1rem)] max-w-md max-h-[90dvh] overflow-y-auto bg-[#FFFEFB] rounded-2xl shadow-sm border border-[#EAE8E2] p-6">

            <div class="flex items-center justify-between mb-5">
                <h3 class="font-serif text-lg text-[#2C2A26]"> 
                    
                    @if($edit)
                        Editar entrada
                    @else
                        Nueva entrada
                    @endif
                </h3>
                <button type="button" wire:click="closeModal"
                        class="text-[#A09B92] hover:text-[#2C2A26] transition-colors text-xl leading-none">
                    ×
                </button>
            </div>

            <form wire:submit="saveModal" class="flex flex-col gap-4">

                {{-- Título --}}
                <div>
                    <label class="text-xs text-[#7A756D] uppercase tracking-wider mb-1 block">Título</label>
                    <input type="text" wire:model="title" placeholder="Ej: Pasta carbonara"
                           class="w-full px-3 py-2 rounded-lg border border-[#E0DDD6] bg-[#F7F5F0] text-sm text-[#2C2A26] placeholder-[#C0BAB0] focus:outline-none focus:border-[#5B52C4] transition-colors">
                    @error('title')
                        <span class="text-xs text-red-400 mt-1 block">{{ $message }}</span>
                    @enderror
                </div>

                {{-- Tipo --}}
                <div>
                    <label class="text-xs text-[#7A756D] uppercase tracking-wider mb-1 block">Tipo</label>
                    <select wire:model.live="type"
                            class="w-full px-3 py-2 rounded-lg border border-[#E0DDD6] bg-[#F7F5F0] text-sm text-[#2C2A26] focus:outline-none focus:border-[#5B52C4] transition-colors">
                        <option value="">— Elige un tipo —</option>
                        <option value="lunch">🍽️ Comida</option>
                        <option value="dinner">🌙 Cena</option>
                        <option value="task">✅ Tarea</option>
                        <option value="event">📅 Evento</option>
                    </select>
                    @error('type')
                        <span class="text-xs text-red-400 mt-1 block">{{ $message }}</span>
                    @enderror
                </div>

                {{-- Asignado a --}}
                <div>
                    <label class="text-xs text-[#7A756D] uppercase tracking-wider mb-1 block">Asignado a</label>
                    <select wire:model="assigned_to"
                            class="w-full px-3 py-2 rounded-lg border border-[#E0DDD6] bg-[#F7F5F0] text-sm text-[#2C2A26] focus:outline-none focus:border-[#5B52C4] transition-colors">
                        <option value="">— Sin asignar —</option>
                        @foreach($users as $user)
                            <option value="{{ $user->id }}">{{ $user->name }}</option>
                        @endforeach
                    </select>
                    @error('assigned_to')
                        <span class="text-xs text-red-400 mt-1 block">{{ $message }}</span>
                    @enderror
                </div>
                {{-- Receta --}}
                
                @if($type === 'lunch' || $type === 'dinner')
                    
                    <div>
                        <label class="text-xs text-[#7A756D] uppercase tracking-wider mb-1 block">Enlace a receta</label>
                        <input type="text" wire:model="recipe_url" placeholder="https://..."
                            class="w-full px-3 py-2 rounded-lg border border-[#E0DDD6] bg-[#F7F5F0] text-sm text-[#2C2A26] placeholder-[#C0BAB0] focus:outline-none focus:border-[#5B52C4] transition-colors">
                    </div>
                @endif

                {{-- Notas --}}
                <div>
                    <label class="text-xs text-[#7A756D] uppercase tracking-wider mb-1 block">Notas</label>
                    <textarea wire:model="notes" placeholder="Notas opcionales..." rows="2"
                              class="w-full px-3 py-2 rounded-lg border border-[#E0DDD6] bg-[#F7F5F0] text-sm text-[#2C2A26] placeholder-[#C0BAB0] focus:outline-none focus:border-[#5B52C4] transition-colors resize-none">
                    </textarea>
                </div>

                <input type="hidden" wire:model="date">

                {{-- Botones --}}
                <div class="flex gap-2 mt-1">
                    <button type="button" wire:click="closeModal"
                            class="flex-1 py-2 rounded-lg border border-[#E0DDD6] text-sm text-[#7A756D] hover:bg-[#F2EFE8] transition-colors">
                        Cancelar
                    </button>
                    <button type="submit"
                            class="flex-1 py-2 rounded-lg bg-[#5B52C4] text-white text-sm hover:bg-[#4F46B8] transition-colors">
                        Guardar
                    </button>
                </div>

            </form>
        </div>
    @elseif($active && $delete)
        {{-- Fondo oscuro --}}
        <div class="fixed inset-0 bg-black/40 z-40"
             wire:click="closeModal">
        </div>

        {{-- Tarjeta del modal --}}
        <div class="fixed top-1/2 left-1/2 -translate-x-1/2 -translate-y-1/2 z-50 w-[calc(100%-1rem)] max-w-md max-h-[90dvh] overflow-y-auto bg-[#FFFEFB] rounded-2xl shadow-sm border border-[#EAE8E2] p-6">
            <div class="flex items-center justify-between mb-5"></div>
                <form wire:submit="saveModal" class="flex flex-col gap-4">
                    <div class="flex items-center justify-between mb-5">
                        <h3 class="font-serif text-lg text-[#2C2A26]"> 
                            ¿Estás seguro de que quieres eliminar esta entrada?
                        </h3>
                        
                    </div>
                    <div class="flex gap-2 mt-1">
                        <button type="button" wire:click="closeModal"
                                class="flex-1 py-2 rounded-lg border border-[#E0DDD6] text-sm text-[#7A756D] hover:bg-[#F2EFE8] transition-colors">
                            Cancelar
                        </button>
                        <button type="submit"
                                class="flex-1 py-2 rounded-lg bg-[#D6220D] text-white text-sm hover:bg-[#D94E3D] transition-colors">
                            Eliminar
                        </button>
                    </div>
                </form>
            </div>
        </div>
    @endif
</div>