<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddPaymentSupportFieldsToUploads extends Migration
{
    public function up()
    {
        $fields = [];

        if (! $this->db->fieldExists('payment_support_email', 'uploads')) {
            $fields['payment_support_email'] = [
                'type' => 'VARCHAR',
                'constraint' => 190,
                'null' => true,
                'after' => 'notification_email',
            ];
        }

        if (! $this->db->fieldExists('payment_support_phone', 'uploads')) {
            $fields['payment_support_phone'] = [
                'type' => 'VARCHAR',
                'constraint' => 50,
                'null' => true,
                'after' => 'payment_support_email',
            ];
        }

        if ($fields !== []) {
            $this->forge->addColumn('uploads', $fields);
        }
    }

    public function down()
    {
        if ($this->db->fieldExists('payment_support_phone', 'uploads')) {
            $this->forge->dropColumn('uploads', 'payment_support_phone');
        }

        if ($this->db->fieldExists('payment_support_email', 'uploads')) {
            $this->forge->dropColumn('uploads', 'payment_support_email');
        }
    }
}
