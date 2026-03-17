<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ShoppingItem extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'shopping_list_id',
        'added_by',
        'name',
        'quantity',
        'unit',
        'category',
        'is_checked',
    ];

    public function list()
    {
        return $this->belongsTo(ShoppingList::class, 'shopping_list_id');
    }

    public function addedBy()
    {
        return $this->belongsTo(User::class, 'added_by');
    }
}
