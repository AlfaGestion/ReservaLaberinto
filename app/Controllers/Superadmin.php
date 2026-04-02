<?php

namespace App\Controllers;

use App\Models\BookingsModel;
use App\Models\CustomersModel;
use App\Models\FieldsModel;
use App\Models\MercadoPagoModel;
use App\Models\MercadoPagoKeysModel;
use App\Models\OffersModel;
use App\Models\PaymentsModel;
use App\Models\RateModel;
use App\Models\TimeModel;
use App\Models\UsersModel;
use App\Models\UploadModel;
use App\Models\ValuesModel;
use CodeIgniter\HTTP\ResponseInterface;
use Config\Services;

class Superadmin extends BaseController
{
    private function formatBookingDate(string $rawDate): string
    {
        $rawDate = trim($rawDate);
        if ($rawDate === '') {
            return '';
        }

        $parsedDate = \DateTime::createFromFormat('Y-m-d', $rawDate)
            ?: \DateTime::createFromFormat('d/m/Y', $rawDate)
            ?: date_create($rawDate);

        if ($parsedDate === false) {
            return $rawDate;
        }

        return $parsedDate->format('d/m/Y');
    }

    private function createEmailService()
    {
        $email = Services::email();
        $email->SMTPTimeout = 8;

        return $email;
    }

    private function sendEmailWithFallback($to, string $subject, string $message): bool
    {
        $emailConfig = config('Email');
        $accounts = $emailConfig->accounts ?? [];

        if ($accounts === []) {
            $accounts = [[
                'fromEmail' => $emailConfig->fromEmail,
                'fromName' => $emailConfig->fromName,
                'SMTPUser' => $emailConfig->SMTPUser,
                'SMTPPass' => $emailConfig->SMTPPass,
            ]];
        }

        foreach ($accounts as $account) {
            try {
                $email = $this->createEmailService();
                $email->fromEmail = $account['fromEmail'] ?? $emailConfig->fromEmail;
                $email->fromName = $account['fromName'] ?? $emailConfig->fromName;
                $email->SMTPUser = $account['SMTPUser'] ?? $emailConfig->SMTPUser;
                $email->SMTPPass = $account['SMTPPass'] ?? $emailConfig->SMTPPass;
                $email->setFrom($email->fromEmail, $email->fromName);
                $email->setTo($to);
                $email->setSubject($subject);
                $email->setMessage($message);

                if ($email->send()) {
                    return true;
                }

                log_message('error', 'Fallo envio SMTP con ' . ($email->fromEmail ?? 'sin cuenta') . ': ' . $email->printDebugger(['headers']));
            } catch (\Throwable $e) {
                log_message('error', 'Fallo envio SMTP con ' . (($account['fromEmail'] ?? '') ?: 'sin cuenta') . ': ' . $e->getMessage());
            }
        }

        return false;
    }

    private function resolveCustomerEmail(array $booking): string
    {
        $customersModel = new CustomersModel();

        if (!empty($booking['id_customer'])) {
            $customer = $customersModel->find($booking['id_customer']);
            $customerEmail = trim((string) ($customer['email'] ?? ''));
            if ($customerEmail !== '' && filter_var($customerEmail, FILTER_VALIDATE_EMAIL)) {
                return $customerEmail;
            }
        }

        $phone = trim((string) ($booking['phone'] ?? ''));
        if ($phone === '') {
            return '';
        }

        $customer = $customersModel->groupStart()
            ->where('phone', $phone)
            ->orWhere('complete_phone', $phone)
            ->groupEnd()
            ->first();

        $customerEmail = trim((string) ($customer['email'] ?? ''));
        if ($customerEmail !== '' && filter_var($customerEmail, FILTER_VALIDATE_EMAIL)) {
            return $customerEmail;
        }

        return '';
    }

