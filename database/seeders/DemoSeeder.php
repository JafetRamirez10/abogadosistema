<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Actions\CrearCliente;
use App\Actions\CrearExpediente;
use App\Actions\RegistrarHora;
use App\Actions\RegistrarPago;
use App\DTOs\ClienteData;
use App\DTOs\ExpedienteData;
use App\Enums\EstadoDocumento;
use App\Enums\EstadoEvento;
use App\Enums\EstadoExpediente;
use App\Enums\EstadoTarea;
use App\Enums\FormatoSalidaPlantilla;
use App\Enums\Materia;
use App\Enums\MetodoPago;
use App\Enums\Moneda;
use App\Enums\PrioridadTarea;
use App\Enums\RolParte;
use App\Enums\TipoActuacion;
use App\Enums\TipoCliente;
use App\Enums\TipoEvento;
use App\Enums\TipoHonorario;
use App\Models\Actuacion;
use App\Models\Cliente;
use App\Models\Despacho;
use App\Models\Documento;
use App\Models\DocumentoVersion;
use App\Models\Evento;
use App\Models\Expediente;
use App\Models\Parte;
use App\Models\Plantilla;
use App\Models\Tarea;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use RuntimeException;

/**
 * Datos demo Costa Rica (sección 15 + 15.1).
 *
 * Volumen medio-alto en SQLite (~10s): ~2 despachos, ~8 users, ~60 clientes,
 * ~85 expedientes, ~300 actuaciones, ~140 eventos, ~165 docs, ~60 tareas.
 * Plan completo (60/120/400…) se redujo un poco por rendimiento SQLite.
 */
class DemoSeeder extends Seeder
{
    private const DEMO_PASSWORD = 'Demo2026!segura';

    private const VOLUMEN_CLIENTES = 40;

    private const VOLUMEN_EXPEDIENTES = 60;

    private const VOLUMEN_ACTUACIONES = 220;

    private const VOLUMEN_EVENTOS = 100;

    private const VOLUMEN_DOCUMENTOS = 120;

    private const VOLUMEN_TAREAS = 40;

    public function run(): void
    {
        if (! app()->environment(['local', 'demo'])) {
            throw new RuntimeException(
                'DemoSeeder solo puede ejecutarse en entornos local o demo.'
            );
        }

        Mail::fake();
        Notification::fake();

        $this->command?->info('Sembrando demo Despacho Legal CR…');

        Carbon::setTestNow(Carbon::parse('2026-04-15 09:00:00', 'America/Costa_Rica'));

        [$despacho, $admin, $abogada, $asistente] = $this->seedDespachoPrincipal();
        $segundo = $this->seedSegundoDespacho();

        Auth::login($admin);
        activity()->causedBy($admin);

        $clientesNarrativos = $this->seedClientesNarrativos($despacho);
        $expedientePrincipal = $this->seedExpedientePrincipal(
            $despacho,
            $abogada,
            $admin,
            $clientesNarrativos['maria'],
        );
        $this->seedOtrosExpedientesNarrativos($despacho, $abogada, $clientesNarrativos);
        $this->seedPlantillas($despacho);
        $this->seedEventosGlobales($despacho, $abogada, $expedientePrincipal);
        $this->seedTareasAbogada($despacho, $abogada, $admin, $expedientePrincipal);

        $this->seedVolumen($despacho, $abogada, $admin, $asistente);
        $this->seedVolumen($segundo['despacho'], $segundo['abogado'], $segundo['admin'], $segundo['asistente']);

        Carbon::setTestNow();
        Auth::logout();

        $this->command?->info('Demo sembrado. Login: admin@demo.cr / '.self::DEMO_PASSWORD);
        $this->imprimirResumen();
    }

