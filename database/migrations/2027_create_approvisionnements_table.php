<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Entrées de stock. On garde la quantité d'avant : le mouvement reste
        // lisible même si le stock courant a bougé depuis.
        Schema::create('approvisionnements', function (Blueprint $table) {
            $table->id('id_approvisionnement');
            $table->date('date_approvisionnement');
            $table->string('reference_commande')->unique()->nullable();
            $table->string('origine')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('approvisionnements');
    }
};
