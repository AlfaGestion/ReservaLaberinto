<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AlignCustomersSchema extends Migration
{
    public function up()
    {
        $fieldsToAdd = [
            'email' => [
                'type' => 'VARCHAR',
                'constraint' => 255,
                'null' => true,
            ],
            'type_institution' => [
                'type' => 'VARCHAR',
                'constraint' => 255,
                'null' => true,
            ],
            'user_name' => [
                'type' => 'VARCHAR',
                'constraint' => 255,
                'null' => true,
            ],
            'pass' => [
                'type' => 'VARCHAR',
                'constraint' => 255,
                'null' => true,
            ],
            'deleted' => [
                'type' => 'TINYINT',
                'constraint' => 1,
                'default' => 0,
                'null' => false,
            ],
            'area_code' => [
                'type' => 'VARCHAR',
                'constraint' => 20,
                'null' => true,
            ],
            'complete_phone' => [
                'type' => 'VARCHAR',
                'constraint' => 50,
                'null' => true,
            ],
        ];

        foreach ($fieldsToAdd as $fieldName => $definition) {
            if (! $this->db->fieldExists($fieldName, 'customers')) {
                $this->forge->addColumn('customers', [$fieldName => $definition]);
            }
        }

        $fieldsToModify = [
            'last_name' => [
                'name' => 'last_name',
                'type' => 'VARCHAR',
                'constraint' => 50,
                'null' => true,
            ],
            'dni' => [
                'name' => 'dni',
                'type' => 'VARCHAR',
                'constraint' => 20,
                'null' => true,
            ],
            'city' => [
                'name' => 'city',
                'type' => 'VARCHAR',
                'constraint' => 150,
                'null' => true,
            ],
        ];

        $this->forge->modifyColumn('customers', $fieldsToModify);
    }

    public function down()
    {
        $fieldsToDrop = ['email', 'type_institution', 'user_name', 'pass', 'deleted', 'area_code', 'complete_phone'];

        foreach ($fieldsToDrop as $fieldName) {
            if ($this->db->fieldExists($fieldName, 'customers')) {
                $this->forge->dropColumn('customers', $fieldName);
            }
        }

        $fieldsToRestore = [
            'last_name' => [
                'name' => 'last_name',
                'type' => 'VARCHAR',
                'constraint' => 50,
                'null' => false,
            ],
            'dni' => [
                'name' => 'dni',
                'type' => 'VARCHAR',
                'constraint' => 20,
                'null' => false,
            ],
            'city' => [
                'name' => 'city',
                'type' => 'VARCHAR',
                'constraint' => 150,
                'null' => false,
            ],
        ];

        $this->forge->modifyColumn('customers', $fieldsToRestore);
    }
}
