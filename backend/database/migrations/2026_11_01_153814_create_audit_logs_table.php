<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('audit_logs', function (Blueprint $table) {
            $table->char('id', 26)->primary();
            $table->char('actor_id', 26)->nullable()->index();
            $table->char('organization_id', 26)->nullable()->index();
            $table->string('action');
            $table->string('resource_type')->nullable();
            $table->char('resource_id', 26)->nullable()->index();
            $table->json('metadata')->nullable();
            $table->char('request_id', 36)->index();
            $table->timestamp('created_at')->useCurrent();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('audit_logs');
    }
};