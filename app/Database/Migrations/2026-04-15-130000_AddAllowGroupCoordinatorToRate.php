<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddAllowGroupCoordinatorToRate extends Migration
{
    public function up()
    {
        if ($this->db->tableExists('rate') && ! $this->db->fieldExists('allow_group_coordinator', 'rate')) {
            $this->forge->addColumn('rate', [
                'allow_group_coordinator' => [
                    'type' => 'TINYINT',
                    'constraint' => 1,
                    'null' => false,
                    'default' => 0,
                ],
            ]);
        }
    }

    public function down()
    {
        if ($this->db->tableExists('rate') && $this->db->fieldExists('allow_group_coordinator', 'rate')) {
            $this->forge->dropColumn('rate', 'allow_group_coordinator');
        }
    }
}