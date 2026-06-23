<?php

namespace App\Models;

use CodeIgniter\Model;

class RejectedPaymentsModel extends Model
{
    protected $DBGroup          = 'default';
    protected $table            = 'rejected_payments';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $useSoftDeletes   = false;
    protected $protectFields    = true;
    protected $allowedFields    = [
        'booking_id',
        'customer_id',
        'name',
        'email',
        'phone',
        'booking_date',
        'booking_time_from',
        'booking_time_until',
        'visitors',
        'total',
        'amount_to_pay',
        'payment_status',
        'payment_reason',
        'preference_id',
        'payment_id',
        'external_reference',
        'retry_url',
        'processing_notified_at',
        'notified_at',
        'expires_at',
        'closed_at',
        'created_at',
        'updated_at',
    ];

    protected $useTimestamps = false;
}
