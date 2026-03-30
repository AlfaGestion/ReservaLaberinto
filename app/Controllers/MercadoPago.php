<?php

namespace App\Controllers;

use App\Controllers\BaseController;
use App\Libraries\MercadoPagoLibrary;
use App\Models\BookingSlotsModel;
use App\Models\BookingsModel;
use App\Models\CustomersModel;
use App\Models\MercadoPagoModel;
use App\Models\PaymentsModel;
use App\Models\RateModel;
use App\Models\UploadModel;
use App\Models\UsersModel;
use Config\Services;

class MercadoPago extends BaseController
{
    private function releaseBookingSlot(BookingSlotsModel $bookingSlotsModel, int $slotId): void
    {
        $slot = $bookingSlotsModel->find($slotId);
        if (!$slot) {
            return;
        }

        $bookingSlotsModel->update($slotId, [
            'active' => 0,
            'status' => 'cancelled',
        ]);
    }

    private function releaseActiveSlots(BookingSlotsModel $bookingSlotsModel, array $conditions): void
    {
        $builder = $bookingSlotsModel->where('active', 1);
        foreach ($conditions as $field => $value) {
            $builder->where($field, $value);
        }

        $slots = $builder->findAll();
        foreach ($slots as $slot) {
            $this->releaseBookingSlot($bookingSlotsModel, (int) $slot['id']);
        }
    }

