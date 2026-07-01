<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddMpCancelledEmailSentAtToBookings extends Migration
{
    public function up()
    {
        if (! $this->db->fieldExists('mp_cancelled_email_sent_at', 'bookings')) {
            $this->forge->addColumn('bookings', [
                'mp_cancelled_email_sent_at' => [
                    'type' => 'DATETIME',
                    'null' => true,
                    'after' => 'mp_confirmed_email_sent_at',
                ],
            ]);
        }
    }

    public function down()
    {
        if ($this->db->fieldExists('mp_cancelled_email_sent_at', 'bookings')) {
            $this->forge->dropColumn('bookings', 'mp_cancelled_email_sent_at');
        }
    }
}
