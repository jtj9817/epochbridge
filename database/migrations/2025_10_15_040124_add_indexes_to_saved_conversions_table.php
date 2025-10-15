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
        Schema::table('saved_conversions', function (Blueprint $table) {
            // Add composite index for user queries with ordering
            $table->index(['user_id', 'created_at'], 'saved_conversions_user_created_at_index');

            // Add index for user_id alone (useful for count queries)
            $table->index('user_id', 'saved_conversions_user_id_index');

            // Add index for created_at alone (useful for cleanup queries)
            $table->index('created_at', 'saved_conversions_created_at_index');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('saved_conversions', function (Blueprint $table) {
            $table->dropIndex('saved_conversions_user_created_at_index');
            $table->dropIndex('saved_conversions_user_id_index');
            $table->dropIndex('saved_conversions_created_at_index');
        });
    }
};
