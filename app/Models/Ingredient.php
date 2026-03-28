<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Ingredient extends Model
{
    protected $fillable = [
        'restaurant_id', 
        'supplier_id', 
        'name', 
        'unit_of_measure', 
        'stock_quantity', 
        'alert_level', 
        'cost_per_unit'
    ];

    protected $appends = ['supplier_name'];

    public function supplier()
    {
        return $this->belongsTo(Supplier::class);
    }

    public function getSupplierNameAttribute()
    {
        return $this->supplier ? $this->supplier->name : 'Own Supply';
    }
}