    /**
     * @return array{0: Despacho, 1: User, 2: User, 3: User}
     */
    private function seedDespachoPrincipal(): array
    {
        $despacho = Despacho::query()->create([
            'nombre' => 'Bufete Ramírez & Asociados',
            'slug' => 'bufete-ramirez',
            'cedula_juridica' => '3101123456',
            'telefono' => '22223344',
            'email' => 'info@bufeteramirez.cr',
            'direccion' => '200 m norte de la iglesia, Barrio Escalante, San José',
        ]);

        RolesAndPermissionsSeeder::seedRolesForDespacho($despacho);

        $admin = User::query()->create([
            'name' => 'Admin Demo',
            'email' => 'admin@demo.cr',
            'telefono' => '88881111',
            'password' => Hash::make(self::DEMO_PASSWORD),
            'email_verified_at' => now(),
        ]);

        $abogada = User::query()->create([
            'name' => 'Lic. Ana Solís',
            'email' => 'abogada@demo.cr',
            'telefono' => '88882222',
            'tarifa_hora' => 35_000,
            'moneda_tarifa' => Moneda::CRC,
            'password' => Hash::make(self::DEMO_PASSWORD),
            'email_verified_at' => now(),
        ]);

        $asistente = User::query()->create([
            'name' => 'Carlos Mora',
            'email' => 'asistente@demo.cr',
            'telefono' => '88883333',
            'password' => Hash::make(self::DEMO_PASSWORD),
            'email_verified_at' => now(),
        ]);

        $pasante = User::factory()->create([
            'name' => 'Sofía Brenes',
            'email' => 'pasante@demo.cr',
            'password' => Hash::make(self::DEMO_PASSWORD),
        ]);

        foreach ([$admin, $abogada, $asistente, $pasante] as $user) {
            $user->despachos()->attach($despacho);
        }

        setPermissionsTeamId($despacho->getKey());
        $admin->assignRole('admin');
        $abogada->assignRole('abogado');
        $asistente->assignRole('asistente');
        $pasante->assignRole('asistente');

        return [$despacho, $admin, $abogada, $asistente];
    }

    /**
     * @return array{despacho: Despacho, admin: User, abogado: User, asistente: User}
     */
    private function seedSegundoDespacho(): array
    {
        $despacho = Despacho::factory()->create([
            'nombre' => 'Estudio Jurídico Cascante',
            'slug' => 'estudio-cascante',
        ]);

        RolesAndPermissionsSeeder::seedRolesForDespacho($despacho);

        $admin = User::factory()->create(['email' => 'admin@cascante.cr']);
        $abogado = User::factory()->abogado(40_000)->create(['email' => 'abogado@cascante.cr']);
        $asistente = User::factory()->create(['email' => 'asistente@cascante.cr']);
        $extra = User::factory()->create(['email' => 'pasante@cascante.cr']);

        foreach ([$admin, $abogado, $asistente, $extra] as $user) {
            $user->despachos()->attach($despacho);
        }

        setPermissionsTeamId($despacho->getKey());
        $admin->assignRole('admin');
        $abogado->assignRole('abogado');
        $asistente->assignRole('asistente');
        $extra->assignRole('asistente');

        return compact('despacho', 'admin', 'abogado', 'asistente');
    }

    /**
     * @return array{maria: Cliente, inversiones: Cliente, extras: list<Cliente>}
     */
    private function seedClientesNarrativos(Despacho $despacho): array
    {
        $crear = app(CrearCliente::class);

        Carbon::setTestNow(Carbon::parse('2026-05-02 10:00:00', 'America/Costa_Rica'));
        $maria = $crear->handle(new ClienteData(
            tipo: TipoCliente::Fisica,
            nombre: 'María Pérez Rodríguez',
            cedula: '112345678',
            telefono: '88881234',
            email: 'maria.perez@ejemplo.cr',
            direccion: '150 m este de la municipalidad, Curridabat, San José',
            notas: 'Cliente principal del expediente laboral demo.',
            despachoId: (int) $despacho->getKey(),
        ));

        Carbon::setTestNow(Carbon::parse('2026-05-05 11:30:00', 'America/Costa_Rica'));
        $inversiones = $crear->handle(new ClienteData(
            tipo: TipoCliente::Juridica,
            nombre: 'Inversiones del Valle S.A.',
            cedula: '3101123456',
            telefono: '22225566',
            email: 'legal@inversionesdelvalle.cr',
            direccion: 'Oficinas Centrales, Escazú, San José',
            despachoId: (int) $despacho->getKey(),
        ));

        $extrasData = [
            ['nombre' => 'José Rojas Vargas', 'cedula' => '109876543', 'tipo' => TipoCliente::Fisica],
            ['nombre' => 'Laura Jiménez Castro', 'cedula' => '205551234', 'tipo' => TipoCliente::Fisica],
            ['nombre' => 'Comercial Pacífico S.R.L.', 'cedula' => '3101987654', 'tipo' => TipoCliente::Juridica],
        ];

        $extras = [];
        foreach ($extrasData as $i => $data) {
            Carbon::setTestNow(Carbon::parse('2026-05-10', 'America/Costa_Rica')->addDays($i)->setTime(9, 0));
            $extras[] = $crear->handle(new ClienteData(
                tipo: $data['tipo'],
                nombre: $data['nombre'],
                cedula: $data['cedula'],
                telefono: fake()->telefonoCR(),
                email: fake()->unique()->safeEmail(),
                direccion: fake()->direccionCR(),
                despachoId: (int) $despacho->getKey(),
            ));
        }

        return [
            'maria' => $maria,
            'inversiones' => $inversiones,
            'extras' => $extras,
        ];
    }

