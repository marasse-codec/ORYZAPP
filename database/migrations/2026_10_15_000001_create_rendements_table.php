<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Le retour du DP en fin de campagne : ce que chaque superficie déclarée par une demande
        // (un système de production) a réellement produit. Le rendement (t/ha) se déduit
        // (production ÷ superficie récoltée), il n'est pas stocké.
        Schema::create('rendements', function (Blueprint $table) {
            $table->id('id_rendement');
            $table->foreignId('id_systeme_production')->constrained('systemes_productions', 'id_systeme_production')->cascadeOnDelete();
            // Peut être inférieure à la superficie déclarée (sinistre, abandon de parcelle).
            $table->decimal('superficie_recoltee', 10, 2);
            $table->decimal('production_tonnes', 12, 3);
            // Le DP qui a transmis le retour : on garde la trace même si son compte disparaît.
            $table->foreignId('id_user')->nullable()->constrained('users', 'id')->nullOnDelete();
            $table->timestamps();

            // Un seul retour par superficie déclarée ; le DP le corrige plutôt que d'en ajouter un.
            $table->unique('id_systeme_production');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('rendements');
    }
};
