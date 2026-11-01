<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('integration_providers', function (Blueprint $table) {
            $table->string('key')->primary();
            $table->string('service_type');
            $table->string('name');
            $table->timestamps();

            $table->foreign('service_type')->references('key')->on('integration_service_types')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('integration_providers');
    }
};