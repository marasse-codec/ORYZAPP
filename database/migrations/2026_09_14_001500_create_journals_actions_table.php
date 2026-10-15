<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('journals_actions', function (Blueprint $table) {
            $table->id();
            // Le journal survit à ses auteurs : on ne supprime pas un utilisateur qui a agi.
            $table->foreignId('user_id')->constrained()->restrictOnDelete();
            $table->string('action');
            $table->nullableMorphs('cible');
            // Contexte de l'action (motif, quantités…), pour comprendre sans retrouver la cible.
            $table->json('details')->nullable();
            $table->timestamps();
        });

        // ================= Schéma v2 (historisation) =================

        Schema::table('journals_actions', function (Blueprint $table) {
            // Une tentative de connexion échouée n'a pas d'auteur connu : l'auteur devient facultatif.
            $table->foreignId('user_id')->nullable()->change();
            // D'où venait la demande (poste, réseau) : utile pour les connexions et les exports.
            $table->string('adresse_ip', 45)->nullable()->after('details');
            $table->index(['cible_type', 'cible_id', 'created_at'], 'journal_cible_date');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('journals_actions');
    }
};