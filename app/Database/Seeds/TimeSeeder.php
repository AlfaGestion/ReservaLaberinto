<?php

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;

class TimeSeeder extends Seeder
{
    public function run()
    {
        $data = [
            [
                'from' => '10',
                'until' => '23',
                'nocturnal_time' => '18',
            ],
        ];

        $this->db->table('time')->insertBatch($data);

    }
}
