<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sessions_commissions', function (Blueprint $table) {
            $table->id('id_session');
            $table->text('resume')->nullable();
            // Une seule séance ouverte par jour : la commission se tient
            // par quinzaine, deux séances le même jour n'ont pas de sens.
            $table->date('date_debut_session');
            $table->date('date_fin_session')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sessions_commissions');
    }
};
