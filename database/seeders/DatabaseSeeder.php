<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        //manggil seeder
        $this->call([
            AdminSeeder::class,
            StaffSeeder::class,
        ]);
    }
}