# TASKS_ARCHIVE — ci4-domain-starter

> Historial de tareas completadas. Movido desde TASKS.md para mantener el tracker activo liviano.
> Última actualización: 2026-05-07

## ✅ QA-01 — PublicRead y OpenAPI — cerrado 2026-08-10

Envelope versionado, `X-App-Key`, fallback, regresión CRUD y documentación
OpenAPI de Catalog verificados. Evidencia cross-repo en
[`../docs/audits/2026-08-10-qa-01-contractos-openapi.md`](../docs/audits/2026-08-10-qa-01-contractos-openapi.md).

## ✅ QA-02 — EXPLAIN, índices y budgets SQL — cerrado 2026-08-10

Listing Catalog medido con fixtures MySQL volumétricos, presupuesto de queries
y duración, EXPLAIN sobre la query real y regresión contra N+1. Se añadió
`idx_collection_items_public_listing` para cubrir filtros públicos y orden.
Evidencia en [`../docs/audits/2026-08-10-qa-02-explain-indexes.md`](../docs/audits/2026-08-10-qa-02-explain-indexes.md).

---

## ✅ Scaffold inicial + integración hub (Milestone domain-starter v0.1, 2026-05-07)

| ID | Descripción | Estado |
|---|---|---|
| DOM-001 | Scaffold base: clonado desde ci4-api-starter, eliminados módulos Auth/IAM/Users/Files/Identity/Admin. Agregados `Config\Hub`, `Config\DomainPermissions`, `HubClient`, `DomainAuthFilter` (alias `domainauth`), `SyncPermissions`, `Config\Scaffolding` override. Módulo Items de ejemplo generado con make-crud. PHPStan L8 limpio. | ✅ |
| DOM-002 | Integración end-to-end con hub: login → JWT → POST a domain → 201. Negative check: user sin permisos → 403. DomainAuthFilter llama `/auth/introspect` con `X-App-Key`, hub re-resuelve scope por `application_id`. | ✅ |
| DOM-003 | `domain:sync-permissions` rediseñado con `--admin-token` flag. `HubClient::registerPermission()` recibe bearer token explícito, corta en primer 401/403. `init.sh` actualizado para pedir JWT de setup. | ✅ |
| DOM-106 | README y README.es.md reescritos (~170 líneas). `docs/README.md` corregido. 12 docs de features del hub eliminados (stale clones). `docs/tech/jwt-auth` y `docs/architecture/AUTHENTICATION` reescritos como punteros al hub. | ✅ |

---

## ✅ Consumir base classes desde ci4-api-core (CORE-005, 2026-05-07)

24 archivos base eliminados de `app/`, 75 `use App\…` migrados a `dcardenasl\Ci4ApiCore\` vía sed batch. 3 architecture tests pure-core eliminados. PHPStan L8 + 202 tests verdes + CS-Fixer limpio. Smoke `make-crud Widget Demo` + `module:check` pasan.

---

## ✅ Consumo ci4-api-core v0.2.0 (2026-05-07)

Sin ID de tarea — trabajo derivado del runtime decoupling de ci4-api-core:
- Helpers procedurales, audit, HTTP filters, logging stack, mappers, support, `BaseRepository`, exception handlers, `Filterable`/`Searchable`/`QueryBuilder` consumidos desde `dcardenasl/ci4-api-core`
- `findByIds` implementado en `BaseRepository`
- Mapper acepta `object|array` (CORE-009)
- Fixtures de tests actualizados a imports de `dcardenasl/ci4-api-core`
- `composer.lock` regenerado

---

*TASKS_ARCHIVE · ci4-domain-starter · 2026-05-07*

---

## ✅ Cierres 2026-08-05..09 — saneamiento y Catalog PublicRead

- `CFG-02`, `CFG-03`, `CFG-07`, `CFG-08`, `CORE-01..03` y `DEAD-01` quedaron
  reconciliadas y verificadas en el ciclo de saneamiento.
- `CAT-01..03`, `SHARED-01`, `PUB-00`, `PUB-01/02` y `CACHE-03` se completaron
  dentro del plan de entrega pública. La verificación QA sigue abierta.
- `LAYER-01`, `LAYER-03` y `LAYER-07` ya no deben reaparecer como tareas activas;
  cualquier hallazgo nuevo debe abrir un ID nuevo con evidencia.

Los detalles y criterios de ambos planes están en los documentos enlazados desde
[`../TASKS.md`](../TASKS.md).
