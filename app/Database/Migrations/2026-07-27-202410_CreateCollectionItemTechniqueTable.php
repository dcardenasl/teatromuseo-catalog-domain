<?php

declare(strict_types=1);

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateCollectionItemTechniqueTable extends Migration
{
    public function up(): void
    {
        $this->forge->addField([
            'collection_item_id' => [
                'type'       => 'INT',
                'constraint' => 11,
                'unsigned'   => true,
                'null'       => false,
            ],
            'technique_id' => [
                'type'       => 'INT',
                'constraint' => 11,
                'unsigned'   => true,
                'null'       => false,
            ],
            'created_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
        ]);

        $this->forge->addPrimaryKey(['collection_item_id', 'technique_id']);
        $this->forge->addKey('technique_id');

        $this->forge->addForeignKey('collection_item_id', 'collection_items', 'id', 'CASCADE', 'CASCADE');
        $this->forge->addForeignKey('technique_id', 'techniques', 'id', 'CASCADE', 'CASCADE');

        $this->forge->createTable('collection_item_technique');
    }

    public function down(): void
    {
        $this->forge->dropTable('collection_item_technique');
    }
}
