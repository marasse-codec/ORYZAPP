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

            $table->date('periode_debut_mise_en_place')->nullable();
            $table->date('periode_fin_mise_en_place')->nullable();

            $table->boolean('soumise')->default(false);

            $table->foreignId('id_acteur')->constrained('acteurs', 'id_acteur')->cascadeOnDelete();
            $table->foreignId('id_user')->constrained('users', 'id')->cascadeOnDelete();
            $table->foreignId('id_type_acteur')->constrained('types_acteurs', 'id_type_acteur');
            $table->foreignId('id_localite')->constrained('localites', 'id_localite');

            $table->foreignId('id_campagne')->constrained('campagnes', 'id_campagne');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('demandes');
    }
};
