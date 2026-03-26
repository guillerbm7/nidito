<?php

use Livewire\Component;
use App\Models\Recipe;
use Livewire\Attributes\On;
use App\Models\User;

new class extends Component
{
    public bool $active = false;
    public ?int $recipeId = null;
    public ?Recipe $recipe = null;

    #[On('open-recipe-detail')] 
    public function openModal($recipeId){
        $this->recipe = Recipe::with(['createdBy', 'ingredients'])->find($recipeId);
        $this->active = true;
    }

    public function closeModal(): void
    {
        $this->active = false;
        $this->reset();
    }

    public function deleteModal()
    {
        
        $this->recipe->delete();
        $this->dispatch('refresh-recipes');
        $this->closeModal();
        
    }

    public function editRecipe()
    {
        $this->dispatch('open-recipe-form', recipeId: $this->recipe->id);
        $this->closeModal();
    }

    public function with(): array
    {
        return [
           
        ];
    }
};
?>
<div>
    @if($active)
        <div class="modal-backdrop" wire:click="closeModal"></div>

        <div class="fixed top-1/2 left-1/2 -translate-x-1/2 -translate-y-1/2 z-50
                    w-[calc(100%-1rem)] max-w-3xl max-h-[90dvh] overflow-y-auto
                    bg-surface-primary rounded-2xl border border-border shadow-xl">
            <div class="flex flex-col md:flex-row">
                {{-- Columna izquierda: información principal --}}
                <div class="md:w-1/2 p-6 flex flex-col">
                    <button wire:click="closeModal"
                            class="absolute top-4 right-4 text-text-subtle hover:text-text-primary text-2xl leading-none z-10">
                        ×
                    </button>

                    <h3 class="font-serif text-2xl text-text-primary mb-1">{{ $recipe->title }}</h3>
                    <p class="text-sm text-text-subtle mb-2">{{ $recipe->created_at->format('d/m/Y') }}</p>

                    {{-- Creador --}}
                    <div class="flex items-center gap-2 mb-4">
                        <div class="avatar-circle" style="--avatar-color: {{ $recipe->createdBy->avatar_color }}">
                            {{ strtoupper(substr($recipe->createdBy->name, 0, 1)) }}
                        </div>
                        <span class="text-sm text-text-muted">Creada por {{ $recipe->createdBy->name }}</span>
                    </div>

                    {{-- Instrucciones --}}
                    <div class="mb-6">
                        <p class="text-xs uppercase tracking-wider text-text-label mb-1">Instrucciones</p>
                        <div class="text-sm text-text-muted leading-relaxed prose prose-sm max-w-none">
                            {!! nl2br(e($recipe->instructions)) !!}
                        </div>
                    </div>

                    {{-- Enlace fuente (si existe) --}}
                    @if($recipe->source_url)
                        <div class="mb-4">
                            <a href="{{ $recipe->source_url }}" target="_blank" 
                               class="text-sm text-accent-purple-light hover:underline inline-flex items-center gap-1">
                                🔗 Ver fuente original
                            </a>
                        </div>
                    @endif

                    {{-- Botones de acción --}}
                    <div class="flex gap-2 mt-2">
                        <button wire:click="editRecipe" class="btn-primary flex-1">Editar</button>
                        <button wire:click="deleteModal" wire:confirm="Are you sure you want to delete this post?" class="btn-danger flex-1">Eliminar</button>
                    </div>
                </div>

                {{-- Columna derecha: ingredientes --}}
                <div class="md:w-1/2 p-6 bg-surface-tertiary rounded-r-2xl">
                    <p class="text-xs uppercase tracking-wider text-text-label mb-3">Ingredientes</p>
                    @if($recipe->ingredients->count())
                        <ul class="space-y-2">
                            @foreach($recipe->ingredients as $ingredient)
                                <li class="text-sm text-text-primary">
                                    {{ $ingredient->name }}
                                    @if($ingredient->quantity)
                                        <span class="text-text-muted text-xs">
                                            ({{ $ingredient->quantity }} {{ $ingredient->unit }})
                                        </span>
                                    @endif
                                </li>
                            @endforeach
                        </ul>
                    @else
                        <p class="text-sm text-text-muted italic">No hay ingredientes registrados.</p>
                    @endif
                </div>
            </div>
        </div>
    @endif
</div>