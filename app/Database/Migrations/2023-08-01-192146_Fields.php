<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class Fields extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id' => [
                'type'           => 'INT',
                'constraint'     => 11,
                'unsigned'       => true,
                'auto_increment' => true,
            ],
            'name' => [
                'type'       => 'VARCHAR',
                'constraint' =>  100,
            ],
            'floor_type' => [
                'type'       => 'VARCHAR',
                'constraint' =>  250,
            ],
            'sizes' => [
                'type'       => 'VARCHAR',
                'constraint' =>  250,
            ],
            'ilumination' => [
                'type'       => 'BIT',
                'constraint' =>  1,
            ],
            'field_type' => [
                'type'       => 'VARCHAR',
                'constraint' =>  250,
            ],
            'roofed' => [
                'type'       => 'BIT',
                'constraint' =>  1,
            ],
            'value' => [
                'type'       => 'FLOAT',
                'constraint' =>  8,
            ],
            'ilumination_value' => [
                'type'       => 'FLOAT',
                'constraint' =>  8,
            ],
            'elements_rent' => [
                'type'       => 'BIT',
                'constraint' =>  1,
            ],
            'disabled' => [
                'type'       => 'BIT',
                'constraint' =>  1,
            ],
        ]);

        $this->forge->addKey('id', true);
        $this->forge->createTable('fields');
    }

    public function down()
    {
        $this->forge->dropTable('fields');
    }
}
