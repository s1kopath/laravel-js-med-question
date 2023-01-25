<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    protected $fillable = [
        'title', 'sku', 'description'
    ];

    public function productToProductVariantPrice()
    {
        return $this->hasMany(ProductVariantPrice::class, 'product_id', 'id');
    }

    public function prices()
    {
        return $this->hasMany(ProductVariantPrice::class, 'product_id', 'id');
    }
    
    public function productToVariants()
    {
        return $this->hasMany(ProductVariant::class, 'product_id', 'id');
    }
}
