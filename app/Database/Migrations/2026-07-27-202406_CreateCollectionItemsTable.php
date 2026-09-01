<?php

declare(strict_types=1);

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateCollectionItemsTable extends Migration
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
            'name' => [
                'type' => 'VARCHAR',
                'constraint' => 255,
                'null' => false,
            ],
            'category_id' => [
                'type' => 'INT',
                'unsigned' => true,
                'null' => false,
            ],
            'inventory_code' => [
                'type' => 'VARCHAR',
                'constraint' => 255,
                'null' => true,
            ],
            'status' => [
                'type' => 'VARCHAR',
                'constraint' => 255,
                'null' => false,
            ],
            'summary' => [
                'type' => 'TEXT',
                'null' => true,
            ],
            'curiosidad' => [
                'type' => 'TEXT',
                'null' => true,
            ],
            'contenido' => [
                'type' => 'LONGTEXT',
                'null' => true,
            ],
            'origin' => [
                'type' => 'VARCHAR',
                'constraint' => 255,
                'null' => true,
            ],
            'period' => [
                'type' => 'VARCHAR',
                'constraint' => 255,
                'null' => true,
            ],
            'creator' => [
                'type' => 'VARCHAR',
                'constraint' => 255,
                'null' => true,
            ],
            'ubicacion' => [
                'type' => 'VARCHAR',
                'constraint' => 255,
                'null' => true,
            ],
            'materials' => [
                'type' => 'TEXT',
                'null' => true,
            ],
            'cover_file_id' => [
                'type' => 'BIGINT',
                'unsigned' => true,
                'null' => true,
            ],
            'gallery_file_ids' => [
                'type' => 'TEXT',
                'null' => true,
            ],
            'show_in_totem' => [
                'type' => 'TINYINT',
                'constraint' => 1,
                'default' => 0,
                'null' => false,
            ],
            'internal_notes' => [
                'type' => 'TEXT',
                'null' => true,
            ],
            'collection_number' => [
                'type' => 'VARCHAR',
                'constraint' => 255,
                'null' => true,
            ],
            'collection_group' => [
                'type' => 'VARCHAR',
                'constraint' => 255,
                'null' => true,
            ],
            'physical_description' => [
                'type' => 'TEXT',
                'null' => true,
            ],
            'dimensions' => [
                'type' => 'VARCHAR',
                'constraint' => 255,
                'null' => true,
            ],
            'ingress_type' => [
                'type' => 'VARCHAR',
                'constraint' => 255,
                'null' => true,
            ],
            'donated_by' => [
                'type' => 'VARCHAR',
                'constraint' => 255,
                'null' => true,
            ],
            'tags' => [
                'type' => 'TEXT',
                'null' => true,
            ],
            'links' => [
                'type' => 'TEXT',
                'null' => true,
            ],
            'company_history' => [
                'type' => 'TEXT',
                'null' => true,
            ],
            'is_active' => [
                'type' => 'TINYINT',
                'constraint' => 1,
                'default' => 1,
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
            'deleted_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
        ]);

        $this->forge->addKey('id', true);
        $this->forge->addKey('name');
        $this->forge->addUniqueKey('inventory_code');
        $this->forge->addForeignKey('category_id', 'categories', 'id', 'RESTRICT', 'CASCADE');
        $this->forge->createTable('collection_items');
    }

    public function down(): void
    {
        $this->forge->dropTable('collection_items');
    }
}
