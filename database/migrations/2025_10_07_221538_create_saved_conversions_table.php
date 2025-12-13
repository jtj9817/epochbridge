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
        Schema::create('saved_conversions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('original_input');
            // Legacy fields (kept for backward compatibility).
            $table->string('gregorian_date')->nullable();
            $table->string('jalali_date')->nullable();
            $table->bigInteger('unix_timestamp')->nullable();
            $table->string('iso8601_string')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('saved_conversions');
    }
};
