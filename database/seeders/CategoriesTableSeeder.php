<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class CategoriesTableSeeder extends Seeder
{
    public function run(): void
    {
        $now = Carbon::now();
        DB::table('categories')->insert([
            ['category_name'=>'Shoes',        'description'=>'All kinds of shoes','created_at'=>$now,'updated_at'=>$now],
            ['category_name'=>'Electronics',  'description'=>'Gadgets and devices','created_at'=>$now,'updated_at'=>$now],
            ['category_name'=>'Accessories',  'description'=>'Wearable accessories','created_at'=>$now,'updated_at'=>$now],
            ['category_name'=>'Home & Living','description'=>'Home improvement','created_at'=>$now,'updated_at'=>$now],
        ]);
    }
}