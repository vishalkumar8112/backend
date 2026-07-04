<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    protected $appends = ['image_url'];

    public function getImageUrlAttribute()
    {
        if ($this->image == "") {
            return "";
        }
        return asset('uploads/products/small/' . $this->image);
    }

    // ✅ ProductImage model use karo
    public function product_images()
    {
        return $this->hasMany(ProductImage::class);
    }

    // ✅ ProductSize model use karo
    public function product_sizes()
    {
        return $this->hasMany(ProductSize::class);
    }

    // ✅ sizes table se (belongsToMany)
    public function sizes()
    {
        return $this->belongsToMany(Size::class, 'product_sizes', 'product_id', 'size_id');
    }
}