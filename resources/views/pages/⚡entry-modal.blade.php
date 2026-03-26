<?php

use App\Models\CalendarEntry;
use App\Models\User;
use Livewire\Attributes\On;
use Livewire\Attributes\Validate;
use Livewire\Component;
use App\Models\Recipe;

new class extends Component
{
    public bool $active = false;
    public bool $delete = false;
    public bool $edit = false;
    public ?int $entryId = null;

    #[Validate('required')]
    public string $title = '';

    #[Validate('required|date')]
    public string $date = '';

    #[Validate('required')]
    public string $type = '';

    public ?int $recipe_id = null;
    public ?string $recipe_url = null;
    public ?string $notes = null;
    public ?int $assigned_to = null;
    public ?CalendarEntry $entry = null;

    // Open modal for new entry 
    #[On('open-entry-modal')]
    public function openModal(string $date): void
    {
        $this->active = true;
        $this->date = $date;
    }

    //Open modal for editing existing entry
    #[On('open-entry-modal-edit')]
    public function editModal(string $entryId): void
    {
        $this->active = true;
        $this->edit = true;
        $this->entryId = (int) $entryId;

        $this->entry = CalendarEntry::find($this->entryId);
        if (! $this->entry) {
            $this->closeModal();
            return;
        }
        
        $this->title = $this->entry->title;
        $this->type = $this->entry->type;
        $this->date = $this->entry->date;
        $this->notes = $this->entry->notes ?? '';
        $this->recipe_id = $this->entry->recipe_id ?? null;
        $this->recipe_url = $this->entry->recipe_url ?? '';
        $this->assigned_to = $this->entry->assigned_to;
    }

    // Close modal and reset
    public function closeModal(): void
    {
        $this->active = false;
        $this->reset();
    }

    //Save or delete entry
    public function saveModal(): void
    {
        // Delete mode
        if ($this->delete) {
            $calendarEntry = CalendarEntry::find($this->entryId);
            if (! $calendarEntry) {
                $this->closeModal();
                return;
            }
            $calendarEntry->delete();
            $this->closeModal();
            $this->dispatch('entry-saved');
            return;
        }

        // Prevent duplicate meals
        if ($this->type === 'lunch' || $this->type === 'dinner') {
            $exists = CalendarEntry::where('type', $this->type)
                ->where('date', $this->date)
                ->when($this->edit, fn ($q) => $q->where('id', '!=', $this->entry->id))
                ->exists();

            if ($exists) {
                $this->addError('type', 'Ya existe una entrada de ese tipo para este día.');
                return;
            }
        }

        $this->validate();
        
        // Update existing
        if ($this->edit) {
            
            $calendarEntry = CalendarEntry::find($this->entryId);
            if (! $calendarEntry) {
                $this->closeModal();
                return;
            }
            $calendarEntry->update([
                'assigned_to' => $this->assigned_to,
                'title' => $this->title,
                'type' => $this->type,
                'recipe_id' => $this->recipe_id,
                'notes' => $this->notes,
                'recipe_url' => $this->recipe_url,
            ]);
        // Create new
        } else {
            CalendarEntry::create([
                'created_by' => session('selected_user_id'),
                'assigned_to' => $this->assigned_to,
                'title' => $this->title,
                'date' => $this->date,
                'recipe_id' => $this->recipe_id,
                'type' => $this->type,
                'notes' => $this->notes,
                'recipe_url' => $this->recipe_url,
            ]);
        }

        $this->closeModal();
        $this->dispatch('entry-saved');
    }

    // Open delete confirmation 
    #[On('entry-modal-delete')]
    public function deleteModal($entryId)
    {
        $this->active = true;
        $this->delete = true;
        $this->entryId = (int) $entryId;
    }

    // Reset type error when changed
    public function updatedType(): void
    {
        $this->resetErrorBag('type');
    }

    public function with(): array
    {
        return [
            'users' => User::all(),
            'recipes' => Recipe::all(),
        ];
    }
};
?>
<div>

    {{-- CREATE / EDIT MODAL --}}
    @if($active && !$delete)
        <div class="modal-backdrop" wire:click="closeModal"></div>

        <div class="modal-card">
            {{-- Header --}}
            <div class="flex items-center justify-between mb-5">
                <h3 class="font-serif text-lg text-text-primary"> 
                    @if($edit)
                        Editar entrada
                    @else
                        Nueva entrada
                    @endif
                </h3>
                <button type="button" wire:click="closeModal"
                        class="text-text-subtle hover:text-text-primary transition-colors text-xl leading-none">
                    ×
                </button>
            </div>

            {{-- Form --}}
            <form wire:submit="saveModal" class="flex flex-col gap-4">

                {{-- Title --}}
                <div>
                    @php
                        $value = null;
                        if($recipe_id){
                            $value = Recipe::find($recipe_id)->first()->title;
                        }
                    @endphp
                    <label class="form-label">Título</label>
                    <input type="text" wire:model="title" placeholder="Ej: Pasta carbonara" class="form-input" value="{{ $value }}">
                    @error('title') <span class="form-error">{{ $message }}</span> @enderror
                </div>

                {{-- Type --}}
                <div>
                    <label class="form-label">Tipo</label>
                    <select wire:model.live="type" class="form-input">
                        <option value="">— Elige un tipo —</option>
                        <option value="lunch">🍽️ Comida</option>
                        <option value="dinner">🌙 Cena</option>
                        <option value="task">✅ Tarea</option>
                        <option value="event">📅 Evento</option>
                    </select>
                    @error('type') <span class="form-error">{{ $message }}</span> @enderror
                </div>

                {{-- Assigned to --}}
                <div>
                    <label class="form-label">Asignado a</label>
                    <select wire:model="assigned_to" class="form-input">
                        <option value="">— Sin asignar —</option>
                        @foreach($users as $user)
                            <option value="{{ $user->id }}">{{ $user->name }}</option>
                        @endforeach
                    </select>
                    @error('assigned_to') <span class="form-error">{{ $message }}</span> @enderror
                </div>
                
                {{-- Recipe URL (only for meals) --}}
                @if($type === 'lunch' || $type === 'dinner')
                   {{-- <div>
                        <label class="form-label">Enlace a receta</label>
                        <input type="text" wire:model="recipe_url" placeholder="https://..." class="form-input">
                    </div> --}}
                    <div>
                        <label class="form-label">Receta</label>
                        <select  wire:model.live="recipe_id" class="form-input">
                            <option value="">— Sin asignar —</option>
                            @foreach ( $recipes as $recipe)
                                <option value="{{ $recipe->id }}">{{ $recipe->title }}</option>
                            @endforeach
                        </select>
                    </div>
                @endif

                {{-- Notes --}}
                <div>
                    <label class="form-label">Notas</label>
                    <textarea wire:model="notes" placeholder="Notas opcionales..." rows="2" class="form-input resize-none"></textarea>
                </div>

                <input type="hidden" wire:model="date">

                {{-- Buttons --}}
                <div class="flex gap-2 mt-1">
                    <button type="button" wire:click="closeModal" class="btn-secondary">Cancelar</button>
                    <button type="submit" class="btn-primary">Guardar</button>
                </div>

            </form>
        </div>

    {{-- DELETE CONFIRMATION MODAL --}}
    @elseif($active && $delete)
        <div class="modal-backdrop" wire:click="closeModal"></div>

        <div class="modal-card">
            <form wire:submit="saveModal" class="flex flex-col gap-4">
                <h3 class="font-serif text-lg text-text-primary"> 
                    ¿Estás seguro de que quieres eliminar esta entrada?
                </h3>
                <div class="flex gap-2 mt-1">
                    <button type="button" wire:click="closeModal" class="btn-secondary">Cancelar</button>
                    <button type="submit" class="btn-danger">Eliminar</button>
                </div>
            </form>
        </div>
    @endif
</div>
