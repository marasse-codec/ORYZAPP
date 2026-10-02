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
        Schema::create('traitements', function (Blueprint $table) {

            $table->id('id_traitement');

            $table->foreignId('id_demande')
                ->constrained('demandes', 'id_demande')
                ->cascadeOnDelete();

            $table->foreignId('id_service')
                ->constrained('services', 'id_service')
                ->cascadeOnDelete();

            $table->timestamps();

            $table->unique(['id_demande', 'id_service']);
            
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('traitements');
    }
};