    private function seedExpedientePrincipal(
        Despacho $despacho,
        User $abogada,
        User $admin,
        Cliente $maria,
    ): Expediente {
        Carbon::setTestNow(Carbon::parse('2026-06-01 09:00:00', 'America/Costa_Rica'));

        $expediente = app(CrearExpediente::class)->handle(new ExpedienteData(
            clienteId: (int) $maria->getKey(),
            responsableId: (int) $abogada->getKey(),
            numeroInterno: 'EXP-2026-001',
            titulo: 'María Pérez Rodríguez vs Empresa X S.A.',
            materia: Materia::Laboral,
            fechaInicio: '2026-06-01',
            tipoHonorario: TipoHonorario::Fijo,
            numeroJudicial: '26-000123-0166-LA',
            estado: EstadoExpediente::EnProgreso,
            juzgado: 'Juzgado de Trabajo del I Circuito Judicial de San José',
            moneda: Moneda::CRC,
            honorarioPactado: '600000',
            descripcion: 'Demanda laboral por despido injustificado y pago de prestaciones.',
            despachoId: (int) $despacho->getKey(),
        ));

        Parte::query()->create([
            'despacho_id' => $despacho->getKey(),
            'expediente_id' => $expediente->getKey(),
            'nombre' => 'María Pérez Rodríguez',
            'cedula' => '112345678',
            'rol' => RolParte::Actor,
            'abogado_contrario' => null,
            'contacto' => '88881234',
        ]);

        Parte::query()->create([
            'despacho_id' => $despacho->getKey(),
            'expediente_id' => $expediente->getKey(),
            'nombre' => 'Empresa X S.A.',
            'cedula' => '3101555666',
            'rol' => RolParte::Demandado,
            'abogado_contrario' => 'Lic. Pedro Vargas',
            'contacto' => '22224455',
        ]);

        Carbon::setTestNow(Carbon::parse('2026-06-15 14:00:00', 'America/Costa_Rica'));
        Actuacion::query()->create([
            'despacho_id' => $despacho->getKey(),
            'expediente_id' => $expediente->getKey(),
            'user_id' => $abogada->getKey(),
            'fecha' => '2026-06-15',
            'tipo' => TipoActuacion::Escrito,
            'descripcion' => 'Presentación de demanda laboral ante el Juzgado de Trabajo.',
        ]);

        Carbon::setTestNow(Carbon::parse('2026-07-20 10:30:00', 'America/Costa_Rica'));
        Actuacion::query()->create([
            'despacho_id' => $despacho->getKey(),
            'expediente_id' => $expediente->getKey(),
            'user_id' => $abogada->getKey(),
            'fecha' => '2026-07-20',
            'tipo' => TipoActuacion::Notificacion,
            'descripcion' => 'Notificación de traslado de la demanda a la parte demandada.',
        ]);

        // Audiencia 5 nov 2026 9:00
        Evento::query()->create([
            'despacho_id' => $despacho->getKey(),
            'expediente_id' => $expediente->getKey(),
            'responsable_id' => $abogada->getKey(),
            'tipo' => TipoEvento::Audiencia,
            'titulo' => 'Audiencia preliminar laboral',
            'inicio' => Carbon::parse('2026-11-05 09:00:00', 'America/Costa_Rica'),
            'fin' => Carbon::parse('2026-11-05 11:00:00', 'America/Costa_Rica'),
            'lugar' => 'Juzgado de Trabajo del I Circuito Judicial de San José',
            'dias_aviso' => 7,
            'estado' => EstadoEvento::Pendiente,
        ]);

        // Semáforos relativos al "hoy" real (no al setTestNow del historial)
        $hoyRef = $this->hoyReal();

        Evento::query()->create([
            'despacho_id' => $despacho->getKey(),
            'expediente_id' => $expediente->getKey(),
            'responsable_id' => $abogada->getKey(),
            'tipo' => TipoEvento::Vencimiento,
            'titulo' => 'Vence plazo para contestar excepciones',
            'inicio' => $hoyRef->copy()->addDay()->setTime(16, 0),
            'fin' => null,
            'dias_aviso' => 3,
            'estado' => EstadoEvento::Pendiente,
        ]);

        Evento::query()->create([
            'despacho_id' => $despacho->getKey(),
            'expediente_id' => $expediente->getKey(),
            'responsable_id' => $abogada->getKey(),
            'tipo' => TipoEvento::Recordatorio,
            'titulo' => 'Preparar alegatos finales',
            'inicio' => $hoyRef->copy()->addDays(10)->setTime(10, 0),
            'fin' => $hoyRef->copy()->addDays(10)->setTime(11, 0),
            'dias_aviso' => 3,
            'estado' => EstadoEvento::Pendiente,
        ]);

        Carbon::setTestNow(Carbon::parse('2026-06-10 12:00:00', 'America/Costa_Rica'));
        app(RegistrarPago::class)->handle([
            'despacho_id' => $despacho->getKey(),
            'expediente_id' => $expediente->getKey(),
            'monto' => 100_000,
            'moneda' => Moneda::CRC->value,
            'fecha' => '2026-06-10',
            'metodo' => MetodoPago::Sinpe->value,
            'referencia' => 'SINPE-DEMO-001',
            'nota' => 'Adelanto de honorarios',
            'user_id' => $admin->getKey(),
        ]);

        Carbon::setTestNow(Carbon::parse('2026-06-12 16:00:00', 'America/Costa_Rica'));
        app(RegistrarHora::class)->handle([
            'despacho_id' => $despacho->getKey(),
            'expediente_id' => $expediente->getKey(),
            'user_id' => $abogada->getKey(),
            'fecha' => '2026-06-12',
            'minutos' => 90,
            'descripcion' => 'Redacción de demanda laboral y revisión de pruebas',
            'tarifa' => 35_000,
            'moneda' => Moneda::CRC->value,
            'facturable' => true,
        ]);

        foreach (EstadoDocumento::cases() as $i => $estado) {
            $this->crearDocumentoConVersion(
                $despacho,
                $expediente,
                $abogada,
                'Documento '.$estado->label().' — EXP-2026-001',
                $estado,
            );
            Carbon::setTestNow(Carbon::parse('2026-07-01', 'America/Costa_Rica')->addDays($i)->setTime(11, 0));
        }

        return $expediente;
    }

