<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddNotificationEmailToUploads extends Migration
{
    public function up()
    {
        if (! $this->db->tableExists('uploads')) {
            return;
        }

        if ($this->db->fieldExists('notification_email', 'uploads')) {
            return;
        }

        $this->forge->addColumn('uploads', [
            'notification_email' => [
                'type' => 'VARCHAR',
                'constraint' => 150,
                'null' => true,
                'after' => 'secondary_color',
            ],
        ]);
    }

    public function down()
    {
        if ($this->db->tableExists('uploads') && $this->db->fieldExists('notification_email', 'uploads')) {
            $this->forge->dropColumn('uploads', 'notification_email');
        }
    }
}
