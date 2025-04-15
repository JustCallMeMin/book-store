<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
   public function up()
{
    Schema::table('orders', function (Blueprint $table) {
        $table->renameColumn('customer_name', 'recipient_name');
        $table->renameColumn('customer_email', 'recipient_email');
        $table->renameColumn('customer_phone', 'recipient_phone');
        $table->renameColumn('customer_address', 'recipient_address');
    });
}

public function down()
{
    Schema::table('orders', function (Blueprint $table) {
        $table->renameColumn('recipient_name', 'customer_name');
        $table->renameColumn('recipient_email', 'customer_email');
        $table->renameColumn('recipient_phone', 'customer_phone');
        $table->renameColumn('recipient_address', 'customer_address');
    });
}

};
