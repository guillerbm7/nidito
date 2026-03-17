<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Str;
use Laravel\Fortify\TwoFactorAuthenticatable;

#[Fillable(['name', 'email', 'password'])]
#[Hidden(['password', 'two_factor_secret', 'two_factor_recovery_codes', 'remember_token'])]
class User extends Authenticatable
{
    public $timestamps = false;

    protected $fillable = ['name', 'avatar_color'];

    public function calendarEntries()
    {
        return $this->hasMany(CalendarEntry::class, 'created_by');
    }

    public function assignedTasks()
    {
        return $this->hasMany(CalendarEntry::class, 'assigned_to');
    }

    public function shoppingItems()
    {
        return $this->hasMany(ShoppingItem::class, 'added_by');
    }

    public function movies()
    {
        return $this->belongsToMany(Movie::class, 'movie_user')
                    ->withPivot('rating', 'notes', 'watched_at');
    }
}
