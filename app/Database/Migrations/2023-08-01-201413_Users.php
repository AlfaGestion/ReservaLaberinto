<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class Users extends Migration
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
            'user' => [
                'type'       => 'VARCHAR',
                'constraint' =>  100,
                'null'       => true,
            ],
            'password' => [
                'type'       => 'VARCHAR',
                'constraint' =>  255,
            ],
            'superadmin' => [
                'type'       => 'BIT',
                'constraint' =>  1,
                'null'       => true,
            ],
            'name' => [
                'type'       => 'VARCHAR',
                'constraint' =>  255,
                'null'       => true,
            ],
            'active' => [
                'type'       => 'BIT',
                'constraint' =>  1,
                'default'    => 1,
            ],
        ]);

        $this->forge->addKey('id', true);
        $this->forge->createTable('users');
    }

    public function down()
    {
        $this->forge->dropTable('users');
    }
}
