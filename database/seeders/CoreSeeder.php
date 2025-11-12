<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use App\Models\Supplier;
use App\Models\Warehouse;
use App\Models\Category;
use App\Models\Product;
use App\Models\User;
use App\Models\StockLevel;

class CoreSeeder extends Seeder
{
    public function run(): void
    {
        // ===== Suppliers =====
        $supplier = Supplier::updateOrCreate([
            'supplier_code' => 'SUP-001',
        ], [
            'name' => 'Supplier A',
                'address' => 'Jl. Contoh No. 1',
                'phone' => '081234567890',
                'note' => 'Supplier pertama',
                'bank_name' => 'Bank A',
                'bank_account' => '1234567890',
                'created_at' => now(),
                'updated_at' => now(),
        ]);

        // ===== Warehouses =====
        $wh1 = Warehouse::updateOrCreate([
            'warehouse_code' => 'WH-BDG'
        ], [
            'warehouse_name' => 'Warehouse Bandung',
            'address' => 'Jl. Pasteur No. 22 Bandung',
            'note' => 'Cabang Bandung'
        ]);

        $wh2 = Warehouse::updateOrCreate([
            'warehouse_code' => 'WH-JKT'
        ], [
            'warehouse_name' => 'Warehouse Jakarta',
            'address' => 'Jl. Gatot Subroto No. 10 Jakarta',
            'note' => 'Cabang Jakarta'
        ]);

        // ===== Categories =====
        $cat = Category::updateOrCreate([
            'category_code' => 'CAT-SKN'
        ], [
            'category_name' => 'Skincare',
            'description' => 'Produk skincare harian'
        ]);

        // ===== Products =====
        $prod1 = Product::updateOrCreate([
            'product_code' => 'SKU-001'
        ], [
            'name' => 'Avoskin Toner 100ml',
            'category_id' => $cat->id,
            'description' => 'Toner brightening wajah',
            'purchasing_price' => 50000,
            'selling_price' => 75000,
            'stock_minimum' => 10,
        ]);

        $prod2 = Product::updateOrCreate([
            'product_code' => 'SKU-002'
        ], [
            'name' => 'Avoskin Serum 30ml',
            'category_id' => $cat->id,
            'description' => 'Serum untuk kulit glowing',
            'purchasing_price' => 100000,
            'selling_price' => 135000,
            'stock_minimum' => 5,
        ]);

        // ===== Users =====
        $admin = User::updateOrCreate(['email' => 'admin@local'], [
            'name' => 'Admin Pusat',
            'username' => 'admin',
            'password' => Hash::make('password123'),
            'role' => 'admin',
            'status' => 'active'
        ]);

        $wh_bdg = User::updateOrCreate(['email' => 'wh_bdg@local'], [
            'name' => 'Admin Warehouse BDG',
            'username' => 'wh_bdg',
            'password' => Hash::make('password123'),
            'role' => 'warehouse',
            'warehouse_id' => $wh1->id,
            'status' => 'active'
        ]);

        $wh_jkt = User::updateOrCreate(['email' => 'wh_jkt@local'], [
            'name' => 'Admin Warehouse JKT',
            'username' => 'wh_jkt',
            'password' => Hash::make('password123'),
            'role' => 'warehouse',
            'warehouse_id' => $wh2->id,
            'status' => 'active'
        ]);

        $sales_bdg = User::updateOrCreate(['email' => 'sales_bdg@local'], [
            'name' => 'Sales Bandung',
            'username' => 'sales_bdg',
            'password' => Hash::make('password123'),
            'role' => 'sales',
            'warehouse_id' => $wh1->id,
            'status' => 'active'
        ]);

        $sales_jkt = User::updateOrCreate(['email' => 'sales_jkt@local'], [
            'name' => 'Sales Jakarta',
            'username' => 'sales_jkt',
            'password' => Hash::make('password123'),
            'role' => 'sales',
            'warehouse_id' => $wh2->id,
            'status' => 'active'
        ]);

        // ===== Stock Levels =====
        foreach ([$prod1, $prod2] as $product) {
            // stok pusat
            StockLevel::updateOrCreate([
                'owner_type' => 'pusat',
                'owner_id' => 0,
                'product_id' => $product->id
            ], ['quantity' => 100]);

            // stok warehouse
            StockLevel::updateOrCreate([
                'owner_type' => 'warehouse',
                'owner_id' => $wh1->id,
                'product_id' => $product->id
            ], ['quantity' => 20]);

            StockLevel::updateOrCreate([
                'owner_type' => 'warehouse',
                'owner_id' => $wh2->id,
                'product_id' => $product->id
            ], ['quantity' => 25]);

            // stok sales
            StockLevel::updateOrCreate([
                'owner_type' => 'sales',
                'owner_id' => $sales_bdg->id,
                'product_id' => $product->id
            ], ['quantity' => 10]);

            StockLevel::updateOrCreate([
                'owner_type' => 'sales',
                'owner_id' => $sales_jkt->id,
                'product_id' => $product->id
            ], ['quantity' => 12]);
        }
    }
}