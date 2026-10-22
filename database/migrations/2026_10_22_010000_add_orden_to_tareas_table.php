<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tareas', function (Blueprint $table): void {
            $table->unsignedInteger('orden')->default(0)->after('estado');
            $table->index(['despacho_id', 'estado', 'orden']);
        });
    }

    public function down(): void
    {
        Schema::table('tareas', function (Blueprint $table): void {
            $table->dropIndex(['despacho_id', 'estado', 'orden']);
            $table->dropColumn('orden');
        });
    }
};
