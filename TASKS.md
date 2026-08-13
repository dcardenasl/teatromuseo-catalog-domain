# TASKS — teatromuseo-catalog-domain

> Trabajo abierto de este repositorio. Programa cross-repo:
> [`../TASKS.md`](../TASKS.md). Cierres históricos:
> [`TASKS_ARCHIVE.md`](TASKS_ARCHIVE.md).

## ✅ Completadas

- [x] **QA-05 — `LISTING_FIELDS` sin `created_at`/`updated_at` (500 en vez de datos)** —
  cerrada 2026-08-13, ejecutando la Fase 0 de
  [`../docs/audits/2026-08-13-auditoria-carga-fria-web-domains.md`](../docs/audits/2026-08-13-auditoria-carga-fria-web-domains.md)
  (hallazgos C y D). `PublicReadController::LISTING_FIELDS` omitía `created_at`/`updated_at` aunque
  `DETAIL_FIELDS` ya los expone y `PUBLIC_COLUMNS`/`columnsFor()` en
  `PublicReadCollectionItemReader` ya los seleccionaba — no hacía falta tocar el `SELECT`, solo
  espejar el allowlist del controlador. Combinado con el fix de librería (`CORE-026` en
  `ci4-api-core`, que este repo consume vía symlink de path repo), la misma combinación de
  `fields=` que `teatromuseo-web` construye para las tarjetas de listado pasó de `500` genérico a
  `200` con los dos campos, y un campo realmente inválido pasa de `500` a `422` estructurado.
  Verificado con dos tests Feature nuevos en `PublicReadQueryBudgetTest.php`
  (`testListingWithCreatedAtUpdatedAtFieldsStaysWithinBudgetAndKeepsTheIndex` — confirma que sumar
  las dos columnas escalares no cambia el plan de `EXPLAIN` ni el presupuesto de `QA-02`, sigue
  usando `idx_collection_items_public_listing`; `testInvalidFieldsParamReturns422WithStructuredErrorsNot500`)
  y con `curl` real contra el servidor local en :8191 (200 y 422 confirmados). 274/274 tests,
  PHPStan 0 errores, CS-Fixer limpio, `swagger.json` regenerado sin diff (el contrato ya documentaba
  `422 — Invalid query`, no había un enum de campos que actualizar). `event-domain` no requería
  cambio de allowlist (sin gap funcional hoy) — confirmado con curl real contra :8193 que también
  pasó de 500 a 422 estructurado gracias solo al fix de `ci4-api-core`.

- [x] **PERF-03 — Retirar el N+1 de `PublicCollectionItemController::index()`
  y la ruta `GET public/catalog/collection-items`** — cerrada 2026-08-13,
  ejecutando §2.5/§2.F de
  [`../docs/audits/2026-08-12-auditoria-parte2-rendimiento-listados-publicos.md`](../docs/audits/2026-08-12-auditoria-parte2-rendimiento-listados-publicos.md).
  Verificado (agente de investigación dedicado): el `foreach` de `index()`
  llamaba `CollectionItemMediaResolutionService::resolveMediaFields()` por
  ítem (una llamada HTTP al Hub por ítem); cero consumidores en
  teatromuseo-web/bff/admin/totem confirmado también por timing de logs
  reales (`teatromuseo-web` migró el 2026-08-10, logs posteriores muestran
  0 hits a la ruta legacy). Cero tests cubrían `index()` (los 5 tests de
  `PublicCollectionItemControllerTest` golpean solo `show()`, que queda
  intacto — no era el patrón N+1 auditado, aunque también carece de
  consumidores confirmados; decisión explícita de no expandir el alcance).
  `CollectionItemMediaResolutionService` se conserva (sigue en uso por
  `show()`). Verificado: 272/272 tests, PHPStan 0 errores, CS-Fixer limpio,
  swagger.json regenerado.

- [x] **PERF-02 — Evaluar con EXPLAIN si falta índice compuesto para
  category_id/technique_id** — cerrada 2026-08-13. Pedido explícito de David,
  ejecutando §2.D de
  [`../docs/audits/2026-08-12-auditoria-parte2-rendimiento-listados-publicos.md`](../docs/audits/2026-08-12-auditoria-parte2-rendimiento-listados-publicos.md).
  `testFiltersSearchAndOrdersStayWithinTheReadBudget` solo tenía un fixture
  de 1 fila (sin señal real de selectividad); se agregó
  `testCategoryFilteredListingUsesAnIndexAtRealisticVolume` con 600 filas
  repartidas en 5 categorías (~20% selectividad). Medido: MySQL ya usa
  `collection_items_category_id_foreign` (el índice implícito de la FK,
  `type=ref`, sin full scan) de forma eficiente para ese filtro — no se
  agregó ningún índice nuevo, decisión basada en la medición, no por simetría
  con el índice agregado en event-domain (PERF-02 de ese repo). Verificado:
  272/272 tests, PHPStan 0 errores, CS-Fixer limpio.

