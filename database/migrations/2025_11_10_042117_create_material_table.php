<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {

        Schema::dropIfExists('items');

        Schema::create('items', function (Blueprint $table) {
            $table->id();
            $table->string('transaction_type')->default('production')->index();
            $table->date('tanggal');
            $table->string('customer')->nullable();
            $table->string('material');
            $table->string('part')->nullable();
            $table->string('no_lot')->nullable();
            $table->string('kode')->nullable();
            $table->decimal('berat_mentah', 15, 3)->default(0);
            $table->integer('gpcs')->default(0);
            $table->decimal('gkg', 15, 3)->default(0);
            $table->decimal('scrap', 15, 3)->default(0);
            $table->decimal('cakalan', 15, 3)->default(0);

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('items');
    }
};