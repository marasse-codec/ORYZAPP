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
            $table->decimal('quantite_demandee',12,2)->nullable();
            $table->boolean('cession')->nullable();
            $table->foreignId('id_demande')->constrained('demandes', 'id_demande')->cascadeOnDelete();
            $table->foreignId('id_type_appui')->constrained('types_appuis', 'id_type_appui');
            $table->foreignId('id_appui')->nullable()->constrained('appuis', 'id_appui');
            $table->timestamps();

            $table->unique(['id_demande', 'id_type_appui']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('lignes_demandes');
    }
};
