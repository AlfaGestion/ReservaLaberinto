<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddPayByEntriesDefaultPercentageToUploads extends Migration
{
    public function up()
    {
        if (! $this->db->fieldExists('pay_by_entries_default_percentage', 'uploads')) {
            $this->forge->addColumn('uploads', [
                'pay_by_entries_default_percentage' => [
                    'type' => 'INT',
                    'constraint' => 11,
                    'default' => 50,
                    'after' => 'pay_by_entries_min_days_before_booking',
                ],
            ]);
        }
    }

    public function down()
    {
        if ($this->db->fieldExists('pay_by_entries_default_percentage', 'uploads')) {
            $this->forge->dropColumn('uploads', 'pay_by_entries_default_percentage');
        }
    }
}
