<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('departements', function (Blueprint $table) {
            $table->id('id_departement');
            $table->string('lib_departement', 250);
            $table->timestamps();
        });

        // ================= Schéma v2 (responsabilité des délégués de pôle) =================

        // Les départements dont un délégué de pôle (DP) est responsable : il voit les demandes
        // de ces départements, leurs attributions, et reçoit les notifications qui les concernent.
        Schema::create('departements_users', function (Blueprint $table) {
            $table->foreignId('id_departement')->constrained('departements', 'id_departement')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users', 'id')->cascadeOnDelete();
            $table->primary(['id_departement', 'user_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('departements_users');
        Schema::dropIfExists('departements');
    }
};
