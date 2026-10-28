<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('legal_sources', function (Blueprint $table): void {
            $table->id();
            $table->string('codigo', 64)->unique();
            $table->string('titulo');
            $table->string('materia', 64)->nullable();
            $table->string('origen', 255)->nullable();
            $table->boolean('es_demo')->default(true);
            $table->timestamps();
        });

        Schema::create('legal_chunks', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('legal_source_id')->constrained('legal_sources')->cascadeOnDelete();
            $table->string('referencia', 255)->nullable();
            $table->string('titulo', 255)->nullable();
            $table->text('contenido');
            $table->string('materia', 64)->nullable();
            $table->json('keywords')->nullable();
            $table->timestamps();

            $table->index(['materia']);
        });

        Schema::table('ai_consultas', function (Blueprint $table): void {
            $table->json('fuentes')->nullable()->after('tokens');
            $table->string('especialidad', 64)->nullable()->after('fuentes');
        });
    }

    public function down(): void
    {
        Schema::table('ai_consultas', function (Blueprint $table): void {
            $table->dropColumn(['fuentes', 'especialidad']);
        });

        Schema::dropIfExists('legal_chunks');
        Schema::dropIfExists('legal_sources');
    }
};
