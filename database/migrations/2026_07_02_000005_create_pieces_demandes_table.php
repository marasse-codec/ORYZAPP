<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pieces_demandes', function (Blueprint $table) {
            $table->id('id_piece');
            $table->string('chemin_fichier', 250);
            $table->foreignId('id_demande')->constrained('demandes', 'id_demande')->cascadeOnDelete();
            $table->foreignId('id_type_piece')->constrained('types_pieces', 'id_type_piece')->cascadeOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pieces_demandes');
    }
};
