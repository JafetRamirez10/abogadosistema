<?php

declare(strict_types=1);

namespace Database\Factories\Support;

use Faker\Provider\Base;

/**
 * Datos realistas de Costa Rica para factories y seeders.
 */
class CostaRicaProvider extends Base
{
    /** @var list<string> */
    protected static array $firstNamesMale = [
        'Andrés', 'Carlos', 'Daniel', 'Diego', 'Eduardo', 'Felipe', 'Gabriel',
        'José', 'Juan', 'Luis', 'Marco', 'Mateo', 'Miguel', 'Óscar', 'Pedro',
        'Rafael', 'Ricardo', 'Santiago', 'Sebastián', 'Tomás',
    ];

    /** @var list<string> */
    protected static array $firstNamesFemale = [
        'Ana', 'Andrea', 'Carolina', 'Daniela', 'Elena', 'Fernanda', 'Gabriela',
        'Isabel', 'Jimena', 'Laura', 'María', 'Mariana', 'Natalia', 'Paola',
        'Sofía', 'Valeria', 'Valentina', 'Ximena', 'Yolanda', 'Lucía',
    ];

    /** @var list<string> */
    protected static array $lastNames = [
        'Alvarado', 'Arias', 'Brenes', 'Calvo', 'Campos', 'Chaves', 'Cordero',
        'Cruz', 'Fonseca', 'García', 'González', 'Gutiérrez', 'Hernández',
        'Jiménez', 'López', 'Mora', 'Morales', 'Muñoz', 'Navarro', 'Pérez',
        'Quesada', 'Ramírez', 'Rojas', 'Sánchez', 'Solís', 'Vargas', 'Villalobos',
    ];

    /** @var array<string, list<string>> */
    protected static array $provinciasCantones = [
        'San José' => ['Central', 'Escazú', 'Desamparados', 'Puriscal', 'Curridabat', 'Tibás'],
        'Alajuela' => ['Central', 'San Ramón', 'Grecia', 'Atenas', 'Naranjo', 'Palmares'],
        'Cartago' => ['Central', 'Paraíso', 'La Unión', 'Oreamuno', 'El Guarco'],
        'Heredia' => ['Central', 'Barva', 'Santo Domingo', 'Santa Bárbara', 'Belén'],
        'Guanacaste' => ['Liberia', 'Nicoya', 'Santa Cruz', 'Carrillo', 'Cañas'],
        'Puntarenas' => ['Central', 'Esparza', 'Buenos Aires', 'Montes de Oro', 'Quepos'],
        'Limón' => ['Central', 'Pococí', 'Siquirres', 'Talamanca', 'Guácimo'],
    ];

    /** @var list<string> */
    protected static array $puntosReferencia = [
        'la iglesia',
        'el parque central',
        'la municipalidad',
        'el BAC Credomatic',
        'la pulpería La Esperanza',
        'el colegio técnico',
        'la plaza de deportes',
        'el cementerio',
        'el mercado municipal',
        'la estación de buses',
    ];

    /** @var list<string> */
    protected static array $direcciones = [
        'norte',
        'sur',
        'este',
        'oeste',
    ];

    /** @var list<string> */
    protected static array $juzgados = [
        'Juzgado de Trabajo del I Circuito Judicial de San José',
        'Juzgado Civil del II Circuito Judicial de San José',
        'Juzgado de Familia de Heredia',
        'Juzgado Penal del I Circuito Judicial de Alajuela',
        'Juzgado Contencioso Administrativo de San José',
        'Juzgado Agrario de Liberia',
        'Juzgado de Cobro del I Circuito Judicial de San José',
        'Tribunal de Familia de Cartago',
        'Juzgado Notarial de San José',
        'Juzgado de Violencia Doméstica de Desamparados',
    ];

    /** @var list<string> */
    protected static array $materiasJudiciales = [
        'LA', 'CI', 'FA', 'PE', 'CA', 'CO', 'AG', 'NO',
    ];

