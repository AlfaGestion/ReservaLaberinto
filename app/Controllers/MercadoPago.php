<?php

namespace App\Controllers;

use App\Controllers\BaseController;
use App\Libraries\MercadoPagoLibrary;
use App\Models\BookingsModel;
use App\Models\CustomersModel;
use App\Models\MercadoPagoModel;
use App\Models\RateModel;

class MercadoPago extends BaseController
{
    public function setPreference()
    {
        $rateModel = new RateModel();
        $rate = $rateModel->first()['value'];
        $data = $this->request->getJSON();
        $montoTotal = $data->amount;
        $montoParcial = (floatval($montoTotal) * floatval($rate)) / 100;

        $mp = new MercadoPagoLibrary();
        $mp->setPreference('Pago total de reserva', $montoTotal, 1);
        $preferenceIdTotal = $mp->preferenceId;

        $mp = new MercadoPagoLibrary();
        $mp->setPreference('Reserva de laberinto', $montoParcial, 1);
        $preferenceIdParcial = $mp->preferenceId;

        $preferences = [
            'preferenceIdTotal' => $preferenceIdTotal,
            'preferenceIdParcial' => $preferenceIdParcial,
        ];

        try {
            return  $this->response->setJSON($this->setResponse(null, null, $preferences, 'Respuesta exitosa'));
        } catch (\Exception $e) {
            return  $this->response->setJSON($this->setResponse(404, true, null, $e->getMessage()));
        }
    }

    public function success()
    {
        $mercadoPagoModel = new MercadoPagoModel();
        $bookingsModel = new BookingsModel();
        $customersModel = new CustomersModel();

        $preferenceId = $this->request->getVar('preference_id');


        if (!empty($preferenceId)) {
            $paid = '';
            $approved = '';

            $data = [
                'collection_id' => $this->request->getVar('collection_id'),
                'collection_status' => $this->request->getVar('collection_status'),
                'payment_id' => $this->request->getVar('payment_id'),
                'status' => $this->request->getVar('status'),
                'external_reference' => $this->request->getVar('external_reference'),
                'payment_type' => $this->request->getVar('payment_type'),
                'merchant_order_id' => $this->request->getVar('merchant_order_id'),
                'preference_id' => $this->request->getVar('preference_id'),
                'site_id' => $this->request->getVar('site_id'),
                'processing_mode' => $this->request->getVar('processing_mode'),
                'merchant_account_id' => $this->request->getVar('merchant_account_id'),
            ];

            if ($data['status'] == 'approved') $approved = 1;

            $existingBooking = $bookingsModel->where('id_preference_parcial', $preferenceId)
                ->orWhere('id_preference_total', $preferenceId)
                ->first();

            if ($preferenceId == $existingBooking['id_preference_parcial']) {
                $paid = $existingBooking['parcial'];
            } else {
                $paid = $existingBooking['total'];
            }

            $total_payment = $paid == $existingBooking['total'];

            $customer = $customersModel->where('complete_phone', $existingBooking['phone'])->first();

            $queryBooking = [
                'mp' => 1,
                'total_payment' => $total_payment,
                'diference' => $existingBooking['total'] - $paid,
                'reservation' => $paid,
                'payment' => $paid,
                'id_customer' => $customer['id'],
                'approved' => 1
            ];

            $queryCustomer = [
                'quantity' => $customer['quantity'] + 1,
            ];

            $bookingsModel->update($existingBooking['id'], $queryBooking);
            $customersModel->update($customer['id'], $queryCustomer);

            $data['id_booking'] = $existingBooking['id'];
            $mercadoPagoModel->insert($data);

            $booking = $bookingsModel->find($existingBooking['id']);
            $mercadoPago =  $mercadoPagoModel->where('id_booking', $existingBooking['id'])->first();
        }

        return view('mercadoPago/success', ['bookingId' => $existingBooking['id'], 'booking' => $booking, 'mercadoPago' => $mercadoPago]);
    }

    public function failure()
    {
        $mercadoPagoModel = new MercadoPagoModel();
        $bookingsModel = new BookingsModel();

        $preferenceId = $this->request->getVar('preference_id');


        if (!empty($preferenceId)) {
            $data = [
                'collection_id' => $this->request->getVar('collection_id'),
                'collection_status' => $this->request->getVar('collection_status'),
                'payment_id' => $this->request->getVar('payment_id'),
                'status' => $this->request->getVar('status'),
                'external_reference' => $this->request->getVar('external_reference'),
                'payment_type' => $this->request->getVar('payment_type'),
                'merchant_order_id' => $this->request->getVar('merchant_order_id'),
                'preference_id' => $this->request->getVar('preference_id'),
                'site_id' => $this->request->getVar('site_id'),
                'processing_mode' => $this->request->getVar('processing_mode'),
                'merchant_account_id' => $this->request->getVar('merchant_account_id'),
            ];

            $existingBooking = $bookingsModel->where('id_preference_parcial', $data['preference_id'])
                ->orWhere('id_preference_total', $data['preference_id'])
                ->first();

            if ($data['status'] != 'approved') $bookingsModel->update($existingBooking['id'], ['approved' => 0]);

            $data['id_booking'] = $existingBooking['id'];
            $mercadoPagoModel->insert($data);
        }

        return view('mercadoPago/failure');
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

    public function verPruebas()
    {
        return view('superadmin/reportes');
    }
}
