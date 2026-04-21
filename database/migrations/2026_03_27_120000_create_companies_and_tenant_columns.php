<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('companies', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->timestamps();
        });

        $now = now();
        DB::table('companies')->insert([
            'name'       => 'Default organization',
            'created_at' => $now,
            'updated_at' => $now,
        ]);
        $companyId = (int) DB::table('companies')->orderByDesc('id')->value('id');

        Schema::table('users', function (Blueprint $table) {
            $table->foreignId('company_id')->nullable()->after('id')->constrained()->nullOnDelete();
        });

        Schema::table('events', function (Blueprint $table) {
            $table->foreignId('company_id')->nullable()->after('id')->constrained()->cascadeOnDelete();
        });

        DB::table('users')->whereNull('company_id')->update(['company_id' => $companyId]);
        DB::table('events')->whereNull('company_id')->update(['company_id' => $companyId]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('events', function (Blueprint $table) {
            $table->dropForeign(['company_id']);
            $table->dropColumn('company_id');
        });

        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['company_id']);
            $table->dropColumn('company_id');
        });

        Schema::dropIfExists('companies');
    }
};
