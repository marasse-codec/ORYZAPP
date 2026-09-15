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
            $table->date('debut_periode_mise_en_place');
            $table->string('motifs_rejet', 500)->nullable();
            $table->date('date_rejet')->nullable();
            $table->timestamp('date_prise_en_charge')->nullable();
            $table->boolean('en_cours_de_correction')->default(false);
            $table->date('date_attribution')->nullable();
            $table->foreignId('id_acteur')->constrained('acteurs', 'id_acteur')->cascadeOnDelete();
            $table->foreignId('id_user')->constrained('users', 'id')->cascadeOnDelete();
            $table->foreignId('id_type_acteur')->constrained('types_acteurs', 'id_type_acteur');
            $table->foreignId('id_localite')->constrained('localites', 'id_localite');
            $table->foreignId('id_statut_demande')->constrained('statuts_demandes', 'id_statut_demande');
            $table->foreignId('id_campagne')->constrained('campagnes', 'id_campagne');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('demandes');
    }
};
