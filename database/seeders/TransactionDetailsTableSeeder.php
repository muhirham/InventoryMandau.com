<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class TransactionDetailsTableSeeder extends Seeder
{
    public function run(): void
    {
        $now = Carbon::now();

        // Ambil 4 transaksi pertama & 4 produk pertama
        $trxIds  = DB::table('transactions')->orderBy('id')->limit(4)->pluck('id')->all();
        $prodIds = DB::table('products')->orderBy('id')->limit(4)->pluck('id')->all();

        if (count($trxIds) < 4 || count($prodIds) < 4) {
            $this->command->warn('Butuh min 4 transactions & 4 products untuk seed detail.');
            return;
        }

        $rows = [
            // trx 1: jual produk 1, qty 1, harga 1.750.000
            ['transaction_id'=>$trxIds[0],'product_id'=>$prodIds[0],'quantity'=>1,'price'=>1_750_000,'subtotal'=>1_750_000,'created_at'=>$now,'updated_at'=>$now],
            // trx 2: jual produk 2, qty 1, harga 3.950.000
            ['transaction_id'=>$trxIds[1],'product_id'=>$prodIds[1],'quantity'=>1,'price'=>3_950_000,'subtotal'=>3_950_000,'created_at'=>$now,'updated_at'=>$now],
            // trx 3: beli produk 3, qty 2, harga 2.300.000 (subtotal 4.600.000)
            ['transaction_id'=>$trxIds[2],'product_id'=>$prodIds[2],'quantity'=>2,'price'=>2_300_000,'subtotal'=>4_600_000,'created_at'=>$now,'updated_at'=>$now],
            // trx 4: jual produk 4, qty 1, harga 799.000
            ['transaction_id'=>$trxIds[3],'product_id'=>$prodIds[3],'quantity'=>1,'price'=>799_000,'subtotal'=>799_000,'created_at'=>$now,'updated_at'=>$now],
        ];

        DB::table('transaction_details')->insert($rows);
    }
}
