# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

## [1.0.0] — 2026-09-01

### Removed

- **`/api/v1/public-read/{locale}/collection-items` and `/api/v1/public/catalog/categories`** —
  retired now that `teatromuseo-bff` reads this domain's database directly and serves the
  public-read surface exclusively; removed the controllers, DTOs, OpenAPI docs and their
  transitional shared-package dependency.

### Added

- **`POST /api/v1/catalog/sort-orders`** — atomic batch reorder for categories and
  techniques (up to 500 rows per request), replacing the Admin's per-row HTTP
  loop with one transactional `CASE`-based update gated by the resource's
  existing write permission.
- **`/api/v1/public-read/{locale}/collection-items` endpoints** — versioned envelope read model
  for public collection-item listing/detail, backed by a set-based query and batched Hub media
  resolution, gated by a dedicated public-read throttle bucket.
- **`HubClient::resolvePublicFileMeta()`** — chunks batches to the Hub's 200-id limit and falls
  back to a bounded stale cache when the Hub is unreachable, instead of dropping the miss set.
- **Localized technique metadata** — public technique responses now expose localized metadata
  consistently alongside the existing catalog content.

- **Localized catalog seed data and slug sync** — catalog seeders now ship multilingual content and keep public slugs synchronized for localized collection items.
- **Catalog translations & public slug store** — added `CatalogTranslationModel`, `CatalogPublicSlugModel`, and migrations `2026-07-28-050000_CreateCatalogTranslationsTable`, `2026-07-28-050100_CreateCatalogPublicSlugsTable`, `2026-07-28-050200_BackfillCatalogTranslationsAndSlugs` to support localized names/summaries and slug-based routing for collection items, categories, and techniques.
- **Public Collection Item API** — created `PublicCollectionItemController` and `PublicCatalogEndpoints` documentation to expose public collection items, categories, and techniques with i18n translation support and cover/gallery resolution.
- **`CategoryController` / `categories` endpoints** — CRUD module for catalog categories (name, slug, icon, sort order), scaffolded via `make-crud.sh`.
- **`TechniqueController` / `techniques` endpoints** — CRUD module for catalog techniques (name, slug, summary, video/pdf references), scaffolded via `make-crud.sh`.
- **`CollectionItemController` / `collection-items` endpoints** — CRUD module for museum collection items, with a required FK to `categories` and an N:M pivot (`collection_item_technique`) linking items to `techniques`.
- **`catalog:import-excel` command** — bulk-imports collection items from the museum's Excel template, resolving/creating categories and techniques by name and syncing the pivot table.
- **`/api/v1/public/catalog/*` endpoints** — app-key-gated read-only endpoints (categories, techniques, collection items) for `teatromuseo-web` to consume, with cover/gallery images resolved to Hub file metadata via `HubClient::resolvePublicFileMeta()`.
- **`internal/files/*` endpoints** — `HubSignatureFilter` + `InternalFileController` let the Hub
  check whether a file is referenced by a collection item (`cover_file_id`/`gallery_file_ids`)
  before deleting it, and invalidate this domain's cached file metadata after a replace, via HMAC-signed requests.

### Changed

- **Shared API core** — upgraded `dcardenasl/ci4-api-core` to `v1.1.1`.

- **Catalog metadata cache versioning** — file metadata cache keys now carry the shared hub
  version, preventing stale cross-version results.
- **Seed baseline** — demo catalog data and its seeded rows were removed from the domain baseline.

### Fixed

- **`PublicReadController::LISTING_FIELDS`** — added `created_at`/`updated_at`, mirroring
  `DETAIL_FIELDS`; the listing query already selected them, so requesting them via `?fields=` was
  returning a 500 instead of the data `teatromuseo-web` needs for listing cards.
- **Deterministic catalog slugs** — localized transliteration now has a stable fallback, and the
  Excel importer reuses the shared slug generator for consistent identifiers.

- **Localized updates and technique ordering** — translation writes now reuse the current record correctly, and related techniques keep a stable display order.
- **`CollectionItemUpdateRequestDTO`, `TechniqueUpdateRequestDTO`, `CategoryUpdateRequestDTO`, `ItemUpdateRequestDTO`** — update requests can now explicitly clear a nullable field to `null` instead of silently dropping it.
- **`DomainPermissions::PERMISSIONS`** — registered the `cms.category.*` / `cms.technique.*` /
  `cms.collectionItem.*` codes that `catalog.php` routes and `show()` permission checks already
  referenced; `domain:sync-permissions` now actually grants them, and `show()` no longer checks
  against a code (`category.read`, etc.) that was never registered.
- **`DomainPermissions::PERMISSIONS` / `catalog.php` routes / catalog controllers** — renamed the
  `cms.category.*` / `cms.technique.*` / `cms.collectionItem.*` permission codes to
  `catalog.category.*` / `catalog.technique.*` / `catalog.collectionItem.*` so this domain's own
  namespace is used instead of borrowing the CMS domain's prefix.
- **`CategoryResponseDTO` / `TechniqueResponseDTO` / `CollectionItemResponseDTO`** — `created_at`/
  `updated_at` are normalized to strings in `fromArray()` instead of passing through a `Time`
  object where the constructor declares `?string`.
