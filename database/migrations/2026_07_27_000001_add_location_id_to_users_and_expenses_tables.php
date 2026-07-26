<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('users') && !Schema::hasColumn('users', 'location_id')) {
            Schema::table('users', function (Blueprint $table) {
                $table->unsignedBigInteger('location_id')->nullable()->after('business_id');
                $table->foreign('location_id')->references('id')->on('locations')->onDelete('set null');
            });
        }

        if (Schema::hasTable('expenses') && !Schema::hasColumn('expenses', 'location_id')) {
            Schema::table('expenses', function (Blueprint $table) {
                $table->unsignedBigInteger('location_id')->nullable()->after('business_id');
                $table->foreign('location_id')->references('id')->on('locations')->onDelete('set null');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('users') && Schema::hasColumn('users', 'location_id')) {
            Schema::table('users', function (Blueprint $table) {
                $table->dropForeign(['location_id']);
                $table->dropColumn('location_id');
            });
        }

        if (Schema::hasTable('expenses') && Schema::hasColumn('expenses', 'location_id')) {
            Schema::table('expenses', function (Blueprint $table) {
                $table->dropForeign(['location_id']);
                $table->dropColumn('location_id');
            });
        }
    }
};
