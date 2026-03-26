<?php

namespace App\Controllers;

use App\Models\BookingsModel;
use App\Models\CustomersModel;
use App\Models\FieldsModel;
use App\Models\MercadoPagoKeysModel;
use App\Models\OffersModel;
use App\Models\PaymentsModel;
use App\Models\RateModel;
use App\Models\TimeModel;
use App\Models\UsersModel;
use App\Models\UploadModel;
use App\Models\ValuesModel;

class Superadmin extends BaseController
{
    public function index()
    {
        $bookingsModel = new BookingsModel();
        $fieldsModel = new FieldsModel();
        $rateModel = new RateModel();
        $customersModel = new CustomersModel();
        $timeModel = new TimeModel();
        $usersModel = new UsersModel();
        $offerModel = new OffersModel();
        $uploadModel = new UploadModel();
        $valuesModel = new ValuesModel();

        $users = $usersModel->findAll();

        $values = $valuesModel->where('disabled', 0)->findAll();

        $bookings = [];

        foreach ($bookingsModel->getBookings() as $booking) {
            $reserva = [
                'id' => $booking['id'],
                'cancha' => $fieldsModel->getField($booking['id_field'])['name'],
                'fecha' => date("d/m/Y", strtotime($booking['date'])),
                'horario' => $booking['time_from'] . ' a ' . $booking['time_until'],
                'nombre' => $booking['name'],
                'telefono' => $booking['phone'],
                'pago_total' => $booking['total_payment'] == 1 ? 'Si' : 'No',
                'total_reserva' => $booking['total'],
                'diferencia' => $booking['diference'],
                'monto_reserva' => $booking['payment'],
                'metodo_pago' => $booking['payment_method']
            ];

            array_push($bookings, $reserva);
        }

        $getTime = $timeModel->findAll();
        if ($getTime) {
            $time = $getTime[0];
        } else {
            $time = [
                'from' => 0,
                'until' => 0,
                'from_cut' => 0,
                'until_cut' => 0,
                'nocturnal_time' => 0
            ];
        }

        $openingTime = $timeModel->getOpeningTime();

        $getRate = $rateModel->findAll();
        if ($getRate) {
            $rate = $getRate[0];
        } else {
            $rate = 0;
        }

        $getOfferRate = $offerModel->findAll();
        if ($getOfferRate) {
            $offerRate = $getOfferRate[0];
        } else {
            $offerRate = 0;
        }

        $fields = $fieldsModel->findAll();

        $customers = $customersModel->where('deleted', 0)->findAll();

        $logo = $uploadModel->first();

        return view('superadmin/index', ['bookings' => $bookings, 'rate' => $rate, 'customers' => $customers, 'time' => $time, 'openingTime' => $openingTime, 'fields' => $fields, 'users' => $users, 'offerRate' => $offerRate, 'logo' => $logo, 'values' => $values]);
    }

    public function getValue($type)
    {
        $valuesModel = new ValuesModel();
        $value = $valuesModel->where('value', $type)->first();

        try {
            return  $this->response->setJSON($this->setResponse(null, null, $value, 'Respuesta exitosa'));
        } catch (\Exception $e) {
            return  $this->response->setJSON($this->setResponse(404, true, null, $e->getMessage()));
        }
    }

    public function saveValue()
    {
        $valuesModel = new ValuesModel();

        $nombre = $this->request->getVar('serviceName');
        $valor = $this->request->getVar('serviceAmount');
        $value = $this->request->getVar('serviceValue');
        $id = $this->request->getVar('idValue');

        $query = [
            'name' => $nombre,
            'amount' => $valor,
            'value' => $value,
            'disabled' => 0,
        ];

        if ($nombre == '' || $valor == '') {
            return redirect()->to('abmAdmin')->with('msg', ['type' => 'danger', 'body' => 'Debe ingresar todos los datos']);
        }

        if ($value == '') {
            $valor = strtolower(str_replace(' ', '_', $nombre));
            $query['value'] = $valor;
        }

        try {
            if ($id != '') {
                $valuesModel->update($id, $query);
                return redirect()->to('abmAdmin')->with('msg', ['type' => 'success', 'body' => 'Actualizado correctamente']);
            } else {
                $valuesModel->insert($query);
                return redirect()->to('abmAdmin')->with('msg', ['type' => 'success', 'body' => 'Creado correctamente']);
            }
        } catch (\Exception $e) {
            return redirect()->to('abmAdmin')->with('msg', ['type' => 'danger', 'body' => 'Error al insertar datos: ' . $e->getMessage()]);
        }
    }

