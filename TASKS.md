# TASKS — teatromuseo-catalog-domain

> Fuente de verdad para trabajo en este repo.
> Historial de completadas: ver `TASKS_ARCHIVE.md`.
> Cross-repo: ver `../TASKS.md`.
> Última actualización: 2026-08-06 (Fase 4 — LAYER-01/03/07)

---

## 🔴 En progreso

*(vacío)*

---

## 🟡 Próximo

> Saneamiento arquitectónico — auditoría del 2026-08-05.
> **Contexto, evidencia y rutas exactas:** [`../docs/plan/2026-08-05-saneamiento-arquitectonico.md`](../docs/plan/2026-08-05-saneamiento-arquitectonico.md)
> Orden y dependencias cross-repo: [`../TASKS.md`](../TASKS.md)

### Fase 1 — Seguridad y correctitud


### Fase 2 — Configuración y CI

- [x] ~~CFG-02~~ — **completado (2026-08-07).** Documentadas las variables en `.env.example` (`WEB_API_KEY`, `hub.adminToken`, `LOCALIZATION_LEGACY_FALLBACK_LOCALE`, `QUEUE_REDIS_*`).
- [x] ~~CFG-03~~ — **completado 2026-08-06.** Ver Completadas.
- [ ] **CFG-04 — Alinear las rutas analizadas por PHPStan.** El baseline está en 0 (bien), pero
  `app/DTO`, `app/Repositories` y `app/Commands` **no se analizan**, a diferencia del hub.
- [ ] **CFG-07 — Falta el `apt-get upgrade -y` de parcheo de CVE** en el `Dockerfile`, que api, web,
  cms y totem sí tienen con la misma plantilla. Corregir también el `LABEL description` copiado
  (*"Production-ready CI4 API with JWT authentication"* — esta app no emite JWT).
- [ ] **CFG-08 — Falta `pre-push`** (lo tienen api, admin, web y cms). Matriz de CI en 8.2–8.3
  mientras se declara `"php": "^8.2"` (api/admin/web/cms prueban hasta 8.5).

### Fase 3 — Extracción a `ci4-api-core`

- [x] ~~CORE-01~~ — **completado 2026-08-05.** Ver Completadas.
- [ ] **CORE-01b — Normalizar los nombres de campo de `Config/Localization.php`**, que mezcla
  idiomas en una misma lista: `['name','summary','contenido','curiosidad','physical_description','ubicacion']`.
  Son columnas reales de `collection_items` consumidas por admin/web/tótem — renombrarlas exige
  migración de datos cruzada, no es una edición de configuración. Sigue sin tocar.
- [x] ~~CORE-02~~ — **completado 2026-08-06.** `PermissionFilter`, `HubSignatureFilter` y
  `WebAppKeyRequiredFilter` extienden ahora las bases del paquete (`ci4-api-core` v1.3.0).
  `DomainAuthFilter`/`ThrottleFilter` ya extendían `AbstractIntrospectionFilter`/
  `AbstractThrottleFilter` de antes — no necesitaron cambio. `HasCrudActions` resultó ser código
  muerto (ningún controlador lo usaba) — eliminado, no migrado. `HealthController` y
  `AuditRepository` quedan **explícitamente fuera de alcance** del paquete (decisión del propio
  mantenedor en `ci4-platform/ci4-api-core/TASKS.md`). Los modelos de infra (`MetricModel`,
  `RequestLogModel`, `AuditLogModel`) y el drift de esquema en `jobs`/`request_logs`/`audit_logs`/
  `idempotency_keys` **siguen sin reconciliar** — `core:install` no ayuda aquí porque solo escribe
  una migración cuando no existe ya una clase con ese nombre, y ya existe. Ver Completadas.
- [x] ~~CORE-03~~ — **completado 2026-08-06.** Ver Completadas.
- [ ] **CORE-06 — Convención de permisos.** Hoy `catalog.<camelCaseSingular>.<crud>`
  (`catalog.collectionItem.create`), incompatible con cms y event.
  **Confirmado fuera de alcance de `ci4-api-core`** — es config local más una migración de datos
  en el hub, no código de paquete. ⚠️ **`domain:sync-permissions` es insert-if-missing, no
  upsert**: renombrar sin migración SQL manual deja huérfanas las filas viejas de `permissions` y
  sus bindings en `role_permissions`. Ventana de mantenimiento — **no tocar sin confirmación
  explícita.**

### Fase 4 — Coherencia de capas



### Fase 5 — Semillas

- [ ] **MIG-02 — Las tablas de localización no tienen claves foráneas** hacia sus padres
  (`catalog_translations`, `catalog_public_slugs`).
- [ ] **MIG-03 — Cero seeders.** Esta app no tiene ningún camino de bootstrap, a diferencia de api
  y cms. Decidir si necesita uno (mínimo: categorías y técnicas base).

