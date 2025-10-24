<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class RequestForm extends Model
{
    use HasFactory;

    // pakai tabel restock_requests yang sudah ada
    protected $table = 'restock_requests';

    protected $fillable = [
        'product_id',
        'supplier_id',
        'warehouse_id',
        'user_id',
        'request_date',
        'quantity_requested',
        'total_cost',
        'description',
        'status', // pending | approved | rejected
    ];

    protected $casts = [
        'request_date'       => 'date',
        'quantity_requested' => 'float',
        'total_cost'         => 'float',
    ];

    // biar ikut di JSON (optional, useful di UI)
    protected $appends = ['unit_price'];

    /* ---------------- Relationships ---------------- */
    public function product()   { return $this->belongsTo(Product::class,   'product_id'); }
    public function supplier()  { return $this->belongsTo(Supplier::class,  'supplier_id'); }
    public function warehouse() { return $this->belongsTo(Warehouse::class, 'warehouse_id'); }
    public function requester() { return $this->belongsTo(User::class,      'user_id'); }

    /* ---------------- Accessors ---------------- */
    public function getUnitPriceAttribute(): float
    {
        $qty = (float) ($this->quantity_requested ?? 0);
        $tot = (float) ($this->total_cost ?? 0);
        return $qty > 0 ? round($tot / $qty, 2) : 0.0;
    }

    /* ---------------- Scopes (buat filter json) ---------------- */
    public function scopeStatus($q, ?string $status)
    {
        if ($status !== null && $status !== '') $q->where('status', $status);
        return $q;
    }

    public function scopeQuickSearch($q, ?string $term)
    {
        if (!$term) return $q;

        return $q->where(function ($w) use ($term) {
            $w->where('description', 'like', "%{$term}%")
              ->orWhereHas('product',   fn($qq) => $qq->where('product_name',   'like', "%{$term}%"))
              ->orWhereHas('supplier',  fn($qq) => $qq->where('company_name',   'like', "%{$term}%"))
              ->orWhereHas('warehouse', fn($qq) => $qq->where('warehouse_name', 'like', "%{$term}%"));
        });
    }
}
