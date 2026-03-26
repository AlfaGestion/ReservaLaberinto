<?php

namespace App\Controllers;

use App\Controllers\BaseController;
use App\Models\OffersModel;

class Offers extends BaseController
{
    public function saveOfferRate()
    {
        $offerModel = new OffersModel();
        $data = $this->request->getJSON();
        $fechaActual = date("Y-m-d");

        $query = [
            'value' => $data->value,
            'description' => $data->description,
            'expiration_date' => date("Y-m-d", strtotime("+1 day", strtotime($fechaActual))),
        ];

        $existingRate = $offerModel->findAll();

        if($existingRate){
            try {
                $offerModel->update($existingRate[0]['id'], $query);

                return  $this->response->setJSON($this->setResponse(null, null, null, 'Respuesta exitosa')); 
            } catch (\Exception $e) {
                return  $this->response->setJSON($this->setResponse(404, true, null, $e->getMessage()));
            }
        } else {
            try {
                $offerModel->insert($query);
    
                return  $this->response->setJSON($this->setResponse(null, null, null, 'Respuesta exitosa'));
            } catch (\Exception $e) {
                return  $this->response->setJSON($this->setResponse(404, true, null, $e->getMessage()));
            }
        }
    }

    public function getOffersRate(){
        $offersModel = new OffersModel();

        $rate = $offersModel->findAll();

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
