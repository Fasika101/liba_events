<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('organization_registration_requests', function (Blueprint $table) {
            $table->id();
            $table->string('first_name');
            $table->string('last_name');
            $table->string('address');
            $table->string('phone', 20);
            $table->string('organization_name');
            $table->string('organization_address');
            $table->string('organization_phone', 20);
            $table->string('status')->default('pending');
            $table->timestamp('phone_verified_at')->nullable();
            $table->timestamp('reviewed_at')->nullable();
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->text('rejection_reason')->nullable();
            $table->timestamps();

            $table->index('status');
            $table->index('organization_name');
            $table->index('organization_phone');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('organization_registration_requests');
    }
};
