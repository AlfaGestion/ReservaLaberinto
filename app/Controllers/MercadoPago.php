<?php

namespace App\Controllers;

use App\Controllers\BaseController;
use App\Libraries\MercadoPagoLibrary;
use App\Libraries\MercadoPagoReservationService;
use App\Models\BookingSlotsModel;
use App\Models\BookingsModel;
use App\Models\CustomersModel;
use App\Models\FieldsModel;
use App\Models\MercadoPagoModel;
use App\Models\PaymentsModel;
use App\Models\RejectedPaymentsModel;
use App\Models\RateModel;
use App\Models\UploadModel;
use App\Models\UsersModel;
use Config\Services;

class MercadoPago extends BaseController
{
    private const PAYMENT_RETRY_MINUTES = 30;
    private ?MercadoPagoReservationService $reservationService = null;

    private function reservationService(): MercadoPagoReservationService
    {
        if ($this->reservationService === null) {
            $this->reservationService = new MercadoPagoReservationService();
        }

        return $this->reservationService;
    }

    private function logMercadoPagoCallback(string $source, array $payload): void
    {
        try {
            log_message('info', 'MP callback [' . $source . ']: ' . json_encode($payload, JSON_UNESCAPED_UNICODE));
        } catch (\Throwable $e) {
            log_message('error', 'No se pudo registrar log de callback MP [' . $source . ']: ' . $e->getMessage());
        }
    }

