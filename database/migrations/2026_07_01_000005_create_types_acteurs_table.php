<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('types_acteurs', function (Blueprint $table) {
            $table->id('id_type_acteur');
            $table->string('code_type_acteur', 3);
            $table->string('lib_type_acteur', 60);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('types_acteurs');
    }
};
