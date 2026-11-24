<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('marketplace_ads', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->string('body', 500)->nullable();
            $table->string('image_path')->nullable();
            $table->string('link_url', 500)->nullable();
            $table->string('placement', 40)->default('discover')->index();
            $table->boolean('is_active')->default(true)->index();
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamp('starts_at')->nullable();
            $table->timestamp('ends_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('marketplace_ads');
    }
};