    private function codeGenerate(): string
    {
        $chars = '0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZ';
        return substr(str_shuffle($chars), 0, 10);
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

    private function resolvePaymentUserId(): int
    {
        $sessionUserId = (int) (session()->get('id_user') ?? 0);
        if ($sessionUserId > 0) {
            return $sessionUserId;
        }

        $usersModel = new UsersModel();
        $fallbackUser = $usersModel->select('id')->orderBy('id', 'ASC')->first();

        return (int) ($fallbackUser['id'] ?? 0);
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

    public function setPreference()
    {
        $rateModel = new RateModel();
        $bookingsModel = new BookingsModel();
        $bookingSlotsModel = new BookingSlotsModel();
        $slotId = null;

        try {
            $rateRow = $rateModel->first();
            $data = $this->request->getJSON();
            $booking = $data->booking ?? null;
            $montoTotal = $data->amount ?? null;

            if (!$rateRow || !isset($rateRow['value'])) {
                return $this->response->setJSON($this->setResponse(400, true, null, 'No existe porcentaje de reserva configurado.'));
            }

            if (!$booking) {
                return $this->response->setJSON($this->setResponse(400, true, null, 'Faltan datos de la reserva.'));
            }

            if ($montoTotal === null || $montoTotal === '') {
                return $this->response->setJSON($this->setResponse(400, true, null, 'No se recibio el monto de la reserva.'));
            }

            $bookingArr = json_decode(json_encode($booking), true);
            $bookingDate = $bookingArr['fecha'] ?? null;
            $bookingField = $bookingArr['cancha'] ?? null;
            $timeFrom = $bookingArr['horarioDesde'] ?? null;
            $timeUntil = $bookingArr['horarioHasta'] ?? null;

            $existingBooking = $bookingsModel->where('date', $bookingDate)
                ->where('id_field', $bookingField)
                ->where('time_from', $timeFrom)
                ->where('time_until', $timeUntil)
                ->where('annulled', 0)
                ->first();

            if ($existingBooking) {
                return $this->response->setJSON($this->setResponse(409, true, null, 'El horario ya fue tomado por otra reserva. Actualiza e intenta nuevamente.'));
            }

            $slotId = $bookingSlotsModel->insert([
                'date' => $bookingDate,
                'id_field' => $bookingField,
                'time_from' => $timeFrom,
                'time_until' => $timeUntil,
                'status' => 'pending',
                'active' => 1,
                'expires_at' => date('Y-m-d H:i:s', strtotime('+5 minutes')),
                'created_at' => date('Y-m-d H:i:s'),
            ], true);

            if (!$slotId) {
                return $this->response->setJSON($this->setResponse(409, true, null, 'El horario ya fue tomado por otra reserva. Actualiza e intenta nuevamente.'));
            }

            $rate = (float) $rateRow['value'];
            $montoTotal = (float) $montoTotal;
            $montoParcial = ($montoTotal * $rate) / 100;
            $montoDiferencia = $montoTotal - $montoParcial;

            $mp = new MercadoPagoLibrary();
            $mp->setPreference('Pago total de reserva', (float) $montoTotal, 1);
            $preferenceIdTotal = $mp->preferenceId;

            $mp = new MercadoPagoLibrary();
            $mp->setPreference('Reserva de laberinto', (float) $montoParcial, 1);
            $preferenceIdParcial = $mp->preferenceId;

            $bookingId = null;
            $existingPendingBooking = $bookingsModel->where('id_preference_parcial', $preferenceIdParcial)
                ->orWhere('id_preference_total', $preferenceIdTotal)
                ->first();

            if (!$existingPendingBooking) {
                $bookingsModel->insert([
                    'date' => $bookingDate,
                    'id_field' => $bookingField,
                    'time_from' => $timeFrom,
                    'time_until' => $timeUntil,
                    'name' => $bookingArr['nombre'] ?? null,
                    'phone' => $bookingArr['telefono'] ?? null,
                    'visitors' => $bookingArr['visitantes'] ?? null,
                    'code' => $this->codeGenerate(),
                    'payment' => 0,
                    'approved' => 0,
                    'total' => $montoTotal,
                    'parcial' => $montoParcial,
                    'diference' => $montoDiferencia,
                    'reservation' => 0,
                    'total_payment' => 0,
                    'payment_method' => 'Mercado Pago',
                    'id_preference_parcial' => $preferenceIdParcial,
                    'id_preference_total' => $preferenceIdTotal,
                    'use_offer' => $bookingArr['oferta'] ?? 0,
                    'booking_time' => date('Y-m-d H:i:s'),
                    'mp' => 0,
                    'annulled' => 0,
                ]);
                $bookingId = $bookingsModel->getInsertID();
                $bookingSlotsModel->update($slotId, ['booking_id' => $bookingId]);
            } else {
                $bookingId = $existingPendingBooking['id'];
            }

            return $this->response->setJSON($this->setResponse(null, null, [
                'preferenceIdTotal' => $preferenceIdTotal,
                'preferenceIdParcial' => $preferenceIdParcial,
                'bookingId' => $bookingId,
            ], 'Respuesta exitosa'));
        } catch (\Throwable $e) {
            if ($slotId) {
                $this->releaseBookingSlot($bookingSlotsModel, (int) $slotId);
            }
            log_message('error', 'Error en setPreference: ' . $e->getMessage());
            return $this->response->setJSON($this->setResponse(409, true, null, 'El horario ya fue tomado por otra reserva. Actualiza e intenta nuevamente.'));
        }
    }

    public function success()
    {
        $mercadoPagoModel = new MercadoPagoModel();
        $bookingsModel = new BookingsModel();
        $customersModel = new CustomersModel();
        $bookingSlotsModel = new BookingSlotsModel();
        $paymentsModel = new PaymentsModel();

        $preferenceId = $this->request->getVar('preference_id');
        $existingBooking = null;
        $booking = null;
        $mercadoPago = null;

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

            $existingBooking = $bookingsModel->where('id_preference_parcial', $preferenceId)
                ->orWhere('id_preference_total', $preferenceId)
                ->first();

            if ($existingBooking) {
                $paid = $preferenceId === $existingBooking['id_preference_total']
                    ? (float) $existingBooking['total']
                    : (float) $existingBooking['parcial'];

                $customer = $customersModel->groupStart()
                    ->where('phone', $existingBooking['phone'])
                    ->orWhere('complete_phone', $existingBooking['phone'])
                    ->groupEnd()
                    ->first();

                if (!$customer) {
                    $customersModel->insert([
                        'name' => $existingBooking['name'],
                        'phone' => $existingBooking['phone'],
                        'complete_phone' => $existingBooking['phone'],
                        'offer' => 0,
                        'quantity' => 1,
                    ]);
                    $customerId = $customersModel->getInsertID();
                } else {
                    $customerId = $customer['id'];
                    $customersModel->update($customerId, [
                        'name' => $existingBooking['name'],
                        'quantity' => ((int) ($customer['quantity'] ?? 0)) + 1,
                    ]);
                }

                $bookingsModel->update($existingBooking['id'], [
                    'mp' => 1,
                    'approved' => 1,
                    'payment' => $paid,
                    'reservation' => $paid,
                    'diference' => (float) $existingBooking['total'] - $paid,
                    'total_payment' => $paid == (float) $existingBooking['total'],
                    'id_customer' => $customerId,
                ]);

                $bookingSlotsModel->where('booking_id', $existingBooking['id'])
                    ->where('active', 1)
                    ->set(['status' => 'confirmed', 'expires_at' => null])
                    ->update();

                $data['id_booking'] = $existingBooking['id'];
                $mercadoPagoModel->insert($data);

                $alreadyStoredMpPayment = null;
                if (!empty($data['payment_id'])) {
                    $alreadyStoredMpPayment = $paymentsModel
                        ->where('id_booking', $existingBooking['id'])
                        ->where('id_mercado_pago', $data['payment_id'])
                        ->first();
                }

            if (!$alreadyStoredMpPayment) {
                try {
                    $paymentUserId = $this->resolvePaymentUserId();
                    $paymentsModel->insert([
                        'id_user' => $paymentUserId > 0 ? $paymentUserId : 1,
                        'id_booking' => $existingBooking['id'],
                        'id_customer' => $customerId,
                        'id_mercado_pago' => $data['payment_id'] ?? null,
                            'amount' => $paid,
                            'payment_method' => 'mercado_pago',
                            'date' => date('Y-m-d'),
                            'created_at' => date('Y-m-d H:i:s'),
                        ]);
                    } catch (\Throwable $e) {
                        log_message('error', 'No se pudo registrar pago MP: ' . $e->getMessage());
                    }
                }

                $booking = $bookingsModel->find($existingBooking['id']);
                $mercadoPago = $mercadoPagoModel->where('id_booking', $existingBooking['id'])->first();
                $this->sendBookingEmails($booking);
            }
        }

        if (!$existingBooking) {
            return redirect()->to(base_url('pagoRechazado'));
        }

        return redirect()->to(base_url('pagoAprobado/' . $existingBooking['id']));
    }

    public function failure()
    {
        $mercadoPagoModel = new MercadoPagoModel();
        $bookingsModel = new BookingsModel();
        $bookingSlotsModel = new BookingSlotsModel();

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

            $existingBooking = $bookingsModel->where('id_preference_parcial', $preferenceId)
                ->orWhere('id_preference_total', $preferenceId)
                ->first();

            if ($existingBooking) {
                $bookingsModel->update($existingBooking['id'], [
                    'approved' => 0,
                    'annulled' => 1,
                ]);
                $this->releaseActiveSlots($bookingSlotsModel, ['booking_id' => $existingBooking['id']]);
                $data['id_booking'] = $existingBooking['id'];
                $mercadoPagoModel->insert($data);
            }
        }

        return redirect()->to(base_url('pagoRechazado'));
    }

