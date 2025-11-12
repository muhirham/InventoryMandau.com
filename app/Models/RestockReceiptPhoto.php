<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RestockReceiptPhoto extends Model
{
    protected $fillable = ['receipt_id','path'];

    public function receipt() { return $this->belongsTo(RestockReceipt::class, 'receipt_id'); }
}
