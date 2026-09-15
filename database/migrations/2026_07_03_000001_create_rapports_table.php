<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // L'instruction d'une demande par un service. Tant qu'accord_service
        // est nul, le service n'a pas autorisé le passage en commission.
        Schema::create('rapports', function (Blueprint $table) {
            $table->id('id_rapport');
            $table->foreignId('id_demande')->constrained('demandes', 'id_demande')->cascadeOnDelete();
            $table->foreignId('id_service')->constrained('services', 'id_service')->cascadeOnDelete();
            $table->text('commentaire')->nullable();
            $table->date('date_rapport');
            $table->date('accord_service')->nullable();
            $table->timestamps();

            $table->unique(['id_demande', 'id_service']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('rapports');
    }
};
