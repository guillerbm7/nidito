<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\Recipe;

class RecipeIngredient extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'recipe_id',
        'name',
        'quantity',
        'unit',
        'order',
    ];

    public function recipe()
    {
        return $this->belongsTo(Recipe::class, 'recipe_id');
    }


}
