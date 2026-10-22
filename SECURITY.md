# SECURITY.md — Fase 8 (revisión final)

Demo **Despacho Legal CR**. Revisión contra plan §13 (OWASP) y checklist §19.
Fecha: 2026-10-21. Stack: Laravel 11.57 + Filament 3.3 + SQLite (local).

## Resumen ejecutivo

- No quedaron hallazgos **críticos** abiertos en código de aplicación.
- Quedan **riesgos residuales** documentados (advisories Laravel 11 sin backport a 11.x; npm en toolchain de build; SQLite/demo local).
- Tests de tenancy, policies y headers: **OK**.
- `composer qa`: ver sección final (resultado de la corrida de esta fase).

---

## 1. Auditorías de dependencias

### `composer audit` (2026-10-21)

4 advisories en `laravel/framework` **11.57.0** (última 11.x):

| Severidad | Título | Notas |
|-----------|--------|--------|
| high | CRLF injection en regla `email` por defecto | Afecta toda la línea 11.x; fix solo en ≥12.60 |
| medium | Temporary Signed URL Path Confusion | Fix en ≥12.61.1 |
| low | XSS en página de debug | Mitigado con `APP_DEBUG=false` en prod |
| (CVE-2026-48019) | CRLF email (duplicado del high) | Idem |

**Decisión:** no subir a Laravel 12 en esta fase (rompe el pin Filament v3 del demo). Mitigaciones:

- Producción: `APP_DEBUG=false` (cierra XSS de debug).
- Descargas firmadas: rutas propias + Policy + TTL corto (5 min); no confiar solo en la firma.
- Validación de emails de Filament / formularios propios además de la regla por defecto cuando aplique.

### `npm audit` (2026-10-21)

8 vulnerabilidades (3 moderate, 5 high) en cadena **Tailwind CSS 3** (`braces`, `chokidar`, `micromatch`, `postcss-selector-parser`).

- `npm audit fix --force` instalaría **Tailwind 4** → incompatible con el tema Filament v3 del proyecto.
- Son dependencias de **build** (Vite/Tailwind), no runtime PHP del panel.

**Decisión:** no forzar upgrade. Residual aceptado para el demo.

---

## 2. Búsqueda de patrones inseguros

| Patrón | Resultado |
|--------|-----------|
| `{!! !!}` en `resources/views` | Solo excepción justificada: `nl2br(e($contenido))` en `documentos/plantilla-pdf.blade.php` (test `BladeUnsafeEchoTest`) |
| `DB::raw` con input | No. Solo `selectRaw` / `orderByRaw` con literales fijos en widgets |
| `$guarded = []` | No (test `FillableModelsTest`) |
| `dd(` / `dump(` | No en `app/`, `routes/`, `resources/` |
| `env(` fuera de `config/` | No |

---

## 3. Tests tenancy / policies / headers

```
TenantIsolationTest, ExpedientePolicyTest, EventoTenantIsolationTest,
DashboardWidgetsTenantTest, SecurityHeadersTest, BladeUnsafeEchoTest,
FillableModelsTest → PASS
```

---

## 4. SecurityHeaders middleware

Archivo: `app/Http/Middleware/SecurityHeaders.php`

- CSP (compatible Filament: `unsafe-inline` / `unsafe-eval` documentados).
- `X-Frame-Options: DENY`, `nosniff`, `Referrer-Policy`, `Permissions-Policy`.
- **HSTS** solo si `APP_ENV=production` (evita romper local HTTP).
- Registrado globalmente en `bootstrap/app.php`.

---

## 5. Cookies / sesión (`.env.example`)

| Variable | Local (ejemplo) | Producción |
|----------|-----------------|------------|
| `SESSION_ENCRYPT` | `true` | `true` |
| `SESSION_HTTP_ONLY` | `true` | `true` |
| `SESSION_SAME_SITE` | `lax` | `lax` |
| `SESSION_SECURE_COOKIE` | `false` (HTTP local) | **`true`** (HTTPS) |
| `SESSION_LIFETIME` | `120` | `120` |

HTTPS forzado en producción: `URL::forceScheme('https')` en `AppServiceProvider` cuando `production`.

---

## 6. Logs (datos personales)

Revisión de `storage/logs/laravel.log`:

- Sin cédulas, teléfonos, direcciones ni prompts de IA.
- `OpenAiService` solo registra mensaje de conexión / status HTTP; **nunca** la API key ni el prompt.

---

## 7. Backups (`spatie/laravel-backup`)

### Config (`config/backup.php`)

- Fuente: código del proyecto (excluye `vendor`, `node_modules`) + dump de `DB_CONNECTION`.
- Disco destino: `local` (`storage/app/private/...` vía filesystem).
- Cifrado de archivo: `encryption => default` + `BACKUP_ARCHIVE_PASSWORD`.
- Retención: `keep_all_backups_for_days = 14` (alineado al plan).
- Scheduler (`routes/console.php`):
  - `backup:run` diario 02:00
  - `backup:clean` diario 03:00

