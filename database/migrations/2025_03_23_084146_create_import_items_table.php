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
        Schema::create('import_items', function (Blueprint $table) {
            $table->id();
            $table->uuid('import_id');
            $table->foreign('import_id')->references('id')->on('imports')->onDelete('cascade');
            $table->foreignId('book_id')->constrained('books')->onDelete('cascade');
            $table->integer('quantity')->default(1);
            $table->decimal('unit_cost', 10, 2)->comment('Giá nhập');
            $table->decimal('unit_price', 10, 2)->comment('Giá bán');
            $table->decimal('discount_amount', 10, 2)->default(0);
            $table->decimal('final_cost', 10, 2);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('import_items');
    }
};
