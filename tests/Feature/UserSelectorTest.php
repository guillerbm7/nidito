<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

class UserSelectorTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function test_selector_page_loads_correctly(): void
    {
        User::create(['name' => 'Álvaro', 'avatar_color' => '#6366f1']);
        User::create(['name' => 'María',  'avatar_color' => '#ec4899']);

        $response = $this->get('/');

        $response->assertStatus(200);
        $response->assertSee('Álvaro');
        $response->assertSee('María');
    }

    /** @test */
    public function test_user_can_be_selected_and_stored_in_session(): void
    {
        $user = User::create(['name' => 'Álvaro', 'avatar_color' => '#6366f1']);

        $response = $this->post('/session/user', [
            'user_id' => $user->id,
        ]);

        $response->assertRedirect('/dashboard');
        $response->assertSessionHas('selected_user_id', $user->id);
    }

    /** @test */
    public function test_invalid_user_id_does_not_create_session(): void
    {
        $response = $this->post('/session/user', [
            'user_id' => 999,
        ]);

        $response->assertSessionMissing('selected_user_id');
    }
}
