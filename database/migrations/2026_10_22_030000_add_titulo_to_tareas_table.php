<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tareas', function (Blueprint $table): void {
            $table->string('titulo', 255)->default('')->after('creada_por');
        });

        foreach (DB::table('tareas')->orderBy('id')->cursor() as $tarea) {
            $primeraLinea = trim(explode("\n", (string) $tarea->descripcion)[0]);
            $titulo = $primeraLinea !== '' ? $primeraLinea : 'Sin título';

            DB::table('tareas')->where('id', $tarea->id)->update([
                'titulo' => Str::limit($titulo, 120, ''),
            ]);
        }
    }

    public function down(): void
    {
        Schema::table('tareas', function (Blueprint $table): void {
            $table->dropColumn('titulo');
        });
    }
};
