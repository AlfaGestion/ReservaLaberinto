<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddDiscountPercentageToServiceValues extends Migration
{
    public function up()
    {
        if (! $this->db->tableExists('service_values')) {
            return;
        }

        if ($this->db->fieldExists('discount_percentage', 'service_values')) {
            return;
        }

        $this->forge->addColumn('service_values', [
            'discount_percentage' => [
                'type' => 'DOUBLE',
                'null' => true,
                'default' => 0,
                'after' => 'amount',
            ],
        ]);
    }

    public function down()
    {
        if ($this->db->tableExists('service_values') && $this->db->fieldExists('discount_percentage', 'service_values')) {
            $this->forge->dropColumn('service_values', 'discount_percentage');
        }
    }
}
