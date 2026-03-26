<?php

use Livewire\Component;
use Livewire\WithPagination;
use App\Models\Recipe;
use Illuminate\Support\Facades\Http;
use Livewire\Attributes\On;

new class extends Component
{

    use WithPagination;

    public string $search = '';
    
    public function updatedSearch(){
        $this->resetPage();
    }

    public function getRecipesQuery(){

        $query = Recipe::with('createdBy')->withCount('ingredients')->orderBy('created_at', 'DESC');

        if(!empty($this->search)){
            $query->where('title', 'like', "%{$this->search}%");
        }
        
        return $query->orderBy('created_at', 'DESC');
    }
    public function openRecipeDetail($recipeId)
    {
        $this->dispatch('open-recipe-detail', recipeId: $recipeId);
    }
    
    public function openRecipeForm($recipeId = null)
    {
        $this->dispatch('open-recipe-form', recipeId: $recipeId);
    }

    #[On('refresh-recipes')] 
    public function refresh() {}

    public function with(){
        return [
            'recipes' => $this->getRecipesQuery()->paginate(12),
        ];
    }
   
};
?>

<div class="flex h-full">
    <div class="flex-1 flex flex-col overflow-hidden">

        {{-- HEADER - Title and search --}}
        <div class="flex flex-wrap items-center justify-between gap-3 px-4 py-3 lg:px-7 lg:py-5 bg-surface-primary border-b border-border">
            <h2 class="font-serif text-2xl text-text-primary">Recetas</h2>
            <div class="flex gap-2 items-center">
                {{-- Botón para nueva receta --}}
                <button wire:click="openRecipeForm()" class="btn-primary">
                    + Nueva receta
                </button>
                <div class="relative w-full sm:w-72">
                    <input type="text"
                           wire:model.live.debounce.300ms="search"
                           placeholder="Buscar receta..."
                           class="form-input">
                </div>
            </div>
        </div>

        {{-- RECIPE LIST --}}
        <div class="p-4 lg:p-6 overflow-y-auto">
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-4">
                @forelse($recipes as $recipe)
                    <div class="bg-surface-primary border border-border rounded-xl p-3 cursor-pointer hover:shadow-sm transition-shadow relative flex gap-3"
                         wire:click="openRecipeDetail({{ $recipe->id }})">

                        {{-- Icono representativo --}}
                        <div class="w-12 h-12 bg-surface-tertiary rounded-lg shrink-0 flex items-center justify-center text-2xl">
                            📖
                        </div>

                        {{-- Info de la receta --}}
                        <div class="flex-1 min-w-0">
                            <p class="text-sm font-medium text-text-primary truncate">{{ $recipe->title }}</p>
                            <p class="text-xs text-text-subtle">{{ $recipe->created_at->format('d/m/Y') }}</p>
                            <p class="text-xs text-text-muted mt-1">
                                {{ $recipe->ingredients_count ?? $recipe->ingredients->count() }} ingredientes
                            </p>
                            @if($recipe->source_url)
                                <a href="{{ $recipe->source_url }}" target="_blank" class="text-xs text-accent-purple-light hover:underline inline-flex items-center gap-1 mt-1"
                                   wire:click.stop>
                                    🔗 Ver fuente
                                </a>
                            @endif
                        </div>

                        {{-- Avatar del creador (esquina superior derecha) --}}
                        @if($recipe->createdBy)
                            <div class="absolute top-2 right-2 avatar-circle"
                                 style="--avatar-color: {{ $recipe->createdBy->avatar_color }}">
                                {{ strtoupper(substr($recipe->createdBy->name, 0, 1)) }}
                            </div>
                        @endif
                    </div>
                @empty
                    <p class="text-xs text-text-placeholder italic col-span-full">No hay recetas todavía.</p>
                @endforelse
            </div>

            
        </div>
    </div>
    <livewire:pages::recipe-detail-modal />
    <livewire:pages::recipe-form-modal />
</div>