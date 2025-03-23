<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // Xóa cột remember_token_expires_at vì không cần thiết trong mô hình mới
            $table->dropColumn('remember_token_expires_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // Thêm lại cột nếu rollback migration
            $table->timestamp('remember_token_expires_at')->nullable();
        });
    }
};
