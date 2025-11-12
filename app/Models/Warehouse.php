<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Warehouse extends Model
{
    protected $fillable = [
        'warehouse_code', 'warehouse_name', 'address', 'note'
    ];

    public function users()
    {
        return $this->hasMany(User::class);
    }

    public function stockLevels()
    {
        return $this->hasMany(StockLevel::class, 'owner_id');
    }

    public function salesReports()
    {
        return $this->hasMany(SalesReport::class);
    }
    public function products()
    {
        return $this->hasMany(Product::class);
    }
}
