<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('appuis', function (Blueprint $table) {
            $table->id('id_appui');
            $table->string('libelle');
            $table->integer('quantite_en_stock')->default(0);
            // En dessous de ce seuil, l'appui apparaît dans les alertes de stock du tableau de bord.
            $table->unsignedInteger('seuil_alerte')->default(0);
            $table->decimal('cout_unitaire', 12, 2)->nullable();
            $table->decimal('dose_ha',12,3);
            $table->foreignId('id_type_appui')->constrained('types_appuis', 'id_type_appui');
            $table->foreignId('id_unite')->constrained('unites_dosages', 'id_unite');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('appuis');
    }
};
