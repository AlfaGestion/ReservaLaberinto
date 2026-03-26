<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class Time extends Migration
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
            'from' => [
                'type'       => 'VARCHAR',
                'constraint' =>  10,
            ],
            'until' => [
                'type'       => 'VARCHAR',
                'constraint' =>  10,
            ],
            'from_cut' => [
                'type'       => 'VARCHAR',
                'constraint' =>  10,
                'null'       => true,
            ],
            'until_cut' => [
                'type'       => 'VARCHAR',
                'constraint' =>  10,
                'null'       => true,
            ],
            'nocturnal_time' => [
                'type'       => 'VARCHAR',
                'constraint' =>  10,
                'null'       => true,
            ],
        ]);

        $this->forge->addKey('id', true);
        $this->forge->createTable('time');
    }

    public function down()
    {
        $this->forge->dropTable('time');
    }
}
