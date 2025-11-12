<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RestockReceipt extends Model
{
    protected $fillable = [
        'purchase_order_id','code','request_id','product_id','warehouse_id',
        'qty_good','qty_damaged','cost_per_item','notes',
        'received_by','received_at',
    ];

    protected $casts = ['received_at' => 'datetime'];

    public function po() { return $this->belongsTo(PurchaseOrder::class, 'purchase_order_id'); }
    public function product() { return $this->belongsTo(Product::class); }
    public function warehouse(){ return $this->belongsTo(Warehouse::class); }
}
