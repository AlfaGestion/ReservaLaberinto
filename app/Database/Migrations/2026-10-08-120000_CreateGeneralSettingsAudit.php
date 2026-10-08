<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateGeneralSettingsAudit extends Migration
{
    public function up()
    {
        if ($this->db->tableExists('general_settings_audit')) {
            return;
        }

        $this->forge->addField([
            'id' => [
                'type' => 'INT',
                'constraint' => 11,
                'unsigned' => true,
                'auto_increment' => true,
            ],
            'user_id' => [
                'type' => 'INT',
                'constraint' => 11,
                'null' => true,
            ],
            'user_name' => [
                'type' => 'VARCHAR',
                'constraint' => 150,
                'null' => false,
            ],
            'setting_key' => [
                'type' => 'VARCHAR',
                'constraint' => 100,
                'null' => false,
            ],
            'setting_label' => [
                'type' => 'VARCHAR',
                'constraint' => 180,
                'null' => false,
            ],
            'old_value' => [
                'type' => 'TEXT',
                'null' => true,
            ],
            'new_value' => [
                'type' => 'TEXT',
                'null' => true,
            ],
            'ip_address' => [
                'type' => 'VARCHAR',
                'constraint' => 45,
                'null' => true,
            ],
            'user_agent' => [
                'type' => 'TEXT',
                'null' => true,
            ],
            'created_at' => [
                'type' => 'DATETIME',
                'null' => false,
            ],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addKey(['setting_key', 'created_at']);
        $this->forge->createTable('general_settings_audit', true);
    }

    public function down()
    {
        $this->forge->dropTable('general_settings_audit', true);
    }
}
