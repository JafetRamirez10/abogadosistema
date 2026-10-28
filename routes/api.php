<?php

declare(strict_types=1);

use App\Http\Controllers\Api\V1\LegalCorpusController;
use App\Http\Middleware\AuthenticateCorpusApiToken;
use Illuminate\Support\Facades\Route;

/*
| API de corpus legal para agentes de ingesta (leyes, jurisprudencia, doctrina).
| Auth: Authorization: Bearer {CORPUS_API_TOKEN}  o  header X-Corpus-Token
*/

Route::prefix('v1/corpus')
    ->middleware([AuthenticateCorpusApiToken::class, 'throttle:corpus-api'])
    ->group(function (): void {
        Route::get('/health', [LegalCorpusController::class, 'health']);
        Route::get('/sources', [LegalCorpusController::class, 'indexSources']);
        Route::get('/sources/{codigo}', [LegalCorpusController::class, 'showSource']);
        Route::post('/sources', [LegalCorpusController::class, 'upsertSource']);
        Route::put('/sources/{codigo}/chunks', [LegalCorpusController::class, 'upsertChunk']);
        Route::post('/bulk', [LegalCorpusController::class, 'bulkUpsert']);
        Route::delete('/sources/{codigo}', [LegalCorpusController::class, 'destroySource']);
        Route::get('/search', [LegalCorpusController::class, 'searchChunks']);
    });
