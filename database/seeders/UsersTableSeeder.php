<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Carbon\Carbon;

class UsersTableSeeder extends Seeder
{
   // Database\Seeders\UsersTableSeeder.php
public function run(): void
{
    $now = \Carbon\Carbon::now();
    DB::table('users')->insert([
        [
            'name' => 'Admin Utama',
            'email' => 'admin@example.com',
            'email_verified_at' => $now,
            'password' => \Illuminate\Support\Facades\Hash::make('password'),
            'role' => 'admin',
            'remember_token' => \Illuminate\Support\Str::random(10),
            'created_at' => $now, 'updated_at' => $now,
        ],
        [
            'name' => 'Gudang Jakarta',
            'email' => 'gudang.jkt@example.com',
            'email_verified_at' => $now,
            'password' => \Illuminate\Support\Facades\Hash::make('password'),
            'role' => 'warehouse',
            'remember_token' => \Illuminate\Support\Str::random(10),
            'created_at' => $now, 'updated_at' => $now,
        ],
        [
            'name' => 'Keuangan',
            'email' => 'finance@example.com',
            'email_verified_at' => $now,
            'password' => \Illuminate\Support\Facades\Hash::make('password'),
            'role' => 'director',
            'remember_token' => \Illuminate\Support\Str::random(10),
            'created_at' => $now, 'updated_at' => $now,
        ],
        [
            'name' => 'Kasir Toko',
            'email' => 'kasir@example.com',
            'email_verified_at' => null,
            'password' => \Illuminate\Support\Facades\Hash::make('password'),
            'role' => 'warehouse', // ← JANGAN 'user'
            'remember_token' => \Illuminate\Support\Str::random(10),
            'created_at' => $now, 'updated_at' => $now,
        ],
    ]);
}

}