    public function successView($bookingId)
    {
        $bookingsModel = new BookingsModel();
        $mercadoPagoModel = new MercadoPagoModel();

        $booking = $bookingsModel->find($bookingId);
        if (!$booking) {
            return redirect()->to(base_url('pagoRechazado'));
        }

        $mercadoPago = $mercadoPagoModel->where('id_booking', $bookingId)
            ->orderBy('id', 'DESC')
            ->first();

        return view('mercadoPago/success', [
            'bookingId' => $bookingId,
            'booking' => $booking,
            'mercadoPago' => $mercadoPago,
        ]);
    }

    public function failureView()
    {
        return view('mercadoPago/failure');
    }

    public function cancelPendingMpReservation()
    {
        $bookingsModel = new BookingsModel();
        $bookingSlotsModel = new BookingSlotsModel();
        $data = $this->request->getJSON();

        $bookingId = $data->bookingId ?? null;
        $preferenceIdParcial = $data->preferenceIdParcial ?? null;
        $preferenceIdTotal = $data->preferenceIdTotal ?? null;

        try {
            if ($bookingId) {
                $booking = $bookingsModel->find($bookingId);
                if ($booking && (int) ($booking['approved'] ?? 0) !== 1) {
                    $bookingsModel->update($bookingId, ['annulled' => 1]);
                    $this->releaseActiveSlots($bookingSlotsModel, ['booking_id' => $bookingId]);
                }
            } elseif ($preferenceIdParcial || $preferenceIdTotal) {
                $query = $bookingsModel->groupStart();
                if ($preferenceIdParcial) {
                    $query->where('id_preference_parcial', $preferenceIdParcial);
                }
                if ($preferenceIdTotal) {
                    $query->orWhere('id_preference_total', $preferenceIdTotal);
                }
                $query->groupEnd();

                $bookings = $query->findAll();
                foreach ($bookings as $booking) {
                    if ((int) ($booking['approved'] ?? 0) === 1) {
                        continue;
                    }

                    $bookingsModel->update($booking['id'], ['annulled' => 1]);
                    $this->releaseActiveSlots($bookingSlotsModel, ['booking_id' => $booking['id']]);
                }
            }

            return $this->response->setJSON($this->setResponse(null, null, null, 'Respuesta exitosa'));
        } catch (\Throwable $e) {
            return $this->response->setJSON($this->setResponse(500, true, null, $e->getMessage()));
        }
    }

    public function savePreferenceIds()
    {
        return $this->response->setJSON($this->setResponse(null, null, null, 'Respuesta exitosa'));
    }

    public function setResponse($code = 200, $error = false, $data = null, $message = '')
    {
        return [
            'error' => $error,
            'code' => $code,
            'data' => $data,
            'message' => $message,
        ];
    }
}
