<?php

namespace App\Controllers;

use App\Models\BookingsModel;
use App\Models\CustomersModel;
use App\Models\FieldsModel;
use App\Models\MercadoPagoModel;
use App\Models\OffersModel;
use App\Models\SpecialBookingRequestsModel;
use App\Models\TimeModel;
use App\Models\UploadModel;
use DateInterval;
use DateTime;
use Config\Services;

class Home extends BaseController
{
    private function createEmailService()
    {
        $email = Services::email();
        $email->SMTPTimeout = 8;

        return $email;
    }

    private function sendEmailWithFallback($to, string $subject, string $message, bool $isHtml = false): bool
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
                $email->setMailType($isHtml ? 'html' : 'text');
                $email->setMessage($message);

                if ($email->send()) {
                    return true;
                }
            } catch (\Throwable $e) {
                log_message('error', 'Fallo envio solicitud especial: ' . $e->getMessage());
            }
        }

        return false;
    }

    private function renderBookingLanding(array $prefill = [], string $prefillToken = '')
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

        $timeModel = new TimeModel();
        $openingTime = array_slice($timeModel->getOpeningTime(), 0, -7);

        $firstRow = $timeModel->first();
        $isSunday = $firstRow['is_sunday'] ?? 0;

        return view('index', [
            'fields' => $fields,
            'time' => $openingTime,
            'oferta' => $oferta,
            'esDomingo' => $isSunday,
            'prefill' => $prefill,
            'prefillToken' => $prefillToken,
        ]);
    }

    private function resolveSpecialBookingRequestAccessAction(array $prefill): ?string
    {
        if (($prefill['type'] ?? '') !== 'special_booking_request') {
            return null;
        }

        $requestId = (int) ($prefill['request_id'] ?? 0);
        if ($requestId <= 0) {
            return 'invalid';
        }

        $specialBookingRequestsModel = new SpecialBookingRequestsModel();
        $requestItem = $specialBookingRequestsModel->find($requestId);

        if (!$requestItem) {
            return 'unavailable';
        }

        if ($this->hasActiveBookingForSpecialRequest($prefill)) {
            return 'already_confirmed';
        }

        switch ((string) ($requestItem['status'] ?? 'new')) {
            case 'cancelled':
                return 'already_cancelled';
            case 'confirmed':
                return 'already_confirmed';
            default:
                return null;
        }
    }

    private function hasActiveBookingForSpecialRequest(array $prefill): bool
    {
        $requestedDate = trim((string) ($prefill['date'] ?? ''));
        $fieldId = (int) ($prefill['field_id'] ?? 0);
        $timeFrom = trim((string) ($prefill['time_from'] ?? ''));
        $timeUntil = trim((string) ($prefill['time_until'] ?? ''));

        if ($requestedDate === '' || $fieldId <= 0 || $timeFrom === '' || $timeUntil === '') {
            return false;
        }

        $bookingsModel = new BookingsModel();
        $bookingQuery = $bookingsModel->where('date', $requestedDate)
            ->where('id_field', $fieldId)
            ->where('time_from', $timeFrom)
            ->where('time_until', $timeUntil)
            ->where('annulled', 0);

        $phone = trim((string) ($prefill['phone'] ?? ''));
        if ($phone !== '') {
            $bookingQuery->where('phone', $phone);
        }

        return (bool) $bookingQuery->first();
    }

    private function redirectWithSpecialRequestAction(string $action)
    {
        return redirect()->to(site_url('/') . '?specialRequestAction=' . rawurlencode($action));
    }

    public function index()
    {
        $prefillToken = trim((string) $this->request->getGet('request'));
        $prefill = $this->parseReservationAccessToken($prefillToken);
        $specialRequestAction = $this->resolveSpecialBookingRequestAccessAction($prefill);

        if ($specialRequestAction !== null) {
            return $this->redirectWithSpecialRequestAction($specialRequestAction);
        }

        return $this->renderBookingLanding($prefill, $prefillToken);
    }

    public function confirmReservation(?string $token = null)
    {
        $resolvedToken = trim((string) ($token ?? $this->request->getGet('request')));
        $prefill = $this->parseReservationAccessToken($resolvedToken);
        $specialRequestAction = $this->resolveSpecialBookingRequestAccessAction($prefill);

        if ($specialRequestAction !== null) {
            return $this->redirectWithSpecialRequestAction($specialRequestAction);
        }

        return $this->renderBookingLanding($prefill, $resolvedToken);
    }

    public function cancelSpecialBookingRequest(?string $token = null)
    {
        $resolvedToken = trim((string) ($token ?? $this->request->getGet('request')));
        $prefill = $this->parseReservationAccessToken($resolvedToken);

        if (($prefill['type'] ?? '') !== 'special_booking_request') {
            return $this->redirectWithSpecialRequestAction('invalid');
        }

        $requestId = (int) ($prefill['request_id'] ?? 0);
        if ($requestId <= 0) {
            return $this->redirectWithSpecialRequestAction('invalid');
        }

        $specialBookingRequestsModel = new SpecialBookingRequestsModel();
        $requestItem = $specialBookingRequestsModel->find($requestId);

        if (!$requestItem) {
            return $this->redirectWithSpecialRequestAction('unavailable');
        }

        if ($this->hasActiveBookingForSpecialRequest($prefill)) {
            return $this->redirectWithSpecialRequestAction('already_confirmed');
        }

        $status = (string) ($requestItem['status'] ?? 'new');
        if ($status === 'cancelled') {
            return $this->redirectWithSpecialRequestAction('already_cancelled');
        }

        if ($status === 'confirmed') {
            return $this->redirectWithSpecialRequestAction('already_confirmed');
        }

        $specialBookingRequestsModel->update($requestId, [
            'status' => 'cancelled',
            'updated_at' => date('Y-m-d H:i:s'),
        ]);

        return $this->redirectWithSpecialRequestAction('cancelled');
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
            'visitantes'   => $data->visitantes,
            'coordinadores' => (int) ($data->coordinadores ?? 0),
            'entradasCobradas' => (int) ($data->entradasCobradas ?? $data->visitantes)
        ];

        try {
            return  $this->response->setJSON($this->setResponse(null, null, $datosReserva, 'Respuesta exitosa'));
        } catch (\Exception $e) {
            return  $this->response->setJSON($this->setResponse(404, true, null, $e->getMessage()));
        }
    }

    public function requestSpecialBooking()
    {
        $uploadModel = new UploadModel();
        $specialBookingRequestsModel = new SpecialBookingRequestsModel();
        $fieldsModel = new FieldsModel();
        $data = $this->request->getJSON();
        $notificationEmail = trim((string) (($uploadModel->first()['notification_email'] ?? '')));
        $notificationEmailList = array_values(array_filter(array_map('trim', explode(';', $notificationEmail))));

        if ($notificationEmailList === []) {
            return $this->response->setJSON($this->setResponse(400, true, null, 'No hay email configurado para recibir solicitudes especiales.'));
        }

        $visitors = (int) ($data->visitantes ?? 0);
        $formattedDate = '';
        if (!empty($data->fecha)) {
            $parsedDate = DateTime::createFromFormat('Y-m-d', (string) $data->fecha) ?: date_create((string) $data->fecha);
            if ($parsedDate instanceof \DateTimeInterface) {
                $formattedDate = $parsedDate->format('d/m/Y');
            }
        }

        $minimumVisitors = (int) ($data->minVisitantes ?? 0);
        if ($visitors <= 0) {
            return $this->response->setJSON($this->setResponse(400, true, null, 'Debe indicar una cantidad valida de visitantes.'));
        }

        if ($minimumVisitors > 0 && $visitors < $minimumVisitors) {
            return $this->response->setJSON($this->setResponse(400, true, null, 'La cantidad de visitantes no puede ser menor al minimo requerido.'));
        }

        $customerName = trim((string) ($data->nombre ?? ''));
        $customerLastName = trim((string) ($data->apellido ?? ''));
        $customerPhone = trim((string) ($data->telefono ?? ''));
        $customerEmail = trim((string) ($data->email ?? ''));
        $customerDni = trim((string) ($data->dni ?? ''));
        $customerCity = trim((string) ($data->ciudad ?? ''));
        $customerInstitutionType = trim((string) ($data->tipoInstitucion ?? ''));
        $selectedFieldId = !empty($data->cancha) ? (int) $data->cancha : null;
        $timeFrom = trim((string) ($data->horarioDesde ?? ''));
        $timeUntil = trim((string) ($data->horarioHasta ?? ''));
        $coordinators = max(0, (int) ($data->coordinadores ?? 0));
        $chargedTickets = max(0, (int) ($data->entradasCobradas ?? $visitors));
        $totalAmount = isset($data->importeTotal) ? (float) $data->importeTotal : null;
        $formattedTime = trim($timeFrom !== '' || $timeUntil !== '' ? ($timeFrom . ' a ' . $timeUntil) : '');
        $selectedField = $selectedFieldId ? $fieldsModel->getField($selectedFieldId) : null;
        $selectedFieldName = trim((string) ($selectedField['name'] ?? ''));

        if ($selectedFieldId === null || $selectedFieldName === '') {
            return $this->response->setJSON($this->setResponse(400, true, null, 'Debe seleccionar un servicio valido.'));
        }

        $requestMessage = sprintf(
            'El cliente desea reservar %s para el %s%s con menos de 48 hs de anticipacion. Nombre: %s%s. Telefono: %s. Email: %s. DNI: %s. Ciudad: %s. Tipo: %s. Visitantes solicitados: %s. Coordinadores sin cargo: %s. Entradas a cobrar: %s. Minimo requerido: %s. Importe estimado: %s.',
            $selectedFieldName !== '' ? 'el servicio ' . $selectedFieldName : 'un servicio',
            $formattedDate !== '' ? $formattedDate : ((string) ($data->fechaDisplay ?? 'Fecha a confirmar')),
            $formattedTime !== '' ? ' en el horario ' . $formattedTime : '',
            $customerName,
            $customerLastName !== '' ? ' ' . $customerLastName : '',
            $customerPhone,
            $customerEmail !== '' ? $customerEmail : 'No informado',
            $customerDni !== '' ? $customerDni : 'No informado',
            $customerCity !== '' ? $customerCity : 'No informada',
            $customerInstitutionType !== '' ? $customerInstitutionType : 'No informado',
            (string) $visitors,
            (string) $coordinators,
            (string) $chargedTickets,
            (string) $minimumVisitors,
            $totalAmount !== null ? '$' . number_format($totalAmount, 2, ',', '.') : 'No calculado'
        );

        $requestId = $specialBookingRequestsModel->insert([
            'requested_date' => !empty($data->fecha) ? (string) $data->fecha : null,
            'customer_id' => !empty($data->customerId) ? (int) $data->customerId : null,
            'field_id' => $selectedFieldId,
            'field_name' => $selectedFieldName !== '' ? $selectedFieldName : null,
            'customer_name' => $customerName,
            'customer_last_name' => $customerLastName !== '' ? $customerLastName : null,
            'customer_phone' => $customerPhone,
            'customer_email' => $customerEmail !== '' ? $customerEmail : null,
            'customer_dni' => $customerDni !== '' ? $customerDni : null,
            'customer_city' => $customerCity !== '' ? $customerCity : null,
            'customer_type_institution' => $customerInstitutionType !== '' ? $customerInstitutionType : null,
            'time_from' => $timeFrom !== '' ? $timeFrom : null,
            'time_until' => $timeUntil !== '' ? $timeUntil : null,
            'visitors' => $visitors,
            'minimum_visitors' => $minimumVisitors,
            'total_amount' => $totalAmount,
            'request_message' => $requestMessage,
            'status' => 'new',
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
        ], true);

        $subject = 'Solicitud de reserva con menos de 48 hs - ' . ($formattedDate !== '' ? $formattedDate : 'Fecha a confirmar');
        $html = $this->renderEmailCard([
            'eyebrow' => 'Solicitud especial',
            'title' => 'Consulta por reserva con menos de 48 hs',
            'intro' => 'Un cliente solicito evaluar una reserva para una fecha cercana.',
            'details' => [
                'Solicitud' => '#' . $requestId,
                'Fecha solicitada' => $formattedDate !== '' ? $formattedDate : (string) ($data->fechaDisplay ?? ''),
                'Servicio' => $selectedFieldName !== '' ? $selectedFieldName : 'No informado',
                'Nombre' => (string) ($data->nombre ?? ''),
                'Apellido' => $customerLastName !== '' ? $customerLastName : 'No informado',
                'Telefono' => (string) ($data->telefono ?? ''),
                'Email' => (string) ($data->email ?? 'No informado'),
                'Horario solicitado' => $formattedTime !== '' ? $formattedTime : 'No informado',
                'DNI' => $customerDni !== '' ? $customerDni : 'No informado',
                'Ciudad' => $customerCity !== '' ? $customerCity : 'No informada',
                'Tipo / Institucion' => $customerInstitutionType !== '' ? $customerInstitutionType : 'No informado',
                'Visitantes solicitados' => (string) ($data->visitantes ?? ''),
                'Coordinadores sin cargo' => (string) $coordinators,
                'Entradas a cobrar' => (string) $chargedTickets,
                'Minimo requerido' => (string) $minimumVisitors,
                'Importe estimado' => $totalAmount !== null ? '$' . number_format($totalAmount, 2, ',', '.') : 'No calculado',
            ],
            'messageHtml' => '<p>' . esc($requestMessage) . '</p><p>Revisar disponibilidad manualmente y responder por los canales habituales.</p>',
        ]);

        if (!$this->sendEmailWithFallback($notificationEmailList, $subject, $html, true)) {
            return $this->response->setJSON($this->setResponse(400, true, null, 'No se pudo enviar la solicitud especial.'));
        }

        return $this->response->setJSON($this->setResponse(null, false, null, 'Solicitud enviada correctamente. Te contactaremos para confirmar disponibilidad.'));
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
