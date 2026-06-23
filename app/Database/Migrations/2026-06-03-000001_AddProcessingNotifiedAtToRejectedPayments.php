<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddProcessingNotifiedAtToRejectedPayments extends Migration
{
    public function up()
    {
        if (! $this->db->fieldExists('processing_notified_at', 'rejected_payments')) {
            $this->forge->addColumn('rejected_payments', [
                'processing_notified_at' => [
                    'type' => 'DATETIME',
                    'null' => true,
                    'after' => 'retry_url',
                ],
            ]);
        }
    }

    public function down()
    {
        if ($this->db->fieldExists('processing_notified_at', 'rejected_payments')) {
            $this->forge->dropColumn('rejected_payments', 'processing_notified_at');
        }
    }
}
