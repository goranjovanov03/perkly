<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('integration_secrets', function (Blueprint $table) {
            $table->char('connection_id', 26);
            $table->string('key');
            $table->text('ciphertext');
            $table->timestamp('created_at')->useCurrent();
            $table->timestamp('updated_at')->useCurrent()->useCurrentOnUpdate();

            $table->primary(['connection_id', 'key']);
            $table->foreign('connection_id')->references('id')->on('integration_connections')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('integration_secrets');
    }
};