    public function saveField()
    {
        $fieldsModel = new FieldsModel();

        $this->request->getVar('iluminacion') ? $iluminacion = true : $iluminacion = false;
        $this->request->getVar('tipoTecho') ? $techada = true : $techada = false;

        $nombre = $this->request->getVar('nombre');
        $medidas = $this->request->getVar('medidas');
        $tipoPiso = $this->request->getVar('tipoPiso');
        $tipoCancha = $this->request->getVar('tipoCancha');
        $valor = $this->request->getVar('valor');
        $valorIluminacion = $this->request->getVar('valorIluminacion');


        $query = [
            'name' => $nombre,
            'sizes' => $medidas,
            'floor_type' => $tipoPiso,
            'field_type' => $tipoCancha,
            'ilumination' => $iluminacion,
            'roofed' => $techada,
            'value' => $valor,
            'ilumination_value' => $valorIluminacion,
            'disabled' => 0,
        ];

        if ($nombre == '' || $medidas == '' || $tipoPiso == '' || $tipoCancha == '' || $valor == '' || $valorIluminacion == '') {
            return redirect()->to('abmAdmin')->with('msg', ['type' => 'danger', 'body' => 'Debe ingresar todos los datos']);
        }


        try {
            $fieldsModel->insert($query);
        } catch (\Exception $e) {
            return redirect()->to('abmAdmin')->with('msg', ['type' => 'danger', 'body' => 'Error al insertar datos: ' . $e->getMessage()]);
        }

        return redirect()->to('abmAdmin')->with('msg', ['type' => 'success', 'body' => 'Cancha creada correctamente']);
    }

    public function editField($id)
    {
        $fieldsModel = new FieldsModel();

        $this->request->getVar('iluminacion') ? $iluminacion = true : $iluminacion = false;
        $this->request->getVar('tipoTecho') ? $techada = true : $techada = false;

        $nombre = $this->request->getVar('nombre');
        // $medidas = $this->request->getVar('medidas');
        // $tipoPiso = $this->request->getVar('tipoPiso');
        // $tipoCancha = $this->request->getVar('tipoCancha');
        $valor = $this->request->getVar('valor');
        $valorIluminacion = $this->request->getVar('valorIluminacion');
        $disabled = $this->request->getVar('disabled');


        $query = [
            'name' => $nombre,
            // 'sizes' => $medidas,
            // 'floor_type' => $tipoPiso,
            // 'field_type' => $tipoCancha,
            'ilumination' => $iluminacion,
            'roofed' => $techada,
            'value' => $valor,
            'ilumination_value' => $valorIluminacion,
            'disabled' => $disabled,
        ];

        // if ($nombre == '' || $medidas == '' || $tipoPiso == '' || $tipoCancha == '' || $valor == '' || $valorIluminacion == '') {
        //     return redirect()->to('abmAdmin')->with('msg', ['type' => 'danger', 'body' => 'Debe ingresar todos los datos']);
        // }

        try {
            $fieldsModel->update($id, $query);
        } catch (\Exception $e) {
            return redirect()->to('abmAdmin')->with('msg', ['type' => 'danger', 'body' => 'Error al insertar datos: ' . $e->getMessage()]);
        }

        return redirect()->to('abmAdmin')->with('msg', ['type' => 'success', 'body' => 'Editado correctamente']);
    }

