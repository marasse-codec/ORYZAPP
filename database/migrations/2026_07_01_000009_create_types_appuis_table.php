<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('types_appuis', function (Blueprint $table) {
            $table->id('id_type_appui');
            $table->string('lib_type_appui', 100);
            $table->boolean('est_intrant')->default(false);
            // Le service qui offre le type d'appui : c'est par lui qu'une
            // demande trouve ses instructeurs, sans table de routage.
            $table->foreignId('id_service')->constrained('services', 'id_service')->cascadeOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('types_appuis');
    }
};
