<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class Booking extends Migration
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
            'id_field' => [
                'type'           => 'INT',
                'constraint'     => 11,
                'unsigned'       => true,
                'null' => true,
            ],
            'date' => [
                'type'       => 'DATE',
                'null' => true,
            ],
            'time_from' => [
                'type'       => 'VARCHAR',
                'constraint' =>  20,
                'null' => true,
            ],
            'time_until' => [
                'type'       => 'VARCHAR',
                'constraint' =>  20,
                'null' => true,
            ],
            'name' => [
                'type'       => 'VARCHAR',
                'constraint' =>  50,
                'null' => true,
            ],
            'phone' => [
                'type'       => 'VARCHAR',
                'constraint' =>  50,
                'null' => true,
            ],
            'total_payment' => [
                'type'       => 'BIT',
                'constraint' =>  1,
                'null' => true,
            ],
            'total' => [
                'type'       => 'FLOAT',
                'null' => true,
            ],
            'parcial' => [
                'type'       => 'FLOAT',
                'null' => true,
            ],
            'diference' => [
                'type'       => 'FLOAT',
                'null' => true,
            ],
            'reservation' => [
                'type'       => 'FLOAT',
                'null' => true,
            ],
            'payment' => [
                'type'       => 'FLOAT',
                'null' => true,
            ],
            'payment_method' => [
                'type'       => 'VARCHAR',
                'constraint' =>  50,
                'null' => true,
            ],
            'approved' => [
                'type'       => 'BIT',
                'constraint' =>  1,
                'null' => true,
            ],
            'use_offer' => [
                'type'       => 'BIT',
                'constraint' =>  1,
                'null' => true,
            ],
            'description' => [
                'type'       => 'VARCHAR',
                'constraint' =>  250,
                'null' => true,
            ],
            'annulled' => [
                'type'       => 'BIT',
                'constraint' =>  1,
                'null' => true,
                'default' => 0,
            ],
            'booking_time' => [
                'type'       => 'DATETIME',
                'null' => true,
            ],
            'id_customer' => [
                'type'           => 'INT',
                'constraint'     => 11,
                'unsigned'       => true,
                'null' => true,
            ],
            'id_preference_parcial' => [
                'type'           => 'VARCHAR',
                'constraint'     => 250,
            ],
            'id_preference_total' => [
                'type'           => 'VARCHAR',
                'constraint'     => 250,
            ],
        ]);

        $this->forge->addKey('id', true);
        $this->forge->addForeignKey('id_field', 'fields', 'id');
        $this->forge->addForeignKey('id_customer', 'customers', 'id');
        $this->forge->addUniqueKey('id');
        $this->forge->createTable('bookings');
    }

    public function down()
    {
        $this->forge->dropTable('bookings');
    }
}
