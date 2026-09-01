<?php

declare(strict_types=1);

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Stores localized content for Catalog Domain resources.
 *
 * Same contract as the event domain's event_translations: the CMS owns the
 * language catalog, but this domain deliberately stores the locale code
 * instead of a CMS language id. Domains have independent databases, and a
 * BCP-47-like code is the stable cross-domain contract.
 */
class CreateCatalogTranslationsTable extends Migration
{
    public function up(): void
    {
        $this->forge->addField([
            'id' => [
                'type'           => 'INT',
                'constraint'     => 11,
                'unsigned'       => true,
                'auto_increment' => true,
            ],
            'translatable_type' => [
                'type'       => 'VARCHAR',
                'constraint' => 80,
                'null'       => false,
            ],
            'translatable_id' => [
                'type'       => 'INT',
                'constraint' => 11,
                'unsigned'   => true,
                'null'       => false,
            ],
            'locale' => [
                'type'       => 'VARCHAR',
                'constraint' => 35,
                'null'       => false,
            ],
            'field' => [
                'type'       => 'VARCHAR',
                'constraint' => 80,
                'null'       => false,
            ],
            'value' => [
                'type' => 'MEDIUMTEXT',
                'null' => false,
            ],
            'created_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
            'updated_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
        ]);

        $this->forge->addKey('id', true);
        $this->forge->addUniqueKey([
            'translatable_type',
            'translatable_id',
            'locale',
            'field',
        ], 'uq_catalog_translations_resource_locale_field');
        $this->forge->addKey([
            'translatable_type',
            'translatable_id',
            'locale',
        ]);
        $this->forge->createTable('catalog_translations');
    }

    public function down(): void
    {
        $this->forge->dropTable('catalog_translations');
    }
}
