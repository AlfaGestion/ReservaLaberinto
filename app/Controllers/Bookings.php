<?php

namespace App\Controllers;

use App\Controllers\BaseController;
use App\Libraries\PrintBookings;
use App\Models\BookingSlotsModel;
use App\Models\BookingsModel;
use App\Models\CustomersModel;
use App\Models\FieldsModel;
use App\Models\MercadoPagoModel;
use App\Models\PaymentsModel;
use App\Models\TimeModel;
use App\Models\UsersModel;
use App\Models\OffersModel;
use App\Models\UploadModel;
use App\Models\RateModel;
use App\Models\ValuesModel;
use CodeIgniter\I18n\Time;
use Config\Services;

class Bookings extends BaseController
{
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

    private function expireActiveBookingSlots(BookingSlotsModel $bookingSlotsModel, array $conditions): void
    {
        $builder = $bookingSlotsModel->where('active', 1);

        foreach ($conditions as $field => $value) {
            $builder->where($field, $value);
        }

        $slots = $builder->findAll();
        foreach ($slots as $slot) {
            $bookingSlotsModel->update($slot['id'], [
                'active' => 0,
                'status' => 'cancelled',
            ]);
        }
    }

    public function saveBooking()
    {
        $bookingsModel = new BookingsModel();
        $customersModel = new CustomersModel();

        $data = $this->request->getJSON();
        // log_message('info', 'Procesando reserva: ' . json_encode($data, JSON_PRETTY_PRINT));
        $searchTerm = '%' . $data->telefono . '%';

        $queryCustomer = [
            'name'  => $data->nombre,
            'phone' => $data->telefono,
            'complete_phone' => $data->telefono,
            'email' => $data->email ?? '',
            'offer' => 0,
        ];

        // Verificar si ya existe una reserva activa
        $existingBooking = $bookingsModel->where('date', $data->fecha)
            ->where('id_field', $data->cancha)
            ->where('time_from', $data->horarioDesde)
            ->where('time_until', $data->horarioHasta)
            ->where('annulled', 0) // Solo trae las no anuladas
            ->first();

        if ($existingBooking) {
            return $this->response->setJSON($this->setResponse(400, true, null, 'Ya existe una reserva activa para esa fecha, servicio y horario.'));
        }

        // $idCustomer = $customersModel->where('phone', $data->telefono)->first()['id'] ?? null;
        $existingCustomer = $customersModel->where('phone', 'like', $searchTerm)->first();
        // $existingCustomer = $customersModel->where('phone', $data->telefono)->first();
        $exist = true;

        if ($existingCustomer) {
            $exist = false;

            $queryCustomer = [
                'offer' => 0,
                'quantity' => $existingCustomer['quantity'] + 1
            ];

            if (empty($existingCustomer['email']) && !empty($data->email)) {
                $queryCustomer['email'] = $data->email;
            }

            $customersModel->update($existingCustomer['id'], $queryCustomer);
        }

        if ($exist) {
            $customersModel->insert($queryCustomer);
            $idCustomer = $customersModel->getInsertID();
        }

        $queryBooking = [
            'date'                  => $data->fecha,
            'id_field'              => $data->cancha,
            'time_from'             => $data->horarioDesde,
            'time_until'            => $data->horarioHasta,
            'name'                  => $data->nombre,
            'visitors'              => $data->visitantes,
            'phone'                 => $data->telefono,
            'payment'               => $data->monto,
            'approved'              => 0,
            'total'                 => $data->total,
            'parcial'               => $data->parcial,
            'diference'             => $data->diferencia,
            'reservation'           => $data->reservacion,
            'code'                  => $this->codeGenerate(),
            'total_payment'         => $data->pagoTotal,
            'payment_method'        => $data->metodoDePago,
            'id_preference_parcial' => $data->preferenceIdParcial ?? null,
            'id_preference_total'   => $data->preferenceIdTotal ?? null,
            'use_offer'             => $data->oferta,
            'booking_time'          => date("Y-m-d H:i:s"),
            'mp'                    => 0,
            'annulled'              => 0, // Aseguramos que este nuevo registro no esté anulado
            'id_customer'           => $idCustomer,
        ];


        try {
            if (count($queryBooking) != 0) {
                $bookingId = $bookingsModel->insert($queryBooking);
                $this->sendBookingNotificationEmail($bookingsModel->find($bookingId), $data);
                return $this->response->setJSON($this->setResponse(null, null, null, 'Respuesta exitosa'));
            }
        } catch (\Exception $e) {
            return $this->response->setJSON($this->setResponse(404, true, null, $e->getMessage()));
        }
    }



