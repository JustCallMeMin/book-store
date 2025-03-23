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
        Schema::create('orders', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('user_id');
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->string('order_code')->unique();
            
            // Thông tin khách hàng
            $table->string('customer_name');
            $table->string('customer_email')->nullable();
            $table->string('customer_phone')->nullable();
            $table->string('customer_address');
            
            // Thông tin đơn hàng
            $table->decimal('total_amount', 10, 2)->default(0);
            $table->decimal('shipping_fee', 10, 2)->default(0);
            $table->decimal('tax_amount', 10, 2)->default(0);
            $table->decimal('discount_amount', 10, 2)->default(0);
            $table->decimal('final_amount', 10, 2)->default(0);
            
            // Lịch sử đơn hàng
            $table->timestamp('order_date')->useCurrent();
            $table->timestamp('payment_date')->nullable();
            $table->timestamp('shipping_date')->nullable();
            $table->timestamp('delivery_date')->nullable();
            
            // Trạng thái đơn hàng
            $table->enum('status', [
                'pending', 'processing', 'confirmed', 'shipping', 
                'delivered', 'completed', 'cancelled', 'refunded'
            ])->default('pending');
            
            // Phương thức thanh toán và vận chuyển
            $table->string('payment_method')->default('cash');
            $table->string('payment_status')->default('pending');
            $table->string('shipping_method')->nullable();
            $table->text('notes')->nullable();
            
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('orders');
    }
};
