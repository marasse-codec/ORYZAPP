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
        Schema::create('demandes_localites', function (Blueprint $table) {
            $table->id();
            
            // Clés étrangères vers les tables parentes
            $table->foreignId('id_demande')
                  ->constrained('demandes','id_demande')
                  ->cascadeOnDelete();

            $table->foreignId('id_localite')
                  ->constrained('localites','id_localite')
                  ->cascadeOnDelete();

            // Empêche d'associer deux fois le même appui au même système de production
            $table->unique(['id_demande', 'id_localite']);

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('demandes_localites');
    }
};
