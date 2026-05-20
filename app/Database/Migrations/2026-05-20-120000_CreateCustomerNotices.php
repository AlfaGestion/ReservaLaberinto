<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateCustomerNotices extends Migration
{
    public function up()
    {
        if ($this->db->tableExists('customer_notices')) {
            return;
        }

        $this->forge->addField([
            'id' => [
                'type'           => 'INT',
                'constraint'     => 10,
                'unsigned'       => true,
                'auto_increment' => true,
            ],
            'message' => [
                'type' => 'TEXT',
                'null' => false,
            ],
            'type' => [
                'type'       => 'VARCHAR',
                'constraint' => 20,
                'null'       => false,
            ],
            'date_from' => [
                'type' => 'DATE',
                'null' => false,
            ],
            'date_until' => [
                'type' => 'DATE',
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
        $this->forge->addKey('type');
        $this->forge->addKey('date_from');
        $this->forge->addKey('date_until');
        $this->forge->createTable('customer_notices');
    }

    public function down()
    {
        if ($this->db->tableExists('customer_notices')) {
            $this->forge->dropTable('customer_notices');
        }
    }
}
