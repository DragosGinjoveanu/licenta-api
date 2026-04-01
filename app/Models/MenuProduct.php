<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MenuProduct extends Model {
  protected $fillable = [
      'menu_category_id', 'product_id', 'order', 
      'show_price', 'show_description', 'show_ingredients', 'visible_ingredients'
  ];

  protected $casts = [
      'visible_ingredients' => 'array',
      'show_price' => 'boolean',
      'show_description' => 'boolean',
      'show_ingredients' => 'boolean',
  ];

  public function productDetails() {
      return $this->belongsTo(Product::class, 'product_id');
  }

  public function menuCategory() {
      return $this->belongsTo(MenuCategory::class, 'menu_category_id');
  }
}
