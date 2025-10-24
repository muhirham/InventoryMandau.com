<?php
// app/Models/Product.php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    protected $table = 'products'; // opsional kalau sudah default
    protected $fillable = [
        'product_code','product_name','category_id','supplier_id','warehouse_id',
        'purchase_price','selling_price','stock','package_type','product_group','registration_number'
    ];

    public function category()
    {
        // FK default: category_id → table: categories → PK: id
        return $this->belongsTo(Category::class, 'category_id', 'id');
    }

    public function supplier()
    {
        return $this->belongsTo(Supplier::class, 'supplier_id', 'id');
    }

    public function warehouse()
    {
        return $this->belongsTo(Warehouse::class, 'warehouse_id', 'id');
    }
}
