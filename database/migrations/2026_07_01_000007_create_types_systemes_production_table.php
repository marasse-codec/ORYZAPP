<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('types_systemes_production', function (Blueprint $table) {
            $table->id('id_type_systeme_production');
            $table->string('lib_type_systeme_production', 60);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('types_systemes_production');
    }
};
