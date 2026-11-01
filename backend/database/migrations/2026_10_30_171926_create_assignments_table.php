<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('assignments', function (Blueprint $table) {
            $table->char('id', 26)->primary();
            $table->char('person_id', 26);
            $table->char('organization_unit_id', 26);
            $table->date('start_date');
            $table->date('end_date')->nullable();
            $table->string('role')->nullable();
            $table->timestamps();

            $table->foreign('person_id')->references('id')->on('people')->cascadeOnDelete();
            $table->foreign('organization_unit_id')->references('id')->on('organization_units')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('assignments');
    }
};