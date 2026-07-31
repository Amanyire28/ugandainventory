<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pwa_devices', function (Blueprint $table) {
            $table->id();
            $table->string('device_uuid', 128)->unique();
            $table->string('device_name');
            $table->unsignedBigInteger('business_id');
            $table->unsignedBigInteger('user_id');
            $table->string('app_version');
            $table->timestamp('last_online_at')->nullable();
            $table->timestamp('last_sync_at')->nullable();
            $table->enum('status', ['online', 'offline', 'syncing'])->default('online');
            $table->timestamps();

            $table->foreign('business_id')
                  ->references('id')
                  ->on('businesses')
                  ->onDelete('cascade');

            $table->foreign('user_id')
                  ->references('id')
                  ->on('users')
                  ->onDelete('cascade');

            $table->index('business_id');
            $table->index('user_id');
            $table->index('device_uuid');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pwa_devices');
    }
};
