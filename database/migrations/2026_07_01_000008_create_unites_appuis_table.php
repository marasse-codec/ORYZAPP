<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('unites_appuis', function (Blueprint $table) {
            $table->id('id_unite_appui');
            $table->string('lib_unite_appui', 60);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('unites_appuis');
    }
};
