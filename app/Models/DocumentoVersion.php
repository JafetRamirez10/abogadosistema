<?php

declare(strict_types=1);

namespace App\Models;

use Database\Factories\DocumentoVersionFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $despacho_id
 * @property int $documento_id
 * @property int $version
 * @property string $ruta
 * @property string $disco
 * @property string $mime
 * @property int $tamano
 * @property string $sha256
 * @property int $user_id
 * @property string|null $nota
 */
class DocumentoVersion extends DespachoScopedModel
{
    /** @use HasFactory<DocumentoVersionFactory> */
    use HasFactory;

    protected $table = 'documento_versiones';

    /** @var list<string> */
    protected $fillable = [
        'despacho_id',
        'documento_id',
        'version',
        'ruta',
        'disco',
        'mime',
        'tamano',
        'sha256',
        'user_id',
        'nota',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'version' => 'integer',
            'tamano' => 'integer',
        ];
    }

    /** @return BelongsTo<Documento, $this> */
    public function documento(): BelongsTo
    {
        return $this->belongsTo(Documento::class);
    }

    /** @return BelongsTo<User, $this> */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
