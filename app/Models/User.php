<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class User extends Model
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

    public function recipes()
    {
        return $this->hasMany(Recipe::class, 'created_by');
                    
    }
}