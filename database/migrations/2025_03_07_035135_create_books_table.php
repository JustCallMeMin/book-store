<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up()
    {
        Schema::create('books', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('gutendex_id')->unique()->nullable()->comment('Project Gutenberg ID');
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
            
            // Thông tin thêm cho cửa hàng sách
            $table->decimal('price', 10, 2)->default(0)->comment('Giá bán');
            $table->decimal('original_price', 10, 2)->nullable()->comment('Giá gốc');
            $table->unsignedInteger('quantity')->default(0)->comment('Số lượng tồn kho');
            $table->string('isbn')->nullable()->comment('Mã ISBN');
            $table->integer('publication_year')->nullable()->comment('Năm xuất bản');
            $table->string('cover_image')->nullable()->comment('URL ảnh bìa');
            $table->integer('pages')->nullable()->comment('Số trang');
            $table->text('description')->nullable()->comment('Mô tả chi tiết');
            $table->foreignId('publisher_id')->nullable()->constrained('publishers')->nullOnDelete();
            $table->boolean('featured')->default(false)->comment('Sách nổi bật');
            $table->boolean('active')->default(true)->comment('Trạng thái hoạt động');
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('books');
    }
};

