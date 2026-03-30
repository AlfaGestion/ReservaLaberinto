<?php

namespace App\Controllers;

use App\Models\BookingsModel;
use App\Models\CustomersModel;
use App\Models\FieldsModel;
use App\Models\MercadoPagoModel;
use App\Models\OffersModel;
use App\Models\TimeModel;
use DateInterval;
use DateTime;

class Home extends BaseController
{
    public function index()
    {
        $offersModel = new OffersModel();

        $currentDate = date("Y-m-d");
        $existingOffer = $offersModel->first();

        if (isset($existingOffer)) {


            if ($existingOffer['expiration_date'] == $currentDate) {
                $update = [
                    'value' => 0,
                    'description' => '',
                    'expiration_date' => '',
                ];

                $offersModel->update($existingOffer['id'], $update);
            }
        }

        $oferta = $offersModel->first();

        $fieldsModel = new FieldsModel();
        $fields = $fieldsModel->where('disabled', 0)->findAll();

        // dd($fields);

        $timeModel = new TimeModel();
        $openingTime = array_slice($timeModel->getOpeningTime(), 0, -7);

        $firstRow = $timeModel->first();
        $isSunday = $firstRow['is_sunday'] ?? 0;


        // $time = [];

        // if ($openingTime) {
        //     if ($openingTime[0]['from']) {
        //         $from = $openingTime[0]['from'] - 1;
        //         $until = $openingTime[0]['until'];

        //         while ($from != $until) {
        //             $from++;
        //             if($from == '24') $from = '00';

        //             array_push($time, $from);
        //         }
        //     }
        // }

        // if ($openingTime) {
        //     if ($openingTime[0]['from_cut']) {
        //         $from_cut = $openingTime[0]['from_cut'] - 1;
        //         $until_cut = $openingTime[0]['until_cut'];

        //         while ($from_cut != $until_cut) {
        //             $from_cut++;
        //             array_push($time, $from_cut);
        //         }
        //     }
        // }

        return view('index', ['fields' => $fields, 'time' => $openingTime, 'oferta' => $oferta, 'esDomingo' => $isSunday]);
    }

    // public function deleteRejected()
    // {
    //     $bookingsModel = new BookingsModel();
    //     $mercadoPagoModel = new MercadoPagoModel();

    //     try {
    //         $mercadoPagoModel->where('status', 'rejected')->delete();
    //         $bookingsModel->where('approved', 0)
    //             ->orWhere('approved', null)->delete();
    //         return  $this->response->setJSON($this->setResponse(null, null, null, 'Respuesta exitosa'));
    //     } catch (\Exception $e) {
    //         return  $this->response->setJSON($this->setResponse(404, true, null, $e->getMessage()));
    //     }
    // }

    public function deleteRejected()
    {
        $bookingsModel = new BookingsModel();
        $mercadoPagoModel = new MercadoPagoModel();

        $hora_actual = date("Y-m-d H:i:s");
        $nueva_hora = date("Y-m-d H:i:s", strtotime($hora_actual . " -10 minutes"));

        $mpPayments = $mercadoPagoModel->where('status', 'rejected')
            ->orWhere('status', 'null')->findAll();

        try {

            if (isset($mpPayments)) {
                $mercadoPagoModel->where('status', 'rejected')
                    ->orWhere('status', 'null')
                    ->delete();
            }

            $bookingsModel->where('approved', 0)
                ->orWhere('approved', null)
                ->where('booking_time <', $nueva_hora)->delete();

            return  $this->response->setJSON($this->setResponse(null, null, null, 'Respuesta exitosa'));
        } catch (\Exception $e) {
            return  $this->response->setJSON($this->setResponse(404, true, null, $e->getMessage()));
        }
    }


    public function infoReserva()
    {
        $data = $this->request->getJSON();
        $fieldsModel = new FieldsModel();

        $datosReserva = [
            'fecha'        => $data->fecha,
            'cancha'       => $fieldsModel->getField($data->cancha)['name'],
            'horarioDesde' => $data->horarioDesde,
            'horarioHasta' => $data->horarioHasta,
            'nombre'       => $data->nombre,
            'telefono'     => $data->telefono,
            'visitantes'   => $data->visitantes
        ];

        try {
            return  $this->response->setJSON($this->setResponse(null, null, $datosReserva, 'Respuesta exitosa'));
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
