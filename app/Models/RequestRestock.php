<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RequestRestock extends Model
{
    protected $table = 'request_restocks';

    protected $fillable = [
        'code','supplier_id','product_id',
        'quantity_requested','quantity_received',
        'cost_per_item','total_cost','status','note',
        'warehouse_id','requested_by','approved_by',
        'approved_at','received_at',
    ];

    protected $casts = [
        'quantity_requested' => 'int',
        'quantity_received'  => 'int',
        'cost_per_item'      => 'int',
        'total_cost'         => 'int',
    ];

    public function product(){ return $this->belongsTo(Product::class); }
    public function supplier(){ return $this->belongsTo(Supplier::class); }
}
