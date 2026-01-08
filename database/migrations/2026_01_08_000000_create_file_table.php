<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('file', function (Blueprint $table) {
            $table->id();
            $table->string('language')->default('en');
            $table->string('type')->default('all');
            $table->unsignedTinyInteger('month');
            $table->unsignedTinyInteger('day');
            $table->longText('day_info');
            $table->timestamp('fetched_at');
            $table->timestamps();

            $table->unique(['language', 'type', 'month', 'day']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('file');
    }
};
