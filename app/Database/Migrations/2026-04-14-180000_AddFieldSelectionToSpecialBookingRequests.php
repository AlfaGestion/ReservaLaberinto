<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddFieldSelectionToSpecialBookingRequests extends Migration
{
    public function up()
    {
        if (!$this->db->tableExists('special_booking_requests')) {
            return;
        }

        $fields = [];

        if (!$this->db->fieldExists('field_id', 'special_booking_requests')) {
            $fields['field_id'] = [
                'type'       => 'INT',
                'constraint' => 10,
                'unsigned'   => true,
                'null'       => true,
                'after'      => 'customer_id',
            ];
        }

        if (!$this->db->fieldExists('field_name', 'special_booking_requests')) {
            $fields['field_name'] = [
                'type'       => 'VARCHAR',
                'constraint' => 120,
                'null'       => true,
                'after'      => 'field_id',
            ];
        }

        if ($fields !== []) {
            $this->forge->addColumn('special_booking_requests', $fields);
        }
    }

    public function down()
    {
        if (!$this->db->tableExists('special_booking_requests')) {
            return;
        }

        if ($this->db->fieldExists('field_name', 'special_booking_requests')) {
            $this->forge->dropColumn('special_booking_requests', 'field_name');
        }

        if ($this->db->fieldExists('field_id', 'special_booking_requests')) {
            $this->forge->dropColumn('special_booking_requests', 'field_id');
        }
    }
}