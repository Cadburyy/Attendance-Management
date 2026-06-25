<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('attendances', function (Blueprint $table) {
            // Change the column to LONGTEXT to handle the massive encrypted JSON string
            $table->longText('image')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('attendances', function (Blueprint $table) {
            // Revert back to string if you ever rollback
            $table->string('image', 255)->nullable()->change();
        });
    }
};