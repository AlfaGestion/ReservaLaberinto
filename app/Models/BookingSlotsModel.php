<?php

namespace App\Models;

use CodeIgniter\Model;

class BookingSlotsModel extends Model
{
    protected $DBGroup          = 'default';
    protected $table            = 'booking_slots';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $useSoftDeletes   = false;
    protected $protectFields    = true;
    protected $allowedFields    = [
        'date',
        'id_field',
        'time_from',
        'time_until',
        'booking_id',
        'status',
        'active',
        'expires_at',
        'created_at',
    ];

    protected $useTimestamps = false;
}
