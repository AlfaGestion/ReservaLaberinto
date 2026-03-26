<?php

namespace App\Models;

use CodeIgniter\Model;

class FieldsModel extends Model
{
    protected $DBGroup          = 'default';
    protected $table            = 'fields';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $useSoftDeletes   = false;
    protected $protectFields    = true;
    protected $allowedFields    = ['name', 'floor_type', 'sizes', 'ilumination', 'field_type', 'roofed', 'value', 'ilumination_value', 'elements_rent', 'disabled'];

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

    public function getFields(){
        $fields = $this->where('disabled', 0)->findAll();

        // log_message('debug', 'CANCHAS', var_dump($fields));

        return $fields;
    }

    public function getField($id){
        $field = $this->find($id);

        return $field;
    }

    public function getName($id){
        $field = $this->find($id);

        return $field['name'];
    }
}
