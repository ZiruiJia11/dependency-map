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
        Schema::create('dependencies', function (Blueprint $table) {
            $table->id();
            // All three foreign keys cascade so graph edges never become orphaned.
            $table->foreignId('project_id')->constrained()->cascadeOnDelete();
            $table->foreignId('source_node_id')->constrained('nodes')->cascadeOnDelete();
            $table->foreignId('target_node_id')->constrained('nodes')->cascadeOnDelete();
            $table->text('description')->nullable();
            $table->timestamps();

            // These indexes support outgoing and incoming edge lookups per project.
            $table->index(['project_id', 'source_node_id']);
            $table->index(['project_id', 'target_node_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('dependencies');
    }
};
