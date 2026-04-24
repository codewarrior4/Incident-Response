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
        Schema::create('incidents', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('title');
            $table->text('message');
            $table->string('service')->index();
            $table->enum('severity', ['low', 'medium', 'high', 'critical'])->index();
            $table->string('hash', 64)->unique();
            $table->enum('status', ['open', 'investigating', 'resolved'])->index()->default('open');
            $table->timestamps();
            
            $table->index('created_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('incidents');
    }
};
