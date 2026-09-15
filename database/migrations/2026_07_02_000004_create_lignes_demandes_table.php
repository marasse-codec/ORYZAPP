<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('lignes_demandes', function (Blueprint $table) {
            $table->id('id_ligne_demande');
            $table->foreignId('id_demande')->constrained('demandes', 'id_demande')->cascadeOnDelete();
            $table->foreignId('id_appui')->constrained('appuis', 'id_appui');
            $table->integer('quantite_demandee');
            $table->dateTime('date_rejet_service')->nullable();
            $table->String('motif_rejet_service')->nullable();
            $table->timestamps();

            $table->unique(['id_demande', 'id_appui']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('lignes_demandes');
    }
};
