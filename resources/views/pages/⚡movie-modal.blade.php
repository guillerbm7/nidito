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
    public ?int $movieId = null;
    public ?Movie $movie = null;
    public ?int $userRating = null;
    public ?string $notes = null;
    public ?bool $userWatched = null;
    public ?bool $canDelete = false;

    #[On('open-movie-modal')]
    public function openModal(int $movieId): void
    {
        $this->movieId = $movieId;
        $this->movie = Movie::with('genres')->find($this->movieId);

        $movieUser = MovieUser::where('movie_id', $this->movieId)->where('user_id', session('selected_user_id'))->first();

        $this->userRating = $movieUser?->rating;
        $this->notes = $movieUser?->notes;
        $this->userWatched = !is_null($movieUser?->watched_at);
        
        $hasBeenWatched  = MovieUser::where('movie_id', $this->movieId)
            ->whereNotNull('watched_at')->exists();

        $this->canDelete = !$hasBeenWatched;       
        $this->active = true;
    }

    public function closeModal(): void
    {
        $this->active = false;
        $this->reset();
    }

    public function saveRatingAndNotes(){

        $movieUser = MovieUser::where('movie_id', $this->movieId)
            ->where('user_id', session('selected_user_id'))
            ->first();

        if($movieUser){

            $movieUser->update([
                'rating' => $this->userRating,
                'notes' => $this->notes
            ]);
        }else{

            MovieUser::create([
                'user_id' => session('selected_user_id'),
                'movie_id' => $this->movieId,
                'rating' => $this->userRating,
                'notes' => $this->notes
            ]);
        }

        $this->dispatch('refresh-movies');
        $this->closeModal();
    }

    public function markAsWatched(){
        $movieUser = MovieUser::where('movie_id', $this->movieId)
            ->where('user_id', session('selected_user_id'))
            ->first();

        if($movieUser){

            $movieUser->update([
                'watched_at' => Carbon::now()->toDateString(),
            ]);
        }else{

            MovieUser::create([
                'user_id' => session('selected_user_id'),
                'movie_id' => $this->movieId,
                'watched_at' => Carbon::now()->toDateString(),
            ]);
        }

        $this->dispatch('refresh-movies');
        $this->closeModal();
    }

    public function deleteMovie(){
 
        $movie = Movie::find($this->movieId);

        if($movie){
            
            $movie->delete();
        }

        $this->dispatch('refresh-movies');
        $this->closeModal();
    }

};
?>

<div>
    @if($active)
        {{-- Fondo oscuro --}}
        <div class="modal-backdrop" wire:click="closeModal"></div>

        {{-- Modal --}}
        <div class="modal-card">
            <div class="flex items-center justify-between mb-5">
                <h3 class="font-serif text-lg text-text-primary">{{ $movie?->title ?? 'Película' }}</h3>
                <button wire:click="closeModal" class="text-text-subtle hover:text-text-primary text-xl">×</button>
            </div>

            {{-- Póster y detalles --}}
            <div class="flex gap-4 mb-4">
                @if($movie?->poster_path)
                    <img src="https://image.tmdb.org/t/p/w200{{ $movie->poster_path }}"
                         class="w-20 h-28 object-cover rounded-lg shrink-0">
                @else
                    <div class="w-20 h-28 bg-surface-tertiary rounded-lg flex items-center justify-center text-xs text-text-placeholder">
                        Sin carátula
                    </div>
                @endif
                <div>
                    <p class="text-sm text-text-muted">{{ $movie?->release_year }}</p>
                    <p class="text-xs text-text-muted mt-1">{{ $movie?->overview }}</p>
                </div>
            </div>

            {{-- Géneros (si los hay) --}}
            @if($movie?->genres && $movie->genres->count())
                <div class="mb-4">
                    <p class="text-xs text-text-label uppercase tracking-wide mb-1">Géneros</p>
                    <div class="flex flex-wrap gap-1">
                        @foreach($movie->genres as $genre)
                            <span class="text-xs bg-surface-tertiary px-2 py-1 rounded">{{ $genre->name }}</span>
                        @endforeach
                    </div>
                </div>
            @endif

            {{-- Rating personal y notas --}}
            <div class="mb-4">
                <label class="form-label">Mi puntuación</label>
                <select wire:model="userRating" class="form-input">
                    <option value="">Sin valorar</option>
                    @for($i = 1; $i <= 10; $i++)
                        <option value="{{ $i }}">{{ $i }} / 10</option>
                    @endfor
                </select>
            </div>

            <div class="mb-4">
                <label class="form-label">Mis notas</label>
                <textarea wire:model="notes" rows="3" class="form-input resize-none" placeholder="Escribe tus impresiones..."></textarea>
            </div>

            {{-- Botones de acción --}}
            <div class="flex flex-col gap-2">
                @if(!$userWatched)
                    <button wire:click="markAsWatched" class="btn-secondary">Marcar como vista</button>
                @else
                    <button disabled class="mt-auto btn-add">✓ Ya vista</button>
                @endif

                <button wire:click="saveRatingAndNotes" class="btn-secondary w-full">Guardar valoración</button>

                @if($canDelete)
                    <button wire:click="deleteMovie" class="btn-danger w-full">Eliminar película</button>
                @endif
            </div>

            {{-- Mensaje de error para eliminación --}}
            @error('delete')
                <div class="mt-2 text-sm text-red-500">{{ $message }}</div>
            @enderror
        </div>
    @endif
</div>