<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class StaffSeeder extends Seeder
{
  public function run(): void
    {
        User::create([
            'name' => 'KeyshAa Hadeed',
            'email' => 'staff@gmail.com',
            'password' => Hash::make('staff123'),
            'is_admin' => false,
            'is_active' => true,
        ]);
    }
}