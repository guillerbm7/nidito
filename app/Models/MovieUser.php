<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MovieUser extends Model
{
    protected $table = 'movie_user';

    public $timestamps = false; 

    protected $fillable = [
        'movie_id',
        'user_id',
        'rating',
        'notes',
        'watched_at',
    ];
}