### Procedimiento de restore (documentado; **no ejecutado restore real en esta fase**)

> No se realizó una restauración completa contra una base real en esta revisión.

1. Generar backup: `php artisan backup:run`
2. Listar: `php artisan backup:list`
3. Copiar el ZIP desde el disco de backups a un entorno de staging.
4. Si hay `BACKUP_ARCHIVE_PASSWORD`, descomprimir con esa clave.
5. Restaurar dump SQL/SQLite al destino (MySQL: `mysql ... < dump.sql`; SQLite: reemplazar el archivo `.sqlite` con la app detenida).
6. Restaurar archivos de `storage/app/private` desde el ZIP.
7. `php artisan config:clear && php artisan migrate --force` solo si el dump no trae el esquema completo.
8. Verificar login y un expediente demo.

En producción: cron `* * * * * cd /ruta-app && php artisan schedule:run >> /dev/null 2>&1`.

---

## 8. Producción (checklist operativo)

- [ ] `APP_ENV=production`
- [ ] `APP_DEBUG=false`
- [ ] `APP_URL=https://...`
- [ ] `SESSION_SECURE_COOKIE=true`
- [ ] MySQL (no SQLite) + backups cifrados
- [ ] Worker de colas: `php artisan queue:work` (o supervisor)
- [ ] Scheduler activo (cron arriba)
- [ ] `OPENAI_API_KEY` solo en entorno, nunca en repo
- [ ] `.env` fuera de git (ya en `.gitignore`)

---

## 9. Checklist §19 (marcado)

- [x] Policies en todos los modelos y acciones; filament-shield sincronizado.
- [x] Aislamiento de tenant probado (Resources, descargas, jobs).
- [x] `$fillable` explícito en todos los modelos.
- [x] Validación en todas las entradas (Filament rules o FormRequest).
- [x] CSRF activo; sin `{!! !!}` sin justificar; sin SQL crudo con input.
- [x] Archivos: mime y tamaño validados, disco privado, UUID, descarga autorizada o firmada.
- [x] Rate limits: login, IA y descargas.
- [x] Contraseñas con hash; política fuerte; 2FA disponible.
- [x] Cookies Secure/HttpOnly/SameSite documentadas; sesión encriptada; HTTPS forzado en prod; HSTS en prod.
- [x] CSP, X-Frame-Options, nosniff, Referrer-Policy y Permissions-Policy.
- [x] `APP_DEBUG=false` documentado para prod; `.env` fuera del repo; secretos en env.
- [x] Cédula, teléfono, dirección y respuesta de IA encriptados.
- [x] Activity log activo y sin datos personales en los logs de aplicación.
- [x] IA con minimización, defensa prompt injection y salida escapada.
- [ ] `composer audit` y `npm audit` **limpios** — residuales documentados (§1); no se forzó upgrade mayor.
- [ ] Backups automáticos **con restauración probada** — schedule + config OK; **restore real no ejecutado** (§7).

---

## 10. Correcciones hechas en esta fase

1. `SecurityHeaders`: HSTS solo en `production`; eliminado nonce CSP no usado.
2. `.env.example`: `SESSION_HTTP_ONLY`, `SESSION_SAME_SITE`, notas de Secure/prod/backup/queues.
3. `routes/console.php`: schedule `backup:run` / `backup:clean`.
4. `config/backup.php`: retención `keep_all` = 14 días.
5. `SecurityHeadersTest`: aserta CSP, Permissions-Policy y ausencia de HSTS fuera de prod.
6. Este `SECURITY.md`.

---

## 11. Riesgos residuales

1. **Laravel 11 advisories** (CRLF email, signed URL path confusion, XSS debug) — sin fix en 11.x; mitigados por config y diseño; upgrade a L12+ pendiente de migración Filament.
2. **npm / Tailwind 3** advisories de build — no runtime; fix force = Tailwind 4.
3. **SQLite** en demo local — no apto para multi-escritor / prod.
4. **`SESSION_SECURE_COOKIE=false`** en local HTTP — correcto para demo; obligatorio `true` en prod.
5. **CSP con `unsafe-inline`/`unsafe-eval`** — requerido por Filament/Livewire; reduce rigor XSS del CSP.
6. **Backup restore** no verificado end-to-end en esta corrida.
7. **URLs firmadas** (`temporarySignedRoute` en descargas) — advisory residual; compensado con Policy + TTL.

---

## 12. QA

`composer qa` (2026-10-21): **verde**

- Pint `--test`: passed  
- PHPStan (Larastan): OK, 0 errors  
- Pest: **52 passed** (433 assertions)
