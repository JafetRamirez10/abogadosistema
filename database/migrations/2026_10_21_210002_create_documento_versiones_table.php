<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('documento_versiones', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('despacho_id')->constrained('despachos')->cascadeOnDelete();
            $table->foreignId('documento_id')->constrained('documentos')->cascadeOnDelete();
            $table->unsignedInteger('version');
            $table->string('ruta');
            $table->string('disco')->default('local');
            $table->string('mime');
            $table->unsignedBigInteger('tamano');
            $table->string('sha256', 64);
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('nota')->nullable();
            $table->timestamps();

            $table->unique(['documento_id', 'version']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('documento_versiones');
    }
};
