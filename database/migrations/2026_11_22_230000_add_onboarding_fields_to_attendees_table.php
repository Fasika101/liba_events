<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('attendees', function (Blueprint $table) {
            $table->string('email')->nullable()->after('phone');
            $table->boolean('notifications_enabled')->default(true)->after('locale');
            $table->timestamp('channel_joined_at')->nullable()->after('notifications_enabled');
            $table->timestamp('profile_completed_at')->nullable()->after('channel_joined_at');
        });
    }

    public function down(): void
    {
        Schema::table('attendees', function (Blueprint $table) {
            $table->dropColumn([
                'email',
                'notifications_enabled',
                'channel_joined_at',
                'profile_completed_at',
            ]);
        });
    }
};