    private function sendBookingEmails(array $booking): void
    {
        $uploadModel = new UploadModel();
        $config = $uploadModel->first();
        $notificationEmail = trim((string) ($config['notification_email'] ?? ''));
        $notificationEmailList = array_values(array_filter(array_map('trim', explode(';', $notificationEmail))));

        if ($notificationEmailList !== []) {
            $formattedDate = $this->formatBookingDate((string) ($booking['date'] ?? ''));
            $subjectName = trim((string) ($booking['name'] ?? ''));
            $subjectTimeFrom = trim((string) ($booking['time_from'] ?? ''));
            $subject = "Reserva - Laberinto: {$subjectName} - {$formattedDate} {$subjectTimeFrom}";

            $message = "Se recibio una nueva reserva.\n\n";
            $message .= 'Nombre: ' . ($booking['name'] ?? '') . "\n";
            $message .= 'Telefono: ' . ($booking['phone'] ?? '') . "\n";
            $message .= 'Fecha: ' . $formattedDate . "\n";
            $message .= 'Horario desde: ' . ($booking['time_from'] ?? '') . "\n";
            $message .= 'Horario hasta: ' . ($booking['time_until'] ?? '') . "\n";
            $message .= 'Visitantes: ' . ($booking['visitors'] ?? '') . "\n";
            $message .= 'Total: ' . ($booking['total'] ?? '') . "\n";
            $message .= 'Codigo: ' . ($booking['code'] ?? '') . "\n";
            $message .= 'Ver reserva: ' . site_url('MisReservas/' . rawurlencode($this->buildReservationAccessToken([
                'code' => (string) ($booking['code'] ?? ''),
            ]))) . "\n";

            $this->sendEmailWithFallback($notificationEmailList, $subject, $message);
        }

        $customerEmail = $this->resolveCustomerEmail($booking);
        if ($customerEmail === '') {
            return;
        }

        $customerName = trim((string) ($booking['name'] ?? 'Cliente'));
        $formattedDate = $this->formatBookingDate((string) ($booking['date'] ?? ''));
        $subject = "Reserva confirmada - Laberinto: {$customerName}";

        $message = "Hola {$customerName}, tu reserva fue registrada correctamente.\n\n";
        $message .= 'Nombre: ' . $customerName . "\n";
        $message .= 'Fecha: ' . $formattedDate . "\n";
        $message .= 'Horario: ' . trim(($booking['time_from'] ?? '') . ' a ' . ($booking['time_until'] ?? '')) . "\n";
        $message .= 'Cantidad: ' . ($booking['visitors'] ?? '') . "\n";
        $message .= 'Total: ' . ($booking['total'] ?? '') . "\n";
        $message .= 'Codigo: ' . ($booking['code'] ?? '') . "\n\n";
        $message .= "Importante:\n";
        $message .= "Se asume el compromiso y la responsabilidad de asistir en el dia y horario acordados. ";
        $message .= "En caso de inasistencia, no se realizaran devoluciones de dinero y la reprogramacion quedara sujeta a disponibilidad.\n\n";
        $bookingLink = site_url('MisReservas/' . rawurlencode($this->buildReservationAccessToken([
            'code' => (string) ($booking['code'] ?? ''),
            'phone' => (string) ($booking['phone'] ?? ''),
            'email' => $customerEmail,
        ])));
        $message .= "Ver tus reservas:\n" . $bookingLink . "\n";

        $this->sendEmailWithFallback($customerEmail, $subject, $message);
    }

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

        $latestBookingRow = $bookingsModel->selectMax('date', 'latest_date')->first();
        $latestBookingDate = $latestBookingRow['latest_date'] ?? date('Y-m-d');
        $today = date('Y-m-d');

        if (empty($latestBookingDate) || $latestBookingDate < $today) {
            $latestBookingDate = $today;
        }

        $weekStart = date('Y-m-d', strtotime('monday this week'));

        return view('superadmin/index', [
            'bookings' => $bookings,
            'rate' => $rate,
            'customers' => $customers,
            'time' => $time,
            'openingTime' => $openingTime,
            'fields' => $fields,
            'users' => $users,
            'offerRate' => $offerRate,
            'logo' => $logo,
            'values' => $values,
            'latestBookingDate' => $latestBookingDate,
            'weekStart' => $weekStart,
        ]);
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
        $isAjax = $this->request->isAJAX();

