<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class ProductStockTableSeeder extends Seeder
{
    public function run(): void
    {
        $now = Carbon::now();

        // satu kombinasi unik per produk (product_id, warehouse_id) agar lolos unique index
        DB::table('product_stock')->insert([
            ['product_id'=>1,'warehouse_id'=>1,'initial_stock'=>900.000000,'stock_in'=>80.000000,'stock_out'=>38.000000,'final_stock'=>942.000000,'last_update'=>$now,'created_at'=>$now,'updated_at'=>$now],
            ['product_id'=>2,'warehouse_id'=>2,'initial_stock'=>560.000000,'stock_in'=>60.000000,'stock_out'=>33.000000,'final_stock'=>587.000000,'last_update'=>$now,'created_at'=>$now,'updated_at'=>$now],
            ['product_id'=>3,'warehouse_id'=>3,'initial_stock'=>430.000000,'stock_in'=>60.000000,'stock_out'=>22.000000,'final_stock'=>468.000000,'last_update'=>$now,'created_at'=>$now,'updated_at'=>$now],
            ['product_id'=>4,'warehouse_id'=>4,'initial_stock'=>480.000000,'stock_in'=>60.000000,'stock_out'=>21.000000,'final_stock'=>519.000000,'last_update'=>$now,'created_at'=>$now,'updated_at'=>$now],
        ]);
    }
}
