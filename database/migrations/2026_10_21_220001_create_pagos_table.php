<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pagos', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('despacho_id')->constrained('despachos')->cascadeOnDelete();
            $table->foreignId('expediente_id')->constrained('expedientes')->cascadeOnDelete();
            $table->decimal('monto', 14, 2);
            $table->string('moneda')->default('CRC');
            $table->date('fecha');
            $table->string('metodo');
            $table->string('referencia')->nullable();
            $table->string('nota')->nullable();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->timestamps();

            $table->index('fecha');
            $table->index(['expediente_id', 'moneda']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pagos');
    }
};
