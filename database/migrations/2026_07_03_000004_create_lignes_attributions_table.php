<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('lignes_attributions', function (Blueprint $table) {
            $table->id('id_ligne_attribution');
            $table->foreignId('id_attribution')->constrained('attributions', 'id_attribution')->cascadeOnDelete();
            $table->foreignId('id_appui')->constrained('appuis', 'id_appui');
            $table->decimal('quantite_attribuee', 12, 2);
            // Le coût est figé à l'attribution : la commission peut le réviser
            // à la baisse par rapport au coût unitaire du catalogue.
            $table->decimal('cout_attribution', 12, 2)->nullable();
            $table->decimal('remise', 5, 2)->default(0);
            $table->timestamps();

            $table->unique(['id_attribution', 'id_appui']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('lignes_attributions');
    }
};
