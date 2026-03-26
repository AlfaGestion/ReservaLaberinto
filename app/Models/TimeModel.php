<?php

namespace App\Models;

use CodeIgniter\Model;

class TimeModel extends Model
{
    public $schedules = [
        '07:00',
        '07:30',
        '08:00',
        '08:30',
        '09:00',
        '09:30',
        '10:00',
        '10:30',
        '11:00',
        '11:30',
        '12:00',
        '12:30',
        '13:00',
        '13:30',
        '14:00',
        '14:30',
        '15:00',
        '15:30',
        '16:00',
        '16:30',
        '17:00',
        '17:30',
        '18:00',
        '18:30',
        '19:00',
        '19:30',
        '20:00',
        '20:30',
        '21:00',
        '21:30',
        '22:00',
        '22:30',
        '23:00',
        '23:30',
        '00:00',
        '00:30',
        '01:00',
        '01:30',
        '02:00',
        '02:30',
        '03:00',
        '03:30',
        '04:00',
        '04:30',
        '05:00',
        '05:30',
        '06:00',
        '06:30'
    ];

    protected $DBGroup          = 'default';
    protected $table            = 'time';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $useSoftDeletes   = false;
    protected $protectFields    = true;
    protected $allowedFields    = ['from', 'until', 'from_cut', 'until_cut', 'nocturnal_time', 'is_sunday', 'is_monday', 'is_tuesday', 'is_wednesday', 'is_thursday', 'is_friday', 'is_saturday'];

    // Dates
    protected $useTimestamps = false;
    protected $dateFormat    = 'datetime';
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';
    protected $deletedField  = 'deleted_at';

    // Validation
    protected $validationRules      = [];
    protected $validationMessages   = [];
    protected $skipValidation       = false;
    protected $cleanValidationRules = true;

    // Callbacks
    protected $allowCallbacks = true;
    protected $beforeInsert   = [];
    protected $afterInsert    = [];
    protected $beforeUpdate   = [];
    protected $afterUpdate    = [];
    protected $beforeFind     = [];
    protected $afterFind      = [];
    protected $beforeDelete   = [];
    protected $afterDelete    = [];

    public function getOpeningTime()
    {
        $times = $this->findAll();
        $time = [];

        if (count($times) > 0) {
            $from = $this->formatHour($times[0]['from']);
            $until = $this->formatHour($times[0]['until']);
            $fromIndex = array_search($from, $this->schedules);
            $untilIndex = array_search($until, $this->schedules);
            // dd($fromIndex);

            if ($fromIndex !== false && $untilIndex !== false) {
                if ($fromIndex > $untilIndex) {
                    $time = array_merge(
                        array_slice($this->schedules, $fromIndex),
                        array_slice($this->schedules, 0, $untilIndex + 1)
                    );
                } else {
                    $time = array_slice($this->schedules, $fromIndex, $untilIndex - $fromIndex + 1);
                }
            }
        }

        // dd($time);
        $time['is_monday'] = $times[0]['is_monday'] ?? 0;
        $time['is_tuesday'] = $times[0]['is_tuesday'] ?? 0;
        $time['is_wednesday'] = $times[0]['is_wednesday'] ?? 0;
        $time['is_thursday'] = $times[0]['is_thursday'] ?? 0;
        $time['is_friday'] = $times[0]['is_friday'] ?? 0;
        $time['is_saturday'] = $times[0]['is_saturday'] ?? 0;
        $time['is_sunday'] = $times[0]['is_sunday'] ?? 0;

        return $time;
    }

    private function formatHour($hour)
    {
        return strlen($hour) === 2 ? $hour . ':00' : $hour;
    }
}
