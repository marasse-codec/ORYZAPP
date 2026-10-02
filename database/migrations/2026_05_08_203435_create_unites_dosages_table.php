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
        Schema::create('unites_dosages', function (Blueprint $table) {
            $table->id('id_unite');
            $table->string('code_unite');
            $table->string('lib_unite');
            $table->timestamps();

            $table->unique(['code_unite']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('unites_dosages');
    }
};
