    <?php

    use Illuminate\Support\Facades\DB;
    use Illuminate\Database\Migrations\Migration;

    return new class extends Migration {
        /**
         * Run the migrations.
         */
        public function up()
        {
            DB::statement("ALTER TABLE orders MODIFY status ENUM(
            'pending',
            'paid',
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

        /**
         * Reverse the migrations.
         */
        public function down()
        {
            DB::statement("ALTER TABLE orders MODIFY status ENUM(
            'pending',
            'paid',
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
