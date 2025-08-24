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
        Schema::create('uploaded_files', function (Blueprint $table) {
            $table->id();
            $table->foreignId('file_share_id')->constrained()->onDelete('cascade');
            $table->string('original_name'); // Nome original do arquivo
            $table->string('stored_name'); // Nome do arquivo no storage
            $table->string('extension'); // Extensão do arquivo
            $table->unsignedBigInteger('size'); // Tamanho em bytes
            $table->string('mime_type'); // Tipo MIME
            $table->integer('duration_seconds'); // Duração em segundos
            $table->timestamp('expires_at'); // Data de expiração
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('uploaded_files');
    }
};
