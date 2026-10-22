<?php

declare(strict_types=1);

use App\Http\Controllers\AiConsultaController;
use App\Http\Controllers\DocumentoDescargaController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::middleware(['auth', 'throttle:documentos-descarga'])->group(function (): void {
    Route::get('/documentos/{documento}/firmar-descarga', [DocumentoDescargaController::class, 'firmar'])
        ->name('documentos.firmar-descarga');

    Route::get('/documentos/{documento}/versiones/{version}/descargar', [DocumentoDescargaController::class, 'descargar'])
        ->name('documentos.descargar')
        ->middleware('signed');
});

Route::middleware(['auth'])->prefix('ia')->name('ia.')->group(function (): void {
    Route::post('/expedientes/{expediente}/resumir', [AiConsultaController::class, 'resumir'])
        ->name('resumir');
    Route::post('/expedientes/{expediente}/borrador', [AiConsultaController::class, 'borrador'])
        ->name('borrador');
});
