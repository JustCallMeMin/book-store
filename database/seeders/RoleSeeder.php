<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Role;

class RoleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run()
    {
        $roles = ['User', 'Admin'];

        foreach ($roles as $roleName) {
            Role::firstOrCreate(
                ['name' => $roleName], // Điều kiện kiểm tra tồn tại
                ['created_at' => now(), 'updated_at' => now()] // Dữ liệu để tạo nếu chưa tồn tại
            );
        }
    }
}
