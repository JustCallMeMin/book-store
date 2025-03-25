<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('users', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('first_name');
            $table->string('last_name');
            $table->string('email')->unique();
            $table->string('provider')->nullable(); // 'google', 'email', etc
            $table->string('provider_id')->nullable(); // ID từ provider (google_id)
            $table->boolean('oauth_verified')->default(false); // Trạng thái xác thực OAuth
            $table->timestamp('oauth_verified_at')->nullable(); // Thời điểm xác thực OAuth
            $table->string('oauth_token')->nullable(); // Token từ OAuth provider
            $table->string('oauth_refresh_token')->nullable(); // Refresh token
            $table->timestamp('email_verified_at')->nullable();
            $table->string('password');
            $table->boolean('remember_me')->default(false);
            $table->rememberToken();
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('users');
    }
}; 