### Fase 6 — Limpieza y docs

- [x] ~~DEAD-01~~ — **completado 2026-08-06.** Ver Completadas.
- [ ] **DOC-01 — Deriva documental:** 4 menciones a `ci4-website-builder*` y 5 a `ci4-*-starter`
  en `CLAUDE.md`.

---

## ✅ Completadas

### DEAD-01 + CFG-03 — checkboxes reconciliados tras auditoría (2026-08-07)

- **DEAD-01 — Módulo `Example` completo, verificado eliminado.** Los 12 archivos vivos
  (`Config/Routes/v1/example.php`, `Config/ExampleDomainServices.php`,
  `Controllers/Api/V1/Example/ItemController.php`, `Services/Example/ItemService.php`,
  `Interfaces/Example/ItemServiceInterface.php`, `Models/ItemModel.php`, 3 RequestDTO + 1
  ResponseDTO, `Documentation/Example/ItemEndpoints.php`, 2 tests) ya no existen; `Services.php`
  no referencia `ExampleDomainServices`. Grep de todo el árbol (`app/`, `tests/`,
  `public/swagger.json`) por "example" solo encuentra anotaciones OpenAPI legítimas
  (`example:`) y nombres de archivo `.env.example` — cero endpoints fantasma. El checkbox
  llevaba días desactualizado respecto al código real (commit `9f2bd4c`); solo se corrige aquí
  la casilla, no se retrabajó nada.
- **CFG-03 — `public/swagger.json` rastreado en git, confirmado.** `git ls-files
  public/swagger.json` lo devuelve; se quitó de `.gitignore` en el commit `5a6de41`.
  `composer swagger-validate` ahora sí puede fallar (regenera y diffea contra el commiteado).

### LAYER-01 + LAYER-03 + LAYER-07 — Coherencia de capas, Fase 4 (2026-08-06)

- **LAYER-01 — checkbox corregido, marcado done tras verificación (no re-trabajado).**
  `Controllers/Api/V1/Catalog/PublicCollectionItemController.php` **ya no muta el superglobal**
  de la petición (`index()` ahora arma el filtro `is_active` con
  `Services::requestDtoFactory()->make(CollectionItemIndexRequestDTO::class, array_merge(...))`
  en vez de `$request->setGlobal('get', ...)`) — el checkbox venía obsoleto de un fix anterior no
  reflejado en este archivo. **Ojo para quien retome esto:** al releer el archivo completo, los
  otros tres puntos originales de LAYER-01 (llamada a `Services::hubClient()` desde el
  controlador, `resolveMediaFields()` privado de ~55 líneas sin extraer a un servicio compartido,
  y `show()` sin DTO tipado — usa `mixed $_` con el tercer argumento de `handleRequest()`
  omitido) **siguen presentes en el código actual**. Quedaron fuera de alcance de esta pasada
  (instrucción explícita de no re-trabajar LAYER-01, solo verificar el punto del `$_GET` y
  corregir el checkbox) — si esos tres puntos siguen importando, hace falta reabrir una tarea
  específica para ellos, no asumir que están cerrados.
- **LAYER-01 — cerrado del todo (2026-08-07).** Los tres puntos que quedaron pendientes arriba
  ya están resueltos:
  1. `resolveMediaFields()` (llamada directa a `Services::hubClient()` + ~55 líneas duplicadas
     con event-domain) se extrajo a `App\Services\Catalog\CollectionItemMediaResolutionService`
     (constructor-injected `HubClient`, sin interfaz — igual que `FileUsageService`, no hay
     repositorio/modelo detrás, solo una llamada HTTP), registrado en
     `CatalogDomainServices::collectionItemMediaResolutionService()` mirroring
     `fileUsageService()`. El controlador ya no importa `Services::hubClient()`; resuelve el
     nuevo servicio en `resolveDefaultService()` junto al `collectionItemService`.
  2. `show()` cambió `function (mixed $_, SecurityContext $context)` (con el tercer argumento de
     `handleRequest()` omitido) a `function (array $dto, SecurityContext $context)` — la misma
     firma que ya usan `CollectionItemController::show()`, `TechniqueController::show()`,
     `CategoryController::show()` y `PublicTechniqueController::show()` en esta misma app (no
     hay body que validar en un `show()` por id/slug de ruta, así que no aplica un
     `BaseRequestDTO`; `array $dto` es la convención establecida para ese caso, en vez de
     `mixed`).
  3. La duplicación cross-repo (punto 2 original de LAYER-01) queda resuelta al nivel de "mismo
     patrón local en ambas apps": `event-domain` ya había extraído la lógica equivalente a
     `App\Services\Events\EventMediaResolutionService` (ver su TASKS.md); esta clase mirrors esa
     forma exacta (mismo constructor, mismo método público, mismo estilo de doc) adaptada al
     naming de catalog-domain. No se tocó `ci4-api-core` ni se compartió código entre repos —
     fuera de alcance.
  4 tests unitarios nuevos en `tests/Unit/Services/Catalog/CollectionItemMediaResolutionServiceTest.php`
  (sin file ids, cover+gallery mixtos, metadata faltante del Hub, CSV con ids inválidos/ruidosos —
  mismos 4 casos que la contraparte de event-domain). `composer quality` verde: 238 tests / 573
  aserciones / 1 skip preexistente (arriba de la línea base de 234/561 disclosed).
