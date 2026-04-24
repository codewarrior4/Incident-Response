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
        Schema::create('incident_analyses', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('incident_id')->constrained()->onDelete('cascade');
            $table->text('root_cause');
            $table->text('suggested_fix');
            $table->integer('confidence_score');
            $table->boolean('ai_generated')->default(false);
            $table->timestamps();
            
            $table->index('incident_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('incident_analyses');
    }
};
