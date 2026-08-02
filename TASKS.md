# TASKS — ci4-domain-starter

> Fuente de verdad para trabajo en este repo.
> Historial de completadas: ver `TASKS_ARCHIVE.md`.
> Cross-repo: ver `../TASKS.md`.
> Última actualización: 2026-08-02 (CAT-DOM-003 ✅ completado — eliminado el seeder/datos de ejemplo)

---

## 🔴 En progreso

*(vacío)*

---## 🟡 Próximo

*(vacío)*

---

## ✅ Completadas

### CAT-DOM-003 — Eliminar el seeder de ejemplo y sus datos (2026-08-02)
- **Qué**: David pidió que catalog-domain solo muestre lo que efectivamente viene de la BD
  legacy de teatromuseo.cl. Confirmado con `legacy_migration_map` (tabla de control del hub):
  **cero** filas con `target_system='catalog-domain'` — nunca se migró nada real a este
  dominio. Los únicos 4 `collection_items` existentes ("Telón Azul", "Traje de Gala", "Máscara
  de Lino", "Programa de Temporada") junto con sus categorías y técnicas eran 100% del seeder
  `TeatroMuseoCatalogSeeder` ("Seeds representative museum catalog data"), que `init.sh`
  corría en cada instalación nueva.
- **Fix**: los 4 `collection_items` + 3 `categories` + 4 `techniques` borrados vía
  `DELETE /api/v1/catalog/{collection-items,categories,techniques}/{id}` (soft-delete).
  `TeatroMuseoCatalogSeeder.php` eliminado del todo; su llamada en `init.sh` removida.
- **Consecuencia esperada**: `/museo/colección` en el sitio público queda vacío hasta que se
  defina qué contenido legacy real (si existe) debería vivir aquí — confirmado con David antes
  de ejecutar.
- **Verificado**: `composer quality` ✅ (205 tests, 1 skip preexistente no relacionado,
  PHPStan sin errores). Página pública verificada en vivo: renderiza correctamente el estado
  vacío, sin errores.

### CAT-DOM-002 — Fix "no se puede limpiar un campo nullable vía update" en las 4 *UpdateRequestDTO (2026-07-30)
- **Qué**: `CollectionItemUpdateRequestDTO` (24 campos, la mayoría metadata opcional nullable —
  el más afectado del monorepo), `TechniqueUpdateRequestDTO`, `CategoryUpdateRequestDTO`,
  `ItemUpdateRequestDTO` (Example, scaffolding demo sin tabla real, corregido igual por
  consistencia). Mismo bug que en event-domain: `array_filter($v !== null)` descartaba cualquier
  campo enviado como `null`. Corregido con ternario de una línea por propiedad +
  `array_key_exists()` + acumulador `$mappedFields`, NOT NULL vs nullable decidido por `DESCRIBE`
  real (`name`/`category_id`/`status`/`show_in_totem`/`is_active` protegidos; el resto —
  incluidos `cover_file_id`/`gallery_file_ids` — ahora sí se pueden limpiar).
- **Verificado**: `composer quality` ✅ (205/205 tests, PHPStan sin errores).

### CAT-FILE-GUARD-001 — Endpoints internal/files/* para el Hub (usage-check + invalidate-cache) (2026-07-30)
- **Qué**: `App\Filters\HubSignatureFilter` (alias `hubsignature`) verifica llamadas HMAC del
  Hub (`hub.internalSecret`/env `HUB_INTERNAL_SECRET`, fail-closed). Nuevo
  `App\Services\Catalog\FileUsageService::getUsagesByHubFileId()` (no existía en este domain,
  a diferencia de cms-domain) — prefiltra por SQL (`cover_file_id` o `LIKE` sobre
  `gallery_file_ids`) y verifica membresía CSV exacta en PHP para evitar falsos positivos por
  substring (file 1 no debe matchear "21" ni "12,1"). `InternalFileController::usage()`/
  `invalidateCache()` bajo `internal/files/*`, extendiendo `\CodeIgniter\Controller` (no
  `ApiController`) — excepción documentada en `ControllerDtoRequestContractsTest`.
- **Por qué**: el Hub no veía usages de `collection_items.cover_file_id/gallery_file_ids` antes
  de borrar un archivo, y `HubClient::invalidateFileMetaCache()` era dead code.
- **Verificado**: end-to-end real contra el Hub (409 en delete de archivo en uso, invalidación
  reflejada sin TTL tras `replace()`), `composer quality` ✅ (205/205 tests).

### CAT-DOM-001 — Multi-idioma (port del event-domain) + slugs públicos por locale (2026-07-28)
- **Qué**: port 1:1 del stack de localización del event-domain — `catalog_translations` (EAV
  locale-agnóstico), `LocalizedTranslationStore`, `TranslationFieldCatalog`
  (`collection_item`: name/summary/contenido/curiosidad/physical_description/ubicacion;
  `category`: name/short_description; `technique`: name/summary), trait
  `HasLocalizedTranslations`, `RequestLocaleResolver`, `SlugGenerator` y `PublicSlugStore` +
  trait `HasPublicSlugs` (compartido con el event-domain; extraerlos a ci4-api-core queda en
  backlog). `catalog_public_slugs` con UNIQUE `(type, locale, slug)`. `CollectionItemService`
  genera slugs estables por locale desde `name` (manual override vía key `slug` en
  `translations`); `getPublicActive()` ahora resuelve id → inventory_code (compat) → slug por
  locale. DTOs de respuesta exponen `translations`/`localized` (+`slug`/`slugs` en items) y los
  Request DTOs aceptan `translations` — todo aditivo, campos legacy intactos. Backfill de
  traducciones y slugs para datos existentes; seeder sincroniza slugs idempotente. OpenAPI de
  los endpoints públicos agregado (no existía).
- **Por qué**: el sitio web generaba slugs cliente-side desde el nombre que el backend no podía
  resolver (404 para items sin inventory_code) y el catálogo no tenía contenido multi-idioma,
  a diferencia del event-domain. Ahora ambos dominios comparten el mismo contrato de idiomas
  dinámicos del CMS (locale codes BCP-47, sin dependencia domain→domain).
- **Verificado**: `composer quality` ✅ completo (PHPStan L8, CS-Fixer, swagger, arch-drift,
  i18n-check, 205 tests / 512 assertions); migrate → rollback → re-migrate limpio; backfill y
  seeder generan slugs reales en la BD dev.

### DOM-110 — Automatización de Sync de Permisos en Desarrollo (DX)
- Modificar `app/Commands/SyncPermissions.php` para resolver automáticamente el token de administración en local usando la DB de IAM local en desarrollo.
- Implementar borrado automático de caché (`cache:clear`) al terminar la sincronización local.

### DOM-111 — Documentación de Arquitectura de Seguridad
- Agregar `docs/architecture/permissions.md` detallando el flujo de permisos cruzados y la caché de introspección.

### DOM-109 — `domain:sync-permissions`: fail-loud + HubClient role lookup fix (KICK-027)
- **Qué**: (1) `HubClient::findRoleByCode` ahora parsea `{items:[...]}` en vez de `$data[0]` — el API devuelve una colección paginada, no un array plano; (2) `SyncPermissions` ahora termina con `exit≠0` cuando `--assign-to-role` está seteado pero el rol no se encontró/enlazó (`$roleLinkFailed` flag); (3) composer.lock actualizado a ci4-api-core v0.9.3 que incluye `registerPermission(3 params)` para reenviar `applicationId` correctamente; (4) tests añadidos en `HubClientTest` y `SyncPermissionsTest` para los dos behaviors corregidos.
- **Por qué**: en el POC E2E (2026-06-03) `domain:sync-permissions --assign-to-role superadmin` reportaba éxito pero no enlazaba nada al rol: (a) `findRoleByCode` retornaba null porque intentaba `$data[0]` sobre un `{items:[...]}` paginado, y (b) la firma de 2 parámetros en la versión bloqueada de api-core descartaba el `application_id` del mirror.
- **Verificado**: PHP lint limpio, bash -n limpio, tests pasan.

### DOM-103 — `php spark domain:doctor` diagnóstico del hub
- **Qué**: se añadió `php spark domain:doctor` para auditar el enlace del domain starter con el hub. El comando reporta tres checks: `service-token`, `introspect` cuando se pasa `--token`, y `register-permission` cuando se pasa `--admin-token`. El probe de registro usa un payload inválido a propósito para mantenerse read-only y solo validar reachability/autenticación.
- **Por qué**: la tarea pedía un diagnóstico operativo que ayudara a detectar problemas de conectividad y auth sin tener que lanzar manualmente varios comandos de setup.
- **Verificado**: `vendor/bin/phpunit tests/Unit/Commands/DoctorTest.php --testdox --no-coverage` ✅ (2 tests, 17 assertions).

### DOM-105 — Strip `AuthTokenSchema` leftover (2026-05-26)
- **Qué**: se eliminó `app/Documentation/Common/AuthTokenSchema.php`, un leftover heredado del clone de `ci4-api-starter` que ya no correspondía al contrato actual del domain starter. Durante la verificación también se tipó `app/Services/Example/ItemService.php` con el genérico `ItemEntity` y se regeneró `public/swagger.json` para aceptar la salida real del generador OpenAPI.
- **Por qué**: el archivo referenciaba un schema inexistente y hacía más frágil la validación OpenAPI del repo sin aportar valor funcional. El ajuste de generics cerró un drift de PHPStan que apareció al correr `composer quality`.
- **Verificado**: `composer quality` limpio en el repo (PHPStan, CS-Fixer, OpenAPI y PHPUnit).

### DOM-108 — Onboarding desatendido y vinculación de roles (2026-05-25)
- **Qué**: `init.sh` ahora acepta `--assign-to-role=ID|code` y lo pasa a `domain:sync-permissions`. `HubClient` captura `ValidationException` para tratar 422 como éxito idempotente. `init.sh` corre `php spark core:install` automáticamente.
- **Por qué**: (Bulletproof V2) Permitir despliegues 100% automáticos desde el orquestador, vinculando nuevos permisos al rol `superadmin` sin intervención manual. Garantizar que el runtime del core esté listo tras el bootstrap.
- **Verificado**: `php -l` limpio. Scripts probados en flujo de kickstart.

### DOM-107 — Patrón de aggregate extension documentado
- **Qué**: `docs/architecture/EXTENSION_GUIDE.{md,es.md}` ahora documenta cuándo `make:crud` deja de alcanzar y cómo evolucionar el módulo generado hacia un aggregate con custom actions, nested resources, relation sync y response enrichment. `README.md` y `docs/README.md` enlazan explícitamente ese patrón.
- **Por qué**: la auditoría del bootstrap `ci4-catalog` mostró que el problema no era solo generar menos código, sino no tener una guía canónica para el salto desde CRUD plano a aggregate real.
- **Verificado**: documentación enlazada desde los entry points principales del repo (`README.md`, `docs/README.md`) y alineada con el playbook de scaffolding existente.

### DOM-106 — Paridad `boolean_like` con el scaffolder
- **Qué**: `App\Validations\Rules\CustomRules` ahora implementa `boolean_like()` con el mismo contrato esperado por `ci4-api-scaffolding`: acepta bools, `0/1`, y strings `true/false/yes/no/on/off` de forma case-insensitive. Se añadieron los strings de validación en `app/Language/en/Validation.php` y `app/Language/es/Validation.php`.
- **Por qué**: el scaffolder emite `boolean_like` para fields `bool`, pero `ci4-domain-starter` no exponía esa regla. Eso rompía CRUDs generados con booleanos y obligaba a parchear DTOs/modelos a mano.
- **Verificado**: `vendor/bin/phpunit tests/Unit/Validations/CustomRulesTest.php --configuration=phpunit.xml --no-coverage --testdox` ✅ (10 tests, 28 assertions).

### BFF-107 — Refactor `HubClient` sobre `AbstractServiceClient`
- **Qué**: `app/Libraries/Hub/HubClient.php` pasó de 220 a 155 líneas extendiendo `dcardenasl\Ci4ApiCore\Http\Client\AbstractServiceClient`. Paths del hub movidos a `Config\Hub::$introspectPath/$serviceTokenPath/$permissionsPath`. `RuntimeException` reemplazado por `ServiceUnavailableException`/`AuthenticationException`/`AuthorizationException` canónicas. `registerPermission()` ahora trata 422 igual que 409 como duplicado idempotente. Heredada gratis: propagación de `X-Request-Id`, retry 1× en 5xx/network, allow-list de headers en `forward()`.
- **Por qué**: eliminar drift entre los dos `HubClient.php` (BFF-102 hizo el mismo refactor en el BFF). Cualquier ajuste futuro a timeout/retry/headers se hace una vez, en el core.
- **Verificado**: `DomainAuthFilter` consume `HubClient::introspect()` que mantuvo su firma (devuelve `IntrospectResult`) — cero cambios necesarios en el filter. `composer quality` limpio en domain (PHPStan L8 + CS-Fixer + 145 tests / 353 assertions). 10 tests nuevos en `HubClientTest` (cache hit, refresh, 5xx con retry, introspect downgrade, registerPermission idempotente, 401/403 → excepciones canónicas).
- **Cross-repo**: ver `../TASKS.md` milestone "ci4-bff-starter v1.1".

### DOM-102 — ADR-001: Hub-Domain Split Architecture (2026-05-26)
- **Qué**: Documentación centralizada en `TASKS.md` y `README.md` sobre la delegación de autenticación, propiedad de permisos y la prohibición explícita de tablas de usuarios en dominios.
- **Por qué**: Establecer la arquitectura canónica para evitar deuda técnica al escalar dominios.
- **Verificado**: Arquitectura documentada en "Contratos de arquitectura".

### DOM-101 — Suite de Smoke tests (2026-05-26)
- **Qué**: Implementación de tests críticos (`DomainAuthFilterTest`, `HubClientTest`, `CreateItemTest`) garantizando la integridad del flujo principal.
- **Por qué**: Asegurar que la delegación de auth y la comunicación con el hub son robustas antes de despliegue.
- **Verificado**: Suite de 145 tests / 353 assertions activa y pasando en `composer quality`.

---

## ⚪ Backlog

*(vacío)*

## 🏗️ Contratos de arquitectura

- **DTO-First:** todo Controller in/out usa DTOs. Request DTOs extienden `BaseRequestDTO`. Nunca arrays raw.
- **Services puros:** no conocen HTTP. Reciben DTOs, devuelven DTOs o lanzan excepciones de dominio.
- **Controllers delgados:** usar `ApiController::handleRequest()`. Sin lógica de negocio.
- **Separador de permisos:** punto `.` (NO `:`).
- **Hub delegation:** nunca validar JWTs localmente. Siempre `HubClient::introspect()`.
- **No tabla users:** si estás agregando una migración de usuarios, para — esos datos viven en el hub.
- **Rutas por dominio:** `app/Config/Routes/v1/<dominio>.php`.
- **Tests:** todo endpoint nuevo necesita al menos un Feature test (o waiver explícito en TASKS.md).
- **`composer cs-fix` antes de commitear.** No bypasear el pre-commit hook con `--no-verify`.

### 🚧 Technical Debt (Orchestration)
- [x] **Clean .env Management**: Migrate init.sh from appending to .env to using bootstrap_env.php to prevent duplicate keys. ✅ (Verificado en Bulletproof V2)
- [x] **Permission Assignment**: Add --assign-to-role=superadmin option to domain:sync-permissions to automate linking new permissions. ✅ 2026-05-25
