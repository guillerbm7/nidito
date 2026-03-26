<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\User;
use App\Models\RecipeIngredient;
use App\Models\CalendarEntry;

class Recipe extends Model
{
    public $timestamps = true;

    protected $fillable = [
        'title',
        'instructions',
        'source_url',
        'created_by',
    ];

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function ingredients()
    {
        return $this->hasMany(RecipeIngredient::class)->orderBy('order');
    }

    public function calendar()
    {
        return $this->hasMany(CalendarEntry::class);
    }
}
