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

            $table->string('observation_interne')->nullable();

            $table->dateTime('date_fin_traitement')->nullable();

            $table->foreignId('id_demande')
                ->constrained('demandes', 'id_demande')
                ->cascadeOnDelete();

            $table->foreignId('id_service')
                ->constrained('services', 'id_service')
                ->cascadeOnDelete();

            $table->unique(['id_demande', 'id_service']);
            $table->timestamps();

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
