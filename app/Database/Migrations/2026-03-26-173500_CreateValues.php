<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateValues extends Migration
{
    public function up()
    {
        if ($this->db->tableExists('service_values')) {
            return;
        }

        $this->forge->addField([
            'id' => [
                'type' => 'INT',
                'constraint' => 11,
                'unsigned' => true,
                'auto_increment' => true,
            ],
            'name' => [
                'type' => 'VARCHAR',
                'constraint' => 255,
                'null' => false,
            ],
            'value' => [
                'type' => 'VARCHAR',
                'constraint' => 255,
                'null' => false,
            ],
            'amount' => [
                'type' => 'FLOAT',
                'null' => false,
            ],
            'extra_amount' => [
                'type' => 'FLOAT',
                'null' => true,
            ],
            'disabled' => [
                'type' => 'TINYINT',
                'constraint' => 1,
                'default' => 0,
                'null' => false,
            ],
        ]);

        $this->forge->addKey('id', true);
        $this->forge->createTable('service_values');
    }

    public function down()
    {
        if ($this->db->tableExists('service_values')) {
            $this->forge->dropTable('service_values');
        }
    }
}
