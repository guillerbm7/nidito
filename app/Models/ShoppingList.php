<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ShoppingList extends Model
{
    public $timestamps = false;

    protected $fillable = ['week_start'];

    public function items()
    {
        return $this->hasMany(ShoppingItem::class);
    }

    public function receipts()
    {
        return $this->hasMany(Receipt::class);
    }
}
