<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Les règlements d'une attribution : un acteur peut payer en plusieurs fois.
        // Le reste dû se déduit (montant de l'attribution − somme des paiements), il n'est pas stocké.
        Schema::create('paiements', function (Blueprint $table) {
            $table->id('id_paiement');
            $table->foreignId('id_attribution')->constrained('attributions', 'id_attribution')->cascadeOnDelete();
            $table->decimal('montant', 12, 2);
            $table->date('date_paiement');
            $table->string('mode', 30);
            $table->string('reference', 100)->nullable();
            // L'agent qui a encaissé : on garde la trace même si son compte disparaît.
            $table->foreignId('id_user')->nullable()->constrained('users', 'id')->nullOnDelete();
            $table->timestamps();

            $table->index(['id_attribution', 'date_paiement']);
        });

        // ================= Schéma v2 (règlement en brouillon puis soumis) =================

        // Un règlement est d'abord un brouillon : le comptable peut le corriger ou le supprimer.
        // Il ne compte dans ce qui est payé qu'une fois soumis (date de soumission renseignée).
        Schema::table('paiements', function (Blueprint $table) {
            $table->dateTime('date_soumission')->nullable()->after('reference');
            $table->foreignId('id_user_soumission')->nullable()->after('date_soumission')->constrained('users', 'id')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('paiements');
    }
};
