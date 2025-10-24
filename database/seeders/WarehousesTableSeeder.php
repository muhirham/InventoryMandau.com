<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class WarehousesTableSeeder extends Seeder
{
    public function run(): void
    {
        $now = Carbon::now();
        DB::table('warehouses')->insert([
            ['warehouse_code'=>'WH-JKT','warehouse_name'=>'Gudang Jakarta','address'=>'Kbn. Industri Jkt','note'=>'Pusat','created_at'=>$now,'updated_at'=>$now],
            ['warehouse_code'=>'WH-SBY','warehouse_name'=>'Gudang Surabaya','address'=>'Pelabuhan SBY','note'=>'Timur','created_at'=>$now,'updated_at'=>$now],
            ['warehouse_code'=>'WH-BDG','warehouse_name'=>'Gudang Bandung','address'=>'Cimahi','note'=>'Bandung Raya','created_at'=>$now,'updated_at'=>$now],
            ['warehouse_code'=>'WH-DPS','warehouse_name'=>'Gudang Denpasar','address'=>'Badung','note'=>'Bali','created_at'=>$now,'updated_at'=>$now],
        ]);
    }
}