    public function getBookings($fecha)
    {
        $bookingsModel = new BookingsModel();
        $bookingSlotsModel = new BookingSlotsModel();
        $fieldsModel = new FieldsModel();
        $timeModel = new TimeModel();

        $time = $timeModel->getOpeningTime();
        $occupied = [];

        if ($fecha != '') {
            $now = date('Y-m-d H:i:s');

            $this->expireActiveBookingSlots($bookingSlotsModel, [
                'status' => 'pending',
                'expires_at <' => $now,
            ]);

            $bookings = $bookingsModel->where('date', $fecha)->where('annulled', 0)->findAll();

            foreach ($bookings as $booking) {
                $occupied[] = [
                    'id_field' => $booking['id_field'],
                    'time_from' => $booking['time_from'],
                    'time_until' => $booking['time_until'],
                ];
            }

            $pendingSlots = $bookingSlotsModel->where('date', $fecha)
                ->where('active', 1)
                ->where('status', 'pending')
                ->where('expires_at >=', $now)
                ->findAll();

            foreach ($pendingSlots as $slot) {
                $occupied[] = [
                    'id_field' => $slot['id_field'],
                    'time_from' => $slot['time_from'],
                    'time_until' => $slot['time_until'],
                ];
            }
        }

        $timeBookings = [];

        foreach ($occupied as $booking) {
            $found = false;

            foreach ($timeBookings as &$timeBooking) {
                if (intval($timeBooking['id_cancha']) === intval($booking['id_field'])) {
                    $indexFrom = array_search($booking['time_from'], $time);
                    $indexUntil = array_search($booking['time_until'], $time);

                    // ✅ Cambio clave: UNTIL ahora es exclusivo
                    for ($currentTime = $indexFrom; $currentTime < $indexUntil; $currentTime++) {
                        $timeBooking['time'][] = $time[$currentTime];
                    }

                    $found = true;
                    break;
                }
            }

            if (!$found) {
                $reserva = [
                    'id_cancha' => $booking['id_field'],
                    'nombre_cancha' => $fieldsModel->getField($booking['id_field'])['name'],
                    'time' => [],
                ];

                $indexFrom = array_search($booking['time_from'], $time);
                $indexUntil = array_search($booking['time_until'], $time);

                // ✅ Cambio clave: UNTIL ahora es exclusivo
                for ($currentTime = $indexFrom; $currentTime < $indexUntil; $currentTime++) {
                    $reserva['time'][] = $time[$currentTime];
                }

                $timeBookings[] = $reserva;
            }
        }


        if ($occupied) {
            try {
                return $this->response->setJSON($this->setResponse(null, null, [
                    'reservas' => $timeBookings,
                    'canchas' => $fieldsModel->findAll()
                ], 'Respuesta exitosa'));
            } catch (\Exception $e) {
                return $this->response->setJSON($this->setResponse(404, true, null, $e->getMessage()));
            }
        } else {
            log_message('info', 'No hay reservas para la fecha seleccionada');
            return $this->response->setJSON($this->setResponse(200, true, null, 'No hay reservas para la fecha seleccionada'));
        }
    }



    public function completePayment($id)
    {
        $bookingsModel = new BookingsModel();
        $paymentsModel = new PaymentsModel();
        $data = $this->request->getJSON();
        $booking = $bookingsModel->getBooking($id);

        $total = floatval($booking['total']);
        $pagoAnterior = floatval($booking['payment']);
        $nuevoPago = floatval($data->pago);
        $nuevoAcumulado = $pagoAnterior + $nuevoPago;

        // ✅ Validación para evitar pagos duplicados o montos excedidos
        if ($nuevoPago <= 0 || $nuevoAcumulado > $total) {
            return $this->response->setJSON(
                $this->setResponse(400, true, null, 'El monto es inválido o excede el total de la reserva.')
            );
        }

        $diferencia = $total - $nuevoAcumulado;
        $pagoTotal = abs($total - $nuevoAcumulado) < 0.01 ? 1 : 0;

        $queryBookings = [
            'total_payment' => $pagoTotal,
            'payment' => $nuevoAcumulado,
            'diference' => $diferencia,
        ];

        $queryPayments = [
            'id_user' => $data->idUser,
            'id_booking' => $id,
            'id_customer' => $data->idCustomer,
            'amount' => $nuevoPago,
            'payment_method' => $data->medioPago,
            'date' => Time::now()->toDateString(),
            'created_at' => Time::now(),
        ];

        try {
            $bookingsModel->update($id, $queryBookings);
            $paymentsModel->insert($queryPayments);
            return $this->response->setJSON($this->setResponse(null, null, null, 'Respuesta exitosa'));
        } catch (\Exception $e) {
            return $this->response->setJSON($this->setResponse(404, true, null, $e->getMessage()));
        }
    }




    public function getBooking($id)
    {
        $bookingsModel = new BookingsModel();
        $booking = $bookingsModel->getBooking($id);

        if ($booking) {
            try {
                return  $this->response->setJSON($this->setResponse(null, null, $booking, 'Respuesta exitosa'));
            } catch (\Exception $e) {
                return  $this->response->setJSON($this->setResponse(404, true, null, $e->getMessage()));
            }
        }
    }

