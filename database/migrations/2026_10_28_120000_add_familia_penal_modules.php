<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('expedientes', function (Blueprint $table): void {
            $table->string('tipo_caso_familia', 64)->nullable()->after('materia');
            $table->string('etapa_penal', 64)->nullable()->after('tipo_caso_familia');
            $table->boolean('datos_sensibles')->default(false)->after('etapa_penal');
        });

        Schema::create('beneficiarios_familiares', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('despacho_id')->constrained('despachos')->cascadeOnDelete();
            $table->foreignId('expediente_id')->constrained('expedientes')->cascadeOnDelete();
            $table->string('nombre');
            $table->date('fecha_nacimiento')->nullable();
            $table->string('parentesco', 100)->nullable();
            $table->boolean('es_menor')->default(true);
            $table->text('cedula')->nullable();
            $table->text('notas')->nullable();
            $table->timestamps();

            $table->index(['despacho_id', 'expediente_id']);
        });

        Schema::create('pensiones_alimentarias', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('despacho_id')->constrained('despachos')->cascadeOnDelete();
            $table->foreignId('expediente_id')->constrained('expedientes')->cascadeOnDelete();
            $table->decimal('cuota_mensual', 14, 2);
            $table->string('moneda', 3)->default('CRC');
            $table->unsignedTinyInteger('dia_vencimiento')->default(1);
            $table->boolean('incluye_aguinaldo')->default(true);
            $table->boolean('incluye_salario_escolar')->default(true);
            $table->decimal('monto_aguinaldo', 14, 2)->nullable();
            $table->decimal('monto_salario_escolar', 14, 2)->nullable();
            $table->date('fecha_inicio');
            $table->boolean('activa')->default(true);
            $table->text('notas')->nullable();
            $table->timestamps();

            $table->unique(['expediente_id']);
            $table->index(['despacho_id', 'activa']);
        });

        Schema::create('cuotas_alimentarias', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('despacho_id')->constrained('despachos')->cascadeOnDelete();
            $table->foreignId('expediente_id')->constrained('expedientes')->cascadeOnDelete();
            $table->foreignId('pension_alimentaria_id')->constrained('pensiones_alimentarias')->cascadeOnDelete();
            $table->string('concepto', 40);
            $table->string('periodo', 7)->nullable();
            $table->decimal('monto', 14, 2);
            $table->decimal('monto_pagado', 14, 2)->default(0);
            $table->string('moneda', 3)->default('CRC');
            $table->date('vence_el');
            $table->string('estado', 20)->default('pendiente');
            $table->text('notas')->nullable();
            $table->timestamps();

            $table->index(['despacho_id', 'estado', 'vence_el']);
            $table->index(['pension_alimentaria_id', 'periodo', 'concepto']);
        });

        Schema::create('pagos_alimentarios', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('despacho_id')->constrained('despachos')->cascadeOnDelete();
            $table->foreignId('expediente_id')->constrained('expedientes')->cascadeOnDelete();
            $table->foreignId('pension_alimentaria_id')->constrained('pensiones_alimentarias')->cascadeOnDelete();
            $table->foreignId('cuota_alimentaria_id')->nullable()->constrained('cuotas_alimentarias')->nullOnDelete();
            $table->decimal('monto', 14, 2);
            $table->string('moneda', 3)->default('CRC');
            $table->date('fecha');
            $table->string('metodo', 40)->default('sinpe');
            $table->string('referencia', 255)->nullable();
            $table->text('nota')->nullable();
            $table->timestamps();

            $table->index(['despacho_id', 'fecha']);
        });

        Schema::create('medidas_cautelares', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('despacho_id')->constrained('despachos')->cascadeOnDelete();
            $table->foreignId('expediente_id')->constrained('expedientes')->cascadeOnDelete();
            $table->string('tipo', 64);
            $table->string('estado', 32)->default('vigente');
            $table->date('fecha_inicio');
            $table->date('fecha_revision')->nullable();
            $table->date('fecha_vencimiento')->nullable();
            $table->string('autoridad', 255)->nullable();
            $table->text('descripcion')->nullable();
            $table->timestamps();

            $table->index(['despacho_id', 'estado', 'fecha_vencimiento']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('medidas_cautelares');
        Schema::dropIfExists('pagos_alimentarios');
        Schema::dropIfExists('cuotas_alimentarias');
        Schema::dropIfExists('pensiones_alimentarias');
        Schema::dropIfExists('beneficiarios_familiares');

        Schema::table('expedientes', function (Blueprint $table): void {
            $table->dropColumn(['tipo_caso_familia', 'etapa_penal', 'datos_sensibles']);
        });
    }
};
