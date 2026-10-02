<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('localites', function (Blueprint $table) {
            $table->id('id_localite');
            $table->string('lib_localite', 30);
            $table->foreignId('id_departement')->constrained('departements', 'id_departement');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('localites');
    }
};
