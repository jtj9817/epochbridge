<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Carbon;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (! Schema::hasColumn('saved_conversions', 'conversion_data')) {
            Schema::table('saved_conversions', function (Blueprint $table) {
                // Add the new JSON column to store all conversion data
                $table->json('conversion_data')->nullable()->after('id');
            });
        }

        // Migrate existing data to the new JSON format (only for legacy rows)
        DB::table('saved_conversions')->whereNull('conversion_data')->get()->each(function ($conversion) {
            $gregorianFullDate = null;

            if ($conversion->gregorian_date !== null) {
                try {
                    $gregorianFullDate = Carbon::parse($conversion->gregorian_date)->format('Y-m-d');
                } catch (\Throwable $e) {
                    $gregorianFullDate = $conversion->gregorian_date;
                }
            }

            $conversionData = [
                'gregorian' => [
                    'full_date' => $gregorianFullDate,
                    'name' => 'Gregorian',
                    'tags' => ['modern', 'solar'],
                ],
                'jalali' => [
                    'full_date' => $conversion->jalali_date,
                    'name' => 'Jalali',
                    'tags' => ['modern', 'solar'],
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
