<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Carbon\Carbon;
use App\Models\ActivityLog;
use App\Models\StockSnapshot;
use App\Models\Product;
use App\Models\User;

class ActivitySeeder extends Seeder
{
    public function run(): void
    {
        $admin = User::where('role', 'admin')->first();
        $product = Product::where('product_code', 'SKU-001')->first();

        // ===== Activity Logs =====
        ActivityLog::create([
            'user_id' => $admin->id,
            'action' => 'Seeder Init',
            'entity_type' => 'System',
            'entity_id' => null,
            'description' => 'Seeder initial setup data dummy berhasil dibuat.'
        ]);

        ActivityLog::create([
            'user_id' => $admin->id,
            'action' => 'Stock Update',
            'entity_type' => 'Product',
            'entity_id' => $product->id,
            'description' => 'Perubahan stok produk SKU-001 oleh admin pusat.'
        ]);

        // ===== Stock Snapshots =====
        StockSnapshot::create([
            'owner_type' => 'pusat',
            'owner_id' => 0,
            'product_id' => $product->id,
            'quantity' => 100,
            'recorded_at' => Carbon::now()->toDateString(),
        ]);
    }
}