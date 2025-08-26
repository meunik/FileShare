<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('file_shares', function (Blueprint $table) {
            $table->string('password_hash')->nullable(); // Hash da senha
            $table->integer('duration_seconds')->nullable(); // Duração em segundos
            $table->timestamp('expires_at')->nullable(); // Data de expiração da página
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('file_shares', function (Blueprint $table) {
            $table->dropColumn(['password_hash', 'duration_seconds', 'expires_at']);
        });
    }
};
