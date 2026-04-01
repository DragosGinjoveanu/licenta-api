<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MenuCategory extends Model {
    protected $fillable = ['menu_id', 'category_id', 'order'];

    public function menu() {
        return $this->belongsTo(Menu::class);
    }

    public function menuProducts() {
        return $this->hasMany(MenuProduct::class)->orderBy('order');
    }

    public function categoryDetails() {
        return $this->belongsTo(Category::class, 'category_id');
    }
}
