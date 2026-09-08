<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Existing accounts were created before email verification was enabled
        // and could never have verified their address; mark them verified so
        // enabling the feature does not lock anybody out. Only new
        // registrations go through the verification flow.
        DB::table('users')->whereNull('email_verified_at')->update([
            'email_verified_at' => now(),
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // The pre-migration verified state was never recorded, so there is
        // nothing safe to restore.
    }
};