    /**
     * @param  array{maria: Cliente, inversiones: Cliente, extras: list<Cliente>}  $clientes
     */
    private function seedOtrosExpedientesNarrativos(Despacho $despacho, User $abogada, array $clientes): void
    {
        $crear = app(CrearExpediente::class);

        Carbon::setTestNow(Carbon::parse('2026-07-05 09:00:00', 'America/Costa_Rica'));
        $civil = $crear->handle(new ExpedienteData(
            clienteId: (int) $clientes['inversiones']->getKey(),
            responsableId: (int) $abogada->getKey(),
            numeroInterno: 'EXP-2026-002',
            titulo: 'Cobro de honorarios — contrato comercial',
            materia: Materia::Civil,
            fechaInicio: '2026-07-05',
            tipoHonorario: TipoHonorario::Fijo,
            numeroJudicial: fake()->numeroJudicialCR('CI'),
            estado: EstadoExpediente::Pendiente,
            juzgado: 'Juzgado Civil del II Circuito Judicial de San José',
            moneda: Moneda::USD,
            honorarioPactado: '1500',
            despachoId: (int) $despacho->getKey(),
        ));

        app(RegistrarPago::class)->handle([
            'despacho_id' => $despacho->getKey(),
            'expediente_id' => $civil->getKey(),
            'monto' => 500,
            'moneda' => Moneda::USD->value,
            'fecha' => '2026-07-08',
            'metodo' => MetodoPago::Transferencia->value,
            'referencia' => 'WIRE-USD-500',
            'user_id' => $abogada->getKey(),
        ]);

        Carbon::setTestNow(Carbon::parse('2026-07-12 10:00:00', 'America/Costa_Rica'));
        $crear->handle(new ExpedienteData(
            clienteId: (int) $clientes['extras'][0]->getKey(),
            responsableId: (int) $abogada->getKey(),
            numeroInterno: 'EXP-2026-003',
            titulo: 'Proceso de pensión alimentaria',
            materia: Materia::Familia,
            fechaInicio: '2026-07-12',
            tipoHonorario: TipoHonorario::PorHora,
            estado: EstadoExpediente::EnRevision,
            juzgado: 'Juzgado de Familia de Heredia',
            moneda: Moneda::CRC,
            despachoId: (int) $despacho->getKey(),
        ));

        Carbon::setTestNow(Carbon::parse('2026-07-18 11:00:00', 'America/Costa_Rica'));
        $crear->handle(new ExpedienteData(
            clienteId: (int) $clientes['extras'][1]->getKey(),
            responsableId: (int) $abogada->getKey(),
            numeroInterno: 'EXP-2026-004',
            titulo: 'Protocolización de escritura de compraventa',
            materia: Materia::Notarial,
            fechaInicio: '2026-07-18',
            tipoHonorario: TipoHonorario::Fijo,
            estado: EstadoExpediente::Pendiente,
            moneda: Moneda::CRC,
            honorarioPactado: '250000',
            despachoId: (int) $despacho->getKey(),
        ));

        Carbon::setTestNow(Carbon::parse('2026-03-01 09:00:00', 'America/Costa_Rica'));
        $crear->handle(new ExpedienteData(
            clienteId: (int) $clientes['extras'][2]->getKey(),
            responsableId: (int) $abogada->getKey(),
            numeroInterno: 'EXP-2025-089',
            titulo: 'Asesoría comercial — cerrada',
            materia: Materia::Comercial,
            fechaInicio: '2025-09-01',
            tipoHonorario: TipoHonorario::Fijo,
            estado: EstadoExpediente::Cerrado,
            moneda: Moneda::CRC,
            honorarioPactado: '400000',
            despachoId: (int) $despacho->getKey(),
        ));
    }

