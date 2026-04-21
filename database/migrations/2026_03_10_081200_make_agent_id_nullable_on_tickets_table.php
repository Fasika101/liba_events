<?php

use Illuminate\Database\Migrations\Migration;

/**
 * Originally made agent_id nullable for agent delete + unlink tickets.
 * Superseded: every sold ticket must keep its selling agent (agent_id NOT NULL).
 * Use 2026_03_25_100000_restore_tickets_agent_id_not_null if the DB still has nullable agent_id.
 */
return new class extends Migration
{
    public function up(): void
    {
        //
    }

    public function down(): void
    {
        //
    }
};
