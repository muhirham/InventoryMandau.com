<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PurchaseOrderItem extends Model
{
    protected $fillable = [
        'purchase_order_id','product_id','warehouse_id',
        'qty_ordered','qty_received','unit_price',
        'discount_type','discount_value','line_total','notes'
    ];

    public function po()
    {
        return $this->belongsTo(PurchaseOrder::class, 'purchase_order_id');
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function warehouse()
    {
        return $this->belongsTo(Warehouse::class);
    }

    public function remaining(): int
    {
        return max(0, (int)$this->qty_ordered - (int)$this->qty_received);
    }
}