    /**
     * Cédula física CR: formato visual 1-0234-0567; se guarda como 9 dígitos.
     */
    public function cedulaFisica(bool $formateada = false): string
    {
        $digitos = sprintf(
            '%d%04d%04d',
            self::numberBetween(1, 9),
            self::numberBetween(0, 9999),
            self::numberBetween(0, 9999),
        );

        if (! $formateada) {
            return $digitos;
        }

        return sprintf(
            '%s-%s-%s',
            substr($digitos, 0, 1),
            substr($digitos, 1, 4),
            substr($digitos, 5, 4),
        );
    }

    /**
     * Cédula jurídica CR: formato visual 3-101-123456; se guarda como 10 dígitos.
     */
    public function cedulaJuridica(bool $formateada = false): string
    {
        $digitos = sprintf(
            '3%03d%06d',
            self::numberBetween(100, 199),
            self::numberBetween(0, 999999),
        );

        if (! $formateada) {
            return $digitos;
        }

        return sprintf(
            '%s-%s-%s',
            substr($digitos, 0, 1),
            substr($digitos, 1, 3),
            substr($digitos, 4, 6),
        );
    }

    /**
     * Teléfono CR: móviles 8/7/6 o fijo 2 + 7 dígitos (formato 8xxx-xxxx).
     */
    public function telefonoCR(bool $formateado = false): string
    {
        $prefijo = (string) static::randomElement([8, 7, 6, 2]);
        $resto = self::numerify('#######');
        $digitos = $prefijo.$resto;

        if (! $formateado) {
            return $digitos;
        }

        return substr($digitos, 0, 4).'-'.substr($digitos, 4, 4);
    }

    public function provinciaCR(): string
    {
        return (string) static::randomElement(array_keys(static::$provinciasCantones));
    }

    public function cantonCR(?string $provincia = null): string
    {
        $provincia ??= $this->provinciaCR();
        $cantones = static::$provinciasCantones[$provincia] ?? ['Central'];

        return (string) static::randomElement($cantones);
    }

    /**
     * Dirección típica CR por referencia.
     */
    public function direccionCR(): string
    {
        $metros = self::numberBetween(1, 8) * 50;
        $sentido = (string) static::randomElement(static::$direcciones);
        $punto = (string) static::randomElement(static::$puntosReferencia);
        $provincia = $this->provinciaCR();
        $canton = $this->cantonCR($provincia);

        $de = str_starts_with($punto, 'el ') ? 'del' : 'de';

        return "{$metros} m {$sentido} {$de} {$punto}, {$canton}, {$provincia}";
    }

    public function juzgadoCR(): string
    {
        return (string) static::randomElement(static::$juzgados);
    }

    /**
     * Número de expediente judicial: 26-000123-0166-LA
     */
    public function numeroJudicialCR(?string $materia = null): string
    {
        $anio = self::numberBetween(20, 26);
        $consecutivo = self::numberBetween(1, 999999);
        $oficina = self::numberBetween(1, 9999);
        $materia ??= (string) static::randomElement(static::$materiasJudiciales);

        return sprintf('%02d-%06d-%04d-%s', $anio, $consecutivo, $oficina, $materia);
    }

    public function firstNameMaleCR(): string
    {
        return (string) static::randomElement(static::$firstNamesMale);
    }

    public function firstNameFemaleCR(): string
    {
        return (string) static::randomElement(static::$firstNamesFemale);
    }

    public function lastNameCR(): string
    {
        return (string) static::randomElement(static::$lastNames);
    }

    public function nombreCR(): string
    {
        $first = static::randomElement([
            $this->firstNameMaleCR(),
            $this->firstNameFemaleCR(),
        ]);

        return $first.' '.$this->lastNameCR().' '.$this->lastNameCR();
    }

    public function razonSocialCR(): string
    {
        $tipo = (string) static::randomElement([
            'S.A.',
            'S.R.L.',
            'Limitada',
            'Sociedad Anónima',
        ]);
        $base = (string) static::randomElement([
            'Inversiones',
            'Comercial',
            'Servicios',
            'Constructora',
            'Distribuidora',
            'Consultores',
            'Agropecuaria',
            'Tecnología',
        ]);
        $nombre = (string) static::randomElement([
            'del Valle',
            'del Pacífico',
            'de la Montaña',
            'Centroamericana',
            'Tica',
            'Guanacasteca',
            'Metropolitana',
            'Los Laureles',
        ]);

        return "{$base} {$nombre} {$tipo}";
    }
}
