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
        Schema::create('mayan_calendar_data', function (Blueprint $table) {
            $table->id();
            $table->enum('calendar_type', ['haab', 'tzolkin', 'long_count'])->notNullable();
            $table->integer('position')->notNullable();
            $table->string('name_maya', 50)->notNullable();
            $table->string('name_english', 50)->notNullable();
            $table->text('cultural_significance')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('mayan_calendar_data');
    }
};
