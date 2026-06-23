<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddMpEmailSentAtToBookings extends Migration
{
    public function up()
    {
        if (! $this->db->fieldExists('mp_pending_email_sent_at', 'bookings')) {
            $this->forge->addColumn('bookings', [
                'mp_pending_email_sent_at' => [
                    'type' => 'DATETIME',
                    'null' => true,
                    'after' => 'invoice_email_sent_at',
                ],
            ]);
        }

        if (! $this->db->fieldExists('mp_confirmed_email_sent_at', 'bookings')) {
            $this->forge->addColumn('bookings', [
                'mp_confirmed_email_sent_at' => [
                    'type' => 'DATETIME',
                    'null' => true,
                    'after' => 'mp_pending_email_sent_at',
                ],
            ]);
        }
    }

    public function down()
    {
        if ($this->db->fieldExists('mp_confirmed_email_sent_at', 'bookings')) {
            $this->forge->dropColumn('bookings', 'mp_confirmed_email_sent_at');
        }

        if ($this->db->fieldExists('mp_pending_email_sent_at', 'bookings')) {
            $this->forge->dropColumn('bookings', 'mp_pending_email_sent_at');
        }
    }
}
