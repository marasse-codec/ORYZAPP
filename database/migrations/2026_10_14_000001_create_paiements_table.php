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
    }

    public function down(): void
    {
        Schema::dropIfExists('paiements');
    }
};
