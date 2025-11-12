<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Carbon\Carbon;
use App\Models\StockRequest;
use App\Models\RequestRestock;
use App\Models\StockMovement;
use App\Models\SalesReport;
use App\Models\SalesReturn;
use App\Models\Product;
use App\Models\User;
use App\Models\Warehouse;
use App\Models\Supplier;

class OperationSeeder extends Seeder
{
    public function run(): void
    {
        $prod1 = Product::where('product_code', 'SKU-001')->first();
        $prod2 = Product::where('product_code', 'SKU-002')->first();
        $supplier = Supplier::first();
        $admin = User::where('role', 'admin')->first();
        $wh_bdg = User::where('username', 'wh_bdg')->first();
        $sales_bdg = User::where('username', 'sales_bdg')->first();
        $wh = Warehouse::where('warehouse_code', 'WH-BDG')->first();

        // ===== Stock Request (Sales -> Warehouse) =====
        StockRequest::create([
            'requester_type' => 'sales',
            'requester_id' => $sales_bdg->id,
            'approver_type' => 'warehouse',
            'approver_id' => $wh_bdg->id,
            'product_id' => $prod1->id,
            'quantity_requested' => 10,
            'quantity_approved' => 10,
            'status' => 'approved',
            'note' => 'Request stok toner rutin'
        ]);

        // ===== Request Restock (Warehouse -> Supplier via Admin) =====
        RequestRestock::create([
            'supplier_id' => $supplier->id,
            'product_id' => $prod2->id,
            'quantity_requested' => 50,
            'quantity_received' => 50,
            'cost_per_item' => 50000,
            'total_cost' => 2500000,
            'status' => 'received',
            'approved_by' => $admin->id,
            'approved_at' => now(),
            'received_at' => now(),
            'note' => 'Restock serum dari supplier'
        ]);

        // ===== Stock Movement (Pusat -> Warehouse) =====
        StockMovement::create([
            'product_id' => $prod1->id,
            'from_type' => 'pusat',
            'to_type' => 'warehouse',
            'to_id' => $wh->id,
            'quantity' => 20,
            'status' => 'completed',
            'approved_by' => $admin->id,
            'approved_at' => now(),
            'note' => 'Distribusi ke warehouse Bandung'
        ]);

        // ===== Sales Report =====
        SalesReport::create([
            'sales_id' => $sales_bdg->id,
            'warehouse_id' => $wh->id,
            'date' => Carbon::now()->toDateString(),
            'total_sold' => 8,
            'total_revenue' => 600000,
            'stock_remaining' => 2,
            'damaged_goods' => 0,
            'goods_returned' => 0,
            'notes' => 'Penjualan harian sales BDG',
            'status' => 'approved',
            'approved_by' => $wh_bdg->id,
            'approved_at' => now(),
        ]);

        // ===== Sales Return =====
        SalesReturn::create([
            'sales_id' => $sales_bdg->id,
            'warehouse_id' => $wh->id,
            'product_id' => $prod2->id,
            'quantity' => 1,
            'condition' => 'damaged',
            'reason' => 'Botol retak saat pengiriman',
            'status' => 'approved',
            'approved_by' => $wh_bdg->id,
            'approved_at' => now(),
        ]);
    }
}