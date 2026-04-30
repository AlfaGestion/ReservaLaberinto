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
use App\Models\SpecialBookingRequestsModel;
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

                log_message('error', 'Fallo envio SMTP con ' . ($email->fromEmail ?? 'sin cuenta') . ': ' . $email->printDebugger(['headers']));
            } catch (\Throwable $e) {
                log_message('error', 'Fallo envio SMTP con ' . (($account['fromEmail'] ?? '') ?: 'sin cuenta') . ': ' . $e->getMessage());
            }
        }

        return false;
    }

    private function sendEmailWithAttachmentFallback($to, string $subject, string $message, string $attachmentName, string $attachmentContent, string $mimeType = 'application/pdf', bool $isHtml = false): bool
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

        $tempPath = rtrim(WRITEPATH, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . 'uploads' . DIRECTORY_SEPARATOR . uniqid('booking_invoice_', true) . '_' . $attachmentName;
        file_put_contents($tempPath, $attachmentContent);

        try {
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
                    $email->attach($tempPath, 'attachment', $attachmentName, $mimeType);

                    if ($email->send()) {
                        return true;
                    }

                    log_message('error', 'Fallo envio SMTP con adjunto con ' . ($email->fromEmail ?? 'sin cuenta') . ': ' . $email->printDebugger(['headers']));
                } catch (\Throwable $e) {
                    log_message('error', 'Fallo envio SMTP con adjunto con ' . (($account['fromEmail'] ?? '') ?: 'sin cuenta') . ': ' . $e->getMessage());
                }
            }
        } finally {
            if (is_file($tempPath)) {
                @unlink($tempPath);
            }
        }

        return false;
    }

    private function sendEmailWithAttachmentPathFallback($to, string $subject, string $message, string $attachmentPath, string $attachmentName, string $mimeType = 'application/pdf', bool $isHtml = false): bool
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
                $email->attach($attachmentPath, 'attachment', $attachmentName, $mimeType);

                if ($email->send()) {
                    return true;
                }

                log_message('error', 'Fallo envio SMTP con adjunto local con ' . ($email->fromEmail ?? 'sin cuenta') . ': ' . $email->printDebugger(['headers']));
            } catch (\Throwable $e) {
                log_message('error', 'Fallo envio SMTP con adjunto local con ' . (($account['fromEmail'] ?? '') ?: 'sin cuenta') . ': ' . $e->getMessage());
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
            $bookingSlotsModel->delete($slot['id']);
        }
    }

    public function saveBooking()
    {
        $bookingsModel = new BookingsModel();
        $customersModel = new CustomersModel();

        $data = $this->request->getJSON();
        $specialRequestId = !empty($data->specialRequestId) ? (int) $data->specialRequestId : 0;
        $specialBookingRequestsModel = null;
        $specialRequestItem = null;

        if ($specialRequestId > 0) {
            $specialBookingRequestsModel = new SpecialBookingRequestsModel();
            $specialRequestItem = $specialBookingRequestsModel->find($specialRequestId);

            if (!$specialRequestItem) {
                return $this->response->setJSON($this->setResponse(400, true, null, 'La solicitud especial ya no esta disponible.'));
            }

            $specialRequestStatus = (string) ($specialRequestItem['status'] ?? 'new');
            if ($specialRequestStatus === 'cancelled') {
                return $this->response->setJSON($this->setResponse(400, true, null, 'La solicitud especial fue cancelada y ya no puede confirmarse.'));
            }

            if ($specialRequestStatus === 'confirmed') {
                return $this->response->setJSON($this->setResponse(400, true, null, 'La solicitud especial ya fue confirmada.'));
            }
        }
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
        $idCustomer = null;

        if ($existingCustomer) {
            $idCustomer = $existingCustomer['id'] ?? null;
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

        $visitors = (int) ($data->visitantes ?? 0);
        $partialByEntries = !empty($data->partialByEntries) ? 1 : 0;
        $paidEntries = !empty($data->pagoTotal) ? $visitors : 0;
        if ($partialByEntries) {
            $entriesToPay = (int) ($data->paidEntries ?? $data->entriesToPay ?? 0);
            if (! $this->canPayByEntries($visitors, $data->fecha ?? null) || $entriesToPay <= 0 || $entriesToPay > $visitors) {
                return $this->response->setJSON($this->setResponse(400, true, null, 'La reserva no cumple las condiciones para pago por entradas.'));
            }
            $paidEntries = $entriesToPay;
        }
        $orderId = $this->generateBookingOrderId();

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
            'annulled'              => 0, // Aseguramos que este nuevo registro no estÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â© anulado
            'id_customer'           => $idCustomer,
            'partial_by_entries'    => $partialByEntries,
            'paid_entries'          => $paidEntries,
            'IdPedido'              => $orderId,
        ];


        try {
            if (count($queryBooking) != 0) {
                $bookingId = $bookingsModel->insert($queryBooking);
                $this->logBookingAction($orderId, 'A', 'Alta de reserva por ' . $visitors . ' entradas.');
                if ($specialBookingRequestsModel && $specialRequestId > 0) {
                    $specialBookingRequestsModel->update($specialRequestId, [
                        'status' => 'confirmed',
                        'updated_at' => date('Y-m-d H:i:s'),
                    ]);
                }
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

                    // ÃƒÆ’Ã‚Â¢Ãƒâ€¦Ã¢â‚¬Å“ÃƒÂ¢Ã¢â€šÂ¬Ã‚Â¦ Cambio clave: UNTIL ahora es exclusivo
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

                // ÃƒÆ’Ã‚Â¢Ãƒâ€¦Ã¢â‚¬Å“ÃƒÂ¢Ã¢â€šÂ¬Ã‚Â¦ Cambio clave: UNTIL ahora es exclusivo
                for ($currentTime = $indexFrom; $currentTime < $indexUntil; $currentTime++) {
                    $reserva['time'][] = $time[$currentTime];
                }

                $timeBookings[] = $reserva;
            }
        }


        try {
            return $this->response->setJSON($this->setResponse(200, false, [
                'reservas' => $timeBookings,
                'canchas' => $fieldsModel->findAll()
            ], $occupied ? 'Respuesta exitosa' : 'No hay reservas para la fecha seleccionada'));
        } catch (\Exception $e) {
            return $this->response->setJSON($this->setResponse(404, true, null, $e->getMessage()));
        }
    }



    public function completePayment($id)
    {
        $bookingsModel = new BookingsModel();
        $paymentsModel = new PaymentsModel();
        $data = $this->request->getJSON();
        $booking = $bookingsModel->getBooking($id);

        if (!$booking) {
            return $this->response->setJSON($this->setResponse(404, true, null, 'Reserva no encontrada.'));
        }

        $isPartialByEntries = (int) ($booking['partial_by_entries'] ?? 0) === 1;
        $adminUserId = (int) ($data->idUser ?? session()->get('id_user') ?? 0);
        $paymentMethod = (string) ($data->medioPago ?? '');

        if ($isPartialByEntries) {
            $summary = $this->getBookingEntryPaymentSummary($booking);
            $entriesToPay = (int) ($data->paidEntries ?? $data->paid_entries ?? 0);

            if ($entriesToPay <= 0 || $entriesToPay > $summary['pending_entries']) {
                return $this->response->setJSON(
                    $this->setResponse(400, true, null, 'La cantidad de entradas a abonar es invalida.')
                );
            }

            $unitPrice = (float) $summary['unit_price'];
            $postedAmount = floatval($data->pago ?? 0);
            $nuevoPago = $unitPrice > 0 ? $entriesToPay * $unitPrice : $postedAmount;

            if ($nuevoPago <= 0) {
                return $this->response->setJSON(
                    $this->setResponse(400, true, null, 'El monto es invalido.')
                );
            }

            $pagoAnterior = floatval($booking['payment']);
            $nuevoAcumulado = $pagoAnterior + $nuevoPago;
            $paidEntries = min((int) ($booking['visitors'] ?? 0), (int) ($booking['paid_entries'] ?? 0) + $entriesToPay);
            $pendingEntries = max(0, (int) ($booking['visitors'] ?? 0) - $paidEntries);
            $diferencia = $pendingEntries * $unitPrice;
            $pagoTotal = $pendingEntries === 0 ? 1 : 0;

            $queryBookings = [
                'total_payment' => $pagoTotal,
                'payment' => $nuevoAcumulado,
                'reservation' => $nuevoAcumulado,
                'diference' => $diferencia,
                'total' => $nuevoAcumulado + $diferencia,
                'paid_entries' => $paidEntries,
                'payment_method' => $paymentMethod,
            ];

            $queryPayments = [
                'id_user' => $adminUserId,
                'id_booking' => $id,
                'id_customer' => $data->idCustomer ?? ($booking['id_customer'] ?? null),
                'amount' => $nuevoPago,
                'paid_entries' => $entriesToPay,
                'unit_price' => $unitPrice,
                'payment_type' => $pagoTotal ? 'total' : 'partial_entries',
                'created_by_admin' => 1,
                'admin_user_id' => $adminUserId,
                'payment_method' => $paymentMethod,
                'date' => Time::now()->toDateString(),
                'created_at' => Time::now(),
            ];

            try {
                $bookingsModel->update($id, $queryBookings);
                $paymentsModel->insert($queryPayments);
                $this->logBookingAction(
                    $this->ensureBookingOrderId($booking),
                    'P',
                    'Pago parcial por entradas. Cantidad: ' . $entriesToPay . ' entradas. Precio unitario: ' . $this->formatAuditMoney($unitPrice) . '. Total: ' . $this->formatAuditMoney($nuevoPago) . '. Medio: ' . $paymentMethod . '. Pendiente: ' . $pendingEntries . ' entradas.'
                );

                return $this->response->setJSON($this->setResponse(null, false, [
                    'bookingId' => (int) $id,
                    'totalPaymentCompleted' => (bool) $pagoTotal,
                    'remainingBalance' => $diferencia,
                    'pendingEntries' => $pendingEntries,
                ], 'Respuesta exitosa'));
            } catch (\Exception $e) {
                return $this->response->setJSON($this->setResponse(404, true, null, $e->getMessage()));
            }
        }

        $total = floatval($booking['total']);
        $pagoAnterior = floatval($booking['payment']);
        $nuevoPago = floatval($data->pago);
        $nuevoAcumulado = $pagoAnterior + $nuevoPago;

        // ÃƒÆ’Ã‚Â¢Ãƒâ€¦Ã¢â‚¬Å“ÃƒÂ¢Ã¢â€šÂ¬Ã‚Â¦ ValidaciÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â³n para evitar pagos duplicados o montos excedidos
        if ($nuevoPago <= 0 || $nuevoAcumulado > $total) {
            return $this->response->setJSON(
                $this->setResponse(400, true, null, 'El monto es invÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â¡lido o excede el total de la reserva.')
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
            'id_user' => $adminUserId,
            'id_booking' => $id,
            'id_customer' => $data->idCustomer ?? ($booking['id_customer'] ?? null),
            'amount' => $nuevoPago,
            'payment_type' => $pagoTotal ? 'total' : 'partial_amount',
            'created_by_admin' => 1,
            'admin_user_id' => $adminUserId,
            'payment_method' => $paymentMethod,
            'date' => Time::now()->toDateString(),
            'created_at' => Time::now(),
        ];

        try {
            $bookingsModel->update($id, $queryBookings);
            $paymentsModel->insert($queryPayments);
            $this->logBookingAction(
                $this->ensureBookingOrderId($booking),
                'P',
                ($pagoTotal ? 'Pago total. ' : 'Pago parcial. ') . 'Total abonado: ' . $this->formatAuditMoney($nuevoPago) . '. Medio: ' . $paymentMethod . '. Saldo pendiente: ' . $this->formatAuditMoney($diferencia) . '.'
            );
            return $this->response->setJSON($this->setResponse(null, false, [
                'bookingId' => (int) $id,
                'totalPaymentCompleted' => (bool) $pagoTotal,
                'remainingBalance' => $diferencia,
            ], 'Respuesta exitosa'));
        } catch (\Exception $e) {
            return $this->response->setJSON($this->setResponse(404, true, null, $e->getMessage()));
        }
    }




    public function getBooking($id)
    {
        $bookingsModel = new BookingsModel();
        $booking = $bookingsModel->getBooking($id);

        if ($booking) {
            $entrySummary = $this->getBookingEntryPaymentSummary($booking);
            $booking['IdPedido'] = $this->ensureBookingOrderId($booking);
            $booking['partial_by_entries'] = (int) ($booking['partial_by_entries'] ?? 0);
            $booking['paid_entries'] = $entrySummary['paid_entries'];
            $booking['pending_entries'] = $entrySummary['pending_entries'];
            $booking['current_unit_price'] = $entrySummary['unit_price'];
            $booking['current_entries_amount_due'] = $entrySummary['pending_amount'];
            $booking['pay_by_entries_enabled'] = $booking['partial_by_entries'] === 1;

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
        $bookingSlotsModel = new BookingSlotsModel();
        $data = $this->request->getJSON();
        $idBooking = $data->idBooking;
        $mpPayment = $mercadoPagoModel->where('id_booking', $idBooking)->first();
        $booking = $bookingsModel->find($idBooking);

        try {
            if (isset($mpPayment)) {
                $mercadoPagoModel->update($mpPayment['id'], ['annulled' => 1]);
            }
            $bookingsModel->update($idBooking, ['annulled' => 1]);
            $bookingSlotsModel->where('booking_id', $idBooking)
                ->where('active', 1)
                ->delete();
            if ($booking) {
                $this->logBookingAction($this->ensureBookingOrderId($booking), 'C', 'Cancelacion de reserva.');
            }

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
        $previousBooking = $bookingsModel->find($idBooking);

        if (!$previousBooking) {
            return $this->response->setJSON($this->setResponse(404, true, null, 'Reserva no encontrada.'));
        }

        $newVisitors = (int) ($data->visitantes ?? 0);
        $paidEntries = (int) ($previousBooking['paid_entries'] ?? 0);
        if ((int) ($previousBooking['partial_by_entries'] ?? 0) === 1 && $newVisitors < $paidEntries) {
            return $this->response->setJSON($this->setResponse(400, true, null, 'La cantidad de entradas no puede ser menor a las entradas ya abonadas.'));
        }

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

        if ((int) ($previousBooking['partial_by_entries'] ?? 0) === 1) {
            $bookingForPrice = array_merge($previousBooking, [
                'date' => $data->fecha,
                'visitors' => $newVisitors,
            ]);
            $unitPrice = $this->resolveCurrentUnitPriceForBooking($bookingForPrice);
            $pendingEntries = max(0, $newVisitors - $paidEntries);
            $queryUpdate['diference'] = $pendingEntries * $unitPrice;
            $queryUpdate['total'] = (float) ($previousBooking['payment'] ?? 0) + $queryUpdate['diference'];
            $queryUpdate['total_payment'] = $pendingEntries === 0 ? 1 : 0;
        }

        try {
            $bookingsModel->update($idBooking, $queryUpdate);
            $previousVisitors = (int) ($previousBooking['visitors'] ?? 0);
            $observation = $previousVisitors !== $newVisitors
                ? 'Modificacion de reserva: cambio en cantidad de entradas. Antes: ' . $previousVisitors . '. Ahora: ' . $newVisitors . '.'
                : 'Modificacion de reserva.';
            $this->logBookingAction($this->ensureBookingOrderId($previousBooking), 'M', $observation);

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

        $bookingUrl = site_url('MisReservas/' . rawurlencode($this->buildReservationAccessToken([
            'code' => (string) ($booking['code'] ?? ''),
        ])));
        $messageHtml = '<p>Se registro una nueva reserva en el sistema.</p>';
        $html = $this->renderEmailCard([
            'eyebrow' => 'Nueva reserva',
            'title' => $subjectName !== '' ? $subjectName . ' reservo una visita' : 'Se recibio una nueva reserva',
            'intro' => 'Revise los datos de la visita y haga seguimiento desde el panel o el acceso de reservas.',
            'details' => [
                'Nombre' => (string) ($requestData->nombre ?? ''),
                'Telefono' => (string) ($requestData->telefono ?? ''),
                'Fecha' => $formattedDate,
                'Horario' => trim(((string) ($requestData->horarioDesde ?? '')) . ' a ' . ((string) ($requestData->horarioHasta ?? ''))),
                'Visitantes' => (string) ($requestData->visitantes ?? ''),
                'Total' => '$' . (string) ($requestData->total ?? ''),
                'Codigo' => (string) ($booking['code'] ?? ''),
            ],
            'messageHtml' => $messageHtml,
            'primaryActionUrl' => $bookingUrl,
            'primaryActionLabel' => 'Ver reserva',
        ]);

        $this->sendEmailWithFallback($notificationEmailList, $subject, $html, true);

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

            $downloadPdfUrl = !empty($booking['id']) ? site_url('bookingPdf/' . $booking['id']) : '';
            $messageHtml = '<p>Tu reserva fue registrada correctamente. A continuacion tenes un resumen claro con los datos principales de la visita.</p>';
            $html = $this->renderEmailCard([
                'eyebrow' => 'Reserva confirmada',
                'title' => 'Tu visita ya quedo confirmada',
                'intro' => 'Guardamos los datos de tu reserva y te dejamos los accesos rapidos para revisarla cuando quieras.',
                'details' => [
                    'Nombre' => $customerName,
                    'Fecha' => $formattedDate,
                    'Horario' => trim($timeFrom . ' a ' . $timeUntil),
                    'Cantidad' => $visitors,
                    'Total' => '$' . $total,
                    'Pagado' => '$' . trim((string) ($booking['payment'] ?? $requestData->monto ?? '0')),
                    'Saldo pendiente' => '$' . trim((string) ($booking['diference'] ?? '0')),
                    'Codigo' => $bookingCode,
                ],
                'messageHtml' => $messageHtml,
                'primaryActionUrl' => $bookingLink,
                'primaryActionLabel' => 'Ver reserva',
                'secondaryActionUrl' => $downloadPdfUrl,
                'secondaryActionLabel' => $downloadPdfUrl !== '' ? 'Descargar comprobante' : '',
                'supportText' => 'Se asume el compromiso y la responsabilidad de asistir en el dia y horario acordados. En caso de inasistencia, no se realizaran devoluciones y la reprogramacion queda sujeta a disponibilidad.',
            ]);

            $this->sendEmailWithFallback($customerEmail, $subject, $html, true);
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

    private function buildBookingPdfData(array $booking, ?array $mpPayment = null): array
    {
        $fieldsModel = new FieldsModel();
        $field = $fieldsModel->getField($booking['id_field']) ?? [];
        $mpPayment = $mpPayment ?? ['payment_id' => 'No corresponde', 'status' => ''];

        return [
            'nombre' => $booking['name'],
            'telefono' => $booking['phone'],
            'fecha' => $booking['date'],
            'horario' => $booking['time_from'] . 'hs' . ' a ' . $booking['time_until'] . 'hs',
            'servicio' => $field['name'] ?? 'Reserva',
            'total_servicio' => '$' . $booking['total'],
            'pagado' => '$' . $booking['payment'],
            'saldo' => '$' . $booking['diference'],
            'detalle' => $booking['description'] ?? '',
            'id_mercado_pago' => $mpPayment['payment_id'] ?? 'No corresponde',
            'estado_pago' => $mpPayment['status'] ?? '',
        ];
    }

    private function getInvoiceEmailDefaults(): array
    {
        $uploadModel = new UploadModel();
        $config = $uploadModel->first() ?? [];

        return [
            'subject' => trim((string) ($config['invoice_email_subject'] ?? '')) !== ''
                ? trim((string) ($config['invoice_email_subject'] ?? ''))
                : 'Factura de reserva - Laberinto: {nombre}',
            'message' => trim((string) ($config['invoice_email_message'] ?? '')) !== ''
                ? trim((string) ($config['invoice_email_message'] ?? ''))
                : "Hola {nombre},\n\nTe enviamos adjunto el comprobante de tu reserva.\n\nFecha: {fecha}\nHorario: {horario}\nCodigo: {codigo}\nPagado: {pagado}\n\nGracias.",
        ];
    }

    private function applyInvoiceEmailPlaceholders(string $template, array $booking, string $customerEmail): string
    {
        $replacements = [
            '{nombre}' => trim((string) ($booking['name'] ?? 'Cliente')),
            '{fecha}' => $this->formatBookingDate((string) ($booking['date'] ?? '')),
            '{horario}' => trim(((string) ($booking['time_from'] ?? '')) . ' a ' . ((string) ($booking['time_until'] ?? ''))),
            '{codigo}' => trim((string) ($booking['code'] ?? '')),
            '{pagado}' => '$' . trim((string) ($booking['payment'] ?? '0')),
            '{email}' => $customerEmail,
            '{telefono}' => trim((string) ($booking['phone'] ?? '')),
        ];

        return strtr($template, $replacements);
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
        $booking = $bookingsModel->find($data->bookingId);

        try {
            $bookingsModel->update($data->bookingId, ['mp' => $data->confirm]);
            if ($booking) {
                $this->logBookingAction($this->ensureBookingOrderId($booking), 'M', !empty($data->confirm) ? 'Modificacion de reserva: pago Mercado Pago confirmado.' : 'Modificacion de reserva: pago Mercado Pago marcado como pendiente.');
            }

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

        // Validaciones mÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â­nimas
        if (!$data || empty($data->fecha) || empty($data->nombre) || empty($data->monto) || empty($data->total) || empty($data->cancha)) {
            return $this->response->setJSON($this->setResponse(400, true, null, 'Faltan datos obligatorios.'));
        }

        $pagoTotal = ($data->monto == $data->total) ? 1 : 0;
        $telefonoCompleto = $data->telefono;
        $visitors = (int) ($data->visitantes ?? 0);
        $totalAmount = (float) ($data->total ?? 0);
        $paidAmount = (float) ($data->monto ?? 0);
        $unitPrice = $visitors > 0 ? $totalAmount / $visitors : 0.0;
        $partialByEntries = !empty($data->partialByEntries) ? 1 : 0;
        $paidEntries = $pagoTotal ? $visitors : 0;

        if ($partialByEntries) {
            $entriesToPay = (int) ($data->paidEntries ?? $data->entriesToPay ?? 0);
            if (! $this->canPayByEntries($visitors, $data->fecha ?? null) || $entriesToPay <= 0 || $entriesToPay > $visitors) {
                return $this->response->setJSON($this->setResponse(400, true, null, 'La reserva no cumple las condiciones para pago por entradas.'));
            }

            $paidEntries = $entriesToPay;
            $paidAmount = $unitPrice > 0 ? $entriesToPay * $unitPrice : $paidAmount;
            $pagoTotal = $paidEntries >= $visitors ? 1 : 0;
        }

        // Intentar buscar cliente por telÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â©fono
        $existingCustomer = $customersModel->where('phone', $data->telefono)->first();
        $idCustomer = $data->idCustomer ?? null;
        $orderId = $this->generateBookingOrderId();

        // Armar reserva
        $queryBooking = [
            'date'            => $data->fecha,
            'id_field'        => $data->cancha,
            'time_from'       => $data->horarioDesde,
            'time_until'      => $data->horarioHasta,
            'name'            => $data->nombre,
            'phone'           => $telefonoCompleto,
            'payment'         => $paidAmount,
            'total'           => $partialByEntries ? ($paidAmount + max(0, $visitors - $paidEntries) * $unitPrice) : $data->total,
            'visitors'        => $visitors,
            'description'     => $data->descripcion,
            'code'            => $this->codeGenerate(),
            'diference'       => $partialByEntries ? max(0, $visitors - $paidEntries) * $unitPrice : floatVal($data->total) - floatVal($data->monto),
            'total_payment'   => $pagoTotal,
            'payment_method'  => $data->metodoDePago,
            'approved'        => 1,
            'mp'              => 1,
            'annulled'        => 0,
            'id_customer'     => $idCustomer,
            'partial_by_entries' => $partialByEntries,
            'paid_entries'    => $paidEntries,
            'IdPedido'        => $orderId,
        ];

        try {
            $bookingInsert = $bookingsModel->insert($queryBooking, true);

            if ($bookingInsert) {
                $bookingId = $bookingsModel->getInsertID();

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
                    $bookingsModel->update($bookingId, ['id_customer' => $idCustomer]);
                }

                $queryPayment = [
                    'id_booking'     => $bookingId,
                    'id_user'        => session()->get('id_user'),
                    'id_customer'    => $idCustomer,
                    'amount'         => $paidAmount,
                    'paid_entries'   => $partialByEntries ? $paidEntries : ($pagoTotal ? $visitors : null),
                    'unit_price'     => $unitPrice > 0 ? $unitPrice : null,
                    'payment_type'   => $partialByEntries ? ($pagoTotal ? 'total' : 'partial_entries') : ($pagoTotal ? 'total' : 'partial_amount'),
                    'created_by_admin' => 1,
                    'admin_user_id'  => session()->get('id_user'),
                    'payment_method' => $data->metodoDePago,
                    'date'           => $data->fecha,
                    'created_at'     => Time::now(),
                ];

                $paymentsModel->insert($queryPayment);

                $this->logBookingAction($orderId, 'A', 'Alta de reserva por ' . $visitors . ' entradas.');
                if ($paidAmount > 0) {
                    if ($partialByEntries && !$pagoTotal) {
                        $pendingEntries = max(0, $visitors - $paidEntries);
                        $this->logBookingAction($orderId, 'P', 'Pago parcial por entradas. Cantidad: ' . $paidEntries . ' entradas. Precio unitario: ' . $this->formatAuditMoney($unitPrice) . '. Total: ' . $this->formatAuditMoney($paidAmount) . '. Medio: ' . $data->metodoDePago . '. Pendiente: ' . $pendingEntries . ' entradas.');
                    } else {
                        $paymentLabel = $pagoTotal ? 'Pago total. ' : 'Pago parcial. ';
                        $pendingAmount = max(0, (float) ($queryBooking['diference'] ?? 0));
                        $this->logBookingAction($orderId, 'P', $paymentLabel . 'Total abonado: ' . $this->formatAuditMoney($paidAmount) . '. Medio: ' . $data->metodoDePago . '. Saldo pendiente: ' . $this->formatAuditMoney($pendingAmount) . '.');
                    }
                }

                $savedBooking = $bookingsModel->find($bookingId);
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

    public function sendBookingInvoiceEmail($bookingId)
    {
        $bookingsModel = new BookingsModel();
        $mercadoPagoModel = new MercadoPagoModel();
        $pdfLibrary = new PrintBookings();
        $requestData = $this->request->getJSON(true);
        if (!is_array($requestData) || $requestData === []) {
            $requestData = [
                'email' => $this->request->getPost('email'),
                'subject' => $this->request->getPost('subject'),
                'message' => $this->request->getPost('message'),
                'attachInvoice' => $this->request->getPost('attachInvoice'),
            ];
        }

        $booking = $bookingsModel->getBooking($bookingId);
        if (!$booking) {
            return $this->response->setStatusCode(404)
                ->setJSON($this->setResponse(404, true, null, 'Reserva no encontrada.'));
        }

        if ((int) ($booking['annulled'] ?? 0) === 1) {
            return $this->response->setStatusCode(400)
                ->setJSON($this->setResponse(400, true, null, 'No se puede enviar la factura de una reserva anulada.'));
        }

        if ((int) ($booking['total_payment'] ?? 0) !== 1) {
            return $this->response->setStatusCode(400)
                ->setJSON($this->setResponse(400, true, null, 'La reserva todavia no tiene el pago completo.'));
        }

        $customerEmail = trim((string) ($requestData['email'] ?? ''));
        if ($customerEmail === '') {
            $customerEmail = $this->resolveCustomerEmail($booking, (object) []);
        }
        if ($customerEmail === '') {
            return $this->response->setStatusCode(400)
                ->setJSON($this->setResponse(400, true, null, 'El cliente no tiene un email valido cargado.'));
        }
        if (!filter_var($customerEmail, FILTER_VALIDATE_EMAIL)) {
            return $this->response->setStatusCode(400)
                ->setJSON($this->setResponse(400, true, null, 'El email ingresado no es valido.'));
        }

        $mpPayment = $mercadoPagoModel->where('id_booking', $bookingId)->first();
        $defaults = $this->getInvoiceEmailDefaults();
        $subjectTemplate = trim((string) ($requestData['subject'] ?? '')) !== '' ? trim((string) ($requestData['subject'] ?? '')) : $defaults['subject'];
        $messageTemplate = trim((string) ($requestData['message'] ?? '')) !== '' ? trim((string) ($requestData['message'] ?? '')) : $defaults['message'];
        $subject = $this->applyInvoiceEmailPlaceholders($subjectTemplate, $booking, $customerEmail);
        $message = $this->applyInvoiceEmailPlaceholders($messageTemplate, $booking, $customerEmail);
        $attachInvoice = array_key_exists('attachInvoice', $requestData) ? (bool) $requestData['attachInvoice'] : true;
        $uploadedInvoiceFile = $this->request->getFile('invoicePdfFile');
        $hasUploadedInvoiceFile = $uploadedInvoiceFile && $uploadedInvoiceFile->isValid() && !$uploadedInvoiceFile->hasMoved();

        if ($hasUploadedInvoiceFile) {
            $clientExtension = strtolower((string) $uploadedInvoiceFile->getClientExtension());
            $mimeType = strtolower((string) $uploadedInvoiceFile->getMimeType());
            if ($clientExtension !== 'pdf' || strpos($mimeType, 'pdf') === false) {
                return $this->response->setStatusCode(400)
                    ->setJSON($this->setResponse(400, true, null, 'El archivo adjunto debe ser un PDF valido.'));
            }
        }

        if ($hasUploadedInvoiceFile) {
            $html = $this->renderEmailCard([
                'eyebrow' => 'Factura de reserva',
                'title' => 'Tu comprobante esta listo',
                'intro' => 'Aca tenes el detalle de tu reserva y el acceso para consultarla cuando quieras.',
                'messageHtml' => '<div>' . nl2br(esc($message)) . '</div>',
                'primaryActionUrl' => site_url('MisReservas/' . rawurlencode($this->buildReservationAccessToken([
                    'code' => (string) ($booking['code'] ?? ''),
                    'phone' => (string) ($booking['phone'] ?? ''),
                    'email' => $customerEmail,
                ]))),
                'primaryActionLabel' => 'Ver reserva',
            ]);
            $sent = $this->sendEmailWithAttachmentPathFallback(
                $customerEmail,
                $subject,
                $html,
                $uploadedInvoiceFile->getTempName(),
                $uploadedInvoiceFile->getClientName() ?: ('factura_reserva_' . $bookingId . '.pdf'),
                $uploadedInvoiceFile->getMimeType() ?: 'application/pdf',
                true
            );
        } elseif ($attachInvoice) {
            $pdf = $pdfLibrary->renderBooking($this->buildBookingPdfData($booking, $mpPayment));
            $html = $this->renderEmailCard([
                'eyebrow' => 'Factura de reserva',
                'title' => 'Tu comprobante esta listo',
                'intro' => 'Aca tenes el detalle de tu reserva y el acceso para consultarla cuando quieras.',
                'messageHtml' => '<div>' . nl2br(esc($message)) . '</div>',
                'primaryActionUrl' => site_url('MisReservas/' . rawurlencode($this->buildReservationAccessToken([
                    'code' => (string) ($booking['code'] ?? ''),
                    'phone' => (string) ($booking['phone'] ?? ''),
                    'email' => $customerEmail,
                ]))),
                'primaryActionLabel' => 'Ver reserva',
                'secondaryActionUrl' => site_url('bookingPdf/' . $bookingId),
                'secondaryActionLabel' => 'Descargar comprobante',
            ]);
            $sent = $this->sendEmailWithAttachmentFallback($customerEmail, $subject, $html, $pdf['name'], $pdf['content'], 'application/pdf', true);
        } else {
            $html = $this->renderEmailCard([
                'eyebrow' => 'Factura de reserva',
                'title' => 'Tu comprobante esta listo',
                'intro' => 'Aca tenes el detalle de tu reserva y el acceso para consultarla cuando quieras.',
                'messageHtml' => '<div>' . nl2br(esc($message)) . '</div>',
                'primaryActionUrl' => site_url('bookingPdf/' . $bookingId),
                'primaryActionLabel' => 'Descargar comprobante',
            ]);
            $sent = $this->sendEmailWithFallback($customerEmail, $subject, $html, true);
        }

        if (!$sent) {
            return $this->response->setStatusCode(400)
                ->setJSON($this->setResponse(400, true, null, 'No se pudo enviar el email.'));
        }

        $invoiceEmailSentAt = date('Y-m-d H:i:s');
        $invoiceEmailTracked = false;

        try {
            $invoiceEmailTracked = $bookingsModel->update($bookingId, [
                'invoice_email_sent_at' => $invoiceEmailSentAt,
            ]);
        } catch (\Throwable $e) {
            log_message('error', 'La factura se envio, pero no se pudo registrar la fecha de envio de la reserva ' . $bookingId . ': ' . $e->getMessage());
        }

        $responseMessage = $invoiceEmailTracked
            ? 'Factura enviada correctamente.'
            : 'Factura enviada correctamente, pero no se pudo registrar la fecha de envio.';

        return $this->response->setJSON($this->setResponse(null, false, [
            'email' => $customerEmail,
            'bookingId' => (int) $bookingId,
            'invoiceEmailSentAt' => $invoiceEmailSentAt,
            'invoiceEmailSentAtDisplay' => date('d/m/Y H:i', strtotime($invoiceEmailSentAt)),
            'invoiceEmailTracked' => $invoiceEmailTracked,
        ], $responseMessage));
    }


    public function bookingPdf($bookingId)
    {
        $pdfLibrary = new PrintBookings();
        $bookingsModel = new BookingsModel();
        $mercadoPagoModel = new MercadoPagoModel();

        $booking = $bookingsModel->getBooking($bookingId);
        if (!$booking) {
            return $this->response->setStatusCode(404)->setBody('Reserva no encontrada.');
        }

        $mpPayment = $mercadoPagoModel->where('id_booking', $bookingId)->first();
        $pdf = $pdfLibrary->renderBooking($this->buildBookingPdfData($booking, $mpPayment));

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

        $timezone = 'America/Argentina/Buenos_Aires';
        $today = Time::now($timezone)->setTime(0, 0, 0);
        $requestedStartDate = trim((string) $this->request->getGet('startDate'));
        $startDay = $today->addDays(1);

        if ($requestedStartDate !== '') {
            try {
                $parsedStartDay = Time::parse($requestedStartDate, $timezone)->setTime(0, 0, 0);
                $startDay = $parsedStartDay->isBefore($today) ? $today : $parsedStartDay;
            } catch (\Throwable $exception) {
                $startDay = $today->addDays(1);
            }
        }

        $availabilityDays = 30;
        $endOfRange = $startDay->addDays($availabilityDays - 1)->toDateString();

        $currentWeek = [];
        $currentDay = $startDay;

        while ($currentDay->toDateString() <= $endOfRange) {
            $currentWeek[] = $currentDay->toDateString();
            $currentDay = $currentDay->addDays(1);
        }

        $openingHours = $timeModel->getOpeningTime();

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

        $reservedBookings = $bookingsModel
            ->where('id_field', $idField)
            ->where('date >=', $startDay->toDateString())
            ->where('date <=', $endOfRange)
            ->where('annulled', 0)
            ->findAll();

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
            'Wednesday' => 'miercoles',
            'Thursday' => 'jueves',
            'Friday' => 'viernes',
            'Saturday' => 'sabados'
        ];

        foreach ($currentWeek as $date) {
            $currentDay = Time::parse($date);
            $dayName = $currentDay->format('l');

            $dayKey = $dayMapping[$dayName] ?? null;

            if ($dayKey && ($openingHours[$dayKey] ?? null) == 1) {
                $dayNameInSpanish = $dayNamesSpanish[$dayName] ?? $dayName;
                $availability[] = [
                    'date' => $currentDay->format('d/m/Y'),
                    'available_slots' => ["Cerrado los {$dayNameInSpanish}"]
                ];
                continue;
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

                if (!$isReserved) {
                    $availableToday[] = $slot['from'] . ' a ' . $slot['until'];
                }
            }

            $availability[] = [
                'date' => Time::parse($date)->format('d/m/Y'),
                'available_slots' => $availableToday
            ];
        }

        try {
            return $this->response->setJSON($this->setResponse(200, false, [
                'start_date' => $startDay->toDateString(),
                'days' => $availabilityDays,
                'availability' => $availability
            ], 'Horarios disponibles obtenidos correctamente.'));
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
        // $codigo_alfanumerico podrÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â­a ser algo como "aZ3qLp8oTf"
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
