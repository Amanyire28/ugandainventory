<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sales', function (Blueprint $table) {
            $table->string('offline_uuid', 36)->nullable()->after('id');
            $table->string('device_id', 128)->nullable()->after('offline_uuid');
            $table->boolean('is_offline_sale')->default(false)->after('device_id');
            $table->string('sync_status', 20)->default('synced')->after('is_offline_sale');
            $table->timestamp('synced_at')->nullable()->after('sync_status');

            $table->index('offline_uuid');
            $table->index('device_id');
            $table->index('sync_status');
        });

        Schema::table('customers', function (Blueprint $table) {
            $table->string('offline_uuid', 36)->nullable()->after('id');
            $table->string('device_id', 128)->nullable()->after('offline_uuid');

            $table->index('offline_uuid');
        });
    }

    public function down(): void
    {
        Schema::table('sales', function (Blueprint $table) {
            $table->dropIndex(['sales_offline_uuid_index']);
            $table->dropIndex(['sales_device_id_index']);
            $table->dropIndex(['sales_sync_status_index']);
            $table->dropColumn(['offline_uuid', 'device_id', 'is_offline_sale', 'sync_status', 'synced_at']);
        });

        Schema::table('customers', function (Blueprint $table) {
            $table->dropIndex(['customers_offline_uuid_index']);
            $table->dropColumn(['offline_uuid', 'device_id']);
        });
    }
};
