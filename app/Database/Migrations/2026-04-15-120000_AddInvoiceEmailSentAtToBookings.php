<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddInvoiceEmailSentAtToBookings extends Migration
{
    public function up()
    {
        if (! $this->db->fieldExists('invoice_email_sent_at', 'bookings')) {
            $this->forge->addColumn('bookings', [
                'invoice_email_sent_at' => [
                    'type' => 'DATETIME',
                    'null' => true,
                    'after' => 'booking_time',
                ],
            ]);
        }
    }

    public function down()
    {
        if ($this->db->fieldExists('invoice_email_sent_at', 'bookings')) {
            $this->forge->dropColumn('bookings', 'invoice_email_sent_at');
        }
    }
}