    private function seedPlantillas(Despacho $despacho): void
    {
        Carbon::setTestNow(Carbon::parse('2026-05-20 08:00:00', 'America/Costa_Rica'));

        $plantillas = [
            [
                'nombre' => 'Poder especial judicial',
                'materia' => Materia::Civil,
                'contenido' => "PODER ESPECIAL JUDICIAL\n\nYo, {{ cliente.nombre }}, cédula {{ cliente.cedula }}, "
                    .'domiciliado en {{ cliente.direccion }}, otorgo poder especial judicial a {{ abogado.nombre }} '
                    .'del despacho {{ despacho.nombre }}, para que me represente en el expediente '
                    .'{{ expediente.numero_interno }} ({{ expediente.titulo }}) ante {{ expediente.juzgado }}. '
                    .'Fecha: {{ fecha_hoy }}.',
            ],
            [
                'nombre' => 'Contestación de demanda (borrador)',
                'materia' => Materia::Laboral,
                'contenido' => "CONTESTACIÓN DE DEMANDA\n\nSeñor(a) Juez(a) del {{ expediente.juzgado }}:\n\n"
                    .'{{ cliente.nombre }}, cédula {{ cliente.cedula }}, contesta la demanda del expediente '
                    .'{{ expediente.numero_interno }} interpuesta por {{ contraparte.nombre }}. '
                    .'Abogado: {{ abogado.nombre }}. Fecha: {{ fecha_hoy }}.',
            ],
            [
                'nombre' => 'Carta de cobro de honorarios',
                'materia' => null,
                'contenido' => "CARTA DE COBRO DE HONORARIOS\n\nEstimado(a) {{ cliente.nombre }}:\n\n"
                    .'Por medio de la presente, {{ despacho.nombre }} le solicita el pago de honorarios '
                    .'pendientes correspondientes al expediente {{ expediente.numero_interno }} '
                    .'({{ expediente.titulo }}). Atentamente, {{ abogado.nombre }}. Fecha: {{ fecha_hoy }}.',
            ],
        ];

        foreach ($plantillas as $data) {
            Plantilla::query()->create([
                'despacho_id' => $despacho->getKey(),
                'nombre' => $data['nombre'],
                'materia' => $data['materia'],
                'contenido' => $data['contenido'],
                'formato_salida' => FormatoSalidaPlantilla::Ambos,
            ]);
        }
    }

