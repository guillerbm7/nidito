<?php

use Livewire\Component;
use App\Models\Recipe;
use App\Models\RecipeIngredient;
use Livewire\Attributes\On;
use Livewire\Attributes\Validate;

new class extends Component
{
    public bool $active = false;
    public bool $edit = false;
    public ?int $recipeId = null;

    #[Validate('required|string|max:150')]
    public string $title = '';

    #[Validate('required|string')]
    public string $instructions = '';

    public string $sourceUrl = '';

    #[Validate('array')]
    public array $ingredients = [];

    #[On('open-recipe-form')] 
    public function openModal($recipeId = null)
    {
        $this->reset(); // limpia todo antes de abrir
        $this->active = true;

        if ($recipeId) {
            $this->edit = true;
            $this->recipeId = $recipeId;

            $recipe = Recipe::with('ingredients')->find($recipeId);
            if ($recipe) {
                $this->title = $recipe->title;
                $this->instructions = $recipe->instructions;
                $this->sourceUrl = $recipe->source_url ?? '';

                // Convertir ingredientes a un array plano para edición dinámica
                $this->ingredients = $recipe->ingredients->map(function ($item) {
                    return [
                        'name'     => $item->name,
                        'quantity' => $item->quantity,
                        'unit'     => $item->unit,
                    ];
                })->toArray();
            }
        } else {
            $this->edit = false;
            $this->ingredients = []; // empezar sin ingredientes
        }
    }

    public function addIngredient()
    {
        $this->ingredients[] = [
            'name'     => '',
            'quantity' => null,
            'unit'     => '',
        ];
    }

    public function removeIngredient($index)
    {
        unset($this->ingredients[$index]);
        $this->ingredients = array_values($this->ingredients); // reindexar
    }

    public function saveRecipe()
    {
        $this->validate();

        if (!$this->edit) {
            // Comprobar duplicado sólo en creación
            $exists = Recipe::where('title', $this->title)->exists();
            if ($exists) {
                $this->addError('title', 'Ya existe una receta con este título.');
                return;
            }
        }

        if ($this->edit) {
            $recipe = Recipe::find($this->recipeId);
            if (!$recipe) {
                $this->addError('form', 'La receta no existe.');
                return;
            }
            $recipe->update([
                'title'       => $this->title,
                'instructions'=> $this->instructions,
                'source_url'  => $this->sourceUrl ?: null,
            ]);
            // Eliminar ingredientes antiguos y crear los nuevos
            $recipe->ingredients()->delete();
        } else {
            $recipe = Recipe::create([
                'created_by'  => session('selected_user_id'),
                'title'       => $this->title,
                'instructions'=> $this->instructions,
                'source_url'  => $this->sourceUrl ?: null,
            ]);
        }

        // Guardar ingredientes (con orden según el array)
        foreach ($this->ingredients as $index => $ing) {
            if (empty($ing['name'])) continue; // saltar ingredientes sin nombre
            RecipeIngredient::create([
                'recipe_id' => $recipe->id,
                'name'      => $ing['name'],
                'quantity'  => $ing['quantity'] ?: null,
                'unit'      => $ing['unit'] ?: null,
                'order'     => $index,
            ]);
        }

        $this->dispatch('refresh-recipes');
        $this->closeModal();
    }

    public function closeModal()
    {
        $this->reset();
        $this->active = false;
    }

};
?>

<div>
    @if($active)
        <div class="modal-backdrop" wire:click="closeModal"></div>

        <div class="fixed top-1/2 left-1/2 -translate-x-1/2 -translate-y-1/2 z-50
            w-[calc(100%-1rem)] max-w-3xl max-h-[90dvh] overflow-y-auto
            bg-surface-primary rounded-2xl border border-border p-6 shadow-xl">
            <div class="flex justify-between items-center mb-5">
                <h3 class="font-serif text-lg text-text-primary">
                    {{ $edit ? 'Editar receta' : 'Nueva receta' }}
                </h3>
                <button wire:click="closeModal" class="text-text-subtle hover:text-text-primary text-xl">×</button>
            </div>

            <form wire:submit="saveRecipe" class="flex flex-col gap-4">
                {{-- Título --}}
                <div>
                    <label class="form-label">Título *</label>
                    <input type="text" wire:model="title" class="form-input" placeholder="Ej: Tortilla de patatas">
                    @error('title') <span class="form-error">{{ $message }}</span> @enderror
                </div>

                {{-- Instrucciones --}}
                <div>
                    <label class="form-label">Instrucciones *</label>
                    <textarea wire:model="instructions" rows="6" class="form-input resize-none" 
                              placeholder="Paso a paso..."></textarea>
                    @error('instructions') <span class="form-error">{{ $message }}</span> @enderror
                </div>

                {{-- Enlace fuente --}}
                <div>
                    <label class="form-label">Enlace original (opcional)</label>
                    <input type="url" wire:model="sourceUrl" class="form-input" placeholder="https://...">
                </div>

                {{-- Ingredientes --}}
                <div>
                    <label class="form-label">Ingredientes</label>
                    <div class="space-y-2 mb-2 gap2">
                        @foreach($ingredients as $index => $ing)
                            <div class="grid grid-cols-1 sm:grid-cols-[1fr_auto_auto_auto] gap-2 items-center">
                                {{-- Nombre --}}
                                <input type="text" 
                                    wire:model="ingredients.{{ $index }}.name" 
                                    class="form-input w-full" 
                                    placeholder="Ej: Huevos">

                                {{-- Cantidad --}}
                                <input type="number" step="0.01" 
                                    wire:model="ingredients.{{ $index }}.quantity" 
                                    class="form-input w-full sm:w-28" 
                                    placeholder="Cantidad">

                                {{-- Unidad --}}
                                <select wire:model="ingredients.{{ $index }}.unit" 
                                        class="form-input w-full sm:w-28">
                                    <option value="unidad">Unidad</option>
                                    <option value="gr">Gramos</option>
                                    <option value="ml">Mililitros</option>
                                    <option value="cup">Vasos</option>
                                    <option value="spoons">Cucharas</option>
                                    <option value="splash">Chorro</option>
                                </select>

                                {{-- Botón eliminar --}}
                                <button type="button" 
                                        wire:click="removeIngredient({{ $index }})" 
                                        class="text-red-500 hover:text-red-700 text-lg px-2 justify-self-start sm:justify-self-auto">
                                    ✕
                                </button>
                            </div>
                        @endforeach
                    </div>
                    <button type="button" wire:click="addIngredient" class="btn-secondary text-sm py-1 px-3">
                        + Añadir ingrediente
                    </button>
                </div>

                {{-- Errores globales --}}
                @error('form') <p class="form-error">{{ $message }}</p> @enderror
                @error('duplicate') <p class="form-error">{{ $message }}</p> @enderror

                <div class="flex gap-2 mt-2">
                    <button type="button" wire:click="closeModal" class="btn-secondary flex-1">Cancelar</button>
                    <button type="submit" class="btn-primary flex-1">Guardar</button>
                </div>
            </form>
        </div>
    @endif
</div>