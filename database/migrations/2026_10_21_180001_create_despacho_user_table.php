<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('despacho_user', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('despacho_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['despacho_id', 'user_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('despacho_user');
    }
};
