<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ReportsTableSeeder extends Seeder
{
    public function run(): void
    {
        $now = now();

        DB::table('reports')->insert([
            [
                'report_type'  => 'sales',
                'user_id'      => 1,
                'period_start' => $now->copy()->startOfMonth()->toDateString(),
                'period_end'   => $now->copy()->endOfMonth()->toDateString(),
                'created_at'   => $now,
                'summary'      => json_encode([
                    'orders' => 125,
                    'revenue' => 125000000,
                    'top_product' => 'Air Jordan',
                ]),
            ],
            [
                'report_type'  => 'purchases',
                'user_id'      => 1,
                'period_start' => $now->copy()->subMonth()->startOfMonth()->toDateString(),
                'period_end'   => $now->copy()->subMonth()->endOfMonth()->toDateString(),
                'created_at'   => $now,
                'summary'      => json_encode([
                    'po_count' => 14,
                    'spend' => 89000000,
                    'main_supplier' => 'Mitra Jaya Global',
                ]),
            ],
            [
                'report_type'  => 'stock',
                'user_id'      => 2,
                'period_start' => $now->copy()->subDays(14)->toDateString(),
                'period_end'   => $now->toDateString(),
                'created_at'   => $now,
                'summary'      => json_encode([
                    'items' => 420,
                    'low_stock' => 12,
                    'over_stock' => 3,
                ]),
            ],
            [
                'report_type'  => 'restock',
                'user_id'      => 3,
                'period_start' => $now->copy()->subDays(30)->toDateString(),
                'period_end'   => $now->toDateString(),
                'created_at'   => $now,
                'summary'      => json_encode([
                    'requests' => 9,
                    'approved' => 6,
                    'rejected' => 1,
                    'pending' => 2,
                ]),
            ],
        ]);
    }
}
