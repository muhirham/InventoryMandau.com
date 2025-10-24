<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class SuppliersTableSeeder extends Seeder
{
    public function run(): void
    {
        $now = Carbon::now();
        DB::table('suppliers')->insert([
            ['company_name'=>'PT Nusantara Supply','address'=>'Jl. Merdeka No. 1, Jakarta','contact_person'=>'Budi','phone_number'=>'+62 21 1111 111','bank_name'=>'BCA','bank_account'=>'1234567890','created_at'=>$now,'updated_at'=>$now],
            ['company_name'=>'CV Sejahtera Abadi','address'=>'Jl. Kenangan No. 2, Bandung','contact_person'=>'Sari','phone_number'=>'+62 22 2222 222','bank_name'=>'Mandiri','bank_account'=>'9876543210','created_at'=>$now,'updated_at'=>$now],
            ['company_name'=>'Mitra Jaya Global','address'=>'Jl. Mawar No. 3, Surabaya','contact_person'=>'Joko','phone_number'=>'+62 31 3333 333','bank_name'=>'BNI','bank_account'=>'5678901234','created_at'=>$now,'updated_at'=>$now],
            ['company_name'=>'Global Sukses','address'=>'Jl. Pahlawan No. 4, Denpasar','contact_person'=>'Dewi','phone_number'=>'+62 361 4444 444','bank_name'=>'BRI','bank_account'=>'0123456789','created_at'=>$now,'updated_at'=>$now],
        ]);
    }
}