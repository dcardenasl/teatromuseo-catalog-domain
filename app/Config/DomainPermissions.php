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
        ['code' => 'cms.category.read', 'resource' => 'categories', 'action' => 'read', 'description' => 'Read museum categories'],
        ['code' => 'cms.category.create', 'resource' => 'categories', 'action' => 'create', 'description' => 'Create museum categories'],
        ['code' => 'cms.category.update', 'resource' => 'categories', 'action' => 'update', 'description' => 'Update museum categories'],
        ['code' => 'cms.category.delete', 'resource' => 'categories', 'action' => 'delete', 'description' => 'Delete museum categories'],

        // Techniques
        ['code' => 'cms.technique.read', 'resource' => 'techniques', 'action' => 'read', 'description' => 'Read museum techniques'],
        ['code' => 'cms.technique.create', 'resource' => 'techniques', 'action' => 'create', 'description' => 'Create museum techniques'],
        ['code' => 'cms.technique.update', 'resource' => 'techniques', 'action' => 'update', 'description' => 'Update museum techniques'],
        ['code' => 'cms.technique.delete', 'resource' => 'techniques', 'action' => 'delete', 'description' => 'Delete museum techniques'],

        // Collection Items
        ['code' => 'cms.collectionItem.read', 'resource' => 'collection-items', 'action' => 'read', 'description' => 'Read museum collection items'],
        ['code' => 'cms.collectionItem.create', 'resource' => 'collection-items', 'action' => 'create', 'description' => 'Create museum collection items'],
        ['code' => 'cms.collectionItem.update', 'resource' => 'collection-items', 'action' => 'update', 'description' => 'Update museum collection items'],
        ['code' => 'cms.collectionItem.delete', 'resource' => 'collection-items', 'action' => 'delete', 'description' => 'Delete museum collection items'],
    ];
}
