<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;
use App\Models\User;
use Livewire\Livewire;
use Carbon\Carbon;

class CalendarTest extends TestCase
{   
    use RefreshDatabase;
    public function test_calendar_loads_correctly(): void
    {
        $user = User::create(['name' => 'Guille', 'avatar_color' => '#6366f1']);
        session(['selected_user_id' => $user->id]);

        Livewire::test('pages::calendar')
            ->assertStatus(200);
    }

    public function test_calendar_shows_current_week_on_load(): void
    {
        $user = User::create(['name' => 'Guille', 'avatar_color' => '#6366f1']);
        session(['selected_user_id' => $user->id]);
        $expectedWeekStart = Carbon::now()->startOfWeek()->toDateString();

        Livewire::test('pages::calendar')
            ->assertSet('weekStart', $expectedWeekStart);
    }

    public function test_calendar_selected_date_is_today_on_load(){
        $user = User::create(['name' => 'Guille', 'avatar_color' => '#6366f1']);
        session(['selected_user_id' => $user->id]);

        $expectedSelectedDate = Carbon::now()->toDateString();
        Livewire::test('pages::calendar')
            ->assertSet('selectedDate', $expectedSelectedDate);
    }

    public function test_previous_week_goes_back_one_week(){
        $user = User::create(['name' => 'Guille', 'avatar_color' => '#6366f1']);
        session(['selected_user_id' => $user->id]);

        $expectedWeekDay = Carbon::now()->startOfWeek()->subWeek()->toDateString();

        Livewire::test('pages::calendar')->call('previousWeek')
            ->assertSet('weekStart', $expectedWeekDay);
    }
    public function test_next_week_goes_forward_one_week(){
        $user = User::create(['name' => 'Guille', 'avatar_color' => '#6366f1']);
        session(['selected_user_id' => $user->id]);

        $expectedWeekDay = Carbon::now()->startOfWeek()->addWeek()->toDateString();

        Livewire::test('pages::calendar')->call('nextWeek')
            ->assertSet('weekStart', $expectedWeekDay);
    }
    public function test_select_day_changes_selected_date(){
        $user = User::create(['name' => 'Guille', 'avatar_color' => '#6366f1']);
        session(['selected_user_id' => $user->id]);

        $expectedSelectedDay = Carbon::now()->addDays(2)->toDateString();
        
        Livewire::test('pages::calendar')->call('selectDay', $expectedSelectedDay)
            ->assertSet('selectedDate', $expectedSelectedDay);
    }

}