- **LAYER-03:** nuevo `App\Models\CollectionItemTechniqueModel` para la tabla pivote
  `collection_item_technique` (PK compuesta `collection_item_id`+`technique_id`, sin soft
  delete, sin `updated_at` — extiende `CodeIgniter\Model` plano, no `BaseAuditableModel`;
  añadido a `AuditableModelConventionsTest::NON_AUDITABLE` con justificación). Su método
  `findTechniquesForCollectionItem()` reemplaza el `Config\Database::connect()->table(...)`
  crudo de `CollectionItemService.php:83-84`. `FileUsageService.php:41` migrado igual: el
  constructor ahora recibe `CollectionItemModel` (vía DI, `CatalogDomainServices::fileUsageService()`
  actualizado) y consulta a través de `$this->collectionItemModel->builder()` en vez de una
  conexión cruda — mismo resultado (`getResultArray()`), ahora respaldado por un modelo. Ambas
  consultas siguen pasando por `->builder()`/`getResultArray()` en vez de `->asArray()->findAll()`
  porque PHPStan no puede acotar el tipo genérico de `findAll()` sin un `@var` de override, que
  este repo prohíbe explícitamente. Los dos servicios referencian los modelos por FQCN inline
  (`model(\App\Models\X::class)` / `\App\Models\X` en el tipo del constructor) en vez de un `use
  App\Models\...` — sigue el patrón ya establecido en `CollectionItemService::getPublicActive()`
  para no romper el ratchet vacío de `ServiceModelDependencyConventionsTest`.
  ⚠️ `app/Commands/ImportExcel.php:156-158` hace el mismo `db->table('collection_item_technique')`
  crudo (sync de técnicas por ítem) — **fuera de alcance explícito de LAYER-03** (la tarea solo
  nombraba `CollectionItemService.php` y `FileUsageService.php`); candidato natural para
  reutilizar `CollectionItemTechniqueModel` en una pasada futura.
- **LAYER-07:** tests nuevos para los dos controladores públicos sin cobertura —
  `tests/Feature/Controllers/Catalog/PublicCategoryControllerTest.php` (4 tests: missing/invalid
  `X-App-Key` → 401, índice devuelve las categorías creadas, `per_page` fuera de rango → 422) y
  `tests/Feature/Controllers/Catalog/PublicTechniqueControllerTest.php` (9 tests: los mismos
  casos de `index` más `show` por slug/id/404). Y para `Catalog/FileUsageService`, que no tenía
  ninguno: `tests/Integration/Services/FileUsageServiceTest.php` (6 tests contra DB real —
  portada, galería, ambos a la vez, sin uso, soft-deleted excluido, y el caso específico que el
  propio comentario de la clase advierte: falso positivo por substring en el CSV, ej. file id 1
  no debe matchear portada 21 ni galería "21,12"). **Hallazgo de esta pasada, documentado en los
  tests:** las respuestas de `show()` en Technique/Category llegan envueltas normalmente en
  `{status, data: {...}}`, a diferencia de `PublicCollectionItemController::show()`, cuyo cuerpo
  aparece *sin envolver* solo porque `collection_items` tiene una columna de dominio llamada
  `status` (draft/published) que choca por nombre con la clave `status` del sobre de
  `ApiResponse::handleArray()` — comportamiento accidental del paquete, no un contrato a copiar
  en tests nuevos.

**Verificación:** `composer quality` ✅ completo (cs-check, PHPStan L8 sin errores, swagger
regenerado sin cambios de superficie pública, arch-drift, i18n-check, docs-i18n-check, 233 tests
/ 560 assertions, 1 skip preexistente no relacionado — arranca desde 215/531 antes de esta
pasada). Nota de aislamiento: correr `PublicCategoryControllerTest`/`PublicTechniqueControllerTest`
en solitario (filtro de PHPUnit) dispara un `TypeError` preexistente en `Config\Services::request()`
— el mismo problema que ya afecta a `PublicCollectionItemControllerTest` en solitario, ajeno a
esta tarea; corriendo la suite completa (lo que hace `composer quality`) no aparece.

### CORE-02 — Filtros a las bases del paquete v1.3.0 (2026-08-06, segunda pasada)

