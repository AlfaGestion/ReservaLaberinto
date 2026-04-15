<?php

namespace App\Models;

use CodeIgniter\Model;

class BookingsModel extends Model
{
    protected $DBGroup          = 'default';
    protected $table            = 'bookings';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $useSoftDeletes   = false;
    protected $protectFields    = true;
    protected $allowedFields    = ['id_field', 'date', 'time_from', 'time_until', 'visitors', 'name', 'phone', 'code', 'total_payment', 'total', 'parcial', 'diference', 'description', 'reservation', 'payment', 'payment_method', 'id_customer', 'id_preference_parcial', 'id_preference_total', 'approved', 'use_offer', 'annulled', 'booking_time', 'invoice_email_sent_at', 'mp'];

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


    public function getBookings()
    {
        $bookings = $this->where('date >=', date('Y-m-d'))->findAll();

        // dd($bookings);

        return $bookings;
    }

    public function getBooking($id)
    {
        $booking = $this->find($id);

        return $booking;
    }
}
