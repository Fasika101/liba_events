<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (DB::getDriverName() !== 'mysql') {
            Schema::table('ticket_orders', function ($table) {
                $table->text('transaction_reference')->nullable()->change();
            });

            return;
        }

        $indexName = $this->findIndexName('ticket_orders', 'transaction_reference');
        if ($indexName) {
            DB::statement('ALTER TABLE `ticket_orders` DROP INDEX `'.$indexName.'`');
        }

        DB::statement('ALTER TABLE `ticket_orders` MODIFY `transaction_reference` TEXT NULL');
    }

    public function down(): void
    {
        if (DB::getDriverName() !== 'mysql') {
            Schema::table('ticket_orders', function ($table) {
                $table->string('transaction_reference')->nullable()->change();
            });

            return;
        }

        DB::statement('ALTER TABLE `ticket_orders` MODIFY `transaction_reference` VARCHAR(255) NULL');
        DB::statement('ALTER TABLE `ticket_orders` ADD INDEX `ticket_orders_transaction_reference_index` (`transaction_reference`(191))');
    }

    private function findIndexName(string $table, string $column): ?string
    {
        $database = DB::getDatabaseName();
        $row = DB::selectOne(
            'SELECT INDEX_NAME AS name
             FROM information_schema.STATISTICS
             WHERE TABLE_SCHEMA = ?
               AND TABLE_NAME = ?
               AND COLUMN_NAME = ?
               AND INDEX_NAME != "PRIMARY"
             LIMIT 1',
            [$database, $table, $column]
        );

        return $row->name ?? null;
    }
};