    private function seedEventosGlobales(Despacho $despacho, User $abogada, Expediente $expediente): void
    {
        $hoy = $this->hoyReal();

        Evento::query()->create([
            'despacho_id' => $despacho->getKey(),
            'expediente_id' => $expediente->getKey(),
            'responsable_id' => $abogada->getKey(),
            'tipo' => TipoEvento::Audiencia,
            'titulo' => 'Audiencia de hoy — seguimiento',
            'inicio' => $hoy->copy()->setTime(14, 0),
            'fin' => $hoy->copy()->setTime(15, 30),
            'lugar' => fake()->juzgadoCR(),
            'dias_aviso' => 1,
            'estado' => EstadoEvento::Pendiente,
        ]);

        Evento::query()->create([
            'despacho_id' => $despacho->getKey(),
            'expediente_id' => $expediente->getKey(),
            'responsable_id' => $abogada->getKey(),
            'tipo' => TipoEvento::Cita,
            'titulo' => 'Cita con cliente — preparación de pruebas',
            'inicio' => $hoy->copy()->addDays(4)->setTime(10, 0),
            'fin' => $hoy->copy()->addDays(4)->setTime(11, 0),
            'lugar' => 'Oficinas del bufete',
            'dias_aviso' => 2,
            'estado' => EstadoEvento::Pendiente,
        ]);

        Evento::query()->create([
            'despacho_id' => $despacho->getKey(),
            'expediente_id' => $expediente->getKey(),
            'responsable_id' => $abogada->getKey(),
            'tipo' => TipoEvento::Cita,
            'titulo' => 'Cita cancelada con perito',
            'inicio' => $hoy->copy()->addDays(2)->setTime(9, 0),
            'fin' => $hoy->copy()->addDays(2)->setTime(10, 0),
            'dias_aviso' => 2,
            'estado' => EstadoEvento::Cancelada,
            'notas' => 'Cancelada a solicitud del perito.',
        ]);
    }

    private function seedTareasAbogada(
        Despacho $despacho,
        User $abogada,
        User $admin,
        Expediente $expediente,
    ): void {
        $hoy = $this->hoyReal();

        $tareas = [
            [
                'titulo' => 'Revisar contestación de demanda',
                'descripcion' => "Revisar argumentos de la contraparte.\nMarcar inconsistencias y preparar réplica.",
                'prioridad' => PrioridadTarea::Alta,
                'vence_el' => $hoy->copy()->addDays(2)->toDateString(),
                'estado' => EstadoTarea::Pendiente,
            ],
            [
                'titulo' => 'Solicitar certificación de salarios',
                'descripcion' => 'Pedir certificación al patrono o al MTSS para respaldar la cuantía.',
                'prioridad' => PrioridadTarea::Alta,
                'vence_el' => $hoy->copy()->subDays(2)->toDateString(),
                'estado' => EstadoTarea::Pendiente,
            ],
            [
                'titulo' => 'Preparar interrogatorio de testigos',
                'descripcion' => 'Lista de preguntas para la audiencia. Coordinar con la clienta.',
                'prioridad' => PrioridadTarea::Media,
                'vence_el' => $hoy->copy()->addDays(5)->toDateString(),
                'estado' => EstadoTarea::EnProgreso,
            ],
            [
                'titulo' => 'Actualizar bitácora del expediente',
                'descripcion' => 'Registrar las últimas actuaciones y adjuntar notas internas.',
                'prioridad' => PrioridadTarea::Media,
                'vence_el' => $hoy->copy()->addDays(7)->toDateString(),
                'estado' => EstadoTarea::EnProgreso,
            ],
            [
                'titulo' => 'Archivar constancia de notificación',
                'descripcion' => 'Guardar la constancia en el expediente digital y marcar como notificado.',
                'prioridad' => PrioridadTarea::Baja,
                'vence_el' => $hoy->copy()->addDays(10)->toDateString(),
                'estado' => EstadoTarea::Completada,
                'completada_at' => $hoy->copy()->subDay(),
            ],
            [
                'titulo' => 'Coordinar agenda de noviembre',
                'descripcion' => 'Confirmar audiencias y vencimientos del mes con el equipo.',
                'prioridad' => PrioridadTarea::Baja,
                'vence_el' => $hoy->copy()->addDays(14)->toDateString(),
                'estado' => EstadoTarea::Pendiente,
            ],
        ];

        foreach ($tareas as $index => $tarea) {
            Tarea::query()->create([
                'despacho_id' => $despacho->getKey(),
                'expediente_id' => $expediente->getKey(),
                'asignada_a' => $abogada->getKey(),
                'orden' => $index,
                'creada_por' => $admin->getKey(),
                'titulo' => $tarea['titulo'],
                'descripcion' => $tarea['descripcion'],
                'vence_el' => $tarea['vence_el'],
                'prioridad' => $tarea['prioridad'],
                'estado' => $tarea['estado'],
                'completada_at' => $tarea['completada_at'] ?? null,
            ]);
        }
    }

