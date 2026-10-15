<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('attributions', function (Blueprint $table) {
            $table->id('id_attribution');
            // Une demande donne lieu à une attribution au plus.
            $table->foreignId('id_demande')->nullable()->constrained('demandes', 'id_demande')->cascadeOnDelete();
            $table->foreignId('id_session')->constrained('sessions_commissions', 'id_session')->restrictOnDelete();
            $table->string('commentaires', 500)->nullable();
            $table->decimal('remise', 5, 2)->default(0);
            // Renseignées au retrait : elles marquent le déstockage. Le scan
            // de la pièce d'identité atteste de qui a effectivement reçu.
            $table->date('date_destockage')->nullable();
            // Personne qui se présente au magasin pour le retrait.
            $table->string('recipiendaire')->nullable();
            $table->string('numero_piece_identite', 60)->nullable();
            $table->string('chemin_piece_identite', 250)->nullable();
            // $table->foreignId('id_statut_attribution')->constrained('statuts_attributions', 'id_statut_attribution');
            $table->dateTime('date_publication')->nullable();

            // $table->foreignId('id_traitement')->nullable()
            //     ->constrained('traitements', 'id_traitement')
            //     ->cascadeOnDelete();

            $table->foreignId('id_user')->nullable()->constrained('users', 'id')->cascadeOnDelete();


            $table->timestamps();
        });

        // ================= Schéma v2 (historisation) =================

        // Suppression « douce » : une attribution annulée n'est plus effacée, elle est
        // marquée (date d'annulation). Elle disparaît des écrans et des calculs, mais
        // son contenu (lignes, superficies) reste consultable pour l'historique.
        Schema::table('attributions', function (Blueprint $table) {
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('attributions');
    }
};
