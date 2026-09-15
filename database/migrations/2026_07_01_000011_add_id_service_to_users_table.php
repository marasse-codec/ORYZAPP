<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // Rattachement d'un agent à son service : c'est lui qui délimite
            // les demandes qu'il a à instruire.
            $table->foreignId('id_service')->nullable()->after('email')
                ->constrained('services', 'id_service')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropConstrainedForeignId('id_service');
        });
    }
};
