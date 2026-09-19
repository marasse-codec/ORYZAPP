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
        Schema::create('resource_subscriptions', function (Blueprint $table) {
        $table->id();
        $table->foreignId('user_id')->constrained()->cascadeOnDelete();
        $table->morphs('subscribable'); // Relation vers Appui, Service, etc.
        $table->string('event_type')->default('all'); // Ex: 'seuil_stock', 'attribution'
        $table->timestamps();

        $table->unique(['user_id', 'subscribable_type', 'subscribable_id', 'event_type'], 'user_sub_unique');
    });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('resource_publications');
    }
};
