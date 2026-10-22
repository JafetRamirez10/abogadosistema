<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('expedientes', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('despacho_id')->constrained('despachos')->cascadeOnDelete();
            $table->foreignId('cliente_id')->constrained('clientes')->cascadeOnDelete();
            $table->foreignId('responsable_id')->constrained('users')->cascadeOnDelete();
            $table->string('numero_interno');
            $table->string('numero_judicial')->nullable();
            $table->string('titulo');
            $table->string('materia');
            $table->string('estado')->default('pendiente');
            $table->string('juzgado')->nullable();
            $table->date('fecha_inicio');
            $table->decimal('cuantia', 14, 2)->nullable();
            $table->string('moneda')->default('CRC');
            $table->string('tipo_honorario');
            $table->decimal('honorario_pactado', 14, 2)->nullable();
            $table->decimal('porcentaje_honorario', 5, 2)->nullable();
            $table->text('descripcion')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['despacho_id', 'numero_interno']);
            $table->index('estado');
            $table->index('materia');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('expedientes');
    }
};
