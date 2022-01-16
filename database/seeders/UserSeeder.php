<?php

namespace Database\Seeders;

use App\Models\Client;
use App\Models\Credit;
use App\Models\User;
use Illuminate\Database\Seeder;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        //
        User::factory()
            ->has(Credit::factory()->count(3))
            ->count(10)
            ->create();
    }
}
