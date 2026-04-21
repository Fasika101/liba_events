<?php

use App\Models\User;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Sold tickets must always keep a non-null agent_id. Reverts nullable agent_id
     * from 2026_03_10_081200_make_agent_id_nullable_on_tickets_table where applicable.
     */
    public function up(): void
    {
        if (DB::getDriverName() !== 'mysql') {
            return;
        }

        $nullCount = (int) DB::table('tickets')->whereNull('agent_id')->count();
        if ($nullCount > 0) {
            $fallbackId = env('TICKETS_NULL_AGENT_FALLBACK_ID');
            if ($fallbackId !== null && $fallbackId !== '' && User::query()
                ->whereKey((int) $fallbackId)
                ->where('role', 'agent')
                ->exists()) {
                DB::table('tickets')->whereNull('agent_id')->update(['agent_id' => (int) $fallbackId]);
            } elseif (app()->environment('local')) {
                $firstAgent = User::query()->where('role', 'agent')->orderBy('id')->value('id');
                if (! $firstAgent) {
                    throw new \RuntimeException(
                        "Cannot enforce NOT NULL: {$nullCount} ticket(s) have NULL agent_id and no agent user exists to assign."
                    );
                }
                DB::table('tickets')->whereNull('agent_id')->update(['agent_id' => $firstAgent]);
            } else {
                throw new \RuntimeException(
                    "Cannot enforce NOT NULL on tickets.agent_id: {$nullCount} row(s) have NULL agent_id. ".
                    'Set TICKETS_NULL_AGENT_FALLBACK_ID in .env to a valid agent user id (those tickets will be attributed to that agent), '.
                    'or update tickets.agent_id manually, then run migrate again.'
                );
            }
        }

        $fkName = $this->findAgentIdForeignKeyName();
        if ($fkName !== null) {
            DB::statement('ALTER TABLE tickets DROP FOREIGN KEY `'.$fkName.'`');
        }

        DB::statement('ALTER TABLE tickets MODIFY agent_id BIGINT UNSIGNED NOT NULL');

        // RESTRICT: deleting a user who sold tickets fails at DB level; app also blocks agent delete if tickets exist.
        DB::statement(
            'ALTER TABLE tickets ADD CONSTRAINT tickets_agent_id_foreign FOREIGN KEY (agent_id) REFERENCES users(id) ON DELETE RESTRICT'
        );
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (DB::getDriverName() !== 'mysql') {
            return;
        }

        $fkName = $this->findAgentIdForeignKeyName();
        if ($fkName !== null) {
            DB::statement('ALTER TABLE tickets DROP FOREIGN KEY `'.$fkName.'`');
        }

        DB::statement('ALTER TABLE tickets MODIFY agent_id BIGINT UNSIGNED NULL');

        DB::statement(
            'ALTER TABLE tickets ADD CONSTRAINT tickets_agent_id_foreign FOREIGN KEY (agent_id) REFERENCES users(id) ON DELETE SET NULL'
        );
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
