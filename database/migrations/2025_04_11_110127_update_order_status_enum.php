<?php

use Illuminate\Support\Facades\DB;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB as FacadesDB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up()
    {
        DB::statement("ALTER TABLE orders MODIFY status ENUM(
        'pending',
        'processing',
        'confirmed',
        'picking',
        'picked',
        'storing',
        'transporting',
        'sorting',
        'delivering',
        'delivered',
        'completed',
        'cancelled',
        'refunded',
        'delivery_fail',
        'returning',
        'returned'
    )");
    }

    public function down()
    {
        DB::statement("ALTER TABLE orders MODIFY status ENUM(
        'pending',
        'processing',
        'confirmed',
        'picking',
        'picked',
        'storing',
        'transporting',
        'sorting',
        'delivering',
        'delivered',
        'completed',
        'cancelled',
        'refunded',
        'delivery_fail',
        'returning',
        'returned'
    )");
    }
};
