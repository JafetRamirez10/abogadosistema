<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\LegalChunk;
use App\Models\LegalSource;
use Illuminate\Database\Seeder;

/**
 * Corpus DEMO para RAG. No sustituye el texto oficial de SCIJ/La Gaceta.
 * Sustituí o ampliá estos fragmentos con normas verificadas antes de producción.
 */
class LegalCorpusSeeder extends Seeder
{
    public function run(): void
    {
        $fuentes = [
            [
                'codigo' => 'demo-familia-alimentos',
                'titulo' => 'Demo — Alimentos y pensión (referencia interna)',
                'materia' => 'familia',
                'origen' => 'Corpus demo Despacho Legal CR (no oficial)',
                'chunks' => [
                    [
                        'referencia' => 'Demo · pensión alimentaria',
                        'titulo' => 'Obligación alimentaria',
                        'contenido' => 'En Costa Rica la obligación alimentaria comprende lo necesario para subsistencia, habitación, vestido, asistencia médica y educación según las posibilidades del deudor y las necesidades del beneficiario. El proceso de pensión alimentaria puede tramitarse de forma expedita conforme al Código Procesal de Familia, con audiencia inicial orientada a conciliación y, en su defecto, sentencia anticipada sujeta a oposición.',
                        'keywords' => ['pension', 'alimentaria', 'cuota', 'aguinaldo', 'salario', 'escolar', 'mora'],
                    ],
                    [
                        'referencia' => 'Demo · guarda e interrelación',
                        'titulo' => 'Guarda, crianza e interrelación familiar',
                        'contenido' => 'En divorcio, separación o procesos de familia con menores, el tribunal prioriza el interés superior de la persona menor de edad al definir guarda, crianza, educación y régimen de interrelación familiar. Los acuerdos de las partes pueden ser revisados o modificados por el juzgado cuando cambien las circunstancias o convenga a los menores.',
                        'keywords' => ['guarda', 'crianza', 'visitas', 'interrelacion', 'menores', 'divorcio'],
                    ],
                ],
            ],
            [
                'codigo' => 'demo-notarial-inmobiliario',
                'titulo' => 'Demo — Notarial e inmobiliario (referencia interna)',
                'materia' => 'notarial',
                'origen' => 'Corpus demo Despacho Legal CR (no oficial)',
                'chunks' => [
                    [
                        'referencia' => 'Demo · poderes',
                        'titulo' => 'Poderes notariales',
                        'contenido' => 'Un poder notarial autoriza a un apoderado a actuar en nombre del poderdante dentro de los límites del instrumento. Conviene distinguir poder general, especial e irrevocable según el acto. En la práctica notarial se identifican comparecientes, se verifica capacidad e identidad, y se protocoliza conforme al Código Notarial. El seguimiento posterior puede incluir uso ante bancos, municipalidades o el Registro Nacional.',
                        'keywords' => ['poder', 'apoderado', 'notarial', 'protocolo', 'autenticacion'],
                    ],
                    [
                        'referencia' => 'Demo · traspaso e hipoteca',
                        'titulo' => 'Traspasos e hipotecas',
                        'contenido' => 'El traspaso de bienes inmuebles suele requerir escritura pública, estudio de gravámenes, pago de tributos aplicables y presentación al Registro Nacional. La cancelación hipotecaria y la constitución de hipoteca o fideicomiso de garantía son actos típicos del kardex notarial. El estado registral (presentado, inscrito, prevenido, rechazado) debe documentarse con fecha y número de asiento cuando exista.',
                        'keywords' => ['traspaso', 'hipoteca', 'finca', 'registro', 'fideicomiso', 'condominio', 'meic'],
                    ],
                    [
                        'referencia' => 'Demo · sociedades y RTBF',
                        'titulo' => 'Sociedades, libros y RTBF',
                        'contenido' => 'La constitución y protocolización de actas societarias, la legalización de libros y la inscripción de PYMES son trámites frecuentes en notaría. El Registro de Transparencia y Beneficiarios Finales (RTBF) del BCCR impone obligaciones de declaración a sujetos obligados; el despacho puede llevar el checklist del trámite y la evidencia de presentación, sin sustituir la validación oficial del registro.',
                        'keywords' => ['sociedad', 'acta', 'libros', 'pyme', 'rtbf', 'bccr'],
                    ],
                ],
            ],
            [
                'codigo' => 'demo-penal-proceso',
                'titulo' => 'Demo — Proceso penal (referencia interna)',
                'materia' => 'penal',
                'origen' => 'Corpus demo Despacho Legal CR (no oficial)',
                'chunks' => [
                    [
                        'referencia' => 'Demo · etapas y cautelares',
                        'titulo' => 'Etapas y medidas cautelares',
                        'contenido' => 'El proceso penal costarricense se estructura en investigación, etapas intermedias y juicio, con posibilidad de recursos y ejecución. Las medidas cautelares (prisión preventiva, impedimento de salida, firma periódica, caución, etc.) tienen fechas de revisión y vencimiento que el defensor debe controlar. Los plazos de impugnación y de audiencias, especialmente en flagrancia, son críticos y no deben confiarse solo a la memoria.',
                        'keywords' => ['penal', 'imputado', 'cautelar', 'prision', 'preventiva', 'audiencia', 'flagrancia'],
                    ],
                ],
            ],
        ];

        foreach ($fuentes as $fuenteData) {
            $chunks = $fuenteData['chunks'];
            unset($fuenteData['chunks']);

            $source = LegalSource::query()->updateOrCreate(
                ['codigo' => $fuenteData['codigo']],
                [
                    'titulo' => $fuenteData['titulo'],
                    'tipo' => 'ley',
                    'materia' => $fuenteData['materia'],
                    'origen' => $fuenteData['origen'],
                    'es_demo' => true,
                    'indexado_at' => now(),
                ],
            );

            foreach ($chunks as $chunk) {
                LegalChunk::query()->updateOrCreate(
                    [
                        'legal_source_id' => $source->getKey(),
                        'referencia' => $chunk['referencia'],
                    ],
                    [
                        'titulo' => $chunk['titulo'],
                        'contenido' => $chunk['contenido'],
                        'materia' => $fuenteData['materia'],
                        'keywords' => $chunk['keywords'],
                    ],
                );
            }
        }
    }
}
