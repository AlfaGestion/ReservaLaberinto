<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateMercadoPagoKeys extends Migration
{
    public function up()
    {
        if ($this->db->tableExists('mercado_pago_keys')) {
            return;
        }

        $this->forge->addField([
            'id' => [
                'type' => 'INT',
                'constraint' => 11,
                'unsigned' => true,
                'auto_increment' => true,
            ],
            'public_key' => [
                'type' => 'VARCHAR',
                'constraint' => 255,
                'null' => true,
            ],
            'access_token' => [
                'type' => 'VARCHAR',
                'constraint' => 255,
                'null' => true,
            ],
        ]);

        $this->forge->addKey('id', true);
        $this->forge->createTable('mercado_pago_keys');
    }

    public function down()
    {
        if ($this->db->tableExists('mercado_pago_keys')) {
            $this->forge->dropTable('mercado_pago_keys');
        }
    }
}
