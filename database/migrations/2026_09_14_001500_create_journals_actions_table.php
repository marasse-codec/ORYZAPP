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
    }

    public function down(): void
    {
        Schema::dropIfExists('journals_actions');
    }
};