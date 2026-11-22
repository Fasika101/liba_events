<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('company_bank_accounts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->string('bank_name');
            $table->string('bank_code', 40)->nullable();
            $table->string('account_name');
            $table->string('account_number');
            $table->text('instructions')->nullable();
            $table->boolean('is_active')->default(true);
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();
        });

        Schema::create('attendees', function (Blueprint $table) {
            $table->id();
            $table->string('telegram_id')->nullable()->unique();
            $table->string('telegram_username')->nullable();
            $table->string('first_name')->nullable();
            $table->string('last_name')->nullable();
            $table->string('name')->nullable();
            $table->string('phone', 30)->nullable()->index();
            $table->string('locale', 5)->default('en');
            $table->timestamps();
        });

        Schema::create('ticket_orders', function (Blueprint $table) {
            $table->id();
            $table->string('public_code', 32)->unique();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('event_id')->constrained()->cascadeOnDelete();
            $table->foreignId('attendee_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('ticket_id')->nullable()->constrained()->nullOnDelete();
            $table->string('buyer_name');
            $table->string('buyer_email')->nullable();
            $table->string('buyer_phone', 30);
            $table->string('buyer_address')->nullable();
            $table->string('buyer_occupation', 150)->nullable();
            $table->boolean('yeneshaa_abat')->default(false);
            $table->decimal('amount', 12, 2);
            $table->string('currency', 10)->default('ETB');
            $table->string('status', 30)->default('pending')->index();
            $table->string('bank_code', 40)->nullable();
            $table->string('transaction_reference')->nullable()->index();
            $table->string('verify_et_request_id')->nullable();
            $table->string('verify_et_status')->nullable();
            $table->text('verify_et_payload')->nullable();
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('reviewed_at')->nullable();
            $table->text('admin_note')->nullable();
            $table->timestamps();
        });

        Schema::table('tickets', function (Blueprint $table) {
            $table->string('source', 20)->default('agent')->after('agent_id');
            $table->foreignId('attendee_id')->nullable()->after('source')->constrained()->nullOnDelete();
            $table->foreignId('ticket_order_id')->nullable()->after('attendee_id')->constrained()->nullOnDelete();
            $table->string('payment_reference')->nullable()->after('ticket_order_id');
        });

        // Allow self-purchases (Telegram / public) with no selling agent.
        if (DB::getDriverName() === 'mysql') {
            $fkName = $this->findAgentIdForeignKeyName();
            if ($fkName !== null) {
                DB::statement('ALTER TABLE tickets DROP FOREIGN KEY `'.$fkName.'`');
            }
            DB::statement('ALTER TABLE tickets MODIFY agent_id BIGINT UNSIGNED NULL');
            DB::statement(
                'ALTER TABLE tickets ADD CONSTRAINT tickets_agent_id_foreign FOREIGN KEY (agent_id) REFERENCES users(id) ON DELETE SET NULL'
            );
        } else {
            Schema::table('tickets', function (Blueprint $table) {
                $table->unsignedBigInteger('agent_id')->nullable()->change();
            });
        }
    }

    public function down(): void
    {
        Schema::table('tickets', function (Blueprint $table) {
            $table->dropConstrainedForeignId('ticket_order_id');
            $table->dropConstrainedForeignId('attendee_id');
            $table->dropColumn(['source', 'payment_reference']);
        });

        Schema::dropIfExists('ticket_orders');
        Schema::dropIfExists('attendees');
        Schema::dropIfExists('company_bank_accounts');
    }

    private function findAgentIdForeignKeyName(): ?string
    {
        $database = DB::getDatabaseName();
        $row = DB::selectOne(
            'SELECT CONSTRAINT_NAME AS name
             FROM information_schema.KEY_COLUMN_USAGE
             WHERE TABLE_SCHEMA = ?
               AND TABLE_NAME = ?
               AND COLUMN_NAME = ?
               AND REFERENCED_TABLE_NAME IS NOT NULL
             LIMIT 1',
            [$database, 'tickets', 'agent_id']
        );

        return $row->name ?? null;
    }
};
