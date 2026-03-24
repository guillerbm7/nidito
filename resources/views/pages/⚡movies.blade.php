<?php

use Livewire\Component;
use App\Models\Movie;
use Illuminate\Support\Facades\Http;
use App\Models\User;

new class extends Component
{
    public string $search = '';
    public ?array $results = null;

    public function searchMovies(){

        $response = Http::get('https://api.themoviedb.org/3/search/movie', [
            'api_key' => config('services.tmdb.key'),
            'query' => $this->search,
        ]);

        $this->results = $response->json('results');
    }

    public function saveMovies(int $tmdbId): void{
     
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
        if(strlen($this->search) > 2) {
            $this->searchMovies();
        } else {
            $this->results = null;
        }
    }

    public function with(){
        return [
            'savedMovies' => Movie::all(),
            'savedTmdbIds' => Movie::pluck('tmdb_id')->map(fn ($id) => (int) $id),
        ];
    }
};
?>

<div class="flex h-full">

    <div class="flex-1 flex flex-col overflow-hidden">

        {{-- Cabecera --}}
        <div class="flex flex-wrap items-center justify-between gap-3 px-4 py-3 lg:px-7 lg:py-5 bg-[#FFFEFB] border-b border-[#EAE8E2]">
            <h2 class="font-serif text-2xl text-[#2C2A26]">Películas</h2>
            <div class="relative w-full sm:w-72">
                @error('search') <p class="text-xs text-red-500 mt-2">{{ $message }}</p> @enderror
                <input type="text"
                       wire:model.live.debounce.200ms="search"
                       placeholder="Buscar película..."
                       class="w-full px-4 py-2 rounded-lg border border-[#E0DDD6] bg-[#F7F5F0] text-sm text-[#2C2A26] placeholder-[#C0BAB0] focus:outline-none focus:border-[#5B52C4] transition-colors">
            </div>
        </div>

        {{-- Resultados --}}
        @if($results)
            <div class="p-4 lg:p-6 overflow-y-auto">
                <p class="text-[10px] uppercase tracking-widest text-[#B5B0A6] mb-4">Resultados</p>

                <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-6 gap-3">
                    @foreach($results as $movie)
                        @php
                            $isSaved = $savedTmdbIds->contains((int) $movie['id']);
                            $stars = round(($movie['vote_average'] ?? 0) / 2);
                        @endphp

                        <div class="flex gap-3 bg-[#FFFEFB] border border-[#EAE8E2] rounded-xl p-3">

                            {{-- Poster --}}
                            @if($movie['poster_path'])
                                <img src="https://image.tmdb.org/t/p/w500{{ $movie['poster_path'] }}"
                                     class="w-14 h-20 object-cover rounded-lg flex-shrink-0">
                            @else
                                <div class="w-14 h-20 bg-[#F2EFE8] rounded-lg flex items-center justify-center text-xs text-[#C0BAB0]">
                                    Sin carátula
                                </div>
                            @endif

                            {{-- Info --}}
                            <div class="flex flex-col flex-1 min-w-0">
                                <p class="text-sm font-medium text-[#2C2A26] truncate">{{ $movie['title'] }}</p>
                                <p class="text-xs text-[#A09B92]">{{ substr($movie['release_date'] ?? '', 0, 4) }}</p>

                                {{-- ⭐ Rating --}}
                                <div class="flex gap-1 mt-1">
                                    @for($i = 1; $i <= 5; $i++)
                                        <span class="text-x {{ $i <= $stars ? 'text-yellow-400' : 'text-gray-300' }}">★</span>
                                    @endfor
                                    <p class="text-[11px] text-[#7A756D] line-clamp-2"> ({{ $movie['vote_count'] }})</p>
                                </div>

                                <p class="text-[11px] text-[#7A756D] line-clamp-2 flex-1">{{ $movie['overview'] }}</p>

                                {{-- Botón --}}
                                @if($isSaved)
                                    <button disabled
                                            class="mt-2 text-[11px] bg-[#EDE8FF] text-[#5B52C4] border border-[#D9D2FF] rounded-md py-1 w-full cursor-not-allowed">
                                        ✓ Ya añadida
                                    </button>
                                @else
                                    <button wire:click="saveMovies({{ $movie['id'] }})"
                                            class="mt-2 text-[11px] text-[#7A756D] bg-[#F7F5F0] border border-[#E0DDD6] hover:border-[#5B52C4] hover:text-[#5B52C4] rounded-md py-1 w-full">
                                        + Añadir
                                    </button>
                                @endif
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>

        {{-- Guardadas --}}
        @else
            <div class="p-4 lg:p-6 overflow-y-auto">
                <p class="text-[10px] uppercase tracking-widest text-[#B5B0A6] mb-4">Mi lista</p>

                @forelse($savedMovies as $movie)
                    @php
                        $stars = round(($movie->rating ?? 0) / 2);
                    @endphp
                    <div class="flex gap-3 bg-[#FFFEFB] border border-[#EAE8E2] rounded-xl p-3 mb-3">

                        {{-- Poster --}}
                        @if($movie->poster_path)
                            <img src="https://image.tmdb.org/t/p/w500{{ $movie->poster_path }}"
                                 class="w-14 h-20 object-cover rounded-lg">
                        @else
                            <div class="w-14 h-20 bg-[#F2EFE8] rounded-lg flex items-center justify-center text-xs text-[#C0BAB0]">
                                Sin carátula
                            </div>
                        @endif

                        {{-- Info --}}
                        <div class="flex flex-col flex-1 min-w-0">
                            <p class="text-sm font-medium text-[#2C2A26] truncate">{{ $movie->title }}</p>
                            <p class="text-xs text-[#A09B92]">{{ $movie->release_year }}</p>

                            {{-- Rating --}}
                            <div class="flex gap-1 mt-1">
                                @for($i = 1; $i <= 5; $i++)
                                    <span class="text-s {{ $i <= $stars ? 'text-yellow-500' : 'text-gray-300' }}">★</span>
                                @endfor
                                <p class="text-[11px] text-[#7A756D]"> ({{ $movie->vote_count }})</p>
                            </div>
                            <p class="text-[11px] text-[#7A756D] line-clamp-2">{{ $movie->overview }}</p>
                        </div>
                        {{-- Added By --}}
                        
                        <div class="w-7 h-7 rounded-full flex items-center justify-center text-xs font-medium flex-shrink-0" 
                            style="background-color: {{ $movie->added_by == 2 ? '#ec489922' : '#6366f122'}}; color: {{ $movie->added_by == 2 ? '#ec4899' : '#6366f1' }}">
                            {{ $movie->added_by == 2 ? 'M' : 'G' }}
                        </div>
                    
                    </div>

                @empty
                    <p class="text-xs text-[#C0BAB0] italic">No hay películas en tu lista todavía.</p>
                @endforelse
            </div>
        @endif

    </div>
</div>