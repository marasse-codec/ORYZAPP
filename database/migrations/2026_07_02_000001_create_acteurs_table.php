<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('acteurs', function (Blueprint $table) {
            $table->id('id_acteur');
            $table->string('nom',60);
            $table->string('telephone',10)->unique()->nullable();
            $table->date('date_naissance')->nullable();
            $table->string('email',160)->unique()->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('acteurs');
    }
};
