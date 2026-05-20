<?php

namespace App\Models;

use CodeIgniter\Model;

class CustomerNoticeModel extends Model
{
    public const TYPES = ['info', 'warning', 'important', 'success'];

    protected $DBGroup          = 'default';
    protected $table            = 'customer_notices';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $useSoftDeletes   = false;
    protected $protectFields    = true;
    protected $allowedFields    = [
        'message',
        'type',
        'date_from',
        'date_until',
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

    public function getActiveNotice(?string $date = null): ?array
    {
        $currentDate = $date ?: date('Y-m-d');

        return $this->where('date_from <=', $currentDate)
            ->where('date_until >=', $currentDate)
            ->orderBy('date_from', 'DESC')
            ->orderBy('id', 'DESC')
            ->first();
    }

    public function getNoticeStatus(array $notice, ?string $date = null): string
    {
        $currentDate = $date ?: date('Y-m-d');
        $dateFrom = (string) ($notice['date_from'] ?? '');
        $dateUntil = (string) ($notice['date_until'] ?? '');

        if ($dateFrom > $currentDate) {
            return 'programado';
        }

        if ($dateUntil < $currentDate) {
            return 'vencido';
        }

        return 'vigente';
    }

    public function getHistoryWithStatus(?string $date = null): array
    {
        $currentDate = $date ?: date('Y-m-d');
        $notices = $this->orderBy('date_from', 'DESC')
            ->orderBy('id', 'DESC')
            ->findAll();

        foreach ($notices as &$notice) {
            $notice['status'] = $this->getNoticeStatus($notice, $currentDate);
        }

        return $notices;
    }
}
