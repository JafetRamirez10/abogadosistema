<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Despacho;
use App\Models\Documento;
use App\Models\DocumentoVersion;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * @extends Factory<DocumentoVersion>
 */
class DocumentoVersionFactory extends Factory
{
    protected $model = DocumentoVersion::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $uuid = (string) Str::uuid();
        $contenido = "%PDF-1.4\n1 0 obj<<>>endobj\ntrailer<<>>\n%%EOF\n";
        $ruta = "despachos/seed/{$uuid}.pdf";

        Storage::disk('local')->put($ruta, $contenido);

        return [
            'despacho_id' => Despacho::factory(),
            'documento_id' => function (array $attributes) {
                return Documento::factory()->create([
                    'despacho_id' => $attributes['despacho_id'],
                ])->getKey();
            },
            'version' => 1,
            'ruta' => $ruta,
            'disco' => 'local',
            'mime' => 'application/pdf',
            'tamano' => strlen($contenido),
            'sha256' => hash('sha256', $contenido),
            'user_id' => User::factory(),
            'nota' => fake()->optional()->sentence(),
        ];
    }
}
