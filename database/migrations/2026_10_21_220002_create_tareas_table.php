<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tareas', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('despacho_id')->constrained('despachos')->cascadeOnDelete();
            $table->foreignId('expediente_id')->nullable()->constrained('expedientes')->cascadeOnDelete();
            $table->foreignId('asignada_a')->constrained('users')->cascadeOnDelete();
            $table->foreignId('creada_por')->constrained('users')->cascadeOnDelete();
            $table->string('descripcion');
            $table->date('vence_el')->nullable();
            $table->string('prioridad')->default('media');
            $table->string('estado')->default('pendiente');
            $table->timestamp('completada_at')->nullable();
            $table->timestamps();

            $table->index(['asignada_a', 'estado']);
            $table->index('vence_el');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tareas');
    }
};
