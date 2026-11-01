<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('organization_units', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('organization_id')->constrained('organizations')->cascadeOnDelete();
            $table->ulid('parent_id')->nullable();
            $table->string('name');
            $table->string('slug');
            $table->timestamps();
        });

        Schema::table('organization_units', function (Blueprint $table) {
            $table->foreign('parent_id')->references('id')->on('organization_units')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('organization_units');
    }
};
