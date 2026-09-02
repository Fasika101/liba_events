<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('companies', function (Blueprint $table) {
            $table->text('sms_api_key')->nullable()->after('premium_enabled_at');
            $table->string('sms_sender_id', 11)->nullable()->after('sms_api_key');
        });

        Schema::create('company_sender_id_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->string('requested_sender_id', 11);
            $table->string('status')->default('pending');
            $table->decimal('price_etb', 12, 2)->nullable();
            $table->text('payment_instructions')->nullable();
            $table->string('payment_screenshot_path')->nullable();
            $table->foreignId('processed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('processed_at')->nullable();
            $table->text('admin_notes')->nullable();
            $table->timestamps();
        });

        Schema::create('company_bulk_sms_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('sent_by')->constrained('users')->cascadeOnDelete();
            $table->string('title')->nullable();
            $table->text('message');
            $table->unsignedInteger('recipient_count')->default(0);
            $table->unsignedInteger('success_count')->default(0);
            $table->unsignedInteger('failed_count')->default(0);
            $table->unsignedInteger('skipped_count')->default(0);
            $table->timestamps();
        });

        Schema::create('company_bulk_sms_log_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_bulk_sms_log_id')->constrained()->cascadeOnDelete();
            $table->string('phone', 20);
            $table->string('recipient_name')->nullable();
            $table->foreignId('ticket_id')->nullable()->constrained()->nullOnDelete();
            $table->string('status');
            $table->text('error_message')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('company_bulk_sms_log_items');
        Schema::dropIfExists('company_bulk_sms_logs');
        Schema::dropIfExists('company_sender_id_requests');
        Schema::table('companies', function (Blueprint $table) {
            $table->dropColumn(['sms_api_key', 'sms_sender_id']);
        });
    }
};
