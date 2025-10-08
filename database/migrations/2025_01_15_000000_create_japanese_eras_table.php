<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('japanese_eras', function (Blueprint $table) {
            $table->id();
            $table->string('name_japanese', 50)->notNullable();
            $table->string('name_romanized', 50)->notNullable();
            $table->string('name_english', 100)->notNullable();
            $table->date('start_date')->notNullable();
            $table->date('end_date')->nullable();
            $table->text('historical_context')->nullable();
            $table->timestamps();
            
            $table->index(['start_date', 'end_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('japanese_eras');
    }
};
