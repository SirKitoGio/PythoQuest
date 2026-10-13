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
        Schema::create('medicinal_actions', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->string('slug')->unique();
            $table->timestamps();
        });

        Schema::create('medicinal_action_plant', function (Blueprint $table) {
            $table->foreignId('plant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('medicinal_action_id')->constrained()->cascadeOnDelete();
            $table->string('evidence_level')->default('traditional use');
            $table->string('source_provider')->nullable();

            $table->primary(['plant_id', 'medicinal_action_id']);
            $table->index('medicinal_action_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('medicinal_action_plant');
        Schema::dropIfExists('medicinal_actions');
    }
};