    private function releaseBookingSlot(BookingSlotsModel $bookingSlotsModel, int $slotId): void
    {
        $slot = $bookingSlotsModel->find($slotId);
        if (!$slot) {
            return;
        }

        $bookingSlotsModel->delete($slotId);
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

    private function expirePendingSlots(BookingSlotsModel $bookingSlotsModel, BookingsModel $bookingsModel, array $conditions = []): void
    {
        $this->reservationService()->expirePendingReservations($conditions, 'setPreference');
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

    private function resolveBrandLogoPath(): string
    {
        $uploadModel = new UploadModel();
        $branding = $uploadModel->first();
        $logoFile = trim((string) ($branding['name'] ?? ''));

        return $logoFile !== ''
            ? base_url(PUBLIC_FOLDER . 'assets/images/uploads/' . $logoFile)
            : base_url(PUBLIC_FOLDER . 'assets/images/sinlogo2.png');
    }

    private function normalizeMpStatus($status): string
    {
        return strtolower(trim((string) $status));
    }

    private function buildRetryPaymentUrlFromBooking(array $booking): string
    {
        $preferenceId = trim((string) ($booking['id_preference_parcial'] ?? ''));
        if ($preferenceId === '') {
            $preferenceId = trim((string) ($booking['id_preference_total'] ?? ''));
        }

        if ($preferenceId === '') {
            return '';
        }

        return 'https://www.mercadopago.com.ar/checkout/v1/redirect?pref_id=' . rawurlencode($preferenceId);
    }

    private function findBookingForMpCallback(array $payload, array $result = []): ?array
    {
        $bookingsModel = new BookingsModel();
        $mercadoPagoModel = new MercadoPagoModel();
        $rejectedPaymentsModel = new RejectedPaymentsModel();

        $bookingId = (int) ($result['booking_id'] ?? $payload['booking_id'] ?? 0);
        if ($bookingId > 0) {
            $booking = $bookingsModel->find($bookingId);
            if (is_array($booking)) {
                return $booking;
            }
        }

        $preferenceId = trim((string) ($payload['preference_id'] ?? ''));
        $paymentId = trim((string) ($payload['payment_id'] ?? ''));
        $externalReference = trim((string) ($payload['external_reference'] ?? ''));
        $identifiers = array_values(array_filter([$preferenceId, $paymentId, $externalReference], static fn ($value) => $value !== ''));

        foreach ($identifiers as $identifier) {
            $booking = $bookingsModel->groupStart()
                ->where('id_preference_parcial', $identifier)
                ->orWhere('id_preference_total', $identifier)
                ->groupEnd()
                ->orderBy('id', 'DESC')
                ->first();

            if (is_array($booking)) {
                return $booking;
            }
        }

        if ($paymentId !== '') {
            $mpLog = $mercadoPagoModel->where('payment_id', $paymentId)->orderBy('id', 'DESC')->first();
            if (!empty($mpLog['id_booking'])) {
                $booking = $bookingsModel->find((int) $mpLog['id_booking']);
                if (is_array($booking)) {
                    return $booking;
                }
            }
        }

        foreach ([$preferenceId, $paymentId] as $identifier) {
            if ($identifier === '') {
                continue;
            }

            $rejectedRecord = $rejectedPaymentsModel->groupStart()
                ->where('preference_id', $identifier)
                ->orWhere('payment_id', $identifier)
                ->groupEnd()
                ->orderBy('id', 'DESC')
                ->first();

            if (!empty($rejectedRecord['booking_id'])) {
                $booking = $bookingsModel->find((int) $rejectedRecord['booking_id']);
                if (is_array($booking)) {
                    return $booking;
                }
            }
        }

        return null;
    }

    private function findRejectedPaymentForBooking(?array $booking, array $payload = []): ?array
    {
        $rejectedPaymentsModel = new RejectedPaymentsModel();
        $preferenceId = trim((string) ($payload['preference_id'] ?? ''));
        $paymentId = trim((string) ($payload['payment_id'] ?? ''));

        if (empty($booking['id']) && $preferenceId === '' && $paymentId === '') {
            return null;
        }

        $query = $rejectedPaymentsModel;

        if (!empty($booking['id'])) {
            $query = $query->where('booking_id', (int) $booking['id']);
        }

        if ($preferenceId !== '' || $paymentId !== '') {
            $query = $query->groupStart();
            $hasCondition = false;

            if ($preferenceId !== '') {
                $query->where('preference_id', $preferenceId);
                $hasCondition = true;
            }

            if ($paymentId !== '') {
                if ($hasCondition) {
                    $query->orWhere('payment_id', $paymentId);
                } else {
                    $query->where('payment_id', $paymentId);
                }
            }

            $query = $query->groupEnd();
        }

        $record = $query->orderBy('id', 'DESC')->first();
        return is_array($record) ? $record : null;
    }

    private function buildMpFailureViewData(array $payload, array $result = []): array
    {
        $booking = $this->findBookingForMpCallback($payload, $result);
        $rejectedRecord = $this->findRejectedPaymentForBooking($booking, $payload);
        $status = $this->normalizeMpStatus($payload['status'] ?? ($result['status'] ?? 'cancelled'));

        if ($status === '' || in_array($status, ['not_found', 'missing_payment_id', 'unknown'], true)) {
            $status = 'cancelled';
        }

        $statusMap = [
            'cancelled' => [
                'title' => 'Pago cancelado',
                'subtitle' => 'No se registró ningún cobro y tu reserva no fue confirmada.',
                'message' => 'Podés volver a iniciar la reserva o intentar nuevamente si querés conservar el horario.',
                'icon' => 'fa-circle-pause',
                'tone' => 'secondary',
            ],
            'rejected' => [
                'title' => 'Pago rechazado',
                'subtitle' => 'No se registró ningún cobro y tu reserva no fue confirmada.',
                'message' => 'Podés volver a intentar el pago desde el enlace disponible o iniciar una nueva reserva.',
                'icon' => 'fa-circle-xmark',
                'tone' => 'danger',
            ],
            'failed' => [
                'title' => 'Pago rechazado',
                'subtitle' => 'No se registró ningún cobro y tu reserva no fue confirmada.',
                'message' => 'Podés volver a intentar el pago desde el enlace disponible o iniciar una nueva reserva.',
                'icon' => 'fa-circle-xmark',
                'tone' => 'danger',
            ],
            'failure' => [
                'title' => 'Pago cancelado',
                'subtitle' => 'No se registró ningún cobro y tu reserva no fue confirmada.',
                'message' => 'Podés volver a iniciar la reserva o intentar nuevamente si querés conservar el horario.',
                'icon' => 'fa-circle-pause',
                'tone' => 'secondary',
            ],
            'not_approved' => [
                'title' => 'Pago no aprobado',
                'subtitle' => 'No se registró ningún cobro y tu reserva no fue confirmada.',
                'message' => 'Podés volver a intentar el pago o iniciar una nueva reserva.',
                'icon' => 'fa-circle-xmark',
                'tone' => 'danger',
            ],
            'pending' => [
                'title' => 'Pago pendiente',
                'subtitle' => 'Todavía no se acreditó ningún cobro.',
                'message' => 'Cuando Mercado Pago confirme la operación, la reserva se validará automáticamente.',
                'icon' => 'fa-hourglass-half',
                'tone' => 'warning',
            ],
            'in_process' => [
                'title' => 'Pago en revisión',
                'subtitle' => 'Todavía no se acreditó ningún cobro.',
                'message' => 'Mercado Pago está verificando la operación. No se confirmó la reserva todavía.',
                'icon' => 'fa-clock',
                'tone' => 'warning',
            ],
            'error' => [
                'title' => 'No pudimos confirmar el pago',
                'subtitle' => 'No se registró ningún cobro y tu reserva no fue confirmada.',
                'message' => 'Podés volver al inicio o intentar nuevamente si el horario sigue disponible.',
                'icon' => 'fa-triangle-exclamation',
                'tone' => 'danger',
            ],
        ];

        $data = $statusMap[$status] ?? $statusMap['rejected'];
        $retryUrl = trim((string) ($rejectedRecord['retry_url'] ?? ''));
        if ($retryUrl === '' && $booking) {
            $retryUrl = $this->buildRetryPaymentUrlFromBooking($booking);
        }

        $bookingDetails = null;
        if (is_array($booking)) {
            $fieldName = trim((string) ($booking['id_field'] ?? '-'));
            if ($fieldName !== '' && is_numeric($fieldName)) {
                $fieldsModel = new FieldsModel();
                $field = $fieldsModel->find((int) $fieldName);
                $fieldName = trim((string) ($field['name'] ?? $fieldName));
            }

            $bookingDetails = [
                'id' => (int) ($booking['id'] ?? 0),
                'name' => trim((string) ($booking['name'] ?? '')),
                'phone' => trim((string) ($booking['phone'] ?? '')),
                'date' => $this->formatBookingDate((string) ($booking['date'] ?? '')),
                'time' => trim(((string) ($booking['time_from'] ?? '')) . ' a ' . ((string) ($booking['time_until'] ?? ''))),
                'field' => $fieldName !== '' ? $fieldName : '-',
                'code' => trim((string) ($booking['code'] ?? '')),
                'total' => format_price_ar($booking['total'] ?? 0),
                'payment' => format_price_ar($booking['payment'] ?? 0),
                'difference' => format_price_ar($booking['diference'] ?? 0),
                'approved' => (int) ($booking['approved'] ?? 0),
                'annulled' => (int) ($booking['annulled'] ?? 0),
            ];
        }

        return [
            'status' => $status,
            'title' => $data['title'],
            'subtitle' => $data['subtitle'],
            'message' => $data['message'],
            'icon' => $data['icon'],
            'tone' => $data['tone'],
            'retryUrl' => $retryUrl,
            'homeUrl' => base_url(),
            'booking' => $bookingDetails,
            'logoPath' => $this->resolveBrandLogoPath(),
            'hasRetryUrl' => $retryUrl !== '',
            'isApprovedBooking' => is_array($booking) && (int) ($booking['approved'] ?? 0) === 1 && (int) ($booking['annulled'] ?? 0) !== 1,
        ];
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

    private function resolveBookingEmailFromData(array $booking): string
    {
        $email = trim((string) ($booking['email'] ?? ''));
        if ($email !== '' && filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return $email;
        }

        return $this->resolveCustomerEmail($booking);
    }

    private function buildRetryPaymentUrl(array $booking): string
    {
        $preferenceId = trim((string) ($booking['id_preference_parcial'] ?? ''));
        if ($preferenceId === '' && !empty($booking['id_preference_total'])) {
            $preferenceId = trim((string) $booking['id_preference_total']);
        }

        if ($preferenceId === '') {
            return '';
        }

        return 'https://www.mercadopago.com.ar/checkout/v1/redirect?pref_id=' . rawurlencode($preferenceId);
    }

    private function upsertRejectedPaymentRecord(array $booking, array $paymentData, string $status, bool $notifyCustomer): ?array
    {
        $bookingId = (int) ($booking['id'] ?? 0);
        if ($bookingId <= 0) {
            return null;
        }

        $rejectedPaymentsModel = new RejectedPaymentsModel();
        $expiresAt = date('Y-m-d H:i:s', strtotime('+' . self::PAYMENT_RETRY_MINUTES . ' minutes'));
        $retryUrl = $this->buildRetryPaymentUrl($booking);

        $recordData = [
            'booking_id' => $bookingId,
            'customer_id' => !empty($booking['id_customer']) ? (int) $booking['id_customer'] : null,
            'name' => $booking['name'] ?? null,
            'email' => $this->resolveBookingEmailFromData($booking),
            'phone' => $booking['phone'] ?? null,
            'booking_date' => $booking['date'] ?? null,
            'booking_time_from' => $booking['time_from'] ?? null,
            'booking_time_until' => $booking['time_until'] ?? null,
            'visitors' => $booking['visitors'] ?? null,
            'total' => $booking['total'] ?? null,
            'amount_to_pay' => $booking['parcial'] ?? $booking['payment'] ?? null,
            'payment_status' => $status,
            'payment_reason' => trim((string) ($paymentData['status_detail'] ?? $paymentData['collection_status'] ?? $paymentData['status'] ?? '')),
            'preference_id' => $paymentData['preference_id'] ?? ($booking['id_preference_parcial'] ?? null),
            'payment_id' => $paymentData['payment_id'] ?? null,
            'external_reference' => $paymentData['external_reference'] ?? null,
            'retry_url' => $retryUrl !== '' ? $retryUrl : null,
            'expires_at' => $expiresAt,
            'updated_at' => date('Y-m-d H:i:s'),
        ];

        $existing = $rejectedPaymentsModel->where('booking_id', $bookingId)
            ->whereIn('payment_status', ['pending_retry', 'rejected', 'expired'])
            ->orderBy('id', 'DESC')
            ->first();

        if ($existing) {
            $rejectedPaymentsModel->update((int) $existing['id'], $recordData);
            $recordId = (int) $existing['id'];
        } else {
            $recordData['created_at'] = date('Y-m-d H:i:s');
            $recordId = (int) $rejectedPaymentsModel->insert($recordData, true);
        }

        $saved = $rejectedPaymentsModel->find($recordId);

        if ($notifyCustomer && $saved) {
            $this->sendRetryPaymentEmail($booking, $saved);
            $rejectedPaymentsModel->update($recordId, ['notified_at' => date('Y-m-d H:i:s')]);
            $saved = $rejectedPaymentsModel->find($recordId);
        }

        return $saved ?: null;
    }

    private function sendRetryPaymentEmail(array $booking, array $rejectedRecord): void
    {
        $to = trim((string) ($rejectedRecord['email'] ?? ''));
        if ($to === '' || !filter_var($to, FILTER_VALIDATE_EMAIL)) {
            return;
        }

        $retryUrl = trim((string) ($rejectedRecord['retry_url'] ?? ''));
        if ($retryUrl === '') {
            return;
        }

        $customerName = trim((string) ($booking['name'] ?? 'Cliente'));
        $formattedDate = $this->formatBookingDate((string) ($booking['date'] ?? ''));
        $paymentStatus = strtolower(trim((string) ($rejectedRecord['payment_status'] ?? 'rejected')));
        $isRejected = in_array($paymentStatus, ['rejected', 'cancelled'], true);
        $isExpired = $paymentStatus === 'expired';
        $subject = $isRejected ? 'Pago no aprobado - Tu reserva no fue confirmada' : 'Tu reserva sigue pendiente de pago';
        $supportText = $this->buildPaymentSupportText($this->getPaymentSupportContact());

        $html = $this->renderEmailCard([
            'eyebrow' => $isRejected ? 'Pago no aprobado' : 'Pago pendiente',
            'title' => $isRejected ? 'Tu pago no fue aprobado' : 'Tu reserva aun no fue confirmada',
            'intro' => $isRejected
                ? 'Detectamos que Mercado Pago rechazo o cancelo el pago.'
                : 'Detectamos que el pago no se acredito.',
            'details' => [
                'Nombre' => $customerName,
                'Fecha' => $formattedDate,
                'Horario' => trim(((string) ($booking['time_from'] ?? '')) . ' a ' . ((string) ($booking['time_until'] ?? ''))),
                'Visitantes' => (string) ($booking['visitors'] ?? ''),
                'Total' => '$' . (string) ($booking['total'] ?? '0'),
            ],
            'messageHtml' => $isRejected
                ? '<p>El pago quedo rechazado o cancelado. Si queres continuar, volve a intentar el pago o contactate con soporte.</p>'
                : '<p>Tu reserva todavia no fue confirmada porque el pago no se acredito. Podes completarlo desde el siguiente boton. Si no se registra el pago dentro de los proximos 30 minutos, la reserva quedara como rechazada y no ocupara disponibilidad.</p>',
            'primaryActionUrl' => $retryUrl,
            'primaryActionLabel' => $isRejected ? 'Reintentar pago' : 'Completar pago',
            'supportText' => $supportText . ($isExpired ? ' La reserva expiro por tiempo de pago.' : ''),
        ]);

        $this->sendEmailWithFallback($to, $subject, $html, true);
    }

    private function getPaymentSupportContact(): array
    {
        $uploadModel = new UploadModel();
        $config = $uploadModel->first() ?: [];

        return [
            'email' => trim((string) ($config['payment_support_email'] ?? '')),
            'phone' => trim((string) ($config['payment_support_phone'] ?? '')),
        ];
    }

    private function buildPaymentSupportText(array $contact): string
    {
        $email = trim((string) ($contact['email'] ?? ''));
        $phone = trim((string) ($contact['phone'] ?? ''));
        $parts = [];

        if ($email !== '' && filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $parts[] = $email;
        }

        if ($phone !== '') {
            $parts[] = $phone;
        }

        if ($parts === []) {
            return 'Si necesitas ayuda con tu pago, respondenos a este correo.';
        }

        return 'Si necesitas ayuda con tu pago, contactate con ' . implode(' o ', $parts) . '.';
    }

    private function sendPendingReservationEmail(array $booking, array $bookingData): bool
    {
        $customerEmail = trim((string) ($bookingData['email'] ?? ''));
        if ($customerEmail === '' || !filter_var($customerEmail, FILTER_VALIDATE_EMAIL)) {
            $customerEmail = $this->resolveCustomerEmail($booking);
        }

        if ($customerEmail === '') {
            return false;
        }

        $supportText = $this->buildPaymentSupportText($this->getPaymentSupportContact());
        $retryUrl = $this->buildRetryPaymentUrl($booking);
        $customerName = trim((string) ($bookingData['nombre'] ?? ($booking['name'] ?? 'Cliente')));
        $subject = 'Reserva pendiente de pago - Laberinto';

        $html = $this->renderEmailCard([
            'eyebrow' => 'Reserva pendiente',
            'title' => 'Tu reserva quedo registrada, pero falta confirmar el pago',
            'intro' => 'La disponibilidad quedo reservada temporalmente hasta que Mercado Pago apruebe el pago.',
            'details' => [
                'Nombre' => $customerName,
                'Fecha' => $this->formatBookingDate((string) ($bookingData['fecha'] ?? ($booking['date'] ?? ''))),
                'Horario' => trim(((string) ($bookingData['horarioDesde'] ?? ($booking['time_from'] ?? ''))) . ' a ' . ((string) ($bookingData['horarioHasta'] ?? ($booking['time_until'] ?? '')))),
                'Visitantes' => (string) ($bookingData['visitantes'] ?? ($booking['visitors'] ?? '')),
                'Precio por entrada individual' => $this->formatAuditMoney($this->resolveCurrentUnitPriceForBooking($booking)),
                'Total' => '$' . (string) ($bookingData['total'] ?? ($booking['total'] ?? '0')),
            ],
            'messageHtml' => '<p>Tu reserva quedo iniciada correctamente. Completá el pago para que quede confirmada y no pierdas el horario elegido.</p>',
            'primaryActionUrl' => $retryUrl,
            'primaryActionLabel' => 'Continuar con el pago',
            'supportText' => $supportText,
        ]);

        return $this->sendEmailWithFallback($customerEmail, $subject, $html, true);
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

            if ($bookingDate && $bookingField && $timeFrom && $timeUntil) {
                $this->expirePendingSlots($bookingSlotsModel, $bookingsModel, [
                    'date' => $bookingDate,
                    'id_field' => $bookingField,
                    'time_from' => $timeFrom,
                    'time_until' => $timeUntil,
                ]);
            }

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
                'expires_at' => $this->reservationService()->getExpiresAtFromNow(),
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
                $bookingData = [
                    'date' => $bookingDate,
                    'id_field' => $bookingField,
                    'time_from' => $timeFrom,
                    'time_until' => $timeUntil,
                    'name' => $bookingArr['nombre'] ?? null,
                    'phone' => $bookingArr['telefono'] ?? null,
                    'email' => $bookingArr['email'] ?? null,
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
                ];

                $bookingDb = db_connect();
                if ($bookingDb->fieldExists('created_by_type', 'bookings')) {
                    $bookingData['created_by_type'] = 'CLIENTE';
                    $bookingData['created_by_name'] = 'CLIENTE';
                    $bookingData['created_by_user_id'] = null;
                }

                $bookingsModel->insert($bookingData);
                $bookingId = $bookingsModel->getInsertID();
                $bookingSlotsModel->update($slotId, ['booking_id' => $bookingId]);
                $this->logBookingAction($orderId, 'A', 'Alta de reserva por ' . $totalEntries . ' entradas.', 'CLIENTE');

                $createdBooking = $bookingsModel->find($bookingId);
                if (is_array($createdBooking) && empty($createdBooking['mp_pending_email_sent_at'])) {
                    if ($this->sendPendingReservationEmail($createdBooking, $bookingArr)) {
                        $db = db_connect();
                        if ($db->fieldExists('mp_pending_email_sent_at', 'bookings')) {
                            $bookingsModel->update($bookingId, [
                                'mp_pending_email_sent_at' => date('Y-m-d H:i:s'),
                            ]);
                        }
                    }
                }
            } else {
                $bookingId = $existingPendingBooking['id'];
                $orderId = $this->ensureBookingOrderId($existingPendingBooking);
                if (!empty($bookingArr['email']) && empty($existingPendingBooking['email'])) {
                    $bookingsModel->update($bookingId, ['email' => $bookingArr['email']]);
                }

                $currentBooking = $bookingsModel->find($bookingId);
                if (is_array($currentBooking) && empty($currentBooking['mp_pending_email_sent_at'])) {
                    if ($this->sendPendingReservationEmail($currentBooking, $bookingArr)) {
                        $db = db_connect();
                        if ($db->fieldExists('mp_pending_email_sent_at', 'bookings')) {
                            $bookingsModel->update($bookingId, [
                                'mp_pending_email_sent_at' => date('Y-m-d H:i:s'),
                            ]);
                        }
                    }
                }
            }

            return $this->response->setJSON($this->setResponse(null, null, [
                'preferenceIdTotal' => $preferenceIdTotal,
                'preferenceIdParcial' => $preferenceIdParcial,
                'bookingId' => $bookingId,
                'payByEntries' => $usePayByEntries,
                'entriesToPay' => $usePayByEntries ? $requestedEntries : null,
                'unitPrice' => $unitPrice,
            ], 'Operación completada'));
        } catch (\Throwable $e) {
            if ($slotId) {
                $this->releaseBookingSlot($bookingSlotsModel, (int) $slotId);
            }
            log_message('error', 'Error en setPreference: ' . $e->getMessage());
            $publicMessage = 'No pudimos preparar el pago de la reserva. Intentá nuevamente.';
            if (strpos($e->getMessage(), 'uniq_booking_slots_active') !== false) {
                $publicMessage = 'Ese horario sigue ocupado por un intento anterior. Actualiza e intenta nuevamente.';
            }

            return $this->response->setJSON($this->setResponse(409, true, null, $publicMessage));
        }
    }

    public function success()
    {
        $payload = [
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

        $result = $this->reservationService()->processCheckoutCallback($payload, 'redirect_success');

        if (!isset($result['booking_id'])) {
            return redirect()->to(base_url('pagoRechazado?status=error'));
        }

        if (($result['status'] ?? '') === 'approved' || ($result['status'] ?? '') === 'already_approved') {
            return redirect()->to(base_url('pagoAprobado/' . $result['booking_id']));
        }

        $status = strtolower(trim((string) ($payload['status'] ?? '')));
        if ($status === '') {
            $status = strtolower(trim((string) ($result['status'] ?? 'rejected')));
        }

        return redirect()->to(base_url('pagoRechazado?status=' . rawurlencode($status)));
    }

    public function failure()
    {
        $payload = [
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

        $result = $this->reservationService()->processCheckoutCallback($payload, 'redirect_failure');
        $status = $this->normalizeMpStatus($result['status'] ?? $payload['status'] ?? 'cancelled');

        if (in_array($status, ['approved', 'already_approved'], true) && !empty($result['booking_id'])) {
            return redirect()->to(base_url('pagoAprobado/' . $result['booking_id']));
        }

        if (empty($result['booking_id'])) {
            $fallbackFilters = array_filter([
                'bookingId' => $this->request->getVar('booking_id'),
                'preferenceIdParcial' => $payload['preference_id'] ?? null,
                'preferenceIdTotal' => $payload['preference_id'] ?? null,
            ], static fn ($value) => $value !== null && $value !== '');

            if ($fallbackFilters !== []) {
                $this->reservationService()->cancelPendingReservations($fallbackFilters, 'redirect_failure_fallback');
            }
        }

        $html = view('mercadoPago/failure', $this->buildMpFailureViewData($payload, $result));

        return $this->response
            ->setHeader('Cache-Control', 'no-store, no-cache, must-revalidate, max-age=0')
            ->setHeader('Pragma', 'no-cache')
            ->setHeader('Expires', '0')
            ->setBody($html);
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
        $payload = [
            'status' => $this->request->getGet('status'),
            'payment_id' => $this->request->getGet('payment_id'),
            'preference_id' => $this->request->getGet('preference_id'),
            'external_reference' => $this->request->getGet('external_reference'),
            'booking_id' => $this->request->getGet('booking_id'),
        ];

        $result = $this->reservationService()->processCheckoutCallback($payload, 'redirect_failure_view');
        $status = $this->normalizeMpStatus($result['status'] ?? $payload['status'] ?? 'cancelled');

        if (in_array($status, ['approved', 'already_approved'], true) && !empty($result['booking_id'])) {
            return redirect()->to(base_url('pagoAprobado/' . $result['booking_id']));
        }

        if (empty($result['booking_id'])) {
            $fallbackFilters = array_filter([
                'bookingId' => $this->request->getGet('booking_id'),
                'preferenceIdParcial' => $payload['preference_id'] ?? null,
                'preferenceIdTotal' => $payload['preference_id'] ?? null,
            ], static fn ($value) => $value !== null && $value !== '');

            if ($fallbackFilters !== []) {
                $this->reservationService()->cancelPendingReservations($fallbackFilters, 'redirect_failure_view_fallback');
            }
        }

        $html = view('mercadoPago/failure', $this->buildMpFailureViewData($payload, $result));

        return $this->response
            ->setHeader('Cache-Control', 'no-store, no-cache, must-revalidate, max-age=0')
            ->setHeader('Pragma', 'no-cache')
            ->setHeader('Expires', '0')
            ->setBody($html);
    }

    public function cancelPendingMpReservation()
    {
        $data = (array) ($this->request->getJSON(true) ?? []);
        $bookingId = $data['bookingId'] ?? null;
        $preferenceIdParcial = $data['preferenceIdParcial'] ?? null;
        $preferenceIdTotal = $data['preferenceIdTotal'] ?? null;
        $date = $data['date'] ?? $data['fecha'] ?? null;
        $fieldId = $data['id_field'] ?? $data['cancha'] ?? null;
        $timeFrom = $data['time_from'] ?? $data['horarioDesde'] ?? null;
        $timeUntil = $data['time_until'] ?? $data['horarioHasta'] ?? null;

        try {
            $filters = [
                'bookingId' => $bookingId,
                'preferenceIdParcial' => $preferenceIdParcial,
                'preferenceIdTotal' => $preferenceIdTotal,
                'date' => $date,
                'id_field' => $fieldId,
                'time_from' => $timeFrom,
                'time_until' => $timeUntil,
            ];

            $result = $this->reservationService()->cancelPendingReservations($filters, 'frontend_cancel_pending_mp');

            return $this->response->setJSON($this->setResponse(null, null, $result, 'Operación completada'));
        } catch (\Throwable $e) {
            return $this->response->setJSON($this->setResponse(500, true, null, $e->getMessage()));
        }
    }

    public function webhook()
    {
        $payload = (array) ($this->request->getJSON(true) ?? []);
        $query = $this->request->getGet();
        $result = $this->reservationService()->processWebhook($payload, is_array($query) ? $query : []);

        log_message('info', 'MP webhook result: ' . json_encode($result, JSON_UNESCAPED_UNICODE));

        return $this->response->setStatusCode(200)->setJSON([
            'ok' => true,
            'status' => $result['status'] ?? 'processed',
        ]);
    }

    public function savePreferenceIds()
    {
        return $this->response->setJSON($this->setResponse(null, null, null, 'Operación completada'));
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

