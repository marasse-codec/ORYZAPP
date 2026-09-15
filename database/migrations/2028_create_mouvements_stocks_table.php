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
        Schema::create('mouvements_stocks', function (Blueprint $table) {
            $table->id('id_mouvement_stock');

            $table->integer('quantite');

            $table->dateTime('date_mouvement');

            $table->string('motif')->nullable();

            // Relations
            $table->foreignId('id_type_mouvement_stock')
                ->constrained('types_mouvements_stocks', 'id_type_mouvement_stock')
                ->restrictOnDelete();

            $table->foreignId('id_appui')
                ->constrained('appuis', 'id_appui')
                ->restrictOnDelete();

            $table->foreignId('id_equipement')
                ->nullable()
                ->constrained('equipements', 'id_equipement')
                ->nullOnDelete();

            $table->foreignId('id_agent')
                ->constrained('agents', 'id_agent')
                ->restrictOnDelete();

            $table->foreignId('id_approvisionnement')
                ->nullable()
                ->constrained('approvisionnements', 'id_approvisionnement')
                ->nullOnDelete();

            $table->foreignId('id_ligne_attribution')
                ->nullable()
                ->constrained('lignes_attributions', 'id_ligne_attribution')
                ->nullOnDelete();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('mouvements_stock');
    }
};
