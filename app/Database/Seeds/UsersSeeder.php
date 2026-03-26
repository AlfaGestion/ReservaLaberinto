<?php

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;

class UsersSeeder extends Seeder
{
    public function run()
    {
        $data = [
            [
                'user' => 'testuser',
                'password' => password_hash('1', PASSWORD_DEFAULT),
                'superadmin' => 1,
                'name' => 'Test',
                'active' => 1,
            ],
        ];

        $this->db->table('users')->insertBatch($data);
    }
}
