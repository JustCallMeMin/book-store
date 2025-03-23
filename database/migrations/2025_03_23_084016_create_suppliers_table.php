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
        Schema::create('suppliers', function (Blueprint $table) {
            $table->id();
            $table->string('name')->comment('Tên nhà cung cấp');
            $table->string('address')->nullable()->comment('Địa chỉ nhà cung cấp');
            $table->string('phone')->nullable()->comment('Số điện thoại nhà cung cấp');
            $table->string('email')->nullable()->comment('Email nhà cung cấp');
            $table->text('description')->nullable()->comment('Mô tả nhà cung cấp');
            $table->boolean('active')->default(true)->comment('Trạng thái hoạt động');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('suppliers');
    }
};
