<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddEmailToBookings extends Migration
{
    public function up()
    {
        if (! $this->db->fieldExists('email', 'bookings')) {
            $this->forge->addColumn('bookings', [
                'email' => [
                    'type' => 'VARCHAR',
                    'constraint' => 190,
                    'null' => true,
                    'after' => 'phone',
                ],
            ]);
        }
    }

    public function down()
    {
        if ($this->db->fieldExists('email', 'bookings')) {
            $this->forge->dropColumn('bookings', 'email');
        }
    }
}
