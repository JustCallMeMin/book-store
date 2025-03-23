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
        // This migration is to ensure compatibility with the GutendexService
        // which uses MD5 hashes (strings) as author IDs instead of integers
        Schema::table('authors', function (Blueprint $table) {
            // We won't modify the column since it's already varchar(64) in the database
            // This migration serves as documentation that the column should be a string
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('authors', function (Blueprint $table) {
            // No changes to revert
        });
    }
};
