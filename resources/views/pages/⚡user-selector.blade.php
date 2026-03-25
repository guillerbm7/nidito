<?php

use App\Models\User;
use Livewire\Attributes\Layout;
use Livewire\Component;

new #[Layout('layouts::blank')] class extends Component
{
    public function selectUser(int $userId): void
    {
        $user = User::findOrFail($userId);
        session(['selected_user_id' => $user->id]);
        $this->redirect(route('dashboard'));
    }

    public function with(): array
    {
        return [
            'users' => User::all(),
        ];
    }
};
?>

<div class="min-h-dvh bg-surface-secondary flex flex-col items-center justify-center p-6">

    {{-- Logo and title --}}
    <div class="mb-10 text-center">
        <span class="text-5xl">🪺</span>
        <h1 class="font-serif text-3xl text-text-primary mt-3 tracking-tight">Nidito</h1>
        <p class="text-sm text-text-subtle mt-1">¿Quién eres hoy?</p>
    </div>

    {{-- User cards --}}
    <div class="flex flex-col sm:flex-row flex-wrap justify-center gap-4 sm:gap-6 w-full max-w-3xl">
        @foreach($users as $user)
            <button
                wire:click="selectUser({{ $user->id }})"
                class="user-card">
                <div class="avatar-circle-lg" style="--avatar-color: {{ $user->avatar_color }}">
                    {{ strtoupper(substr($user->name, 0, 1)) }}
                </div>
                <span class="text-sm font-medium text-text-primary">{{ $user->name }}</span>
            </button>
        @endforeach
    </div>

</div>
