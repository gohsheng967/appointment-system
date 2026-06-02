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
        $hasDuplicateEmailOnly = DB::table('customers')
            ->select('email')
            ->whereNotNull('email')
            ->whereNull('phone_number')
            ->groupBy('email')
            ->havingRaw('COUNT(*) > 1')
            ->exists();

        if ($hasDuplicateEmailOnly) {
            throw new \RuntimeException(
                'Cannot apply nullable identity unique constraints: duplicate email-only customers exist.',
            );
        }

        $hasDuplicatePhoneOnly = DB::table('customers')
            ->select('phone_number')
            ->whereNull('email')
            ->whereNotNull('phone_number')
            ->groupBy('phone_number')
            ->havingRaw('COUNT(*) > 1')
            ->exists();

        if ($hasDuplicatePhoneOnly) {
            throw new \RuntimeException(
                'Cannot apply nullable identity unique constraints: duplicate phone-only customers exist.',
            );
        }

        Schema::table('customers', function (Blueprint $table): void {
            $table
                ->string('email_only_identity')
                ->nullable()
                ->storedAs("CASE WHEN `phone_number` IS NULL THEN `email` ELSE NULL END");

            $table
                ->string('phone_only_identity')
                ->nullable()
                ->storedAs("CASE WHEN `email` IS NULL THEN `phone_number` ELSE NULL END");

            $table->unique('email_only_identity', 'customers_email_only_identity_unique');
            $table->unique('phone_only_identity', 'customers_phone_only_identity_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('customers', function (Blueprint $table): void {
            $table->dropUnique('customers_email_only_identity_unique');
            $table->dropUnique('customers_phone_only_identity_unique');
            $table->dropColumn('email_only_identity');
            $table->dropColumn('phone_only_identity');
        });
    }
};
