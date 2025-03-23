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
        Schema::create('reviews', function (Blueprint $table) {
            $table->id();
            $table->uuid('user_id');
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->foreignId('book_id')->constrained('books')->onDelete('cascade');
            $table->foreignId('order_item_id')->nullable()->constrained('order_items')->nullOnDelete();
            $table->integer('rating')->default(5)->comment('Đánh giá từ 1-5 sao');
            $table->text('comment')->nullable()->comment('Bình luận');
            $table->text('reply')->nullable()->comment('Phản hồi từ admin');
            $table->boolean('is_verified_purchase')->default(false)->comment('Đã mua hàng');
            $table->boolean('is_approved')->default(false)->comment('Đã được duyệt');
            $table->timestamp('reviewed_at')->useCurrent();
            $table->timestamps();
            
            // Mỗi người dùng chỉ được đánh giá một cuốn sách một lần
            $table->unique(['user_id', 'book_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('reviews');
    }
};