    public function getReports()
    {
        $paymentsModel = new PaymentsModel();
        $bookingsModel = new BookingsModel();
        $data = $this->request->getJSON();
        $user = (empty($data->user) || $data->user == '') ? 'all' : $data->user;

        $query = $paymentsModel->select('
            payments.date,
            payments.amount,
            payments.id_user,
            payments.payment_method,
            payments.id_mercado_pago,
            users.name as nombre_usuario,
            customers.name as nombre_cliente,
            customers.phone as telefono_cliente,
            bookings.id as booking_id,
            bookings.name as booking_name,
            bookings.phone as booking_phone,
            bookings.payment as booking_payment,
            bookings.total as booking_total,
            bookings.total_payment as booking_total_payment
        ')
            ->join('users', 'users.id = payments.id_user', 'left')
            ->join('customers', 'customers.id = payments.id_customer', 'left')
            ->join('bookings', 'bookings.id = payments.id_booking', 'left')
            ->where('payments.date >=', $data->fechaDesde)
            ->where('payments.date <=', $data->fechaHasta);

        if ($user !== 'all') {
            $query->where('payments.id_user', $user);
        }

        $paymentsResult = $query->findAll();
        $payments = array_map(function ($payment) {
            $monto = (float)($payment['amount'] ?? 0);
            $metodo = strtolower(str_replace(' ', '_', (string)($payment['payment_method'] ?? '')));
            if ($monto <= 0 && $metodo === 'mercado_pago') {
                $monto = ($payment['booking_total_payment'] ?? 0) ? ($payment['booking_total'] ?? 0) : ($payment['booking_payment'] ?? 0);
            }

            return [
                'fecha' => date("d/m/Y", strtotime($payment['date'])),
                'pago' => $monto,
                'usuario' => $payment['nombre_usuario'] ?? 'N/A',
                'idUsuario' => $payment['id_user'],
                'cliente' => $payment['nombre_cliente'] ?? $payment['booking_name'] ?? 'N/A',
                'telefonoCliente' => $payment['telefono_cliente'] ?? $payment['booking_phone'] ?? 'N/A',
                'metodoPago' => $payment['payment_method'],
                'idMercadoPago' => $payment['id_mercado_pago'],
                'bookingId' => $payment['booking_id'],
                'totalReserva' => $payment['booking_total'],
            ];
        }, $paymentsResult);

        $mpBookings = $bookingsModel->select('bookings.date, bookings.payment, bookings.total, bookings.total_payment, bookings.payment_method, bookings.id, bookings.name as booking_name, bookings.phone as booking_phone, customers.name as customer_name, customers.phone as customer_phone')
            ->join('customers', 'customers.id = bookings.id_customer', 'left')
            ->join('payments', 'payments.id_booking = bookings.id', 'left')
            ->where('bookings.date >=', $data->fechaDesde)
            ->where('bookings.date <=', $data->fechaHasta)
            ->where('bookings.mp', 1)
            ->whereIn('bookings.payment_method', ['Mercado Pago', 'mercado_pago'])
            ->where('payments.id', null)
            ->findAll();

        foreach ($mpBookings as $booking) {
            $monto = ($booking['total_payment'] ?? 0) ? $booking['total'] : $booking['payment'];
            $payments[] = [
                'fecha' => date("d/m/Y", strtotime($booking['date'])),
                'pago' => $monto,
                'usuario' => 'CLIENTE',
                'idUsuario' => null,
                'cliente' => $booking['customer_name'] ?? $booking['booking_name'] ?? 'N/A',
                'telefonoCliente' => $booking['customer_phone'] ?? $booking['booking_phone'] ?? 'N/A',
                'metodoPago' => 'mercado_pago',
                'idMercadoPago' => null,
                'bookingId' => $booking['id'],
                'totalReserva' => $booking['total'],
            ];
        }

        $mpReservations = $bookingsModel->select('bookings.date, bookings.reservation, bookings.total, bookings.total_payment, bookings.id, bookings.name as booking_name, bookings.phone as booking_phone, customers.name as customer_name, customers.phone as customer_phone')
            ->join('customers', 'customers.id = bookings.id_customer', 'left')
            ->join('payments as pmp', "pmp.id_booking = bookings.id AND (pmp.payment_method = 'mercado_pago' OR pmp.payment_method = 'Mercado Pago')", 'left')
            ->where('bookings.date >=', $data->fechaDesde)
            ->where('bookings.date <=', $data->fechaHasta)
            ->where('bookings.mp', 1)
            ->where('bookings.reservation >', 0)
            ->where('bookings.reservation < bookings.total')
            ->where('pmp.id', null)
            ->findAll();

        foreach ($mpReservations as $booking) {
            $payments[] = [
                'fecha' => date("d/m/Y", strtotime($booking['date'])),
                'pago' => $booking['reservation'],
                'usuario' => 'CLIENTE',
                'idUsuario' => null,
                'cliente' => $booking['customer_name'] ?? $booking['booking_name'] ?? 'N/A',
                'telefonoCliente' => $booking['customer_phone'] ?? $booking['booking_phone'] ?? 'N/A',
                'metodoPago' => 'mercado_pago',
                'idMercadoPago' => null,
                'bookingId' => $booking['id'],
                'totalReserva' => $booking['total'],
            ];
        }

        try {
            return  $this->response->setJSON($this->setResponse(null, null, $payments, 'Respuesta exitosa'));
        } catch (\Exception $e) {
            return  $this->response->setJSON($this->setResponse(404, true, null, $e->getMessage()));
        }
    }

    public function cancelBooking()
    {
        $mercadoPagoModel = new MercadoPagoModel();
        $bookingsModel = new BookingsModel();
        $data = $this->request->getJSON();
        $idBooking = $data->idBooking;
        $mpPayment = $mercadoPagoModel->where('id_booking', $idBooking)->first();

        try {
            if (isset($mpPayment)) {
                $mercadoPagoModel->update($mpPayment['id'], ['annulled' => 1]);
            }
            $bookingsModel->update($idBooking, ['annulled' => 1]);

            return  $this->response->setJSON($this->setResponse(null, null, null, 'Respuesta exitosa'));
        } catch (\Exception $e) {
            return  $this->response->setJSON($this->setResponse(404, true, null, $e->getMessage()));
        }
    }

    public function editBooking()
    {
        $bookingsModel = new BookingsModel();
        $data = $this->request->getJSON();

        $idBooking = $data->bookingId;

        $queryUpdate = [
            'id_field' => $data->cancha,
            'diference' => $data->diferencia,
            'date' => $data->fecha,
            'time_from' => $data->horarioDesde,
            'time_until' => $data->horarioHasta,
            'total_payment' => $data->pagoTotal,
            'parcial' => $data->parcial,
            'total' => $data->total,
            'visitors' => $data->visitantes
        ];

        try {
            $bookingsModel->update($idBooking, $queryUpdate);

            return  $this->response->setJSON($this->setResponse(null, null, null, 'Respuesta exitosa'));
        } catch (\Exception $e) {
            return  $this->response->setJSON($this->setResponse(404, true, null, $e->getMessage()));
        }
    }

    private function sendBookingNotificationEmail(array $booking, object $requestData): void
    {
        $uploadModel = new UploadModel();
        $config = $uploadModel->first();
        $notificationEmail = trim($config['notification_email'] ?? '');
        $notificationEmailList = array_values(array_filter(array_map('trim', explode(';', $notificationEmail))));

        if ($notificationEmailList === []) {
            return;
        }

        $formattedDate = $this->formatBookingDate((string) ($requestData->fecha ?? ''));
        $subjectName = trim((string) ($requestData->nombre ?? ''));
        $subjectTimeFrom = trim((string) ($requestData->horarioDesde ?? ''));
        $subjectSuffix = trim($formattedDate . ' ' . $subjectTimeFrom);
        $subject = "Reserva - Laberinto: {$subjectName} - {$subjectSuffix}";

        $message = "Se recibio una nueva reserva.\n\n";
        $message .= 'Nombre: ' . ($requestData->nombre ?? '') . "\n";
        $message .= 'Telefono: ' . ($requestData->telefono ?? '') . "\n";
        $message .= 'Fecha: ' . $formattedDate . "\n";
        $message .= 'Horario desde: ' . ($requestData->horarioDesde ?? '') . "\n";
        $message .= 'Horario hasta: ' . ($requestData->horarioHasta ?? '') . "\n";
        $message .= 'Visitantes: ' . ($requestData->visitantes ?? '') . "\n";
        $message .= 'Total: ' . ($requestData->total ?? '') . "\n";
        $message .= 'Codigo: ' . ($booking['code'] ?? '') . "\n";
        $message .= 'Ver reserva: ' . site_url('MisReservas/' . rawurlencode($this->buildReservationAccessToken([
                'code' => (string) ($booking['code'] ?? ''),
            ]))) . "\n";

        $this->sendEmailWithFallback($notificationEmailList, $subject, $message);

        $this->sendCustomerBookingConfirmationEmail($booking, $requestData);
    }

    private function sendCustomerBookingConfirmationEmail(array $booking, object $requestData): void
    {
        try {
            $customerEmail = $this->resolveCustomerEmail($booking, $requestData);

            if ($customerEmail === '') {
                return;
            }

            $formattedDate = $this->formatBookingDate((string) ($requestData->fecha ?? ''));
            $customerName = trim((string) ($requestData->nombre ?? 'Cliente'));
            $timeFrom = trim((string) ($requestData->horarioDesde ?? ''));
            $timeUntil = trim((string) ($requestData->horarioHasta ?? ''));
            $visitors = trim((string) ($requestData->visitantes ?? ''));
            $total = trim((string) ($requestData->total ?? ($booking['total'] ?? '')));
            $bookingCode = trim((string) ($booking['code'] ?? ''));
            $bookingLink = site_url('MisReservas/' . rawurlencode($this->buildReservationAccessToken([
                'code' => (string) ($booking['code'] ?? ''),
                'phone' => (string) ($requestData->telefono ?? ($booking['phone'] ?? '')),
                'email' => $customerEmail,
            ])));
            $subject = "Reserva confirmada - Laberinto: {$customerName}";

            $message = "Hola {$customerName}, tu reserva fue registrada correctamente.\n\n";
            $message .= 'Nombre: ' . $customerName . "\n";
            $message .= 'Fecha: ' . $formattedDate . "\n";
            $message .= 'Horario: ' . trim($timeFrom . ' a ' . $timeUntil) . "\n";
            $message .= 'Cantidad: ' . $visitors . "\n";
            $message .= 'Total: ' . $total . "\n";
            $message .= 'Codigo: ' . $bookingCode . "\n\n";
            $message .= "Importante:\n";
            $message .= "Se asume el compromiso y la responsabilidad de asistir en el dia y horario acordados. ";
            $message .= "En caso de inasistencia, no se realizaran devoluciones de dinero y la reprogramacion quedara sujeta a disponibilidad.\n\n";
            $message .= "Ver tus reservas:\n{$bookingLink}\n";

            $this->sendEmailWithFallback($customerEmail, $subject, $message);
        } catch (\Throwable $e) {
            log_message('error', 'No se pudo enviar el email de confirmacion al cliente: ' . $e->getMessage());
        }
    }

    private function resolveCustomerEmail(array $booking, object $requestData): string
    {
        $requestEmail = trim((string) ($requestData->email ?? ''));
        if ($requestEmail !== '' && filter_var($requestEmail, FILTER_VALIDATE_EMAIL)) {
            return $requestEmail;
        }

        $customersModel = new CustomersModel();

        if (!empty($booking['id_customer'])) {
            $customer = $customersModel->find($booking['id_customer']);
            $customerEmail = trim((string) ($customer['email'] ?? ''));

            if ($customerEmail !== '' && filter_var($customerEmail, FILTER_VALIDATE_EMAIL)) {
                return $customerEmail;
            }
        }

        $phone = trim((string) ($requestData->telefono ?? ($booking['phone'] ?? '')));
        if ($phone !== '') {
            $customer = $customersModel->groupStart()
                ->where('phone', $phone)
                ->orWhere('complete_phone', $phone)
                ->groupEnd()
                ->where('deleted', 0)
                ->first();

            $customerEmail = trim((string) ($customer['email'] ?? ''));

            if ($customerEmail !== '' && filter_var($customerEmail, FILTER_VALIDATE_EMAIL)) {
                return $customerEmail;
            }
        }

        return '';
    }

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

    public function getMpPayments()
    {
        $bookingsModel = new BookingsModel();
        $data = $this->request->getJSON();

        $bookings = $bookingsModel->where('date >=', $data->fechaDesde)
            ->where('date <=', $data->fechaHasta)
            ->findAll();

        $reservations = [];

        foreach ($bookings as $booking) {
            $fecha = date("d/m/Y", strtotime($booking['date']));
            $reservation = intval($booking['reservation']);

            if (array_key_exists($fecha, $reservations)) {
                $reservations[$fecha] += $reservation;
            } else {
                $reservations[$fecha] = $reservation;
            }
        }

        $result = [];

        foreach ($reservations as $fecha => $pago) {
            $result[] = [
                'fecha' => $fecha,
                'reserva' => $pago
            ];
        }

        try {
            return  $this->response->setJSON($this->setResponse(null, null, $result, 'Respuesta exitosa'));
        } catch (\Exception $e) {
            return  $this->response->setJSON($this->setResponse(404, true, null, $e->getMessage()));
        }
    }

    public function confirmMP()
    {
        $bookingsModel = new BookingsModel();
        $data = $this->request->getJSON();

        try {
            $bookingsModel->update($data->bookingId, ['mp' => $data->confirm]);

            return $this->response->setJSON($this->setResponse(null, null, null, 'Respuesta exitosa'));
        } catch (\Exception $e) {
            return $this->response->setJSON($this->setResponse(404, true, null, $e->getMessage()));
        }
    }

    public function saveAdminBooking()
    {
        $bookingsModel = new BookingsModel();
        $paymentsModel = new PaymentsModel();
        $customersModel = new CustomersModel();

        $data = $this->request->getJSON();

        // Validaciones mínimas
        if (!$data || empty($data->fecha) || empty($data->nombre) || empty($data->monto) || empty($data->total) || empty($data->cancha)) {
            return $this->response->setJSON($this->setResponse(400, true, null, 'Faltan datos obligatorios.'));
        }

        $pagoTotal = ($data->monto == $data->total) ? 1 : 0;
        $telefonoCompleto = $data->telefono;

        // Intentar buscar cliente por teléfono
        $existingCustomer = $customersModel->where('phone', $data->telefono)->first();
        $idCustomer = $data->idCustomer ?? null;

        // Armar reserva
        $queryBooking = [
            'date'            => $data->fecha,
            'id_field'        => $data->cancha,
            'time_from'       => $data->horarioDesde,
            'time_until'      => $data->horarioHasta,
            'name'            => $data->nombre,
            'phone'           => $telefonoCompleto,
            'payment'         => $data->monto,
            'total'           => $data->total,
            'visitors'        => $data->visitantes,
            'description'     => $data->descripcion,
            'code'            => $this->codeGenerate(),
            'diference'       => floatVal($data->total) - floatVal($data->monto),
            'total_payment'   => $pagoTotal,
            'payment_method'  => $data->metodoDePago,
            'approved'        => 1,
            'mp'              => 1,
            'annulled'        => 0,
            'id_customer'     => $idCustomer,
        ];

        try {
            $bookingInsert = $bookingsModel->insert($queryBooking, true);

            if ($bookingInsert) {
                $queryPayment = [
                    'id_booking'     => $bookingsModel->getInsertID(),
                    'id_user'        => session()->get('id_user'),
                    'id_customer'    => $idCustomer,
                    'amount'         => $data->monto,
                    'payment_method' => $data->metodoDePago,
                    'date'           => $data->fecha,
                ];

                // log_message('info', 'Datos recibidos: ' . print_r($queryPayment, true));
                $paymentsModel->insert($queryPayment);

                if ($existingCustomer) {
                    $idCustomer = $existingCustomer['id'];
                    $queryCustomer = [
                        'offer' => 0,
                        'quantity' => $existingCustomer['quantity'] + 1
                    ];
                    $customersModel->update($existingCustomer['id'], $queryCustomer);
                } else {
                    $queryCustomer = [
                        'name'  => $data->nombre,
                        'phone' => $data->telefono,
                        'complete_phone' => $data->telefono,
                        'quantity' => 1,
                        'offer' => 0,
                    ];
                    $customersModel->insert($queryCustomer);
                    $idCustomer = $customersModel->getInsertID();
                }

                if (!empty($idCustomer)) {
                    $bookingsModel->update($bookingsModel->getInsertID(), ['id_customer' => $idCustomer]);
                }

                $savedBooking = $bookingsModel->find($bookingsModel->getInsertID());
                $this->sendBookingNotificationEmail($savedBooking, (object) [
                    'nombre' => $data->nombre ?? '',
                    'telefono' => $data->telefono ?? '',
                    'fecha' => $data->fecha ?? '',
                    'horarioDesde' => $data->horarioDesde ?? '',
                    'horarioHasta' => $data->horarioHasta ?? '',
                    'visitantes' => $data->visitantes ?? '',
                    'total' => $data->total ?? '',
                ]);
            }

            return $this->response->setJSON($this->setResponse(null, null, null, 'Reserva guardada exitosamente'));
        } catch (\Exception $e) {
            return $this->response->setJSON($this->setResponse(500, true, null, 'Error al guardar la reserva: ' . $e->getMessage()));
        }
    }


    public function bookingPdf($bookingId)
    {
        $pdfLibrary = new PrintBookings();
        $bookingsModel = new BookingsModel();
        $mercadoPagoModel = new MercadoPagoModel();
        $fieldsModel = new FieldsModel();

        $booking = $bookingsModel->getBooking($bookingId);
        if (!$booking) {
            return $this->response->setStatusCode(404)->setBody('Reserva no encontrada.');
        }

        $mpPayment = $mercadoPagoModel->where('id_booking', $bookingId)->first();
        $mpPayment = $mpPayment ?? ['payment_id' => 'No corresponde', 'status' => ''];

        //Generar PDF
        $printData = [
            'nombre' => $booking['name'],
            'telefono' => $booking['phone'],
            'fecha' => $booking['date'],
            'horario' => $booking['time_from'] . 'hs' . ' a ' . $booking['time_until'] . 'hs',
            'servicio' => $fieldsModel->getField($booking['id_field'])['name'],
            'total_servicio' => '$' . $booking['total'],
            'pagado' => '$' . $booking['payment'],
            'saldo' => '$' . $booking['diference'],
            'detalle' => $booking['description'] ?? '',
            'id_mercado_pago' => $mpPayment['payment_id'],
            'estado_pago' => $mpPayment['status'],
        ];

        $pdf = $pdfLibrary->renderBooking($printData);

        return $this->response
            ->setHeader('Content-Type', 'application/pdf')
            ->setHeader('Content-Disposition', 'attachment; filename="' . $pdf['name'] . '"')
            ->setBody($pdf['content']);
    }

    public function generateReportPdf($user, $fechaDesde, $fechaHasta)
    {
        $paymentsModel = new PaymentsModel();
        $bookingsModel = new BookingsModel();
        $pdfLibrary = new PrintBookings();

        $query = $paymentsModel->select('
            payments.date,
            payments.amount,
            payments.id_user,
            payments.payment_method,
            payments.id_mercado_pago,
            users.name as nombre_usuario,
            customers.name as nombre_cliente,
            customers.phone as telefono_cliente,
            bookings.id as booking_id,
            bookings.name as booking_name,
            bookings.phone as booking_phone,
            bookings.payment as booking_payment,
            bookings.total as booking_total,
            bookings.total_payment as booking_total_payment
        ')
            ->join('users', 'users.id = payments.id_user', 'left')
            ->join('customers', 'customers.id = payments.id_customer', 'left')
            ->join('bookings', 'bookings.id = payments.id_booking', 'left')
            ->where('payments.date >=', $fechaDesde)
            ->where('payments.date <=', $fechaHasta);

        if ($user !== 'all') {
            $query->where('payments.id_user', $user);
        }

        $paymentsResult = $query->findAll();
        $payments = array_map(function ($payment) {
            $monto = (float)($payment['amount'] ?? 0);
            $metodo = strtolower(str_replace(' ', '_', (string)($payment['payment_method'] ?? '')));
            if ($monto <= 0 && $metodo === 'mercado_pago') {
                $monto = ($payment['booking_total_payment'] ?? 0) ? ($payment['booking_total'] ?? 0) : ($payment['booking_payment'] ?? 0);
            }

            return [
                'fecha' => date("d/m/Y", strtotime($payment['date'])),
                'pago' => $monto,
                'usuario' => $payment['nombre_usuario'] ?? 'N/A',
                'idUsuario' => $payment['id_user'],
                'cliente' => $payment['nombre_cliente'] ?? $payment['booking_name'] ?? 'N/A',
                'telefonoCliente' => $payment['telefono_cliente'] ?? $payment['booking_phone'] ?? 'N/A',
                'metodoPago' => $payment['payment_method'],
                'idMercadoPago' => $payment['id_mercado_pago'],
                'bookingId' => $payment['booking_id'],
                'totalReserva' => $payment['booking_total'],
            ];
        }, $paymentsResult);

        $mpBookings = $bookingsModel->select('bookings.date, bookings.payment, bookings.total, bookings.total_payment, bookings.payment_method, bookings.id, bookings.name as booking_name, bookings.phone as booking_phone, customers.name as customer_name, customers.phone as customer_phone')
            ->join('customers', 'customers.id = bookings.id_customer', 'left')
            ->join('payments', 'payments.id_booking = bookings.id', 'left')
            ->where('bookings.date >=', $fechaDesde)
            ->where('bookings.date <=', $fechaHasta)
            ->where('bookings.mp', 1)
            ->whereIn('bookings.payment_method', ['Mercado Pago', 'mercado_pago'])
            ->where('payments.id', null)
            ->findAll();

        foreach ($mpBookings as $booking) {
            $monto = ($booking['total_payment'] ?? 0) ? $booking['total'] : $booking['payment'];
            $payments[] = [
                'fecha' => date("d/m/Y", strtotime($booking['date'])),
                'pago' => $monto,
                'usuario' => 'CLIENTE',
                'idUsuario' => null,
                'cliente' => $booking['customer_name'] ?? $booking['booking_name'] ?? 'N/A',
                'telefonoCliente' => $booking['customer_phone'] ?? $booking['booking_phone'] ?? 'N/A',
                'metodoPago' => 'mercado_pago',
                'idMercadoPago' => null,
                'bookingId' => $booking['id'],
                'totalReserva' => $booking['total'],
            ];
        }

        $mpReservations = $bookingsModel->select('bookings.date, bookings.reservation, bookings.total, bookings.total_payment, bookings.id, bookings.name as booking_name, bookings.phone as booking_phone, customers.name as customer_name, customers.phone as customer_phone')
            ->join('customers', 'customers.id = bookings.id_customer', 'left')
            ->join('payments as pmp', "pmp.id_booking = bookings.id AND (pmp.payment_method = 'mercado_pago' OR pmp.payment_method = 'Mercado Pago')", 'left')
            ->where('bookings.date >=', $fechaDesde)
            ->where('bookings.date <=', $fechaHasta)
            ->where('bookings.mp', 1)
            ->where('bookings.reservation >', 0)
            ->where('bookings.reservation < bookings.total')
            ->where('pmp.id', null)
            ->findAll();

        foreach ($mpReservations as $booking) {
            $payments[] = [
                'fecha' => date("d/m/Y", strtotime($booking['date'])),
                'pago' => $booking['reservation'],
                'usuario' => 'CLIENTE',
                'idUsuario' => null,
                'cliente' => $booking['customer_name'] ?? $booking['booking_name'] ?? 'N/A',
                'telefonoCliente' => $booking['customer_phone'] ?? $booking['booking_phone'] ?? 'N/A',
                'metodoPago' => 'mercado_pago',
                'idMercadoPago' => null,
                'bookingId' => $booking['id'],
                'totalReserva' => $booking['total'],
            ];
        }

        $pdf = $pdfLibrary->renderReports($payments);

        return $this->response
            ->setHeader('Content-Type', 'application/pdf')
            ->setHeader('Content-Disposition', 'attachment; filename="' . $pdf['name'] . '"')
            ->setBody($pdf['content']);
    }

    public function generatePaymentsReportPdf($fechaDesde, $fechaHasta)
    {
        $bookingsModel = new BookingsModel();
        $pdfLibrary = new PrintBookings();

        $bookings = $bookingsModel->where('date >=', $fechaDesde)
            ->where('date <=', $fechaHasta)
            ->findAll();

        $reservations = [];

        foreach ($bookings as $booking) {
            $fecha = date("d/m/Y", strtotime($booking['date']));
            $reservation = intval($booking['reservation']);

            if (array_key_exists($fecha, $reservations)) {
                $reservations[$fecha] += $reservation;
            } else {
                $reservations[$fecha] = $reservation;
            }
        }

        $result = [];

        foreach ($reservations as $fecha => $pago) {
            $result[] = [
                'fecha' => $fecha,
                'reserva' => $pago
            ];
        }

        $pdf = $pdfLibrary->renderPaymentsReports($result);

        return $this->response
            ->setHeader('Content-Type', 'application/pdf')
            ->setHeader('Content-Disposition', 'attachment; filename="' . $pdf['name'] . '"')
            ->setBody($pdf['content']);
    }

    public function scheduleAvailability($idField)
    {
        $bookingsModel = new BookingsModel();
        $timeModel = new TimeModel();

        // 1. Obtener el punto de partida y el rango de 7 días.
        $today = Time::now('America/Argentina/Buenos_Aires');

        // El punto de inicio de la disponibilidad es MAÑANA.
        $startDay = $today->addDays(1);

        // ⬇️ CORRECCIÓN 1: Definir el final del rango como 6 días después del día de inicio (7 días en total).
        $availabilityDays = 30;
        $endOfRange = $startDay->addDays($availabilityDays - 1)->toDateString();

        $currentWeek = [];
        $currentDay = $startDay; // El bucle comienza en mañana

        // Iteramos exactamente por 7 días (Mañana hasta el final del rango).
        while ($currentDay->toDateString() <= $endOfRange) {
            $currentWeek[] = $currentDay->toDateString();
            $currentDay = $currentDay->addDays(1);
        }

        // 2. Obtener el horario de apertura. (Sin cambios)
        $openingHours = $timeModel->getOpeningTime();

        // 3. Generar todos los lapsos de 2 horas posibles. (Sin cambios)
        $possibleSlots = [];
        $hoursCount = count($openingHours);
        for ($i = 0; $i < $hoursCount - 4; $i += 4) {
            $start = $openingHours[$i];
            if (isset($openingHours[$i + 4])) {
                $end = $openingHours[$i + 4];
                $possibleSlots[] = [
                    'from' => $start,
                    'until' => $end,
                ];
            }
        }

        // 4. Obtener las reservas existentes para el nuevo rango de 7 días.
        $reservedBookings = $bookingsModel
            ->where('id_field', $idField)
            ->where('date >=', $startDay->toDateString())
            ->where('date <=', $endOfRange)
            ->where('annulled', 0)
            ->findAll();

        // Crear una lista de reservas existentes. (Sin cambios)
        $reservedSlots = [];
        foreach ($reservedBookings as $booking) {
            $date = $booking['date'];
            $from = $booking['time_from'];
            $until = $booking['time_until'];

            $reservedSlots[$date][] = [
                'from' => $from,
                'until' => $until
            ];
        }

        $availability = [];
        $dayMapping = [
            'Sunday' => 'is_sunday',
            'Monday' => 'is_monday',
            'Tuesday' => 'is_tuesday',
            'Wednesday' => 'is_wednesday',
            'Thursday' => 'is_thursday',
            'Friday' => 'is_friday',
            'Saturday' => 'is_saturday'
        ];

        $dayNamesSpanish = [
            'Sunday' => 'domingos',
            'Monday' => 'lunes',
            'Tuesday' => 'martes',
            'Wednesday' => 'miércoles',
            'Thursday' => 'jueves',
            'Friday' => 'viernes',
            'Saturday' => 'sábados'
        ];

        foreach ($currentWeek as $date) {
            $currentDay = Time::parse($date);
            $dayName = $currentDay->format('l');

            // ⬇️ CORRECCIÓN 2: Lógica de días cerrados (Restaurada y Funcional)
            $dayKey = $dayMapping[$dayName] ?? null;

            if ($dayKey && ($openingHours[$dayKey] ?? null) == 1) {
                $dayNameInSpanish = $dayNamesSpanish[$dayName] ?? $dayName;
                $availability[] = [
                    'date' => $currentDay->format('d/m/Y'),
                    'available_slots' => ["Cerrado los {$dayNameInSpanish}"]
                ];
                continue; // Pasa al siguiente día del bucle si está cerrado.
            }

            $availableToday = [];
            foreach ($possibleSlots as $slot) {
                $isReserved = false;
                if (isset($reservedSlots[$date])) {
                    foreach ($reservedSlots[$date] as $reservedSlot) {
                        if (
                            $slot['from'] == $reservedSlot['from'] &&
                            $slot['until'] == $reservedSlot['until']
                        ) {
                            $isReserved = true;
                            break;
                        }
                    }
                }

                // Mantenemos solo el filtro de reserva (ya que todos los días son futuros)
                if (!$isReserved) {
                    $availableToday[] = $slot['from'] . ' a ' . $slot['until'];
                }
            }

            if (!empty($availableToday)) {
                $availability[] = [
                    'date' => Time::parse($date)->format('d/m/Y'),
                    'available_slots' => $availableToday
                ];
            }
        }

        // 6. Devolver el resultado en formato JSON.
        try {
            return $this->response->setJSON($this->setResponse(200, false, ['availability' => $availability], 'Horarios disponibles obtenidos correctamente.'));
        } catch (\Exception $e) {
            return $this->response->setJSON($this->setResponse(500, true, null, 'Error al procesar la disponibilidad: ' . $e->getMessage()));
        }
    }

    public function viewBookings($token = null)
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

        $firstRow = $timeModel->first();
        $isSunday = $firstRow['is_sunday'] ?? 0;

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

        $openingTime = array_slice($timeModel->getOpeningTime(), 0, -7);

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

        $prefill = $this->parseReservationAccessToken($token);

        if (!empty($prefill['code'])) {
            $selectedBooking = $bookingsModel->where('code', $prefill['code'])->where('annulled', 0)->first();

            if ($selectedBooking) {
                if (empty($prefill['phone']) && !empty($selectedBooking['phone'])) {
                    $prefill['phone'] = $selectedBooking['phone'];
                }

                if (empty($prefill['email'])) {
                    $resolvedCustomerEmail = '';

                    if (!empty($selectedBooking['id_customer'])) {
                        $selectedCustomer = $customersModel->find($selectedBooking['id_customer']);
                        $resolvedCustomerEmail = trim((string) ($selectedCustomer['email'] ?? ''));
                    }

                    if ($resolvedCustomerEmail === '' && !empty($selectedBooking['phone'])) {
                        $selectedCustomer = $customersModel->groupStart()
                            ->where('phone', $selectedBooking['phone'])
                            ->orWhere('complete_phone', $selectedBooking['phone'])
                            ->groupEnd()
                            ->first();
                        $resolvedCustomerEmail = trim((string) ($selectedCustomer['email'] ?? ''));
                    }

                    if ($resolvedCustomerEmail !== '') {
                        $prefill['email'] = $resolvedCustomerEmail;
                    }
                }
            }
        }

        return view('customers/booking', ['bookings' => $bookings, 'rate' => $rate, 'customers' => $customers, 'time' => $time, 'openingTime' => $openingTime, 'fields' => $fields, 'users' => $users, 'offerRate' => $offerRate, 'logo' => $logo, 'values' => $values, 'esDomingo' => $isSunday, 'prefill' => $prefill]);
    }

    public function showCustomerBooking($code)
    {
        $bookingsModel = new BookingsModel();
        $fieldsModel = new FieldsModel();
        $booking = $bookingsModel->where('code', $code)->where('annulled', 0)->first();
        if ($booking) {
            $field = $fieldsModel->find($booking['id_field']);
            $booking['service_name'] = $field['name'] ?? 'Reserva';
            try {
                return  $this->response->setJSON($this->setResponse(null, null, $booking, 'Respuesta exitosa'));
            } catch (\Exception $e) {
                return  $this->response->setJSON($this->setResponse(404, true, null, $e->getMessage()));
            }
        }

        return $this->response->setJSON($this->setResponse(404, true, null, 'No se encontro la reserva'));
    }

    public function showCustomerBookings()
    {
        $bookingsModel = new BookingsModel();
        $customersModel = new CustomersModel();
        $fieldsModel = new FieldsModel();
        $data = $this->request->getJSON();

        $phone = trim((string) ($data->phone ?? ''));
        $email = trim((string) ($data->email ?? ''));

        if ($phone === '' || $email === '') {
            return $this->response->setJSON($this->setResponse(400, true, null, 'Debe ingresar telefono y email'));
        }

        $customer = $customersModel->groupStart()
            ->where('phone', $phone)
            ->orWhere('complete_phone', $phone)
            ->groupEnd()
            ->where('email', $email)
            ->first();

        if (!$customer) {
            return $this->response->setJSON($this->setResponse(404, true, null, 'No se encontraron reservas para ese cliente'));
        }

        $bookings = $bookingsModel->groupStart()
            ->where('id_customer', $customer['id'])
            ->orGroupStart()
                ->where('phone', $phone)
                ->where('id_customer', null)
            ->groupEnd()
            ->groupEnd()
            ->where('annulled', 0)
            ->orderBy('date', 'DESC')
            ->orderBy('time_from', 'DESC')
            ->findAll();

        if (!$bookings) {
            return $this->response->setJSON($this->setResponse(404, true, null, 'No se encontraron reservas para ese cliente'));
        }

        $result = [];
        foreach ($bookings as $booking) {
            $field = $fieldsModel->find($booking['id_field']);
            $booking['service_name'] = $field['name'] ?? 'Reserva';
            $result[] = $booking;
        }

        return $this->response->setJSON($this->setResponse(null, null, $result, 'Respuesta exitosa'));
    }


    public function codeGenerate()
    {
        // Definir el conjunto de caracteres permitidos
        $caracteres = '0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZ';
        // 1. Baraja la cadena de caracteres
        $caracteres_mezclados = str_shuffle($caracteres);
        // 2. Toma una subcadena de la longitud deseada
        $codigo_alfanumerico = substr($caracteres_mezclados, 0, 10);
        // $codigo_alfanumerico podría ser algo como "aZ3qLp8oTf"
        return $codigo_alfanumerico;
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
