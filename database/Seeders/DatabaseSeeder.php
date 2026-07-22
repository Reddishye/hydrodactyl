<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call(NestSeeder::class);
        $this->call(EggSeeder::class);

        // Dev-only seeders: test users, dev location/node/config
        if (app()->environment('local')) {
            $this->call(TestUserSeeder::class);
            $this->call(DevSetupSeeder::class);
        }
    }
}
