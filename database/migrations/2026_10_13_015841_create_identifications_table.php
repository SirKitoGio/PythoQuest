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
        Schema::create('identifications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('image_path');
            $table->string('plant_part');
            $table->string('status')->default('queued');
            $table->string('model_version')->nullable();
            $table->json('candidates')->nullable();
            $table->foreignId('top_plant_id')->nullable()->constrained('plants')->nullOnDelete();
            $table->decimal('top_confidence', 5, 4)->nullable();
            $table->boolean('needs_more_images')->default(false);
            $table->unsignedInteger('latency_ms')->nullable();
            $table->text('error')->nullable();
            $table->timestamp('escalated_at')->nullable();
            $table->foreignId('confirmed_plant_id')->nullable()->constrained('plants')->nullOnDelete();
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('reviewed_at')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'created_at']);
            $table->index('escalated_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('identifications');
    }
};
