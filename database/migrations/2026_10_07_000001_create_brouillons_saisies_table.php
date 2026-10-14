<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /** Brouillon d'un formulaire en cours de saisie : un seul par utilisateur et par type de formulaire. */
    public function up(): void
    {
        Schema::create('brouillons_saisies', function (Blueprint $table) {
            $table->id('id_brouillon');
            $table->unsignedBigInteger('id_user');
            $table->string('type', 30);
            $table->json('donnees');
            $table->timestamps();

            $table->unique(['id_user', 'type'], 'uq_brouillons_user_type');
            $table->foreign('id_user', 'fk_brouillons_user')->references('id')->on('users')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('brouillons_saisies');
    }
};
