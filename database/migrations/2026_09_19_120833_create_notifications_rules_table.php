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
        Schema::create('notifications_rules', function (Blueprint $table) {
            $table->id();
            $table->string('name');                         // Ex: "Stock critique Appui"
            $table->string('resource_type');                // Ex: "App\Domain\Stock\Models\Appui"
            $table->string('event');                        // "updated" ou "created"
            $table->string('field')->nullable();            // Ex: "quantite"
            $table->string('operator')->nullable();         // "<", ">", "=", "changed"
            $table->string('value')->nullable();            // Ex: "5"
            $table->string('title_template');               // Ex: "⚠️ Alerte Stock : {libelle}"
            $table->text('message_template');               // Ex: "Quantité restante : {quantite}."
            $table->boolean('is_active')->default(true);
            $table->string('recipient_type'); // 'subscribers', 'roles', 'users', 'relation'
            $table->json('recipient_targets')->nullable(); // Contient les IDs des rôles, des users ou le nom de la relation
            $table->json('channels')->nullable(); // Contient les IDs des rôles, des users ou le nom de la relation
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('notifications_rules');
    }
};
