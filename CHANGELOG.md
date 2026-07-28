# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

### Added

- **`CategoryController` / `categories` endpoints** — CRUD module for catalog categories (name, slug, icon, sort order), scaffolded via `make-crud.sh`.
- **`TechniqueController` / `techniques` endpoints** — CRUD module for catalog techniques (name, slug, summary, video/pdf references), scaffolded via `make-crud.sh`.
- **`CollectionItemController` / `collection-items` endpoints** — CRUD module for museum collection items, with a required FK to `categories` and an N:M pivot (`collection_item_technique`) linking items to `techniques`.
- **`catalog:import-excel` command** — bulk-imports collection items from the museum's Excel template, resolving/creating categories and techniques by name and syncing the pivot table.
- **`/api/v1/public/catalog/*` endpoints** — app-key-gated read-only endpoints (categories, techniques, collection items) for `teatromuseo-web` to consume, with cover/gallery images resolved to Hub file metadata via `HubClient::resolvePublicFileMeta()`.
