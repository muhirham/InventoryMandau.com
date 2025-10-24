<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

class Stock extends Model
{
    protected $table = 'product_stock';
    protected $primaryKey = 'id';

    protected $fillable = [
        'product_id',
        'warehouse_id',
        'initial_stock',
        'stock_in',
        'stock_out',
        'final_stock',
        'last_update',
    ];

    protected $casts = [
        'initial_stock' => 'float',
        'stock_in'      => 'float',
        'stock_out'     => 'float',
        'final_stock'   => 'float',
        'last_update'   => 'datetime',
    ];

    // Relasi
    public function product()
    {
        return $this->belongsTo(Product::class, 'product_id');
    }

    public function warehouse()
    {
        return $this->belongsTo(Warehouse::class, 'warehouse_id');
    }

    // Event agar final_stock dan last_update otomatis keisi
    protected static function booted()
    {
        static::saving(function ($model) {
            $model->final_stock = ($model->initial_stock ?? 0)
                                + ($model->stock_in ?? 0)
                                - ($model->stock_out ?? 0);

            if (!$model->last_update) {
                $model->last_update = Carbon::now();
            }
        });
    }

    // Status stok (buat badge/filter)
    public function getStatusAttribute()
    {
        if ($this->final_stock <= 0) return 'empty';
        if ($this->final_stock <= 10) return 'low';
        return 'ok';
    }
}
