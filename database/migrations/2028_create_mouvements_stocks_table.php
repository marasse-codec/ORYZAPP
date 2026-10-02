<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
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

            // Modification : restrictOnDelete() au lieu de nullOnDelete()
            $table->foreignId('id_approvisionnement')
                ->nullable()
                ->constrained('approvisionnements', 'id_approvisionnement')
                ->restrictOnDelete();

            // Modification : restrictOnDelete() au lieu de nullOnDelete()
            $table->foreignId('id_attribution')
                ->nullable()
                ->constrained('attributions', 'id_attribution')
                ->restrictOnDelete();

            $table->timestamps();
        });
        
        // Contrainte d'exclusivité (XOR)
        DB::statement('ALTER TABLE mouvements_stocks ADD CONSTRAINT chk_origine_unique CHECK (
            (id_approvisionnement IS NOT NULL AND id_attribution IS NULL) OR
            (id_approvisionnement IS NULL AND id_attribution IS NOT NULL)
        )');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('mouvements_stocks');
    }
};