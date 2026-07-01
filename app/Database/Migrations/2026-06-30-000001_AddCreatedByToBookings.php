<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddCreatedByToBookings extends Migration
{
    public function up()
    {
        $fields = [];

        if (! $this->db->fieldExists('created_by_type', 'bookings')) {
            $fields['created_by_type'] = [
                'type' => 'VARCHAR',
                'constraint' => 20,
                'null' => true,
                'after' => 'phone',
            ];
        }

        if (! $this->db->fieldExists('created_by_name', 'bookings')) {
            $fields['created_by_name'] = [
                'type' => 'VARCHAR',
                'constraint' => 120,
                'null' => true,
                'after' => 'created_by_type',
            ];
        }

        if (! $this->db->fieldExists('created_by_user_id', 'bookings')) {
            $fields['created_by_user_id'] = [
                'type' => 'INT',
                'constraint' => 11,
                'unsigned' => true,
                'null' => true,
                'after' => 'created_by_name',
            ];
        }

        if ($fields !== []) {
            $this->forge->addColumn('bookings', $fields);
        }
    }

    public function down()
    {
        $fields = [];

        if ($this->db->fieldExists('created_by_user_id', 'bookings')) {
            $fields[] = 'created_by_user_id';
        }

        if ($this->db->fieldExists('created_by_name', 'bookings')) {
            $fields[] = 'created_by_name';
        }

        if ($this->db->fieldExists('created_by_type', 'bookings')) {
            $fields[] = 'created_by_type';
        }

        if ($fields !== []) {
            $this->forge->dropColumn('bookings', $fields);
        }
    }
}
