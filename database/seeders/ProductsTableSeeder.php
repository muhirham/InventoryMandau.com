<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class ProductsTableSeeder extends Seeder
{
    public function run(): void
    {
        $now = Carbon::now();
        DB::table('products')->insert([
            [
                'product_code'=>'PRD-0001','product_name'=>'Air Jordan',
                'category_id'=>1,'supplier_id'=>1,'warehouse_id'=>1,
                'purchase_price'=>950000.00,'selling_price'=>1750000.00,
                'stock'=>942.000000,'package_type'=>'Pair','product_group'=>'Shoes',
                'registration_number'=>'REG-001','created_at'=>$now,'updated_at'=>$now,
            ],
            [
                'product_code'=>'PRD-0002','product_name'=>'Amazon Fire TV',
                'category_id'=>2,'supplier_id'=>2,'warehouse_id'=>2,
                'purchase_price'=>2100000.00,'selling_price'=>3950000.00,
                'stock'=>587.000000,'package_type'=>'Unit','product_group'=>'Electronics',
                'registration_number'=>'REG-002','created_at'=>$now,'updated_at'=>$now,
            ],
            [
                'product_code'=>'PRD-0003','product_name'=>'Apple iPad 10.2"',
                'category_id'=>2,'supplier_id'=>3,'warehouse_id'=>3,
                'purchase_price'=>4500000.00,'selling_price'=>6999000.00,
                'stock'=>468.000000,'package_type'=>'Unit','product_group'=>'Tablet',
                'registration_number'=>'REG-003','created_at'=>$now,'updated_at'=>$now,
            ],
            [
                'product_code'=>'PRD-0004','product_name'=>'BANGE Anti Theft Backpack',
                'category_id'=>3,'supplier_id'=>4,'warehouse_id'=>4,
                'purchase_price'=>350000.00,'selling_price'=>799000.00,
                'stock'=>519.000000,'package_type'=>'Pcs','product_group'=>'Accessories',
                'registration_number'=>'REG-004','created_at'=>$now,'updated_at'=>$now,
            ],
        ]);
    }
}