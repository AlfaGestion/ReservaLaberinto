<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class MercadoPago extends Migration
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
            'collection_id' => [
                'type'           => 'VARCHAR',
                'constraint'     => 50,
            ],
            'collection_status' => [
                'type'           => 'VARCHAR',
                'constraint'     => 50,
            ],
            'payment_id' => [
                'type'           => 'VARCHAR',
                'constraint'     => 50,
            ],
            'status' => [
                'type'           => 'VARCHAR',
                'constraint'     => 11,
            ],
            'external_reference' => [
                'type'           => 'VARCHAR',
                'constraint'     => 50,
                'null'           => true,
            ],
            'payment_type' => [
                'type'           => 'VARCHAR',
                'constraint'     => 20,
            ],
            'merchant_order_id' => [
                'type'           => 'VARCHAR',
                'constraint'     => 50,
            ],
            'preference_id' => [
                'type'           => 'VARCHAR',
                'constraint'     => 250,
            ],
            'site_id' => [
                'type'           => 'VARCHAR',
                'constraint'     => 20,
            ],
            'processing_mode' => [
                'type'           => 'VARCHAR',
                'constraint'     => 20,
            ],
            'merchant_account_id' => [
                'type'           => 'VARCHAR',
                'constraint'     => 50,
                'null'           => true,
            ],
            'annulled' => [
                'type'       => 'BIT',
                'constraint' =>  1,
                'null' => true,
                'default' => 0,
            ],
            'id_booking' => [
                'type'           => 'INT',
                'constraint'     => 11,
                'unsigned'       => true,
            ],
        ]);

        $this->forge->addKey('id', true);
        $this->forge->addForeignKey('id_booking', 'bookings', 'id');
        $this->forge->createTable('mercado_pago');
    }

    public function down()
    {
        $this->forge->dropTable('mercado_pago');
    }
}
