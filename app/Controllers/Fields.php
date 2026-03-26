<?php

namespace App\Controllers;

use App\Controllers\BaseController;
use App\Models\FieldsModel;

class Fields extends BaseController
{
    public function getFields()
    {
        $fieldsModel = new FieldsModel();

        $fields = $fieldsModel->getFields();

        try {
            return  $this->response->setJSON($this->setResponse(null, null, $fields, 'Respuesta exitosa'));
        } catch (\Exception $e) {
            return  $this->response->setJSON($this->setResponse(404, true, null, $e->getMessage()));
        }
    }

    public function getField($id)
    {
        $fieldsModel = new FieldsModel();

        $field = $fieldsModel->getField($id);

        try {
            return  $this->response->setJSON($this->setResponse(null, null, $field, 'Respuesta exitosa'));
        } catch (\Exception $e) {
            return  $this->response->setJSON($this->setResponse(404, true, null, $e->getMessage()));
        }
    }

    public function setResponse($code = 200, $error = false, $data = null, $message = '')
    {
        $response = [
            'error' => $error,
            'code' => $code,
            'data' => $data,
            'message' => $message,
        ];

        return $response;
    }
}
