<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('companies', function (Blueprint $table) {
            $table->string('logo_path')->nullable()->after('name');
        });

        // Map legacy placements onto Discover-only slots.
        DB::table('marketplace_ads')->where('placement', 'discover')->update(['placement' => 'discover_carousel']);
        DB::table('marketplace_ads')->whereIn('placement', ['organizers', 'footer'])->update(['placement' => 'discover_inline']);
    }

    public function down(): void
    {
        DB::table('marketplace_ads')->where('placement', 'discover_carousel')->update(['placement' => 'discover']);
        DB::table('marketplace_ads')->where('placement', 'discover_inline')->update(['placement' => 'footer']);

        Schema::table('companies', function (Blueprint $table) {
            $table->dropColumn('logo_path');
        });
    }
};
