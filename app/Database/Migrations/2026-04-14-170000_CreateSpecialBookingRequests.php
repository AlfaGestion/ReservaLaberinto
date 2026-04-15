<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateSpecialBookingRequests extends Migration
{
    public function up()
    {
        if ($this->db->tableExists('special_booking_requests')) {
            return;
        }

        $this->forge->addField([
            'id' => [
                'type'           => 'INT',
                'constraint'     => 10,
                'unsigned'       => true,
                'auto_increment' => true,
            ],
            'requested_date' => [
                'type' => 'DATE',
                'null' => true,
            ],
            'customer_id' => [
                'type'       => 'INT',
                'constraint' => 10,
                'unsigned'   => true,
                'null'       => true,
            ],
            'customer_name' => [
                'type'       => 'VARCHAR',
                'constraint' => 120,
                'null'       => false,
            ],
            'customer_last_name' => [
                'type'       => 'VARCHAR',
                'constraint' => 120,
                'null'       => true,
            ],
            'customer_phone' => [
                'type'       => 'VARCHAR',
                'constraint' => 60,
                'null'       => false,
            ],
            'customer_email' => [
                'type'       => 'VARCHAR',
                'constraint' => 150,
                'null'       => true,
            ],
            'customer_dni' => [
                'type'       => 'VARCHAR',
                'constraint' => 40,
                'null'       => true,
            ],
            'customer_city' => [
                'type'       => 'VARCHAR',
                'constraint' => 120,
                'null'       => true,
            ],
            'customer_type_institution' => [
                'type'       => 'VARCHAR',
                'constraint' => 120,
                'null'       => true,
            ],
            'time_from' => [
                'type'       => 'VARCHAR',
                'constraint' => 20,
                'null'       => true,
            ],
            'time_until' => [
                'type'       => 'VARCHAR',
                'constraint' => 20,
                'null'       => true,
            ],
            'visitors' => [
                'type'       => 'INT',
                'constraint' => 10,
                'null'       => false,
                'default'    => 0,
            ],
            'minimum_visitors' => [
                'type'       => 'INT',
                'constraint' => 10,
                'null'       => false,
                'default'    => 0,
            ],
            'total_amount' => [
                'type'       => 'DECIMAL',
                'constraint' => '10,2',
                'null'       => true,
            ],
            'request_message' => [
                'type' => 'TEXT',
                'null' => true,
            ],
            'status' => [
                'type'       => 'VARCHAR',
                'constraint' => 20,
                'default'    => 'new',
            ],
            'viewed_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
            'replied_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
            'replied_to_email' => [
                'type'       => 'VARCHAR',
                'constraint' => 150,
                'null'       => true,
            ],
            'reply_subject' => [
                'type'       => 'VARCHAR',
                'constraint' => 190,
                'null'       => true,
            ],
            'reply_message' => [
                'type' => 'TEXT',
                'null' => true,
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
        $this->forge->addKey('status');
        $this->forge->addKey('requested_date');
        $this->forge->addKey('customer_id');
        $this->forge->addKey('created_at');
        $this->forge->createTable('special_booking_requests');
    }

    public function down()
    {
        if ($this->db->tableExists('special_booking_requests')) {
            $this->forge->dropTable('special_booking_requests');
        }
    }
}
