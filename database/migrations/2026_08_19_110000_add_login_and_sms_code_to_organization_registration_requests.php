<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('organization_registration_requests', function (Blueprint $table) {
            $table->string('admin_email')->nullable()->after('organization_phone');
            $table->string('password')->nullable()->after('admin_email');
            $table->string('sms_verification_code', 10)->nullable()->after('password');
        });
    }

    public function down(): void
    {
        Schema::table('organization_registration_requests', function (Blueprint $table) {
            $table->dropColumn(['admin_email', 'password', 'sms_verification_code']);
        });
    }
};
