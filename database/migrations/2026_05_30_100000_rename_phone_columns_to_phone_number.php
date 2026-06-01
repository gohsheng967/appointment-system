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
        if (Schema::hasTable('branches') && Schema::hasColumn('branches', 'phone') && ! Schema::hasColumn('branches', 'phone_number')) {
            Schema::table('branches', function (Blueprint $table): void {
                $table->renameColumn('phone', 'phone_number');
            });
        }

        if (Schema::hasTable('customers') && Schema::hasColumn('customers', 'phone') && ! Schema::hasColumn('customers', 'phone_number')) {
            Schema::table('customers', function (Blueprint $table): void {
                $table->renameColumn('phone', 'phone_number');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('branches') && Schema::hasColumn('branches', 'phone_number') && ! Schema::hasColumn('branches', 'phone')) {
            Schema::table('branches', function (Blueprint $table): void {
                $table->renameColumn('phone_number', 'phone');
            });
        }

        if (Schema::hasTable('customers') && Schema::hasColumn('customers', 'phone_number') && ! Schema::hasColumn('customers', 'phone')) {
            Schema::table('customers', function (Blueprint $table): void {
                $table->renameColumn('phone_number', 'phone');
            });
        }
    }
};
