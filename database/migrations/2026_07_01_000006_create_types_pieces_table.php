<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('types_pieces', function (Blueprint $table) {
            $table->id('id_type_piece');
            $table->string('lib_type_piece', 60);
            $table->timestamps();
        });

        // C'est ce rattachement qui dit quelles pièces un demandeur doit
        // fournir : le type d'acteur choisi au début du formulaire détermine
        // à lui seul la liste des téléversements attendus.
        Schema::create('types_acteurs_types_pieces', function (Blueprint $table) {
            $table->id();
            $table->foreignId('id_type_acteur')->constrained('types_acteurs', 'id_type_acteur')->cascadeOnDelete();
            $table->foreignId('id_type_piece')->constrained('types_pieces', 'id_type_piece')->cascadeOnDelete();
            $table->boolean('est_obligatoire')->default(true);
            $table->timestamps();

            $table->unique(['id_type_acteur', 'id_type_piece']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('types_acteurs_types_pieces');
        Schema::dropIfExists('types_pieces');
    }
};
