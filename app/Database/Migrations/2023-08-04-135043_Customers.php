<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class Customers extends Migration
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
                'type'           => 'VARCHAR',
                'constraint'     => 50,
            ],
            'last_name' => [
                'type'           => 'VARCHAR',
                'constraint'     => 50,
            ],
            'dni' => [
                'type'       => 'VARCHAR',
                'constraint' => 20,
            ],
            'phone' => [
                'type'       => 'VARCHAR',
                'constraint' => 20,
            ],
            'city' => [
                'type'       => 'VARCHAR',
                'constraint' => 150,
            ],
            'offer' => [
                'type'       => 'BIT',
                'constraint' => 1,
                'default'    => 0,
                'null'       => true,
            ],
            'quantity' => [
                'type'       => 'INT',
                'constraint' => 50,
                'default'    => 0,
            ],
        ]);

        $this->forge->addKey('id', true);
        $this->forge->createTable('customers');
    }

    public function down()
    {
        $this->forge->dropTable('customers');
    }
}
