<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Role;
use Illuminate\Support\Facades\Hash;

class AdminSeeder extends Seeder
{
    /**
     * Seed the application's database with an admin user.
     */
    public function run()
    {
        // Định nghĩa email admin mặc định
        $adminEmail = 'admin@example.com';
        $adminPassword = 'Password123!';
        // Tìm hoặc tạo admin user (không ghi đè nếu đã có)
        $admin = User::firstOrCreate(
            ['email' => $adminEmail],
            [
                'first_name' => 'Admin',
                'last_name'  => 'User',
                'password'   => Hash::make($adminPassword), // Thay 'secret' bằng mật khẩu mong muốn
            ]
        );

        // Kiểm tra role "Admin" đã tồn tại chưa
        $adminRole = Role::firstOrCreate(
            ['name' => 'Admin'],
            ['created_at' => now(), 'updated_at' => now()]
        );

        // Nếu user chưa có role, gán role Admin
        if (!$admin->roles()->where('role_id', $adminRole->id)->exists()) {
            $admin->roles()->attach($adminRole->id);
        }
    }
}
