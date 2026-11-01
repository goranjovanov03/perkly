<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('integration_configuration_versions', function (Blueprint $table) {
            $table->char('connection_id', 26);
            $table->unsignedInteger('version_number');
            $table->json('config');
            $table->char('created_by', 26);
            $table->timestamp('created_at')->useCurrent();
            $table->timestamp('updated_at')->useCurrent()->useCurrentOnUpdate();

            $table->primary(['connection_id', 'version_number']);
            $table->foreign('connection_id')->references('id')->on('integration_connections')->cascadeOnDelete();
            $table->foreign('created_by')->references('id')->on('users')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('integration_configuration_versions');
    }
};