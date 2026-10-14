<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // La superficie que la commission peut traiter, par écologie de la demande.
        // Noms de contraintes explicites : ceux générés par Laravel dépassent les 64 caractères de MySQL.
        Schema::create('attributions_systemes_productions', function (Blueprint $table) {
            $table->id('id_attribution_systeme_production');
            $table->unsignedBigInteger('id_attribution');
            $table->unsignedBigInteger('id_type_systeme_production');
            $table->decimal('superficie_traitee', 10, 2);
            $table->timestamps();

            $table->foreign('id_attribution', 'attr_sys_prod_attribution_fk')
                ->references('id_attribution')->on('attributions')->cascadeOnDelete();
            $table->foreign('id_type_systeme_production', 'attr_sys_prod_type_fk')
                ->references('id_type_systeme_production')->on('types_systemes_productions');
            $table->unique(['id_attribution', 'id_type_systeme_production'], 'attr_sys_prod_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('attributions_systemes_productions');
    }
};
