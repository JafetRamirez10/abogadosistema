<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('eventos', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('despacho_id')->constrained('despachos')->cascadeOnDelete();
            $table->foreignId('expediente_id')->nullable()->constrained('expedientes')->cascadeOnDelete();
            $table->foreignId('responsable_id')->constrained('users')->cascadeOnDelete();
            $table->string('tipo');
            $table->string('titulo');
            $table->dateTime('inicio');
            $table->dateTime('fin')->nullable();
            $table->string('lugar')->nullable();
            $table->unsignedTinyInteger('dias_aviso')->default(3);
            $table->string('estado')->default('pendiente');
            $table->timestamp('alerta_enviada_at')->nullable();
            $table->text('notas')->nullable();
            $table->timestamps();

            $table->index('inicio');
            $table->index('estado');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('eventos');
    }
};
