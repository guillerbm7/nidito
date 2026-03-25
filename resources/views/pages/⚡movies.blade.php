<?php

use App\Models\Movie;
use App\Models\User;
use Illuminate\Support\Facades\Http;
use Livewire\Component;

new class extends Component
{
    public string $search = '';

    public ?array $results = null;

    public function searchMovies(): void
    {
        $response = Http::get('https://api.themoviedb.org/3/search/movie', [
            'api_key' => config('services.tmdb.key'),
            'query' => $this->search,
        ]);

        $this->results = $response->json('results');
    }

    public function saveMovies(int $tmdbId): void
    {
        if (empty($this->results)) {
            $this->addError('search', 'No hay resultados para guardar.');

            return;
        }

        $movie = collect($this->results)->firstWhere('id', $tmdbId);

        if (! $movie) {
            $this->addError('search', 'No se encontró esa película en los resultados actuales.');

            return;
        }

        $releaseYear = null;
        if (! empty($movie['release_date'])) {
            $releaseYear = substr($movie['release_date'], 0, 4);
        }

        $saved = Movie::firstOrCreate(
            ['tmdb_id' => $tmdbId],
            [
                'added_by' => (int) session('selected_user_id'),
                'title' => $movie['title'] ?? 'Sin título',
                'poster_path' => $movie['poster_path'] ?? null,
                'overview' => $movie['overview'] ?? null,
                'rating' => $movie['vote_average'] ?? null,
                'vote_count' => $movie['vote_count'] ?? null,
                'release_year' => $releaseYear,
                'genre' => null,
            ]
        );

        if (! $saved->wasRecentlyCreated) {
            $this->addError('search', 'Esta película ya está añadida.');
        }
    }

    public function updatedSearch(): void
    {
        if (strlen($this->search) > 2) {
            $this->searchMovies();
        } else {
            $this->results = null;
        }
    }

    public function with(): array
    {
        return [
            'savedMovies' => Movie::all(),
            'savedTmdbIds' => Movie::pluck('tmdb_id')->map(fn ($id) => (int) $id),
            'users' => User::all()->keyBy('id'),
        ];
    }
};
?>

<div class="flex h-full">

    <div class="flex-1 flex flex-col overflow-hidden">

        {{-- =========================================================================
             HEADER - Search bar
             ========================================================================= --}}
        <div class="flex flex-wrap items-center justify-between gap-3 px-4 py-3 lg:px-7 lg:py-5 bg-surface-primary border-b border-border">
            <h2 class="font-serif text-2xl text-text-primary">Películas</h2>
            <div class="relative w-full sm:w-72">
                @error('search') <p class="text-xs text-red-500 mt-2">{{ $message }}</p> @enderror
                <input type="text"
                       wire:model.live.debounce.200ms="search"
                       placeholder="Buscar película..."
                       class="form-input">
            </div>
        </div>

        {{-- =========================================================================
             SEARCH RESULTS - TMDB results
             ========================================================================= --}}
        @if($results)
            <div class="p-4 lg:p-6 overflow-y-auto">
                <p class="text-[10px] uppercase tracking-widest text-text-label mb-4">Resultados</p>

                <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-6 gap-3">
                    @foreach($results as $movie)
                        @php
                            $isSaved = $savedTmdbIds->contains((int) $movie['id']);
                            $stars = round(($movie['vote_average'] ?? 0) / 2);
                        @endphp

                        <div class="card-with-avatar">

                            {{-- Poster --}}
                            @if($movie['poster_path'])
                                <img src="https://image.tmdb.org/t/p/w500{{ $movie['poster_path'] }}"
                                     class="w-14 h-20 object-cover rounded-lg flex-shrink-0">
                            @else
                                <div class="w-14 h-20 bg-surface-tertiary rounded-lg flex items-center justify-center text-xs text-text-placeholder">
                                    Sin carátula
                                </div>
                            @endif

                            {{-- Info --}}
                            <div>
                                <p class="text-sm font-medium text-text-primary truncate">{{ $movie['title'] }}</p>
                                <p class="text-xs text-text-subtle">{{ substr($movie['release_date'] ?? '', 0, 4) }}</p>

                                {{-- Rating stars --}}
                                <div class="flex gap-1 mt-1">
                                    @for($i = 1; $i <= 5; $i++)
                                        <span class="text-xs {{ $i <= $stars ? 'text-yellow-400' : 'text-gray-300' }}">★</span>
                                    @endfor
                                    <p class="text-[11px] text-text-muted line-clamp-2"> ({{ $movie['vote_count'] }})</p>
                                </div>

                                <p class="text-[11px] text-text-muted line-clamp-2 flex-1">{{ $movie['overview'] }}</p>

                                {{-- Add button --}}
                                @if($isSaved)
                                    <button disabled class="btn-add-disabled">✓ Ya añadida</button>
                                @else
                                    <button wire:click="saveMovies({{ $movie['id'] }})" class="btn-add">+ Añadir</button>
                                @endif
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>

        {{-- =========================================================================
             SAVED MOVIES - User's saved list
             ========================================================================= --}}
        @else
            <div class="p-4 lg:p-6 overflow-y-auto">
                <p class="text-[10px] uppercase tracking-widest text-text-label mb-4">Mi lista</p>

                @forelse($savedMovies as $movie)
                    @php
                        $stars = round(($movie->rating ?? 0) / 2);
                    @endphp
                    <div class="card-with-avatar mb-3">

                        {{-- Poster --}}
                        @if($movie->poster_path)
                            <img src="https://image.tmdb.org/t/p/w500{{ $movie->poster_path }}"
                                 class="w-14 h-20 object-cover rounded-lg">
                        @else
                            <div class="w-14 h-20 bg-surface-tertiary rounded-lg flex items-center justify-center text-xs text-text-placeholder">
                                Sin carátula
                            </div>
                        @endif

                        {{-- Info --}}
                        <div>
                            <p class="text-sm font-medium text-text-primary truncate">{{ $movie->title }}</p>
                            <p class="text-xs text-text-subtle">{{ $movie->release_year }}</p>

                            <div class="flex gap-1 mt-1">
                                @for($i = 1; $i <= 5; $i++)
                                    <span class="text-xs {{ $i <= $stars ? 'text-yellow-500' : 'text-gray-300' }}">★</span>
                                @endfor
                                <p class="text-[11px] text-text-muted"> ({{ $movie->vote_count }})</p>
                            </div>
                            <p class="text-[11px] text-text-muted line-clamp-2">{{ $movie->overview }}</p>
                        </div>

                        {{-- Added by user --}}
                        @if(isset($users[$movie->added_by]))
                            <div class="avatar-circle" style="--avatar-color: {{ $users[$movie->added_by]->avatar_color }}">
                                {{ strtoupper(substr($users[$movie->added_by]->name, 0, 1)) }}
                            </div>
                        @endif
                    </div>
                @empty
                    <p class="text-xs text-text-placeholder italic">No hay películas en tu lista todavía.</p>
                @endforelse
            </div>
        @endif

    </div>
</div>
