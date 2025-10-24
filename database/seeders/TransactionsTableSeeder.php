<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class TransactionsTableSeeder extends Seeder
{
    public function run(): void
    {
        $now = Carbon::now();

        $userId = DB::table('users')->inRandomOrder()->value('id');
        $warehouseId = DB::table('warehouses')->inRandomOrder()->value('id');

        if (!$userId || !$warehouseId) {
            dump('Pastikan users & warehouses sudah di-seed sebelum transaksi.');
            return;
        }

        $rows = [
            [
                'user_id'          => $userId,
                'warehouse_id'     => $warehouseId,
                'transaction_date' => $now,
                'total'            => 1_750_000,
                'paid_amount'      => 1_800_000,
                'change_amount'    => 50_000,
                'transaction_type' => 'sale',
                'status'           => 'completed',
                'created_at'       => $now, 'updated_at' => $now,
            ],
            [
                'user_id'          => $userId,
                'warehouse_id'     => $warehouseId,
                'transaction_date' => $now,
                'total'            => 3_950_000,
                'paid_amount'      => 3_950_000,
                'change_amount'    => 0,
                'transaction_type' => 'sale',
                'status'           => 'completed',
                'created_at'       => $now, 'updated_at' => $now,
            ],
            [
                'user_id'          => $userId,
                'warehouse_id'     => $warehouseId,
                'transaction_date' => $now,
                'total'            => 800_000,
                'paid_amount'      => 799_000,
                'change_amount'    => 1_000,
                'transaction_type' => 'purchase',
                'status'           => 'pending',
                'created_at'       => $now, 'updated_at' => $now,
            ],
        ];

        DB::table('transactions')->insert($rows);
    }
}
