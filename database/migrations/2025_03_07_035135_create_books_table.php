<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up()
    {
        Schema::create('books', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('gutendex_id')->unique()->comment('Project Gutenberg ID');
            $table->string('title');
            $table->text('subjects')->nullable()->comment('JSON array of subjects');
            $table->text('bookshelves')->nullable()->comment('JSON array of bookshelves');
            $table->text('languages')->nullable()->comment('JSON array of languages');
            $table->text('summaries')->nullable()->comment('JSON array of summaries');
            $table->text('translators')->nullable()->comment('JSON array of translators');
            $table->boolean('copyright')->nullable();
            $table->string('media_type')->nullable();
            $table->text('formats')->nullable()->comment('JSON array of formats and URLs');
            $table->unsignedInteger('download_count')->default(0);
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('books');
    }
};

