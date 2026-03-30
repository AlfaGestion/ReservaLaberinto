<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddClosedDaysToTime extends Migration
{
    private array $dayFields = [
        'is_monday' => [
            'type' => 'TINYINT',
            'constraint' => 1,
            'default' => 0,
            'null' => false,
        ],
        'is_tuesday' => [
            'type' => 'TINYINT',
            'constraint' => 1,
            'default' => 0,
            'null' => false,
        ],
        'is_wednesday' => [
            'type' => 'TINYINT',
            'constraint' => 1,
            'default' => 0,
            'null' => false,
        ],
        'is_thursday' => [
            'type' => 'TINYINT',
            'constraint' => 1,
            'default' => 0,
            'null' => false,
        ],
        'is_friday' => [
            'type' => 'TINYINT',
            'constraint' => 1,
            'default' => 0,
            'null' => false,
        ],
        'is_saturday' => [
            'type' => 'TINYINT',
            'constraint' => 1,
            'default' => 0,
            'null' => false,
        ],
        'is_sunday' => [
            'type' => 'TINYINT',
            'constraint' => 1,
            'default' => 0,
            'null' => false,
        ],
    ];

    public function up()
    {
        foreach ($this->dayFields as $fieldName => $definition) {
            if (! $this->db->fieldExists($fieldName, 'time')) {
                $this->forge->addColumn('time', [$fieldName => $definition]);
            }
        }
    }

    public function down()
    {
        foreach (array_keys($this->dayFields) as $fieldName) {
            if ($this->db->fieldExists($fieldName, 'time')) {
                $this->forge->dropColumn('time', $fieldName);
            }
        }
    }
}
