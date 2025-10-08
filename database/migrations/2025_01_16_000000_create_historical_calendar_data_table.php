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
        Schema::create('historical_calendar_data', function (Blueprint $table) {
            $table->id();
            $table->string('calendar_system', 50)->notNullable();
            $table->string('data_type', 50)->notNullable();
            $table->string('key_name', 100)->notNullable();
            $table->text('value')->notNullable();
            $table->text('cultural_context')->nullable();
            $table->text('source_reference')->nullable();
            $table->timestamps();

            $table->index(['calendar_system', 'data_type']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('historical_calendar_data');
    }
};
