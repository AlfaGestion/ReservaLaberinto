<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class RenameValuesTable extends Migration
{
    public function up()
    {
        if ($this->db->tableExists('values') && ! $this->db->tableExists('service_values')) {
            $this->forge->renameTable('values', 'service_values');
        }
    }

    public function down()
    {
        if ($this->db->tableExists('service_values') && ! $this->db->tableExists('values')) {
            $this->forge->renameTable('service_values', 'values');
        }
    }
}
