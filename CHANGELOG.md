# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

### Added

- **Localized catalog seed data and slug sync** — catalog seeders now ship multilingual content and keep public slugs synchronized for localized collection items.
- **Catalog translations & public slug store** — added `CatalogTranslationModel`, `CatalogPublicSlugModel`, and migrations `2026-07-28-050000_CreateCatalogTranslationsTable`, `2026-07-28-050100_CreateCatalogPublicSlugsTable`, `2026-07-28-050200_BackfillCatalogTranslationsAndSlugs` to support localized names/summaries and slug-based routing for collection items, categories, and techniques.
- **Public Collection Item API** — created `PublicCollectionItemController` and `PublicCatalogEndpoints` documentation to expose public collection items, categories, and techniques with i18n translation support and cover/gallery resolution.
- **`CategoryController` / `categories` endpoints** — CRUD module for catalog categories (name, slug, icon, sort order), scaffolded via `make-crud.sh`.
- **`TechniqueController` / `techniques` endpoints** — CRUD module for catalog techniques (name, slug, summary, video/pdf references), scaffolded via `make-crud.sh`.
- **`CollectionItemController` / `collection-items` endpoints** — CRUD module for museum collection items, with a required FK to `categories` and an N:M pivot (`collection_item_technique`) linking items to `techniques`.
- **`catalog:import-excel` command** — bulk-imports collection items from the museum's Excel template, resolving/creating categories and techniques by name and syncing the pivot table.
- **`/api/v1/public/catalog/*` endpoints** — app-key-gated read-only endpoints (categories, techniques, collection items) for `teatromuseo-web` to consume, with cover/gallery images resolved to Hub file metadata via `HubClient::resolvePublicFileMeta()`.

### Fixed

- **Localized updates and technique ordering** — translation writes now reuse the current record correctly, and related techniques keep a stable display order.
- **`DomainPermissions::PERMISSIONS`** — registered the `cms.category.*` / `cms.technique.*` /
  `cms.collectionItem.*` codes that `catalog.php` routes and `show()` permission checks already
  referenced; `domain:sync-permissions` now actually grants them, and `show()` no longer checks
  against a code (`category.read`, etc.) that was never registered.
- **`CategoryResponseDTO` / `TechniqueResponseDTO` / `CollectionItemResponseDTO`** — `created_at`/
  `updated_at` are normalized to strings in `fromArray()` instead of passing through a `Time`
  object where the constructor declares `?string`.
