<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CalendarEntry extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'created_by',
        'assigned_to',
        'title',
        'date',
        'type',
        'notes',
        'recipe_url',
        'color',
        'is_completed',
    ];

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function assignedTo()
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }
}
