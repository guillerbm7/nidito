<?php

namespace App\Livewire;

use App\Models\User;
use Livewire\Component;

class UserSelector extends Component
{
    public function selectUser(int $userId): void
    {
        $user = User::findOrFail($userId);
        session(['selected_user_id' => $user->id]);
        $this->redirect(route('dashboard'), navigate: true);
    }

    public function render()
    {
        return view('livewire.user-selector', [
            'users' => User::all(),
        ])->layout('layouts.nidito-blank');
    }
}