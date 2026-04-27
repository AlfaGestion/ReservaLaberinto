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

            $bookingUrl = site_url('MisReservas/' . rawurlencode($this->buildReservationAccessToken([
                'code' => (string) ($booking['code'] ?? ''),
            ])));
            $html = $this->renderEmailCard([
                'eyebrow' => 'Nueva reserva',
                'title' => $subjectName !== '' ? $subjectName . ' reservo una visita' : 'Se recibio una nueva reserva',
                'intro' => 'La reserva fue confirmada por el flujo de pago y ya quedo registrada.',
                'details' => [
                    'Nombre' => (string) ($booking['name'] ?? ''),
                    'Telefono' => (string) ($booking['phone'] ?? ''),
                    'Fecha' => $formattedDate,
                    'Horario' => trim(((string) ($booking['time_from'] ?? '')) . ' a ' . ((string) ($booking['time_until'] ?? ''))),
                    'Visitantes' => (string) ($booking['visitors'] ?? ''),
                    'Total' => '$' . (string) ($booking['total'] ?? ''),
                    'Codigo' => (string) ($booking['code'] ?? ''),
                ],
                'messageHtml' => '<p>Se registro una nueva reserva desde Mercado Pago.</p>',
                'primaryActionUrl' => $bookingUrl,
                'primaryActionLabel' => 'Ver reserva',
            ]);

            $this->sendEmailWithFallback($notificationEmailList, $subject, $html, true);
        }

        $customerEmail = $this->resolveCustomerEmail($booking);
        if ($customerEmail === '') {
            return;
        }

        $customerName = trim((string) ($booking['name'] ?? 'Cliente'));
        $formattedDate = $this->formatBookingDate((string) ($booking['date'] ?? ''));
        $subject = "Reserva confirmada - Laberinto: {$customerName}";

        $bookingLink = site_url('MisReservas/' . rawurlencode($this->buildReservationAccessToken([
            'code' => (string) ($booking['code'] ?? ''),
            'phone' => (string) ($booking['phone'] ?? ''),
            'email' => $customerEmail,
        ])));
        $downloadPdfUrl = !empty($booking['id']) ? site_url('bookingPdf/' . $booking['id']) : '';
        $html = $this->renderEmailCard([
            'eyebrow' => 'Reserva confirmada',
            'title' => 'Tu visita ya quedo confirmada',
            'intro' => 'El pago se acredito correctamente y la reserva ya esta lista.',
            'details' => [
                'Nombre' => $customerName,
                'Fecha' => $formattedDate,
                'Horario' => trim(($booking['time_from'] ?? '') . ' a ' . ($booking['time_until'] ?? '')),
                'Cantidad' => (string) ($booking['visitors'] ?? ''),
                'Total' => '$' . (string) ($booking['total'] ?? ''),
                'Pagado' => '$' . (string) ($booking['payment'] ?? '0'),
                'Saldo pendiente' => '$' . (string) ($booking['diference'] ?? '0'),
                'Codigo' => (string) ($booking['code'] ?? ''),
            ],
            'messageHtml' => '<p>Te dejamos accesos rapidos para revisar tu reserva y descargar el comprobante.</p>',
            'primaryActionUrl' => $bookingLink,
            'primaryActionLabel' => 'Ver reserva',
            'secondaryActionUrl' => $downloadPdfUrl,
            'secondaryActionLabel' => $downloadPdfUrl !== '' ? 'Descargar comprobante' : '',
            'supportText' => 'Se asume el compromiso y la responsabilidad de asistir en el dia y horario acordados. En caso de inasistencia, no se realizaran devoluciones y la reprogramacion queda sujeta a disponibilidad.',
        ]);

        $this->sendEmailWithFallback($customerEmail, $subject, $html, true);
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
            $totalEntries = (int) ($bookingArr['visitantes'] ?? 0);

            if ($totalEntries <= 0) {
                if ($slotId) {
                    $this->releaseBookingSlot($bookingSlotsModel, (int) $slotId);
                    $slotId = null;
                }
                return $this->response->setJSON($this->setResponse(400, true, null, 'La cantidad de entradas es invalida.'));
            }

            $unitPrice = $montoTotal / $totalEntries;
            $requestedEntries = (int) ($bookingArr['entriesToPay'] ?? $bookingArr['entradasAbonar'] ?? 0);
            $wantsPayByEntries = !empty($bookingArr['partialByEntries']) || $requestedEntries > 0;
            $usePayByEntries = false;

            if ($wantsPayByEntries && empty($bookingArr['pagoTotal'])) {
                if (! $this->canPayByEntries($totalEntries, $bookingDate)) {
                    if ($slotId) {
                        $this->releaseBookingSlot($bookingSlotsModel, (int) $slotId);
                        $slotId = null;
                    }
                    return $this->response->setJSON($this->setResponse(400, true, null, 'La reserva no cumple las condiciones para pago parcial por entradas.'));
                }

                if ($requestedEntries <= 0 || $requestedEntries >= $totalEntries) {
                    if ($slotId) {
                        $this->releaseBookingSlot($bookingSlotsModel, (int) $slotId);
                        $slotId = null;
                    }
                    return $this->response->setJSON($this->setResponse(400, true, null, 'La cantidad de entradas a abonar es invalida.'));
                }

                $usePayByEntries = true;
            }

            $montoParcial = $usePayByEntries
                ? $requestedEntries * $unitPrice
                : ($montoTotal * $rate) / 100;
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
                $orderId = $this->generateBookingOrderId();
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
                    'partial_by_entries' => $usePayByEntries ? 1 : 0,
                    'paid_entries' => 0,
                    'IdPedido' => $orderId,
                ]);
                $bookingId = $bookingsModel->getInsertID();
                $bookingSlotsModel->update($slotId, ['booking_id' => $bookingId]);
                $this->logBookingAction($orderId, 'A', 'Alta de reserva por ' . $totalEntries . ' entradas.', 'CLIENTE');
            } else {
                $bookingId = $existingPendingBooking['id'];
                $orderId = $this->ensureBookingOrderId($existingPendingBooking);
            }

            return $this->response->setJSON($this->setResponse(null, null, [
                'preferenceIdTotal' => $preferenceIdTotal,
                'preferenceIdParcial' => $preferenceIdParcial,
                'bookingId' => $bookingId,
                'payByEntries' => $usePayByEntries,
                'entriesToPay' => $usePayByEntries ? $requestedEntries : null,
                'unitPrice' => $unitPrice,
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
                $alreadyStoredMpPayment = null;
                if (!empty($data['payment_id'])) {
                    $alreadyStoredMpPayment = $paymentsModel
                        ->where('id_booking', $existingBooking['id'])
                        ->where('id_mercado_pago', $data['payment_id'])
                        ->first();
                }

                if ($alreadyStoredMpPayment) {
                    $booking = $bookingsModel->find($existingBooking['id']);
                } else {
                    $isTotalPreference = $preferenceId === $existingBooking['id_preference_total'];
                    $isEntryPayment = (int) ($existingBooking['partial_by_entries'] ?? 0) === 1;
                    $totalEntries = max(0, (int) ($existingBooking['visitors'] ?? 0));
                    $currentPaidEntries = max(0, (int) ($existingBooking['paid_entries'] ?? 0));
                    $unitPriceAtBooking = $totalEntries > 0 ? (float) $existingBooking['total'] / $totalEntries : 0.0;
                    $paid = $isTotalPreference ? (float) $existingBooking['total'] : (float) $existingBooking['parcial'];
                    $paidEntries = null;
                    $paymentType = $isTotalPreference ? 'total' : 'partial_amount';
                    $newPayment = $paid;
                    $newDifference = max(0, (float) $existingBooking['total'] - $paid);
                    $newPaidEntries = $currentPaidEntries;
                    $totalPaymentCompleted = $paid == (float) $existingBooking['total'];

                    if ($isEntryPayment) {
                        if ($isTotalPreference) {
                            $paidEntries = max(0, $totalEntries - $currentPaidEntries);
                            $newPaidEntries = $totalEntries;
                            $newPayment = (float) $existingBooking['total'];
                            $newDifference = 0.0;
                            $totalPaymentCompleted = true;
                            $paymentType = 'total';
                        } else {
                            $pendingEntriesBeforePayment = max(0, $totalEntries - $currentPaidEntries);
                            $paidEntries = $unitPriceAtBooking > 0 ? (int) round($paid / $unitPriceAtBooking) : 0;
                            $paidEntries = min(max(1, $paidEntries), $pendingEntriesBeforePayment);
                            $newPaidEntries = min($totalEntries, $currentPaidEntries + $paidEntries);
                            $pendingEntries = max(0, $totalEntries - $newPaidEntries);
                            $newPayment = (float) ($existingBooking['payment'] ?? 0) + $paid;
                            $newDifference = $pendingEntries * $unitPriceAtBooking;
                            $totalPaymentCompleted = $pendingEntries === 0;
                            $paymentType = 'partial_entries';
                        }
                    } elseif ($isTotalPreference) {
                        $newPaidEntries = $totalEntries;
                    }

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
                        'payment' => $newPayment,
                        'reservation' => $newPayment,
                        'diference' => $newDifference,
                        'total_payment' => $totalPaymentCompleted ? 1 : 0,
                        'id_customer' => $customerId,
                        'paid_entries' => $newPaidEntries,
                    ]);

                    $bookingSlotsModel->where('booking_id', $existingBooking['id'])
                        ->where('active', 1)
                        ->set(['status' => 'confirmed', 'expires_at' => null])
                        ->update();

                    $data['id_booking'] = $existingBooking['id'];
                    $mercadoPagoModel->insert($data);

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
                            'paid_entries' => $paidEntries,
                            'unit_price' => $unitPriceAtBooking > 0 ? $unitPriceAtBooking : null,
                            'payment_type' => $paymentType,
                            'created_by_admin' => 0,
                            'admin_user_id' => null,
                        ]);
                    } catch (\Throwable $e) {
                        log_message('error', 'No se pudo registrar pago MP: ' . $e->getMessage());
                    }

                    $orderId = $this->ensureBookingOrderId($existingBooking);
                    if ($isEntryPayment && $paymentType === 'partial_entries') {
                        $pendingEntries = max(0, $totalEntries - $newPaidEntries);
                        $this->logBookingAction(
                            $orderId,
                            'P',
                            'Pago parcial por entradas. Cantidad: ' . $paidEntries . ' entradas. Precio unitario: ' . $this->formatAuditMoney($unitPriceAtBooking) . '. Total: ' . $this->formatAuditMoney($paid) . '. Medio: Mercado Pago. Pendiente: ' . $pendingEntries . ' entradas.',
                            'CLIENTE'
                        );
                    } elseif ($totalPaymentCompleted) {
                        $this->logBookingAction($orderId, 'P', 'Pago total. Total abonado: ' . $this->formatAuditMoney($paid) . '. Medio: Mercado Pago.', 'CLIENTE');
                    } else {
                        $this->logBookingAction($orderId, 'P', 'Pago parcial. Total abonado: ' . $this->formatAuditMoney($paid) . '. Medio: Mercado Pago. Saldo pendiente: ' . $this->formatAuditMoney($newDifference) . '.', 'CLIENTE');
                    }

                    $booking = $bookingsModel->find($existingBooking['id']);
                    $mercadoPago = $mercadoPagoModel->where('id_booking', $existingBooking['id'])->first();
                    $this->sendBookingEmails($booking);
                }
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
                $this->logBookingAction($this->ensureBookingOrderId($existingBooking), 'C', 'Cancelacion de reserva.', 'CLIENTE');
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
                    $this->logBookingAction($this->ensureBookingOrderId($booking), 'C', 'Cancelacion de reserva pendiente de Mercado Pago.', 'CLIENTE');
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
                    $this->logBookingAction($this->ensureBookingOrderId($booking), 'C', 'Cancelacion de reserva pendiente de Mercado Pago.', 'CLIENTE');
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
