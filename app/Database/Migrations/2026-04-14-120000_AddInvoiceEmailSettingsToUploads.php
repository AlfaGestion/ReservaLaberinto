<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddInvoiceEmailSettingsToUploads extends Migration
{
    public function up()
    {
        $fields = [];

        if (! $this->db->fieldExists('invoice_email_subject', 'uploads')) {
            $fields['invoice_email_subject'] = [
                'type' => 'TEXT',
                'null' => true,
                'after' => 'notification_email',
            ];
        }

        if (! $this->db->fieldExists('invoice_email_message', 'uploads')) {
            $fields['invoice_email_message'] = [
                'type' => 'TEXT',
                'null' => true,
                'after' => 'invoice_email_subject',
            ];
        }

        if ($fields !== []) {
            $this->forge->addColumn('uploads', $fields);
        }
    }

    public function down()
    {
        if ($this->db->fieldExists('invoice_email_message', 'uploads')) {
            $this->forge->dropColumn('uploads', 'invoice_email_message');
        }

        if ($this->db->fieldExists('invoice_email_subject', 'uploads')) {
            $this->forge->dropColumn('uploads', 'invoice_email_subject');
        }
    }
}
