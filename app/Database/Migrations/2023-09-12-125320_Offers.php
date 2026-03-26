<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class Offers extends Migration
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
            'value' => [
                'type'           => 'INT',
                'constraint'     => 10,
            ],
            'description' => [
                'type'           => 'VARCHAR',
                'constraint'     => 500,
                'null'       => true,
            ],
            'expiration_date' => [
                'type'       => 'DATE',
                'null'       => true,
            ],
        ]);

        $this->forge->addKey('id', true);
        $this->forge->createTable('offers');
    }

    public function down()
    {
        $this->forge->dropTable('offers');
    }
}
