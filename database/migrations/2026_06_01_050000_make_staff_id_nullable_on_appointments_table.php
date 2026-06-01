<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('appointments', function (Blueprint $table): void {
            $table->dropForeign(['staff_id']);
            $table->foreignId('staff_id')->nullable()->change();
            $table->foreign('staff_id')->references('id')->on('users')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('appointments', function (Blueprint $table): void {
            $table->dropForeign(['staff_id']);
            $table->foreignId('staff_id')->nullable(false)->change();
            $table->foreign('staff_id')->references('id')->on('users')->cascadeOnDelete();
        });
    }
};

