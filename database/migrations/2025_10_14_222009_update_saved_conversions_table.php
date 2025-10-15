<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('saved_conversions', function (Blueprint $table) {
            // Add the new JSON column to store all conversion data
            $table->json('conversion_data')->nullable()->after('id');
        });

        // Migrate existing data to the new JSON format
        DB::table('saved_conversions')->get()->each(function ($conversion) {
            $conversionData = [
                'gregorian' => [
                    'full_date' => $conversion->gregorian_date,
                    'name' => 'Gregorian',
                    'tags' => ['modern'],
                ],
                'jalali' => [
                    'full_date' => $conversion->jalali_date,
                    'name' => 'Jalali (Persian)',
                    'tags' => ['modern'],
                ],
                'unix' => $conversion->unix_timestamp,
                'iso8601' => $conversion->iso8601_string,
            ];

            DB::table('saved_conversions')
                ->where('id', $conversion->id)
                ->update(['conversion_data' => $conversionData]);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Remove the conversion_data column
        Schema::table('saved_conversions', function (Blueprint $table) {
            $table->dropColumn('conversion_data');
        });
    }
};
