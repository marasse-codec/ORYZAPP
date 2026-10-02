<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('demandes', function (Blueprint $table) {
            $table->id('id_demande');
            $table->date('date_demande');

            $table->string('nom_structure')->nullable();
            $table->boolean('terrain_visite')->default(false);

            $table->string('motifs_rejet')->nullable();
            
            $table->date('periode_debut_mise_en_place')->nullable();
            $table->date('periode_fin_mise_en_place')->nullable();

            $table->dateTime('date_soummission')->nullable()->default(null);

            $table->foreignId('id_acteur')->constrained('acteurs', 'id_acteur')->cascadeOnDelete();
            $table->foreignId('id_user')->constrained('users', 'id')->cascadeOnDelete();
            $table->foreignId('id_type_acteur')->constrained('types_acteurs', 'id_type_acteur');
            $table->foreignId('id_session')->nullable()->constrained('sessions_commissions', 'id_session')->cascadeOnDelete();

            
            $table->string('observations')->nullable();

            $table->foreignId('id_campagne')->constrained('campagnes', 'id_campagne');
            $table->timestamps();

            $table->unique(['id_demande','nom_structure']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('demandes');
    }
};
