<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddUniqueIndexToPaymentsMercadoPagoId extends Migration
{
    public function up()
    {
        if (! $this->db->tableExists('payments')) {
            return;
        }

        // Keep the oldest payment row for duplicated MP ids and null out the rest.
        $duplicates = $this->db->query(
            'SELECT id_mercado_pago FROM payments WHERE id_mercado_pago IS NOT NULL GROUP BY id_mercado_pago HAVING COUNT(*) > 1'
        )->getResultArray();

        foreach ($duplicates as $row) {
            $mpId = (int) ($row['id_mercado_pago'] ?? 0);
            if ($mpId <= 0) {
                continue;
            }

            $items = $this->db->table('payments')
                ->select('id')
                ->where('id_mercado_pago', $mpId)
                ->orderBy('id', 'ASC')
                ->get()
                ->getResultArray();

            if (count($items) <= 1) {
                continue;
            }

            for ($i = 1; $i < count($items); $i++) {
                $this->db->table('payments')
                    ->where('id', (int) $items[$i]['id'])
                    ->update(['id_mercado_pago' => null]);
            }
        }

        try {
            $this->forge->addKey('id_mercado_pago', false, true, 'uniq_payments_mp_payment_id');
            $this->forge->processIndexes('payments');
        } catch (\Throwable $e) {
            // Ignore if index already exists.
        }
    }

    public function down()
    {
        if (! $this->db->tableExists('payments')) {
            return;
        }

        try {
            $this->forge->dropKey('payments', 'uniq_payments_mp_payment_id', true);
        } catch (\Throwable $e) {
            // Ignore if index does not exist.
        }
    }
}
