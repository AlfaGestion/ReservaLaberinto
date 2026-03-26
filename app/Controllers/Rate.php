<?php

namespace App\Controllers;

use App\Controllers\BaseController;
use App\Models\RateModel;

class Rate extends BaseController
{
    public function saveRate()
    {
        $rateModel = new RateModel();
        $data = $this->request->getJSON();
        $rate = $data->value;
        $qtyVisitors = $data->qty_visitors;

        $query = [
            'value' => $rate,
            'qty_visitors' => $qtyVisitors
        ];

        $existingRate = $rateModel->findAll();

        if($existingRate){
            try {
                $rateModel->update($existingRate[0]['id'], $query);

                return  $this->response->setJSON($this->setResponse(null, null, null, 'Respuesta exitosa')); 
            } catch (\Exception $e) {
                return  $this->response->setJSON($this->setResponse(404, true, null, $e->getMessage()));
            }
        } else {
            try {
                $rateModel->insert($query);
    
                return  $this->response->setJSON($this->setResponse(null, null, null, 'Respuesta exitosa'));
            } catch (\Exception $e) {
                return  $this->response->setJSON($this->setResponse(404, true, null, $e->getMessage()));
            }
        }
    }

    public function getRate(){
        $rateModel = new RateModel();

        $rate = $rateModel->findAll();

        try {
            return  $this->response->setJSON($this->setResponse(null, null, $rate[0], 'Respuesta exitosa'));
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