    private function seedVolumen(Despacho $despacho, User $abogado, User $admin, User $asistente): void
    {
        $this->command?->info("Volumen para {$despacho->slug}…");

        $factor = $despacho->slug === 'bufete-ramirez' ? 1.0 : 0.35;
        $nClientes = (int) max(5, round(self::VOLUMEN_CLIENTES * $factor));
        $nExpedientes = (int) max(8, round(self::VOLUMEN_EXPEDIENTES * $factor));
        $nActuaciones = (int) max(20, round(self::VOLUMEN_ACTUACIONES * $factor));
        $nEventos = (int) max(10, round(self::VOLUMEN_EVENTOS * $factor));
        $nDocs = (int) max(15, round(self::VOLUMEN_DOCUMENTOS * $factor));
        $nTareas = (int) max(5, round(self::VOLUMEN_TAREAS * $factor));

        DB::transaction(function () use ($despacho, $nClientes): void {
            $batch = (int) ceil($nClientes / 10);
            for ($i = 0; $i < $nClientes; $i++) {
                if ($i % $batch === 0) {
                    Carbon::setTestNow(
                        Carbon::parse('2026-04-01', 'America/Costa_Rica')
                            ->addDays($i)
                            ->setTime(9, 0),
                    );
                }
                Cliente::factory()->create(['despacho_id' => $despacho->getKey()]);
            }
        });

        $clientes = Cliente::withoutGlobalScopes()
            ->where('despacho_id', $despacho->getKey())
            ->pluck('id');

        $users = [$abogado->getKey(), $admin->getKey(), $asistente->getKey()];

        DB::transaction(function () use ($despacho, $clientes, $users, $nExpedientes): void {
            for ($i = 0; $i < $nExpedientes; $i++) {
                Carbon::setTestNow(
                    Carbon::parse('2026-04-15', 'America/Costa_Rica')
                        ->addDays($i % 150)
                        ->setTime(10, 0),
                );

                Expediente::factory()->create([
                    'despacho_id' => $despacho->getKey(),
                    'cliente_id' => $clientes->random(),
                    'responsable_id' => fake()->randomElement($users),
                    'numero_interno' => sprintf('EXP-V-%s-%03d', $despacho->getKey(), $i + 1),
                    'estado' => fake()->randomElement(EstadoExpediente::cases()),
                ]);
            }
        });

        $expedientes = Expediente::withoutGlobalScopes()
            ->where('despacho_id', $despacho->getKey())
            ->pluck('id');

        DB::transaction(function () use ($despacho, $expedientes, $users, $nActuaciones): void {
            for ($i = 0; $i < $nActuaciones; $i++) {
                if ($i % 40 === 0) {
                    Carbon::setTestNow(
                        Carbon::parse('2026-05-01', 'America/Costa_Rica')
                            ->addDays((int) ($i / 2))
                            ->setTime(11, 0),
                    );
                }

                Actuacion::factory()->create([
                    'despacho_id' => $despacho->getKey(),
                    'expediente_id' => $expedientes->random(),
                    'user_id' => fake()->randomElement($users),
                ]);
            }
        });

        DB::transaction(function () use ($despacho, $expedientes, $users, $nEventos): void {
            for ($i = 0; $i < $nEventos; $i++) {
                Evento::factory()->create([
                    'despacho_id' => $despacho->getKey(),
                    'expediente_id' => $expedientes->random(),
                    'responsable_id' => fake()->randomElement($users),
                    'estado' => fake()->randomElement([
                        EstadoEvento::Pendiente,
                        EstadoEvento::Pendiente,
                        EstadoEvento::Cumplido,
                        EstadoEvento::Cancelada,
                    ]),
                ]);
            }
        });

        DB::transaction(function () use ($despacho, $expedientes, $users, $nDocs): void {
            for ($i = 0; $i < $nDocs; $i++) {
                $expId = $expedientes->random();
                $doc = Documento::factory()->create([
                    'despacho_id' => $despacho->getKey(),
                    'expediente_id' => $expId,
                    'estado' => fake()->randomElement(EstadoDocumento::cases()),
                ]);

                $uuid = (string) Str::uuid();
                $contenido = "%PDF-1.4\n%%EOF\n";
                $ruta = sprintf(
                    'despachos/%d/expedientes/%d/%s.pdf',
                    $despacho->getKey(),
                    $expId,
                    $uuid,
                );
                Storage::disk('local')->put($ruta, $contenido);

                DocumentoVersion::query()->create([
                    'despacho_id' => $despacho->getKey(),
                    'documento_id' => $doc->getKey(),
                    'version' => 1,
                    'ruta' => $ruta,
                    'disco' => 'local',
                    'mime' => 'application/pdf',
                    'tamano' => strlen($contenido),
                    'sha256' => hash('sha256', $contenido),
                    'user_id' => fake()->randomElement($users),
                ]);
            }
        });

        DB::transaction(function () use ($despacho, $expedientes, $abogado, $admin, $nTareas): void {
            for ($i = 0; $i < $nTareas; $i++) {
                $estado = fake()->randomElement([
                    EstadoTarea::Pendiente,
                    EstadoTarea::Pendiente,
                    EstadoTarea::EnProgreso,
                    EstadoTarea::Completada,
                ]);

                Tarea::factory()->create([
                    'despacho_id' => $despacho->getKey(),
                    'expediente_id' => $expedientes->random(),
                    'asignada_a' => $abogado->getKey(),
                    'creada_por' => $admin->getKey(),
                    'prioridad' => fake()->randomElement(PrioridadTarea::cases()),
                    'estado' => $estado,
                    'orden' => $i,
                    'completada_at' => $estado === EstadoTarea::Completada
                        ? now('America/Costa_Rica')
                        : null,
                ]);
            }
        });
    }

