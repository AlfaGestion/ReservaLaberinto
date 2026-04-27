<?php

namespace App\Controllers;

use App\Controllers\BaseController;
use App\Models\RateModel;
use App\Models\UploadModel;

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
        $uploadModel = new UploadModel();

        $rate = $rateModel->first() ?? [
            'value' => 0,
            'qty_visitors' => 0,
            'allow_group_coordinator' => 0,
        ];
        $uploadConfig = $uploadModel->first() ?? [];

        $rate['enable_pay_by_entries'] = !empty($uploadConfig['enable_pay_by_entries']) ? 1 : 0;
        $rate['pay_by_entries_min_entries'] = (int) ($uploadConfig['pay_by_entries_min_entries'] ?? 0);
        $rate['pay_by_entries_min_days_before_booking'] = (int) ($uploadConfig['pay_by_entries_min_days_before_booking'] ?? 0);

        try {
            return  $this->response->setJSON($this->setResponse(null, null, $rate, 'Respuesta exitosa'));
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
