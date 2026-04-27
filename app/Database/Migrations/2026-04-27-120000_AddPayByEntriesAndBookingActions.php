<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddPayByEntriesAndBookingActions extends Migration
{
    public function up()
    {
        $bookingFields = [];

        if (! $this->db->fieldExists('partial_by_entries', 'bookings')) {
            $bookingFields['partial_by_entries'] = [
                'type' => 'TINYINT',
                'constraint' => 1,
                'default' => 0,
                'after' => 'mp',
            ];
        }

        if (! $this->db->fieldExists('paid_entries', 'bookings')) {
            $bookingFields['paid_entries'] = [
                'type' => 'INT',
                'constraint' => 11,
                'default' => 0,
                'after' => 'partial_by_entries',
            ];
        }

        if (! $this->db->fieldExists('IdPedido', 'bookings')) {
            $bookingFields['IdPedido'] = [
                'type' => 'VARCHAR',
                'constraint' => 40,
                'null' => true,
                'after' => 'paid_entries',
            ];
        }

        if ($bookingFields !== []) {
            $this->forge->addColumn('bookings', $bookingFields);
        }

        if ($this->db->fieldExists('IdPedido', 'bookings')) {
            $bookings = $this->db->table('bookings')
                ->select('id, visitors, total_payment')
                ->groupStart()
                    ->where('IdPedido', null)
                    ->orWhere('IdPedido', '')
                ->groupEnd()
                ->get()
                ->getResultArray();

            foreach ($bookings as $booking) {
                $this->db->table('bookings')
                    ->where('id', $booking['id'])
                    ->update([
                        'IdPedido' => 'RES-' . str_pad((string) $booking['id'], 8, '0', STR_PAD_LEFT),
                        'paid_entries' => !empty($booking['total_payment']) ? (int) ($booking['visitors'] ?? 0) : 0,
                    ]);
            }
        }

        $paymentFields = [];

        if (! $this->db->fieldExists('paid_entries', 'payments')) {
            $paymentFields['paid_entries'] = [
                'type' => 'INT',
                'constraint' => 11,
                'null' => true,
                'after' => 'amount',
            ];
        }

        if (! $this->db->fieldExists('unit_price', 'payments')) {
            $paymentFields['unit_price'] = [
                'type' => 'DECIMAL',
                'constraint' => '14,2',
                'null' => true,
                'after' => 'paid_entries',
            ];
        }

        if (! $this->db->fieldExists('payment_type', 'payments')) {
            $paymentFields['payment_type'] = [
                'type' => 'VARCHAR',
                'constraint' => 30,
                'null' => true,
                'after' => 'unit_price',
            ];
        }

        if (! $this->db->fieldExists('created_by_admin', 'payments')) {
            $paymentFields['created_by_admin'] = [
                'type' => 'TINYINT',
                'constraint' => 1,
                'default' => 0,
                'after' => 'payment_type',
            ];
        }

        if (! $this->db->fieldExists('admin_user_id', 'payments')) {
            $paymentFields['admin_user_id'] = [
                'type' => 'INT',
                'constraint' => 11,
                'unsigned' => true,
                'null' => true,
                'after' => 'created_by_admin',
            ];
        }

        if ($paymentFields !== []) {
            $this->forge->addColumn('payments', $paymentFields);
        }

        $uploadFields = [];

        if (! $this->db->fieldExists('enable_pay_by_entries', 'uploads')) {
            $uploadFields['enable_pay_by_entries'] = [
                'type' => 'TINYINT',
                'constraint' => 1,
                'default' => 0,
                'after' => 'invoice_email_message',
            ];
        }

        if (! $this->db->fieldExists('pay_by_entries_min_entries', 'uploads')) {
            $uploadFields['pay_by_entries_min_entries'] = [
                'type' => 'INT',
                'constraint' => 11,
                'default' => 0,
                'after' => 'enable_pay_by_entries',
            ];
        }

        if (! $this->db->fieldExists('pay_by_entries_min_days_before_booking', 'uploads')) {
            $uploadFields['pay_by_entries_min_days_before_booking'] = [
                'type' => 'INT',
                'constraint' => 11,
                'default' => 0,
                'after' => 'pay_by_entries_min_entries',
            ];
        }

        if ($uploadFields !== []) {
            $this->forge->addColumn('uploads', $uploadFields);
        }

        if (! $this->db->tableExists('bookings_acction')) {
            $this->forge->addField([
                'ID' => [
                    'type' => 'INT',
                    'constraint' => 11,
                    'unsigned' => true,
                    'auto_increment' => true,
                ],
                'IdPedido' => [
                    'type' => 'VARCHAR',
                    'constraint' => 40,
                    'null' => false,
                ],
                'fechaHora' => [
                    'type' => 'DATETIME',
                    'null' => false,
                ],
                'Accion' => [
                    'type' => 'CHAR',
                    'constraint' => 1,
                    'null' => false,
                ],
                'observacion' => [
                    'type' => 'TEXT',
                    'null' => true,
                ],
                'usuario' => [
                    'type' => 'VARCHAR',
                    'constraint' => 120,
                    'null' => true,
                ],
            ]);

            $this->forge->addKey('ID', true);
            $this->forge->addKey('IdPedido');
            $this->forge->createTable('bookings_acction');
        }
    }

    public function down()
    {
        if ($this->db->tableExists('bookings_acction')) {
            $this->forge->dropTable('bookings_acction');
        }

        foreach (['pay_by_entries_min_days_before_booking', 'pay_by_entries_min_entries', 'enable_pay_by_entries'] as $field) {
            if ($this->db->fieldExists($field, 'uploads')) {
                $this->forge->dropColumn('uploads', $field);
            }
        }

        foreach (['admin_user_id', 'created_by_admin', 'payment_type', 'unit_price', 'paid_entries'] as $field) {
            if ($this->db->fieldExists($field, 'payments')) {
                $this->forge->dropColumn('payments', $field);
            }
        }

        foreach (['IdPedido', 'paid_entries', 'partial_by_entries'] as $field) {
            if ($this->db->fieldExists($field, 'bookings')) {
                $this->forge->dropColumn('bookings', $field);
            }
        }
    }
}
