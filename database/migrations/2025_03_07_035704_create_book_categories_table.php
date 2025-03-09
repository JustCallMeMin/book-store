<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up()
    {
        Schema::create('book_categories', function (Blueprint $table) {
            $table->foreignId('book_id')->constrained('books')->onDelete('cascade');
            $table->foreignId('category_id')->constrained('categories')->onDelete('cascade');
            $table->primary(['book_id', 'category_id']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('book_categories');
    }
};

