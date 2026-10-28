<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('legal_sources', function (Blueprint $table): void {
            $table->string('tipo', 40)->default('ley')->after('codigo');
            $table->string('external_id', 128)->nullable()->after('origen');
            $table->date('vigente_desde')->nullable()->after('external_id');
            $table->date('vigente_hasta')->nullable()->after('vigente_desde');
            $table->json('metadata')->nullable()->after('vigente_hasta');
            $table->timestamp('indexado_at')->nullable()->after('metadata');

            $table->index(['tipo', 'materia']);
            $table->index('external_id');
        });

        Schema::table('legal_chunks', function (Blueprint $table): void {
            $table->string('external_id', 128)->nullable()->after('referencia');
            $table->unsignedInteger('orden')->default(0)->after('keywords');
            $table->json('metadata')->nullable()->after('orden');

            $table->unique(['legal_source_id', 'external_id']);
        });
    }

    public function down(): void
    {
        Schema::table('legal_chunks', function (Blueprint $table): void {
            $table->dropUnique(['legal_source_id', 'external_id']);
            $table->dropColumn(['external_id', 'orden', 'metadata']);
        });

        Schema::table('legal_sources', function (Blueprint $table): void {
            $table->dropIndex(['tipo', 'materia']);
            $table->dropIndex(['external_id']);
            $table->dropColumn([
                'tipo',
                'external_id',
                'vigente_desde',
                'vigente_hasta',
                'metadata',
                'indexado_at',
            ]);
        });
    }
};
