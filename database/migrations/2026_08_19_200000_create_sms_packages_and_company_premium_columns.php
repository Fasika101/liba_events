<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sms_packages', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->unsignedInteger('sms_count');
            $table->decimal('price_etb', 12, 2);
            $table->decimal('price_per_sms', 8, 4);
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();
        });

        Schema::table('companies', function (Blueprint $table) {
            $table->string('plan')->default('standard')->after('is_suspended');
            $table->unsignedInteger('sms_credits')->default(0)->after('plan');
            $table->decimal('wallet_balance_etb', 12, 2)->default(0)->after('sms_credits');
            $table->boolean('auto_sms_on_sale')->default(false)->after('wallet_balance_etb');
            $table->text('ticket_sms_template')->nullable()->after('auto_sms_on_sale');
            $table->timestamp('premium_requested_at')->nullable()->after('ticket_sms_template');
            $table->timestamp('premium_enabled_at')->nullable()->after('premium_requested_at');
        });

        Schema::create('company_wallet_transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->string('type');
            $table->decimal('amount_etb', 12, 2)->default(0);
            $table->integer('sms_credits')->default(0);
            $table->unsignedInteger('sms_credits_after')->default(0);
            $table->decimal('wallet_balance_etb_after', 12, 2)->default(0);
            $table->string('description');
            $table->foreignId('sms_package_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->nullableMorphs('reference');
            $table->timestamps();
        });

        Schema::create('company_sms_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('ticket_id')->nullable()->constrained()->nullOnDelete();
            $table->string('phone', 20);
            $table->text('message');
            $table->string('purpose');
            $table->string('status');
            $table->decimal('cost_etb', 8, 4)->default(0);
            $table->unsignedTinyInteger('credits_used')->default(1);
            $table->text('error_message')->nullable();
            $table->foreignId('sent_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('company_sms_purchase_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('sms_package_id')->constrained()->cascadeOnDelete();
            $table->string('status')->default('pending');
            $table->foreignId('requested_by')->constrained('users')->cascadeOnDelete();
            $table->foreignId('processed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('processed_at')->nullable();
            $table->timestamps();
        });

        DB::table('sms_packages')->insert([
            'name' => 'Starter — 1,000 SMS',
            'sms_count' => 1000,
            'price_etb' => 1000,
            'price_per_sms' => 1,
            'description' => '1,000 SMS credits at 1 ETB per message',
            'is_active' => true,
            'sort_order' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('company_sms_purchase_requests');
        Schema::dropIfExists('company_sms_logs');
        Schema::dropIfExists('company_wallet_transactions');
        Schema::table('companies', function (Blueprint $table) {
            $table->dropColumn([
                'plan',
                'sms_credits',
                'wallet_balance_etb',
                'auto_sms_on_sale',
                'ticket_sms_template',
                'premium_requested_at',
                'premium_enabled_at',
            ]);
        });
        Schema::dropIfExists('sms_packages');
    }
};
