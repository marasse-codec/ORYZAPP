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
        Schema::create('campagnes', function (Blueprint $table) {
            $table->id('id_campagne');
            $table->string('lib_campagne',30);
            $table->date('mois_debut_campagne');
            $table->date('mois_fin_campagne');

            $table->timestamps();
        });

        // ================= Schéma v2 (clôture) =================

        Schema::table('campagnes', function (Blueprint $table) {
            // Clôture : une campagne clôturée est figée (plus de demande ni d'attribution).
            $table->dateTime('cloturee_le')->nullable();
            $table->foreignId('id_user_cloture')->nullable()->constrained('users', 'id')->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('campagnes');
    }
};
