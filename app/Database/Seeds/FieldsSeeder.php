<?php

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;

class FieldsSeeder extends Seeder
{
    public function run()
    {
        $data = [
            [
                'name' => 'Cancha 1',
                'floor_type' => 'Sintético',
                'sizes' => '16x30',
                'ilumination' => 1,
                'field_type' => 'Fútbol 5',
                'roofed' => 0,
                'value' => 10000,
                'ilumination_value' => 12000,
            ],
            [
                'name' => 'Cancha 2',
                'floor_type' => 'Sintético',
                'sizes' => '16x30',
                'ilumination' => 1,
                'field_type' => 'Fútbol 5',
                'roofed' => 0,
                'value' => 10000,
                'ilumination_value' => 12000,
            ],
            [
                'name' => 'Cancha 3',
                'floor_type' => 'Sintético',
                'sizes' => '16x30',
                'ilumination' => 1,
                'field_type' => 'Fútbol 5',
                'roofed' => 0,
                'value' => 10000,
                'ilumination_value' => 12000,
            ],
        ];

        $this->db->table('fields')->insertBatch($data);
    }
}
