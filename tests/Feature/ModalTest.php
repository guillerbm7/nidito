<?php

namespace Tests\Feature;

use App\Models\CalendarEntry;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class ModalTest extends TestCase
{
    use RefreshDatabase;

    public function test_modal_loads_correctly(): void
    {
        $user = User::create(['name' => 'Guille', 'avatar_color' => '#6366f1']);
        session(['selected_user_id' => $user->id]);

        Livewire::test('pages::entry-modal')
            ->dispatch('open-entry-modal', date: Carbon::now()->toDateString())
            ->assertSet('active', true)
            ->assertSet('date', Carbon::now()->toDateString());
    }

    public function test_it_creates_a_calendar_entry(): void
    {
        $user = User::create(['name' => 'Guille', 'avatar_color' => '#6366f1']);
        session(['selected_user_id' => $user->id]);

        $date = Carbon::now()->toDateString();

        Livewire::test('pages::entry-modal')
            ->dispatch('open-entry-modal', date: $date)
            ->set('title', 'Pasta carbonara')
            ->set('type', 'lunch')
            ->set('notes', 'Con bacon')
            ->set('recipe_url', 'https://example.com/recipe')
            ->set('assigned_to', $user->id)
            ->call('saveModal')
            ->assertHasNoErrors()
            ->assertSet('active', false);

        $this->assertDatabaseHas('calendar_entries', [
            'created_by' => $user->id,
            'assigned_to' => $user->id,
            'title' => 'Pasta carbonara',
            'date' => $date,
            'type' => 'lunch',
        ]);
    }

    public function test_it_validates_required_fields(): void
    {
        $user = User::create(['name' => 'Guille', 'avatar_color' => '#6366f1']);
        session(['selected_user_id' => $user->id]);

        Livewire::test('pages::entry-modal')
            ->dispatch('open-entry-modal', date: Carbon::now()->toDateString())
            ->set('title', '')
            ->set('type', '')
            ->call('saveModal')
            ->assertHasErrors([
                'title' => 'required',
                'type' => 'required',
            ]);
    }

    public function test_it_prevents_duplicate_lunch_or_dinner_for_same_day(): void
    {
        $user = User::create(['name' => 'Guille', 'avatar_color' => '#6366f1']);
        session(['selected_user_id' => $user->id]);

        $date = Carbon::now()->toDateString();

        CalendarEntry::create([
            'created_by' => $user->id,
            'assigned_to' => null,
            'title' => 'Ya existe comida',
            'date' => $date,
            'type' => 'lunch',
            'notes' => null,
            'recipe_url' => null,
        ]);

        Livewire::test('pages::entry-modal')
            ->dispatch('open-entry-modal', date: $date)
            ->set('title', 'Otra comida')
            ->set('type', 'lunch')
            ->call('saveModal')
            ->assertHasErrors(['type']);

        $this->assertDatabaseCount('calendar_entries', 1);
    }

    public function test_it_loads_data_when_editing_an_entry(): void
    {
        $creator = User::create(['name' => 'Guille', 'avatar_color' => '#6366f1']);
        $assignee = User::create(['name' => 'Ana', 'avatar_color' => '#22c55e']);
        session(['selected_user_id' => $creator->id]);

        $entry = CalendarEntry::create([
            'created_by' => $creator->id,
            'assigned_to' => $assignee->id,
            'title' => 'Cena original',
            'date' => Carbon::now()->toDateString(),
            'type' => 'dinner',
            'notes' => 'Nota original',
            'recipe_url' => 'https://example.com/original',
        ]);

        Livewire::test('pages::entry-modal')
            ->dispatch('open-entry-modal-edit', entryId: (string) $entry->id)
            ->assertSet('active', true)
            ->assertSet('edit', true)
            ->assertSet('entryId', $entry->id)
            ->assertSet('title', 'Cena original')
            ->assertSet('type', 'dinner')
            ->assertSet('assigned_to', $assignee->id);
    }

    public function test_it_updates_existing_entry_when_editing(): void
    {
        $creator = User::create(['name' => 'Guille', 'avatar_color' => '#6366f1']);
        $assignee = User::create(['name' => 'Ana', 'avatar_color' => '#22c55e']);
        session(['selected_user_id' => $creator->id]);

        $entry = CalendarEntry::create([
            'created_by' => $creator->id,
            'assigned_to' => null,
            'title' => 'Titulo viejo',
            'date' => Carbon::now()->toDateString(),
            'type' => 'task',
            'notes' => null,
            'recipe_url' => null,
        ]);

        Livewire::test('pages::entry-modal')
            ->dispatch('open-entry-modal-edit', entryId: (string) $entry->id)
            ->set('title', 'Titulo nuevo')
            ->set('type', 'event')
            ->set('notes', 'Nota nueva')
            ->set('assigned_to', $assignee->id)
            ->call('saveModal')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('calendar_entries', [
            'id' => $entry->id,
            'title' => 'Titulo nuevo',
            'type' => 'event',
            'notes' => 'Nota nueva',
            'assigned_to' => $assignee->id,
        ]);
    }

    public function test_it_deletes_entry_from_delete_modal(): void
    {
        $user = User::create(['name' => 'Guille', 'avatar_color' => '#6366f1']);
        session(['selected_user_id' => $user->id]);

        $entry = CalendarEntry::create([
            'created_by' => $user->id,
            'assigned_to' => null,
            'title' => 'Entrada a eliminar',
            'date' => Carbon::now()->toDateString(),
            'type' => 'event',
            'notes' => null,
            'recipe_url' => null,
        ]);

        Livewire::test('pages::entry-modal')
            ->dispatch('entry-modal-delete', entryId: (string) $entry->id)
            ->assertSet('active', true)
            ->assertSet('delete', true)
            ->call('saveModal');

        $this->assertDatabaseMissing('calendar_entries', [
            'id' => $entry->id,
        ]);
    }
}