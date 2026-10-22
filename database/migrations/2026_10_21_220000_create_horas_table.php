<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('horas', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('despacho_id')->constrained('despachos')->cascadeOnDelete();
            $table->foreignId('expediente_id')->constrained('expedientes')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->date('fecha');
            $table->unsignedInteger('minutos');
            $table->string('descripcion');
            $table->decimal('tarifa', 12, 2)->nullable();
            $table->string('moneda')->default('CRC');
            $table->boolean('facturable')->default(true);
            $table->timestamps();

            $table->index('fecha');
            $table->index(['user_id', 'fecha']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('horas');
    }
};
