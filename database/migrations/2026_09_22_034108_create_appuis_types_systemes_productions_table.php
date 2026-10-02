<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('appuis_types_systemes_productions', function (Blueprint $table) {
            $table->id();
            
            // Clé étrangère vers 'appuis' avec un nom de contrainte court (fk_atsp_appui)
            $table->foreignId('id_appui')
                  ->constrained(
                      table: 'appuis',
                      column: 'id_appui',
                      indexName: 'fk_atsp_appui'
                  )
                  ->cascadeOnDelete();

            // Clé étrangère vers 'types_systemes_productions' avec un nom de contrainte court (fk_atsp_sys_prod)
            $table->foreignId('id_type_systeme_production')
                  ->constrained(
                      table: 'types_systemes_productions',
                      column: 'id_type_systeme_production',
                      indexName: 'fk_atsp_sys_prod'
                  )
                  ->cascadeOnDelete();

            // Index unique (uq_atsp_appui_sys)
            $table->unique(
                ['id_appui', 'id_type_systeme_production'],
                'uq_atsp_appui_sys'
            );

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('appuis_types_systemes_productions');
    }
};