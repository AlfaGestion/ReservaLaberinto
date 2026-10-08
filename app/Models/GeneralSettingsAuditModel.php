<?php

namespace App\Models;

use CodeIgniter\Model;

class GeneralSettingsAuditModel extends Model
{
    protected $table = 'general_settings_audit';
    protected $primaryKey = 'id';
    protected $returnType = 'array';
    protected $useTimestamps = false;
    protected $allowedFields = [
        'user_id',
        'user_name',
        'setting_key',
        'setting_label',
        'old_value',
        'new_value',
        'ip_address',
        'user_agent',
        'created_at',
    ];
}
