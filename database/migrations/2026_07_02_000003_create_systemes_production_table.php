<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Une demande déclare la superficie qu'elle exploite, ventilée par
        // système de production (bas-fond, irrigué, plateau).
        Schema::create('systemes_productions', function (Blueprint $table) {
            $table->id('id_systeme_production');
            $table->decimal('superficie', 10, 2);
            $table->foreignId('id_demande')->constrained('demandes', 'id_demande')->cascadeOnDelete();
            $table->foreignId('id_type_systeme_production')->constrained('types_systemes_production', 'id_type_systeme_production');
            $table->timestamps();

            // Une seule superficie par système et par demande.
            $table->unique(['id_demande', 'id_type_systeme_production'], 'systemes_production_demande_type_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('systemes_productions');
    }
};
