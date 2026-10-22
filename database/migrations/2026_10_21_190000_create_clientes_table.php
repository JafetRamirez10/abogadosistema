<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('clientes', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('despacho_id')->constrained('despachos')->cascadeOnDelete();
            $table->string('tipo'); // fisica|juridica
            $table->string('nombre');
            $table->text('cedula');
            $table->string('cedula_hash', 64);
            $table->text('telefono')->nullable();
            $table->string('email')->nullable();
            $table->text('direccion')->nullable();
            $table->text('notas')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['despacho_id', 'cedula_hash']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('clientes');
    }
};
