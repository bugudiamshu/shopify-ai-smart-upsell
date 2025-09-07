<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Shop extends Model
{
    public $guarded = ['id'];

    public function products()
    {
        return $this->hasMany(Product::class);
    }
}