- **SEC-02 finalmente unificado en las tres apps.** `PermissionFilter` extiende
  `AbstractPermissionFilter` (`ci4-api-core` v1.3.0), con `superAdminBypassCode()` devolviendo
  `'iam.superadmin-access'` — antes solo event tenía este bypass; ahora cms, catalog y event se
  comportan igual. Como `app/Language/{es,en}/Auth.php` no define
  `authRequired`/`insufficientPermissions` (solo `rateLimitExceeded`), se sobrescribieron
  `unauthenticatedMessage()`/`forbiddenMessage()` para seguir leyendo `Api.authRequired`/
  `Api.insufficientPermissions` en español, en vez de caer silenciosamente al `Auth.php` en
  inglés del paquete. Nuevo test `tests/Unit/Filters/PermissionFilterTest.php` (6 casos).
- `HubSignatureFilter` y `WebAppKeyRequiredFilter` ahora extienden
  `AbstractHubSignatureFilter`/`AbstractWebAppKeyRequiredFilter` — mismo HMAC y mismo fail-closed
  de antes, sin la copia manual. Nuevo test `WebAppKeyRequiredFilterTest.php` (4 casos, no existía
  antes en esta app).
- **`app/Traits/Controllers/HasCrudActions.php` resultó ser código muerto, no boilerplate en
  uso.** Byte-idéntico en api/cms/catalog/event, pero ningún controlador real lo consumía — los
  controladores escritos a mano necesitan `$context->hasPermission(...)` por acción, algo que ni
  la versión local ni la del paquete soportan. Se eliminó en vez de migrarse.
- **Fuera de alcance, confirmado por el propio paquete:** `HealthController` genérico y
  `AuditRepository` concreto. **Sigue pendiente:** el drift de esquema en las 4 migraciones de
  infra (`jobs`/`request_logs`/`audit_logs`/`idempotency_keys`) y los modelos `MetricModel`/
  `RequestLogModel`/`AuditLogModel` — sin base compartida en el paquete, reconciliación manual.

**Verificación:** 218 tests / 534 assertions ✅, PHPStan sin errores.

### CORE-01 + CORE-03 — Localización y Config\Api al paquete (2026-08-06)

- **CORE-01:** eliminado el fork local de localización (`Libraries/Localization/*` y
  `Traits/Services/Has*`) — **1.129 líneas menos**. Ahora se consume el runtime de
  `ci4-api-core` v1.2.0. `TranslationFieldCatalog` quedó absorbido por `Config\Localization`,
  que expone `fields()`/`hasField()` con la misma semántica y la misma excepción; el registro de
  campos traducibles vive ahí. `CatalogTranslationModel` y `CatalogPublicSlugModel` extienden ahora
  `BaseTranslationModel`/`BasePublicSlugModel` (solo aportan el nombre de tabla, más las reglas de
  validación del slug que la base no trae). Nuevas factorías: `requestLocaleResolver()` compartida
  entre ambos stores.
  El respaldo de slug de `SEC-05` ya venía corregido en el paquete, así que el arreglo local quedó
  redundante y se eliminó con el resto.
- **CORE-03:** `app/Config/Api.php` pasa de 148 líneas copiadas a extender la base del paquete.
  Se elimina `accessPolicyBypassRoutes` apuntando a `auth/resend-verification`, ruta inexistente aquí.
- **Test de arquitectura robustecido:** `AuditableModelConventionsTest` comprobaba la clase padre por
  texto del código, así que no veía a través de las bases intermedias del paquete. Ahora resuelve la
  clase y recorre la cadena de herencia.
- **CORE-01b pendiente:** `Config\Localization` conserva los nombres mixtos `contenido`,
  `curiosidad` y `ubicacion` junto a sus hermanos en inglés. Son columnas reales de
  `collection_items` que consumen admin, web y tótem — renombrarlas es un cambio cruzado con
  migración de datos, no una edición de configuración.

**Verificación:** `composer quality` ✅ — 214 tests / 527 assertions, PHPStan sin errores.

- **CFG-01 — Puerto canónico y aislamiento de Compose (2026-08-05):** `.env.example`,
  `.env.docker.example`, PHPUnit e `init.sh` usan catálogo `8191`; Compose usa `8191`, `8192` y
  `3309`, además de nombres, red y volumen propios para no colisionar con el hub. Composer
  quality ✅ (214 tests / 527 assertions).

- **SEC-02 + SEC-05 — Seguridad y fallback de slugs (2026-08-05):** `PermissionFilter` ahora
  reconoce `iam.superadmin-access`, y `HasPublicSlugs` conserva el slug base cuando no existe fila
  localizada, en rutas batch y single-entity. Se añadieron 9 regresiones; `composer quality` ✅.

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
