<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Movie extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'added_by',
        'tmdb_id',
        'title',
        'poster_path',
        'overview',
        'rating',
        'vote_count',
        'release_year',
        'genre',
    ];

    public function addedBy()
    {
        return $this->belongsTo(User::class, 'added_by');
    }

    public function genres(){
        return $this->belongsToMany(Genre::class, 'genre_movie');
    }

    public function watchers()
    {
        return $this->belongsToMany(User::class, 'movie_user')
                    ->withPivot('rating', 'notes', 'watched_at');
    }

}