    /**
     * Fecha/hora real sin Carbon::setTestNow (para semáforos y vencimientos del demo).
     */
    private function hoyReal(): Carbon
    {
        $frozen = Carbon::getTestNow();
        Carbon::setTestNow();
        $hoy = Carbon::now('America/Costa_Rica');
        Carbon::setTestNow($frozen);

        return $hoy;
    }

    private function crearDocumentoConVersion(
        Despacho $despacho,
        Expediente $expediente,
        User $user,
        string $nombre,
        EstadoDocumento $estado,
    ): Documento {
        $documento = Documento::query()->create([
            'despacho_id' => $despacho->getKey(),
            'expediente_id' => $expediente->getKey(),
            'nombre' => $nombre,
            'version_actual' => 1,
            'estado' => $estado,
        ]);

        $uuid = (string) Str::uuid();
        $contenido = "%PDF-1.4\n1 0 obj<<>>endobj\ntrailer<<>>\n%%EOF\n";
        $ruta = sprintf(
            'despachos/%d/expedientes/%d/%s.pdf',
            $despacho->getKey(),
            $expediente->getKey(),
            $uuid,
        );
        Storage::disk('local')->put($ruta, $contenido);

        DocumentoVersion::query()->create([
            'despacho_id' => $despacho->getKey(),
            'documento_id' => $documento->getKey(),
            'version' => 1,
            'ruta' => $ruta,
            'disco' => 'local',
            'mime' => 'application/pdf',
            'tamano' => strlen($contenido),
            'sha256' => hash('sha256', $contenido),
            'user_id' => $user->getKey(),
            'nota' => 'Versión inicial demo',
        ]);

        return $documento;
    }

    private function imprimirResumen(): void
    {
        if ($this->command === null) {
            return;
        }

        $this->command->table(
            ['Tabla', 'Registros'],
            [
                ['despachos', Despacho::query()->count()],
                ['users', User::query()->count()],
                ['clientes', Cliente::withoutGlobalScopes()->count()],
                ['expedientes', Expediente::withoutGlobalScopes()->count()],
                ['actuaciones', Actuacion::withoutGlobalScopes()->count()],
                ['eventos', Evento::withoutGlobalScopes()->count()],
                ['documentos', Documento::withoutGlobalScopes()->count()],
                ['tareas', Tarea::withoutGlobalScopes()->count()],
                ['plantillas', Plantilla::withoutGlobalScopes()->count()],
                ['activity_log', DB::table('activity_log')->count()],
            ],
        );
    }
}
