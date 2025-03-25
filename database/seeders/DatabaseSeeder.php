<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // Seed bảng roles (nếu cần)
        $this->call([
            RoleSeeder::class, // Nếu bạn có seeder role riêng
            AdminSeeder::class,
            RedisPermissionSeeder::class,
        ]);

        // Seed admin
        $this->call(AdminSeeder::class);
    }
}
