<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProductVariantPrice extends Model
{
    public function variantOnePriceToVarianProduct()
    {
        return $this->belongsTo(ProductVariant::class, 'product_variant_one', 'id');
    }

    public function variantTwoPriceToVarianProduct()
    {
        return $this->belongsTo(ProductVariant::class, 'product_variant_two', 'id');
    }

    public function variantThreePriceToVarianProduct()
    {
        return $this->belongsTo(ProductVariant::class, 'product_variant_three', 'id');
    }
}
