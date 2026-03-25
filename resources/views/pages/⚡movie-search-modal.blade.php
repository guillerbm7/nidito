<?php

use Livewire\Component;
use App\Models\Movie;
use Livewire\Attributes\On;
use App\Models\User;
use App\Models\MovieUser;
use Carbon\Carbon;

new class extends Component
{
    public bool $active = false;
    public ?array $movieData = null;
    public ?int $tmdbId = null;
    public string $title = '';
    public string $posterPath = '';
    public ?string $overview = null;
    public ?string $releaseYear = null;
    public ?string $voteAverage = null;
    public ?string $voteCount = null;
    public bool $isSaved = false;

    #[On('open-movie-search-modal')]
    public function openModal(array $movieData, bool $isSaved = false): void
    {
        $this->movieData = $movieData;
        $this->isSaved = $isSaved;

        $this->tmdbId = $movieData['id'] ?? null;
        $this->title = $movieData['title'] ?? 'Sin título';
        $this->posterPath = $movieData['poster_path'] ?? '';
        $this->overview = $movieData['overview'] ?? null;
        $this->releaseYear = isset($movieData['release_date']) 
            ? substr($movieData['release_date'], 0, 4) 
            : null;
        $this->voteAverage = $movieData['vote_average'] ?? null;
        $this->voteCount = $movieData['vote_count'] ?? null;

        $this->active = true;
    }

    public function closeModal(): void
    {
        $this->reset();
        $this->active = false;
    }

    public function saveMovie(): void
    {
        $exists = Movie::where('tmdb_id', $this->tmdbId)->exists();
        if ($exists) {
            $this->addError('duplicate', 'Esta película ya está en tu lista.');
            return;
        }
        $this->dispatch('add-movie-from-search', tmdbId: $this->tmdbId);
        $this->closeModal();
    }


};
?>

<div>
    @if($active)
        <div class="modal-backdrop" wire:click="closeModal"></div>

        {{-- Modal más ancho: max-w-3xl (768px) --}}
        <div class="fixed top-1/2 left-1/2 -translate-x-1/2 -translate-y-1/2 z-50
                    w-[calc(100%-1rem)] max-w-3xl max-h-[90dvh] overflow-y-auto
                    bg-surface-primary rounded-2xl border border-border p-0 shadow-xl">
            <div class="flex flex-col md:flex-row">
                {{-- Columna izquierda: imagen (ocupa toda la altura) --}}
                <div class="md:w-1/2 bg-surface-tertiary rounded-l-2xl overflow-hidden flex items-center justify-center">
                    @if($posterPath)
                        <img src="https://image.tmdb.org/t/p/w500{{ $posterPath }}"
                             class="w-full h-full object-cover" alt="{{ $title }}">
                    @else
                        <div class="w-full h-64 md:h-full flex items-center justify-center text-text-placeholder">
                            Sin carátula
                        </div>
                    @endif
                </div>

                {{-- Columna derecha: información --}}
                <div class="md:w-1/2 p-6 flex flex-col">
                    {{-- Botón cerrar (absoluto en la esquina superior derecha) --}}
                    <button wire:click="closeModal"
                            class="absolute top-4 right-4 text-text-subtle hover:text-text-primary text-2xl leading-none z-10">
                        ×
                    </button>

                    <h3 class="font-serif text-2xl text-text-primary mb-1">{{ $title }}</h3>
                    <p class="text-sm text-text-subtle mb-2">{{ $releaseYear ?? 'Año desconocido' }}</p>

                    {{-- Rating TMDB con estrellas --}}
                    <div class="flex items-center gap-2 mb-4">
                        @php
                            $stars = round(($voteAverage ?? 0) / 2);
                        @endphp
                        <div class="flex gap-0.5">
                            @for($i = 1; $i <= 5; $i++)
                                <span class="text-sm {{ $i <= $stars ? 'text-yellow-500' : 'text-gray-300' }}">★</span>
                            @endfor
                        </div>
                        <span class="text-xs text-text-muted">({{ $voteCount ?? 0 }} votos)</span>
                    </div>

                    {{-- Sinopsis --}}
                    <div class="mb-6">
                        <p class="text-xs uppercase tracking-wider text-text-label mb-1">Sinopsis</p>
                        <p class="text-sm text-text-muted leading-relaxed line-clamp-6">{{ $overview ?? 'Sin descripción disponible.' }}</p>
                    </div>

                    {{-- Botón añadir --}}
                    @if(!$isSaved)
                        <button wire:click="saveMovie"
                                class="mt-auto w-full py-2.5 rounded-xl bg-accent-purple text-white font-medium hover:bg-accent-purple-dark transition-colors">
                            + Añadir a mi lista
                        </button>
                    @else
                        <button disabled
                                class="mt-auto w-full py-2.5 rounded-xl bg-accent-purple-border text-accent-purple font-medium cursor-not-allowed opacity-70">
                            ✓ Ya añadida
                        </button>
                    @endif
                </div>
            </div>
        </div>
    @endif
</div>