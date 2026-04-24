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
        Schema::create('incident_occurrences', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('incident_id')->constrained()->onDelete('cascade');
            $table->json('context')->nullable();
            $table->timestamps();
            
            $table->index('incident_id');
            $table->index('created_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('incident_occurrences');
    }
};
