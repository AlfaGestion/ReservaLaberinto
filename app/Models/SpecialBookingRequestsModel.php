<?php

namespace App\Models;

use CodeIgniter\Model;

class SpecialBookingRequestsModel extends Model
{
    protected $DBGroup          = 'default';
    protected $table            = 'special_booking_requests';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $useSoftDeletes   = false;
    protected $protectFields    = true;
    protected $allowedFields    = [
        'requested_date',
        'customer_id',
        'field_id',
        'field_name',
        'customer_name',
        'customer_last_name',
        'customer_phone',
        'customer_email',
        'customer_dni',
        'customer_city',
        'customer_type_institution',
        'time_from',
        'time_until',
        'visitors',
        'minimum_visitors',
        'total_amount',
        'request_message',
        'status',
        'viewed_at',
        'replied_at',
        'replied_to_email',
        'reply_subject',
        'reply_message',
        'created_at',
        'updated_at',
    ];

    protected $useTimestamps = false;
    protected $dateFormat    = 'datetime';
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';
    protected $deletedField  = 'deleted_at';

    protected $validationRules      = [];
    protected $validationMessages   = [];
    protected $skipValidation       = false;
    protected $cleanValidationRules = true;

    protected $allowCallbacks = true;
    protected $beforeInsert   = [];
    protected $afterInsert    = [];
    protected $beforeUpdate   = [];
    protected $afterUpdate    = [];
    protected $beforeFind     = [];
    protected $afterFind      = [];
    protected $beforeDelete   = [];
    protected $afterDelete    = [];
}
