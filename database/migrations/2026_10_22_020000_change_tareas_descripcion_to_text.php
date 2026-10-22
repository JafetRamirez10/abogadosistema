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
            $table->text('descripcion')->change();
        });
    }

    public function down(): void
    {
        Schema::table('tareas', function (Blueprint $table): void {
            $table->string('descripcion', 255)->change();
        });
    }
};
