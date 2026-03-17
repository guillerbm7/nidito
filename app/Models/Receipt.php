<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Receipt extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'shopping_list_id',
        'image_path',
        'amount',
        'notes',
        'purchased_at',
    ];

    public function list()
    {
        return $this->belongsTo(ShoppingList::class, 'shopping_list_id');
    }
}