        $nombre = $this->request->getVar('serviceName');
        $valor = $this->request->getVar('serviceAmount');
        $dto = $this->request->getVar('serviceDiscountPercentage');
        $value = $this->request->getVar('serviceValue');
        $id = $this->request->getVar('idValue');

        $query = [
            'name' => $nombre,
            'amount' => $valor,
            'discount_percentage' => $dto === '' ? 0 : $dto,
            'value' => $value,
            'disabled' => 0,
        ];

        if ($nombre == '' || $valor == '') {
            if ($isAjax) {
                return $this->response->setStatusCode(ResponseInterface::HTTP_BAD_REQUEST)
                    ->setJSON(['error' => true, 'message' => 'Debe ingresar todos los datos']);
            }
            return redirect()->to('abmAdmin')->with('msg', ['type' => 'danger', 'body' => 'Debe ingresar todos los datos']);
        }

        if ($value == '') {
            $valor = strtolower(str_replace(' ', '_', $nombre));
            $query['value'] = $valor;
        }

        try {
            if ($id != '') {
                $valuesModel->update($id, $query);
                $savedValue = $valuesModel->find($id);
                if ($isAjax) {
                    return $this->response->setJSON([
                        'error' => false,
                        'action' => 'updated',
                        'message' => 'Actualizado correctamente',
                        'item' => $savedValue,
                    ]);
                }
                return redirect()->to('abmAdmin')->with('msg', ['type' => 'success', 'body' => 'Actualizado correctamente']);
            } else {
                $newId = $valuesModel->insert($query);
                $savedValue = $valuesModel->find($newId);
                if ($isAjax) {
                    return $this->response->setJSON([
                        'error' => false,
                        'action' => 'created',
                        'message' => 'Creado correctamente',
                        'item' => $savedValue,
                    ]);
                }
                return redirect()->to('abmAdmin')->with('msg', ['type' => 'success', 'body' => 'Creado correctamente']);
            }
        } catch (\Exception $e) {
            if ($isAjax) {
                return $this->response->setStatusCode(ResponseInterface::HTTP_BAD_REQUEST)
                    ->setJSON(['error' => true, 'message' => 'Error al insertar datos: ' . $e->getMessage()]);
            }
            return redirect()->to('abmAdmin')->with('msg', ['type' => 'danger', 'body' => 'Error al insertar datos: ' . $e->getMessage()]);
        }
    }

    public function saveField()
    {
        $fieldsModel = new FieldsModel();
        $isAjax = $this->request->isAJAX();

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
            if ($isAjax) {
                return $this->response->setStatusCode(ResponseInterface::HTTP_BAD_REQUEST)
                    ->setJSON(['error' => true, 'message' => 'Debe ingresar todos los datos']);
            }
            return redirect()->to('abmAdmin')->with('msg', ['type' => 'danger', 'body' => 'Debe ingresar todos los datos']);
        }


        try {
            $newId = $fieldsModel->insert($query);
            if ($isAjax) {
                return $this->response->setJSON([
                    'error' => false,
                    'action' => 'created',
                    'item' => $fieldsModel->find($newId),
                    'message' => 'Cancha creada correctamente',
                ]);
            }
        } catch (\Exception $e) {
            if ($isAjax) {
                return $this->response->setStatusCode(ResponseInterface::HTTP_BAD_REQUEST)
                    ->setJSON(['error' => true, 'message' => 'Error al insertar datos: ' . $e->getMessage()]);
            }
            return redirect()->to('abmAdmin')->with('msg', ['type' => 'danger', 'body' => 'Error al insertar datos: ' . $e->getMessage()]);
        }

        return redirect()->to('abmAdmin')->with('msg', ['type' => 'success', 'body' => 'Cancha creada correctamente']);
    }

    public function editField($id)
    {
        $fieldsModel = new FieldsModel();
        $isAjax = $this->request->isAJAX();

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
            if ($isAjax) {
                return $this->response->setJSON([
                    'error' => false,
                    'action' => 'updated',
                    'item' => $fieldsModel->find($id),
                    'message' => 'Editado correctamente',
                ]);
            }
        } catch (\Exception $e) {
            if ($isAjax) {
                return $this->response->setStatusCode(ResponseInterface::HTTP_BAD_REQUEST)
                    ->setJSON(['error' => true, 'message' => 'Error al insertar datos: ' . $e->getMessage()]);
            }
            return redirect()->to('abmAdmin')->with('msg', ['type' => 'danger', 'body' => 'Error al insertar datos: ' . $e->getMessage()]);
        }

        return redirect()->to('abmAdmin')->with('msg', ['type' => 'success', 'body' => 'Editado correctamente']);
    }

    public function disableField($id)
    {
        $fieldsModel = new FieldsModel();

        try {
            $fieldsModel->update($id, ['disabled' => 1]);

            return $this->response->setJSON([
                'error' => false,
                'message' => 'Servicio deshabilitado correctamente',
                'id' => $id,
            ]);
        } catch (\Exception $e) {
            return $this->response->setStatusCode(ResponseInterface::HTTP_BAD_REQUEST)
                ->setJSON(['error' => true, 'message' => 'No se pudo deshabilitar el servicio']);
        }
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

    public function resendBookingEmail($id)
    {
        $bookingsModel = new BookingsModel();
        $mercadoPagoModel = new MercadoPagoModel();

        $booking = $bookingsModel->find($id);
        if (!$booking) {
            return $this->response->setStatusCode(ResponseInterface::HTTP_NOT_FOUND)
                ->setJSON(['error' => true, 'message' => 'No se encontro la reserva']);
        }

        $mpPayment = $mercadoPagoModel->where('id_booking', $id)->orderBy('id', 'DESC')->first();
        if ($mpPayment) {
            $booking['payment_id'] = $mpPayment['payment_id'] ?? null;
            $booking['payment_status'] = $mpPayment['status'] ?? null;
        }

        try {
            $this->sendBookingEmails($booking);

            return $this->response->setJSON([
                'error' => false,
                'message' => 'Se intento reenviar el email de la reserva',
            ]);
        } catch (\Throwable $e) {
            return $this->response->setStatusCode(ResponseInterface::HTTP_BAD_REQUEST)
                ->setJSON(['error' => true, 'message' => 'No se pudo reenviar el email']);
        }
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

    public function saveWebGeneral()
    {
        $uploadModel = new UploadModel();
        $rateModel = new RateModel();

        $data = $this->request->getJSON();
        $qtyVisitors = $data->qty_visitors ?? null;
        $notificationEmail = trim($data->notification_email ?? '');
        $qtyVisitors = ($qtyVisitors === null || $qtyVisitors === '') ? null : (int) $qtyVisitors;
        $notificationEmailList = array_values(array_filter(array_map('trim', explode(';', $notificationEmail))));

        foreach ($notificationEmailList as $email) {
            if (! filter_var($email, FILTER_VALIDATE_EMAIL)) {
                return $this->response->setStatusCode(ResponseInterface::HTTP_BAD_REQUEST)
                    ->setJSON(['error' => true, 'message' => 'Uno de los emails no es valido']);
            }
        }

        try {
            $existingRate = $rateModel->first();
            if ($existingRate) {
                $rateModel->update($existingRate['id'], ['qty_visitors' => $qtyVisitors]);
            } elseif ($qtyVisitors !== null) {
                $rateModel->insert(['value' => 0, 'qty_visitors' => $qtyVisitors]);
            }

            $existingUpload = $uploadModel->first();
            if ($existingUpload) {
                $uploadModel->update($existingUpload['id'], ['notification_email' => $notificationEmail]);
            } elseif ($notificationEmail !== '') {
                $uploadModel->insert(['notification_email' => $notificationEmail]);
            }

            return $this->response->setJSON([
                'error' => false,
                'message' => 'Configuracion general guardada correctamente',
            ]);
        } catch (\Exception $e) {
            return $this->response->setStatusCode(ResponseInterface::HTTP_BAD_REQUEST)
                ->setJSON(['error' => true, 'message' => 'No se pudo guardar la configuracion general']);
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
