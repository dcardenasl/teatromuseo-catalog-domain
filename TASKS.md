# TASKS — teatromuseo-catalog-domain

> Trabajo abierto de este repositorio. Programa cross-repo:
> [`../TASKS.md`](../TASKS.md). Cierres históricos:
> [`TASKS_ARCHIVE.md`](TASKS_ARCHIVE.md).

## ✅ Completadas

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
