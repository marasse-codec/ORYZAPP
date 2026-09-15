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
        Schema::create('demandes_corrections', function (Blueprint $table) {

            $table->id('id_demande_correction');

            $table->foreignId('id_demande')
                ->constrained('demandes', 'id_demande')
                ->cascadeOnDelete();

            $table->foreignId('id_traitement')
                ->constrained('traitements', 'id_traitement')
                ->cascadeOnDelete();

            $table->string('motif', 300);

            $table->dateTime('date_demande_correction');

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('demandes_corrections');
    }
};
