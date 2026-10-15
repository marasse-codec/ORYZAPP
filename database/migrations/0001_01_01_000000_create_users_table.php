<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('email')->unique();
            $table->timestamp('email_verified_at')->nullable();
            $table->string('password');
            $table->rememberToken();
            $table->timestamps();
        });

        Schema::create('password_reset_tokens', function (Blueprint $table) {
            $table->string('email')->primary();
            $table->string('token');
            $table->timestamp('created_at')->nullable();
        });

        Schema::create('sessions', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->foreignId('user_id')->nullable()->index();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->longText('payload');
            $table->integer('last_activity')->index();
        });

        // ================= Schéma v2 (évolutions de l'administration) =================
        // Ajouté à part pour qu'on distingue d'un coup d'œil la table d'origine de ses ajouts.

        // Un compte se désactive au lieu de se supprimer : son historique (demandes,
        // journal) reste rattaché à lui, mais il ne peut plus se connecter.
        Schema::table('users', function (Blueprint $table) {
            $table->boolean('actif')->default(true)->after('password');
            $table->dateTime('desactive_le')->nullable()->after('actif');
            $table->string('motif_desactivation', 255)->nullable()->after('desactive_le');
            $table->dateTime('derniere_connexion_le')->nullable()->after('motif_desactivation');
        });

        // Les paramètres généraux que l'administrateur règle sans toucher au code
        // (longueur du mot de passe, délais des rappels…). Une ligne par paramètre.
        Schema::create('parametres', function (Blueprint $table) {
            $table->id('id_parametre');
            $table->string('cle', 60)->unique();
            $table->string('valeur', 255)->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('parametres');
        Schema::dropIfExists('users');
        Schema::dropIfExists('password_reset_tokens');
        Schema::dropIfExists('sessions');
    }
};
