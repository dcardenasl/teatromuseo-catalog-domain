<?php

declare(strict_types=1);

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

final class AddPublicReadListingIndex extends Migration
{
    public function up(): void
    {
        $this->forge->addKey(
            ['is_active', 'status', 'deleted_at', 'name', 'id'],
            false,
            false,
            'idx_collection_items_public_listing',
        );
        $this->forge->processIndexes('collection_items');
    }

    public function down(): void
    {
        $this->forge->dropKey('collection_items', 'idx_collection_items_public_listing');
    }
}