    public function getActiveBookings()
    {
        $fieldsModel = new FieldsModel();
        $bookingsModel = new BookingsModel();
        $data = $this->request->getJSON();

        $getBookings = $bookingsModel->where('date >=', $data->fechaDesde)
            ->where('date <=', $data->fechaHasta)
            ->where('annulled', 0)
            ->orderBy('time_from', 'ASC')
            ->findAll();

        $bookings = [];

        foreach ($getBookings as $booking) {
            $reserva = [
                'id' => $booking['id'],
                'cancha' => $fieldsModel->getField($booking['id_field'])['name'],
                'fecha' => date("d/m/Y", strtotime($booking['date'])),
                // 'horario' => $booking['time_from'] . ' a ' . $booking['time_until'],
                'horario' => $booking['time_from'],
                'nombre' => $booking['name'],
                'telefono' => $booking['phone'],
                'pago_total' => $booking['total_payment'] == 1 ? 'Si' : 'No',
                'total_reserva' => $booking['total'],
                'diferencia' => $booking['diference'],
                'visitantes' => $booking['visitors'],
                'monto_reserva' => $booking['payment'],
                'descripcion' => $booking['description'],
                'metodo_pago' => $booking['payment_method'],
                'anulada'     => $booking['annulled'],
                'mp'        => $booking['mp'],
                'code'        => $booking['code'],
            ];

            array_push($bookings, $reserva);
        }

        try {
            return  $this->response->setJSON($this->setResponse(null, null, $bookings, 'Respuesta exitosa'));
        } catch (\Exception $e) {
            return  $this->response->setJSON($this->setResponse(404, true, null, $e->getMessage()));
        }
    }

    public function getAnnulledBookings()
    {
        $fieldsModel = new FieldsModel();
        $bookingsModel = new BookingsModel();
        $data = $this->request->getJSON();

        $getBookings = $bookingsModel->where('date >=', $data->fechaDesde)
            ->where('date <=', $data->fechaHasta)
            ->where('annulled', 1)
            ->orderBy('time_from', 'ASC')
            ->findAll();

        $bookings = [];

        foreach ($getBookings as $booking) {
            $reserva = [
                'id' => $booking['id'],
                'cancha' => $fieldsModel->getField($booking['id_field'])['name'],
                'fecha' => date("d/m/Y", strtotime($booking['date'])),
                'horario' => $booking['time_from'] . ' a ' . $booking['time_until'],
                'nombre' => $booking['name'],
                'telefono' => $booking['phone'],
                'pago_total' => $booking['total_payment'] == 1 ? 'Si' : 'No',
                'total_reserva' => $booking['total'],
                'diferencia' => $booking['diference'],
                'monto_reserva' => $booking['payment'],
                'descripcion' => $booking['description'],
                'metodo_pago' => $booking['payment_method'],
                'anulada'     => $booking['annulled'],
            ];

            array_push($bookings, $reserva);
        }

        try {
            return  $this->response->setJSON($this->setResponse(null, null, $bookings, 'Respuesta exitosa'));
        } catch (\Exception $e) {
            return  $this->response->setJSON($this->setResponse(404, true, null, $e->getMessage()));
        }
    }

    public function configMpView()
    {
        return view('mercadoPago/config', ['errors' => []]);
    }

    public function configMp()
    {
        $mpKeysModel = new MercadoPagoKeysModel();

        $publicKey = $this->request->getVar('publicKeyMp');
        $accessToken = $this->request->getVar('accesTokenMp');

        // 1. Validar que al menos uno de los valores no esté vacío
        if (empty($publicKey) && empty($accessToken)) {
            return redirect()->to('abmAdmin')->with('msg', ['type' => 'danger', 'body' => 'Debe proporcionar al menos una clave (Public Key o Access Token).']);
        }

        $query = [
            'public_key'   => $publicKey,
            'access_token' => $accessToken,
        ];

        try {
            $mpKeysModel->emptyTable();

            // 2. Insertar el nuevo registro
            $mpKeysModel->insert($query);

            return redirect()->to('abmAdmin')->with('msg', ['type' => 'success', 'body' => 'Configuración de Mercado Pago guardada con éxito.']);
        } catch (\Exception $e) {
            // En caso de error, puedes considerar si revertir la eliminación es necesario,
            // pero para este caso de 'siempre un solo registro' no suele serlo.
            return redirect()->to('abmAdmin')->with('msg', ['type' => 'danger', 'body' => 'Error al guardar la configuración: ' . $e->getMessage()]);
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
