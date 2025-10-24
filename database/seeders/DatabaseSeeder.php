<?php

namespace Database\Seeders;

// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call([
            UsersTableSeeder::class,
            CategoriesTableSeeder::class,
            SuppliersTableSeeder::class,
            WarehousesTableSeeder::class,
            ProductsTableSeeder::class,
            ProductStockTableSeeder::class,
            TransactionsTableSeeder::class,
            TransactionDetailsTableSeeder::class,
            RestockRequestsTableSeeder::class,
            ReportsTableSeeder::class,
        ]);
    }
}
