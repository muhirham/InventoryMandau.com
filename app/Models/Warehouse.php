<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Warehouse extends Model
{
    protected $table = 'warehouses';

    protected $fillable = [
        'warehouse_code',
        'warehouse_name',
        'address',
        'note',
    ];

    // optional helper accessor
    public function getNameAttribute() {
        return $this->attributes['warehouse_name'] ?? null;
    }
}