- [x] **ADM-DASH-02 — Read model acotado para el dashboard Admin** — cerrada
  2026-08-11. Endpoint autenticado y permission-aware con conteos y actividad
  reciente bounded para colección, categorías y técnicas; contrato, OpenAPI y
  prueba de integración de columnas reales verificados.

- [x] **QA-01 — Contract tests y OpenAPI** — cerrada 2026-08-10. Contrato
  PublicRead Catalog, OpenAPI, auth, envelope, fallback, regresión CRUD y
  estados verificados. Evidencia en [`../docs/audits/2026-08-10-qa-01-contractos-openapi.md`](../docs/audits/2026-08-10-qa-01-contractos-openapi.md).
- [x] **QA-02 — EXPLAIN, índices y budgets SQL** — cerrada 2026-08-10. Listing
  medido con 600 fixtures, máximo 6 queries/500 ms SQL y el índice compuesto
  `idx_collection_items_public_listing`; sin N+1. Evidencia en
  [`../docs/audits/2026-08-10-qa-02-explain-indexes.md`](../docs/audits/2026-08-10-qa-02-explain-indexes.md).
- [x] **QA-03 — Carga fría/caliente/degradada y single-flight** — cerrada
  2026-08-10 como tarea raíz cross-repo; evidencia en
  [`../docs/audits/2026-08-10-qa-03-cache-concurrency.md`](../docs/audits/2026-08-10-qa-03-cache-concurrency.md).
- [x] **QA-04 — Paridad y shadow comparison** — cerrada 2026-08-10 como tarea
  raíz cross-repo; evidencia en
  [`../docs/audits/2026-08-10-qa-04-paridad-shadow.md`](../docs/audits/2026-08-10-qa-04-paridad-shadow.md).

## 🔴 En progreso

- [ ] **REL-01** — Activación controlada; pendiente de ventana de cutover y
  baseline/shadow del runtime anterior.

## 🟡 Próximo

### Plan vigente — PublicRead/PageDelivery/Snapshots (2026-08-09)

`PUB-00`, `PUB-01/02`, `CAT-01..03`, `SHARED-01` y `CACHE-03` están cerradas
y archivadas. Este repo participa ahora en:


Estas casillas reflejan la tarea raíz; no iniciar una rama paralela ni duplicar
la medición en este repositorio.

### Saneamiento arquitectónico heredado (prioridad 2)

- [ ] **CFG-04** — Extender las rutas analizadas por PHPStan a DTOs,
  repositorios y comandos, cuando el baseline pueda mantenerse en cero.
- [ ] **CORE-01b** — Normalizar nombres de campos de localización; requiere
  migración de datos cross-repo, no un cambio aislado de configuración.
- [ ] **CORE-06** — Unificar permisos solo con ventana de mantenimiento y
  migración explícita de `permissions`/`role_permissions` en el Hub.
- [ ] **MIG-02** — Añadir claves foráneas y decidir una convención única para
  estados de tablas localizadas y de dominio.
- [ ] **MIG-03** — Definir y añadir un camino de seed/bootstrap idempotente.
- [ ] **DOC-01** — Eliminar la deriva de nombres starter/builder en la
  documentación restante.

### Dependencias y conflictos

- `QA-02` quedó cerrada con los índices justificados por EXPLAIN; cualquier
  cambio posterior de `MIG-02` que afecte esos planes exige repetir la medición.
- `CORE-01b` y `CORE-06` afectan contratos compartidos; no ejecutarlos durante
  `QA-03/QA-04` ni durante el cutover.
- Los cierres de `CFG-02/03/07/08`, `CORE-01/02/03`, `DEAD-01` y PublicRead se
  trasladaron al archivo; se reabre un ID solo con evidencia nueva.

## 🏗️ Contratos de arquitectura

- Lecturas públicas separadas del CRUD administrativo, con fieldsets y envelopes
  versionados.
- Autenticación delegada al Hub; no decodificar JWT localmente.
- Permisos con `.` y no con `:`.
- Medios resueltos en batch; no hacer I/O por entidad durante el render público.
