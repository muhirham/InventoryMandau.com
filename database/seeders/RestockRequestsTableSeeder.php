<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class RestockRequestsTableSeeder extends Seeder
{
    public function run(): void
    {
        $now = Carbon::now();

        // Ambil beberapa ID referensi yang valid
        $p = DB::table('products')->orderBy('id')->limit(4)->pluck('id')->all();
        $s = DB::table('suppliers')->orderBy('id')->limit(4)->pluck('id')->all();
        $w = DB::table('warehouses')->orderBy('id')->limit(4)->pluck('id')->all();
        $u = DB::table('users')->orderBy('id')->limit(3)->pluck('id')->all();

        if (count($p)<4 || count($s)<4 || count($w)<4 || count($u)<1) {
            $this->command->warn('Pastikan products, suppliers, warehouses, users terisi dulu.');
            return;
        }

        DB::table('restock_requests')->insert([
            [
                'product_id'=>$p[0],'supplier_id'=>$s[0],'warehouse_id'=>$w[0],'user_id'=>$u[0],
                'request_date'=>$now->copy()->subDays(7)->toDateString(),
                'quantity_requested'=>50,'total_cost'=>47_500_000,
                'description'=>'Re-stock item #1','status'=>'approved',
                'created_at'=>$now,'updated_at'=>$now,
            ],
            [
                'product_id'=>$p[1],'supplier_id'=>$s[1],'warehouse_id'=>$w[1],'user_id'=>$u[0],
                'request_date'=>$now->copy()->subDays(5)->toDateString(),
                'quantity_requested'=>30,'total_cost'=>63_000_000,
                'description'=>'Re-stock item #2','status'=>'pending',
                'created_at'=>$now,'updated_at'=>$now,
            ],
            [
                'product_id'=>$p[2],'supplier_id'=>$s[2],'warehouse_id'=>$w[2],'user_id'=>$u[0],
                'request_date'=>$now->copy()->subDays(3)->toDateString(),
                'quantity_requested'=>20,'total_cost'=>90_000_000,
                'description'=>'Re-stock item #3','status'=>'rejected',
                'created_at'=>$now,'updated_at'=>$now,
            ],
            [
                'product_id'=>$p[3],'supplier_id'=>$s[3],'warehouse_id'=>$w[3],'user_id'=>$u[1] ?? $u[0],
                'request_date'=>$now->copy()->subDays(1)->toDateString(),
                'quantity_requested'=>100,'total_cost'=>35_000_000,
                'description'=>'Re-stock item #4','status'=>'approved',
                'created_at'=>$now,'updated_at'=>$now,
            ],
        ]);
    }
}