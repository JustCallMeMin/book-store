<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run()
    {
        // Seed bảng roles (nếu cần)
        $this->call([
            RoleSeeder::class, // Nếu bạn có seeder role riêng
        ]);

        // Seed admin
        $this->call(AdminSeeder::class);
    }
}
