<?php

declare(strict_types=1);

namespace Config;

/**
 * Source of truth for the permissions exposed by this domain app.
 *
 * Add an entry here, then run:
 *
 *     php spark domain:sync-permissions
 *
 * to register them in the hub and attach them to superadmin. The command is
 * idempotent — pre-existing codes are left untouched.
 *
 * Permission codes use `.` as separator (NOT `:`) because CodeIgniter splits
 * filter arguments on `:` (`permission:foo:bar` would be parsed as filter=foo,
 * arg=[bar], silently dropping the rest).
 */
class DomainPermissions
{
    /**
     * @var list<array{code: string, resource: string, action: string, description?: string}>
     */
    public const PERMISSIONS = [
        // Categories
        ['code' => 'catalog.category.read', 'resource' => 'categories', 'action' => 'read', 'description' => 'Read museum categories'],
        ['code' => 'catalog.category.create', 'resource' => 'categories', 'action' => 'create', 'description' => 'Create museum categories'],
        ['code' => 'catalog.category.update', 'resource' => 'categories', 'action' => 'update', 'description' => 'Update museum categories'],
        ['code' => 'catalog.category.delete', 'resource' => 'categories', 'action' => 'delete', 'description' => 'Delete museum categories'],

        // Techniques
        ['code' => 'catalog.technique.read', 'resource' => 'techniques', 'action' => 'read', 'description' => 'Read museum techniques'],
        ['code' => 'catalog.technique.create', 'resource' => 'techniques', 'action' => 'create', 'description' => 'Create museum techniques'],
        ['code' => 'catalog.technique.update', 'resource' => 'techniques', 'action' => 'update', 'description' => 'Update museum techniques'],
        ['code' => 'catalog.technique.delete', 'resource' => 'techniques', 'action' => 'delete', 'description' => 'Delete museum techniques'],

        // Collection Items
        ['code' => 'catalog.collectionItem.read', 'resource' => 'collection-items', 'action' => 'read', 'description' => 'Read museum collection items'],
        ['code' => 'catalog.collectionItem.create', 'resource' => 'collection-items', 'action' => 'create', 'description' => 'Create museum collection items'],
        ['code' => 'catalog.collectionItem.update', 'resource' => 'collection-items', 'action' => 'update', 'description' => 'Update museum collection items'],
        ['code' => 'catalog.collectionItem.delete', 'resource' => 'collection-items', 'action' => 'delete', 'description' => 'Delete museum collection items'],
    ];
}
