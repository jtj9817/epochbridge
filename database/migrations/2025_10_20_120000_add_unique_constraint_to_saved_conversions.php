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
        // Remove duplicate conversions before enforcing uniqueness
        $duplicates = DB::table('saved_conversions')
            ->select('user_id', 'original_input', DB::raw('MIN(id) as keep_id'))
            ->groupBy('user_id', 'original_input')
            ->havingRaw('COUNT(*) > 1')
            ->get();

        foreach ($duplicates as $duplicate) {
            DB::table('saved_conversions')
                ->where('user_id', $duplicate->user_id)
                ->where('original_input', $duplicate->original_input)
                ->where('id', '!=', $duplicate->keep_id)
                ->delete();
        }

        Schema::table('saved_conversions', function (Blueprint $table) {
            $table->unique(['user_id', 'original_input'], 'saved_conversions_user_original_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('saved_conversions', function (Blueprint $table) {
            $table->dropUnique('saved_conversions_user_original_unique');
        });
    }
};
