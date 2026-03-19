<?php

use Livewire\Attributes\Layout;
use Livewire\Component;
use App\Models\User;

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

<div class="min-h-screen bg-[#F7F5F0] flex flex-col items-center justify-center p-6">

    <div class="mb-10 text-center">
        <span class="text-5xl">🪺</span>
        <h1 class="font-serif text-3xl text-[#2C2A26] mt-3 tracking-tight">nidito</h1>
        <p class="text-sm text-[#A09B92] mt-1">¿Quién eres hoy?</p>
    </div>

    <div class="flex gap-6">
        @foreach($users as $user)
            <button
                wire:click="selectUser({{ $user->id }})"
                class="flex flex-col items-center gap-3 p-6 bg-[#FFFEFB] rounded-2xl border border-[#EAE8E2]
                       hover:border-[#5B52C4] hover:shadow-sm transition-all cursor-pointer group w-36">
                <div class="w-16 h-16 rounded-full flex items-center justify-center text-2xl font-medium transition-transform group-hover:scale-105"
                     style="background-color: {{ $user->avatar_color }}22; color: {{ $user->avatar_color }}">
                    {{ strtoupper(substr($user->name, 0, 1)) }}
                </div>
                <span class="text-sm font-medium text-[#2C2A26]">{{ $user->name }}</span>
            </button>
        @endforeach
    </div>

</div>