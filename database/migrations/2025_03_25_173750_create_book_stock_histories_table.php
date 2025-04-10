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
        Schema::create('book_stock_histories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('book_id')->constrained()->onDelete('cascade');
            $table->foreignUuid('user_id')->nullable()->constrained()->nullOnDelete();
            $table->integer('previous_quantity');
            $table->integer('new_quantity');
            $table->integer('adjustment');
            $table->enum('action', ['set', 'add', 'subtract', 'order', 'import', 'return', 'other'])->default('other');
            $table->text('reason')->nullable();
            $table->string('order_id')->nullable()->comment('Related order ID if adjustment was due to an order');
            $table->string('import_id')->nullable()->comment('Related import ID if adjustment was due to an import');
            $table->timestamps();
            
            $table->index(['book_id', 'created_at']);
            $table->index('user_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('book_stock_histories');
    }
};
