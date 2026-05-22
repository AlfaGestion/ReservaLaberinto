<?php

namespace App\Libraries;

use App\Models\BookingSlotsModel;
use App\Models\BookingsModel;
use App\Models\CustomersModel;
use App\Models\MercadoPagoModel;
use App\Models\PaymentsModel;
use App\Models\RejectedPaymentsModel;
use App\Models\UploadModel;
use App\Models\UsersModel;
use Config\Services;

class MercadoPagoReservationService
{
    private const DEFAULT_EXPIRY_MINUTES = 30;

    private BookingsModel $bookingsModel;
    private BookingSlotsModel $bookingSlotsModel;
    private MercadoPagoModel $mercadoPagoModel;
    private PaymentsModel $paymentsModel;
    private RejectedPaymentsModel $rejectedPaymentsModel;
    private CustomersModel $customersModel;
    private UsersModel $usersModel;
    private MercadoPagoLibrary $mercadoPagoLibrary;

    public function __construct()
    {
        $this->bookingsModel = new BookingsModel();
        $this->bookingSlotsModel = new BookingSlotsModel();
        $this->mercadoPagoModel = new MercadoPagoModel();
        $this->paymentsModel = new PaymentsModel();
        $this->rejectedPaymentsModel = new RejectedPaymentsModel();
        $this->customersModel = new CustomersModel();
        $this->usersModel = new UsersModel();
        $this->mercadoPagoLibrary = new MercadoPagoLibrary();
    }

    public function getExpiryMinutes(): int
    {
        $raw = getenv('MP_RESERVATION_EXPIRY_MINUTES');
        $value = is_numeric($raw) ? (int) $raw : self::DEFAULT_EXPIRY_MINUTES;
        return $value > 0 ? $value : self::DEFAULT_EXPIRY_MINUTES;
    }

    public function getExpiresAtFromNow(): string
    {
        return date('Y-m-d H:i:s', strtotime('+' . $this->getExpiryMinutes() . ' minutes'));
    }

    public function expirePendingReservations(array $filters = [], string $source = 'system'): array
    {
        $reviewed = 0;
        $expired = 0;
        $confirmed = 0;
        $released = 0;
        $ignored = 0;
        $failed = 0;

        $expiresBefore = date('Y-m-d H:i:s', strtotime('-' . $this->getExpiryMinutes() . ' minutes'));

        $query = $this->bookingsModel
            ->where('annulled', 0)
            ->where('approved', 0)
            ->where('mp', 0)
            ->whereIn('payment_method', ['Mercado Pago', 'mercado_pago'])
            ->where('booking_time <', $expiresBefore);

        foreach ($filters as $field => $value) {
            if ($value === null || $value === '') {
                continue;
            }
            $query->where($field, $value);
        }

        $bookings = $query->findAll();
        foreach ($bookings as $booking) {
            $reviewed++;
            try {
                $reconcile = $this->reconcileBookingWithMercadoPago($booking, [], $source . ':expire');
                if (($reconcile['status'] ?? '') === 'approved') {
                    $confirmed++;
                    $released += (int) ($reconcile['released_slots'] ?? 0);
                    continue;
                }

                if (in_array(($reconcile['status'] ?? ''), ['api_unreachable', 'reconciliation_failed'], true)) {
                    $ignored++;
                    continue;
                }

                if ((int) ($booking['approved'] ?? 0) === 1 || (int) ($booking['annulled'] ?? 0) === 1) {
                    $ignored++;
                    continue;
                }

                $released += $this->markBookingAsExpired($booking, $source);
                $expired++;
            } catch (\Throwable $e) {
                $failed++;
                log_message('error', 'Error expiring pending MP booking ' . ($booking['id'] ?? '?') . ': ' . $e->getMessage());
            }
        }

        return [
            'reviewed' => $reviewed,
            'confirmed' => $confirmed,
            'expired' => $expired,
            'released' => $released,
            'ignored' => $ignored,
            'failed' => $failed,
        ];
    }

    public function processCheckoutCallback(array $payload, string $source = 'redirect'): array
    {
        $preferenceId = trim((string) ($payload['preference_id'] ?? ''));
        if ($preferenceId === '') {
            return ['status' => 'ignored', 'reason' => 'missing_preference_id'];
        }

        $booking = $this->bookingsModel->where('id_preference_parcial', $preferenceId)
            ->orWhere('id_preference_total', $preferenceId)
            ->first();

        if (!$booking) {
            $this->storeMercadoPagoLog(null, $payload);
            return ['status' => 'not_found'];
        }

        if ((int) ($booking['approved'] ?? 0) === 1 && (int) ($booking['annulled'] ?? 0) === 0) {
            $this->storeMercadoPagoLog((int) $booking['id'], $payload);
            return ['status' => 'already_approved', 'booking_id' => (int) $booking['id']];
        }

        return $this->reconcileBookingWithMercadoPago($booking, $payload, $source);
    }

    public function processWebhook(array $payload, array $query = []): array
    {
        $paymentId = $this->extractPaymentIdFromWebhook($payload, $query);
        $preferenceId = trim((string) ($query['preference_id'] ?? $payload['preference_id'] ?? ''));

        if ($paymentId === '' && $preferenceId === '') {
            log_message('warning', 'MP webhook ignored: missing payment/preference identifiers');
            return ['status' => 'ignored'];
        }

        $booking = null;
        if ($preferenceId !== '') {
            $booking = $this->bookingsModel->where('id_preference_parcial', $preferenceId)
                ->orWhere('id_preference_total', $preferenceId)
                ->first();
        }

        if (!$booking && $paymentId !== '') {
            $mpLog = $this->mercadoPagoModel->where('payment_id', $paymentId)->orderBy('id', 'DESC')->first();
            if ($mpLog && !empty($mpLog['id_booking'])) {
                $booking = $this->bookingsModel->find((int) $mpLog['id_booking']);
            }
        }

        if (!$booking) {
            log_message('warning', 'MP webhook without matching booking. payment_id=' . $paymentId . ', preference_id=' . $preferenceId);
            return ['status' => 'not_found'];
        }

        $hints = [
            'payment_id' => $paymentId,
            'preference_id' => $preferenceId,
            'external_reference' => $query['external_reference'] ?? $payload['external_reference'] ?? null,
            'merchant_order_id' => $query['merchant_order_id'] ?? $payload['merchant_order_id'] ?? null,
            'status' => $query['status'] ?? $payload['status'] ?? null,
            'collection_status' => $query['collection_status'] ?? $payload['collection_status'] ?? null,
        ];

        return $this->reconcileBookingWithMercadoPago($booking, $hints, 'webhook');
    }

    public function markBookingAsAbandoned(array $booking, string $source = 'client_cancel'): void
    {
        if ((int) ($booking['approved'] ?? 0) === 1) {
            return;
        }

        $this->bookingsModel->update((int) $booking['id'], [
            'approved' => 0,
            'annulled' => 1,
        ]);
        $this->releaseBookingSlots((int) $booking['id']);
        $this->upsertRejectedPaymentRecord($booking, [
            'status' => 'abandoned',
            'status_detail' => 'Checkout abandonado por el cliente',
            'preference_id' => $booking['id_preference_parcial'] ?? null,
        ], 'closed', false);
        log_message('info', 'MP booking ' . $booking['id'] . ' cancelled by abandonment [' . $source . ']');
    }

    private function reconcileBookingWithMercadoPago(array $booking, array $hints = [], string $source = 'reconcile'): array
    {
        $bookingId = (int) ($booking['id'] ?? 0);
        if ($bookingId <= 0) {
            return ['status' => 'invalid_booking'];
        }

        if ((int) ($booking['approved'] ?? 0) === 1 && (int) ($booking['annulled'] ?? 0) === 0) {
            return ['status' => 'already_approved', 'booking_id' => $bookingId];
        }

        $paymentId = trim((string) ($hints['payment_id'] ?? ''));
        $preferenceId = trim((string) ($hints['preference_id'] ?? ($booking['id_preference_parcial'] ?? $booking['id_preference_total'] ?? '')));
        $externalReference = trim((string) ($hints['external_reference'] ?? ''));
        $hasPaymentIdentifiers = $paymentId !== '' || $preferenceId !== '' || $externalReference !== '';
        $status = strtolower(trim((string) ($hints['status'] ?? '')));
        $statusDetail = trim((string) ($hints['collection_status'] ?? ''));
        $apiReachable = true;

        $paymentInfo = null;
        if ($paymentId !== '') {
            $paymentResult = $this->mercadoPagoLibrary->getPaymentByIdWithMeta($paymentId);
            $apiReachable = (bool) ($paymentResult['api_reachable'] ?? false);
            $paymentInfo = $paymentResult['data'] ?? null;

            if ($paymentInfo) {
                $status = strtolower(trim((string) ($paymentInfo['status'] ?? $status)));
                $statusDetail = trim((string) ($paymentInfo['status_detail'] ?? $statusDetail));
            }

            if (!$apiReachable) {
                log_message('error', 'MP reconciliation api_unreachable booking_id=' . $bookingId . ' payment_id=' . $paymentId . ' source=' . $source . ' error=' . (string) ($paymentResult['error'] ?? 'unknown'));
                return ['status' => 'api_unreachable', 'booking_id' => $bookingId];
            }
        } elseif ($hasPaymentIdentifiers) {
            log_message('error', 'MP reconciliation_failed booking_id=' . $bookingId . ' source=' . $source . ' reason=missing_payment_id_with_identifiers');
            return ['status' => 'reconciliation_failed', 'booking_id' => $bookingId];
        }

        if ($status === 'approved') {
            $result = $this->approveBookingFromPayment($booking, $paymentId, $hints, $paymentInfo, $source);
            return $result + ['status' => 'approved'];
        }

        $this->storeMercadoPagoLog($bookingId, [
            'payment_id' => $paymentId,
            'status' => $status,
            'collection_status' => $statusDetail,
            'external_reference' => $hints['external_reference'] ?? null,
            'payment_type' => $hints['payment_type'] ?? null,
            'merchant_order_id' => $hints['merchant_order_id'] ?? null,
            'preference_id' => $hints['preference_id'] ?? ($booking['id_preference_parcial'] ?? null),
            'site_id' => $hints['site_id'] ?? null,
            'processing_mode' => $hints['processing_mode'] ?? null,
            'merchant_account_id' => $hints['merchant_account_id'] ?? null,
        ]);

        $rejectedStatus = in_array($status, ['pending', 'in_process', '', 'cancelled'], true) ? 'pending_retry' : 'rejected';
        $this->upsertRejectedPaymentRecord($booking, [
            'payment_id' => $paymentId,
            'status' => $status,
            'status_detail' => $statusDetail,
            'preference_id' => $hints['preference_id'] ?? ($booking['id_preference_parcial'] ?? null),
            'external_reference' => $hints['external_reference'] ?? null,
        ], $rejectedStatus, true);

        return ['status' => $status !== '' ? $status : 'not_approved', 'booking_id' => $bookingId];
    }

    private function approveBookingFromPayment(array $booking, string $paymentId, array $hints, ?array $paymentInfo, string $source): array
    {
        $bookingId = (int) $booking['id'];
        $existingPayment = null;
        if ($paymentId !== '') {
            $existingPayment = $this->paymentsModel
                ->where('id_booking', $bookingId)
                ->where('id_mercado_pago', $paymentId)
                ->first();
        }

        if ($existingPayment) {
            $this->bookingsModel->update($bookingId, ['approved' => 1, 'annulled' => 0, 'mp' => 1]);
            $releasedSlots = $this->releaseBookingSlots($bookingId);
            return ['booking_id' => $bookingId, 'idempotent' => true, 'released_slots' => $releasedSlots];
        }

        $customerId = $this->ensureCustomerForBooking($booking);
        $isTotalPreference = (($hints['preference_id'] ?? '') !== '') && ($hints['preference_id'] ?? '') === ($booking['id_preference_total'] ?? '');
        $isEntryPayment = (int) ($booking['partial_by_entries'] ?? 0) === 1;
        $totalEntries = max(0, (int) ($booking['visitors'] ?? 0));
        $currentPaidEntries = max(0, (int) ($booking['paid_entries'] ?? 0));
        $unitPriceAtBooking = $totalEntries > 0 ? (float) $booking['total'] / $totalEntries : 0.0;
        $paid = $isTotalPreference ? (float) $booking['total'] : (float) $booking['parcial'];

        if ($paymentInfo && isset($paymentInfo['transaction_amount']) && (float) $paymentInfo['transaction_amount'] > 0) {
            $paid = (float) $paymentInfo['transaction_amount'];
        }

        $paymentType = $isTotalPreference ? 'total' : 'partial_amount';
        $paidEntries = null;
        $newPaidEntries = $currentPaidEntries;
        $newPayment = $isTotalPreference ? (float) $booking['total'] : (float) ($booking['payment'] ?? 0) + $paid;
        $newDifference = max(0, (float) $booking['total'] - $newPayment);
        $totalPaymentCompleted = $newDifference <= 0.01;

        if ($isEntryPayment && $totalEntries > 0) {
            if ($isTotalPreference) {
                $paidEntries = max(0, $totalEntries - $currentPaidEntries);
                $newPaidEntries = $totalEntries;
                $newPayment = (float) $booking['total'];
                $newDifference = 0.0;
                $totalPaymentCompleted = true;
                $paymentType = 'total';
            } else {
                $pendingEntriesBeforePayment = max(0, $totalEntries - $currentPaidEntries);
                $paidEntries = $unitPriceAtBooking > 0 ? (int) round($paid / $unitPriceAtBooking) : 0;
                $paidEntries = min(max(1, $paidEntries), $pendingEntriesBeforePayment);
                $newPaidEntries = min($totalEntries, $currentPaidEntries + $paidEntries);
                $pendingEntries = max(0, $totalEntries - $newPaidEntries);
                $newPayment = (float) ($booking['payment'] ?? 0) + $paid;
                $newDifference = $pendingEntries * $unitPriceAtBooking;
                $totalPaymentCompleted = $pendingEntries === 0;
                $paymentType = 'partial_entries';
            }
        } elseif ($isTotalPreference) {
            $newPaidEntries = $totalEntries;
        }

        $this->bookingsModel->update($bookingId, [
            'mp' => 1,
            'approved' => 1,
            'annulled' => 0,
            'payment' => $newPayment,
            'reservation' => $newPayment,
            'diference' => $newDifference,
            'total_payment' => $totalPaymentCompleted ? 1 : 0,
            'id_customer' => $customerId,
            'paid_entries' => $newPaidEntries,
        ]);

        if ($paymentId !== '') {
            $paymentUserId = $this->resolvePaymentUserId();
            $this->paymentsModel->insert([
                'id_user' => $paymentUserId > 0 ? $paymentUserId : 1,
                'id_booking' => $bookingId,
                'id_customer' => $customerId,
                'id_mercado_pago' => $paymentId,
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
        }

        $releasedSlots = $this->releaseBookingSlots($bookingId);

        $this->storeMercadoPagoLog($bookingId, [
            'payment_id' => $paymentId,
            'status' => 'approved',
            'collection_status' => $paymentInfo['status_detail'] ?? ($hints['collection_status'] ?? ''),
            'external_reference' => $paymentInfo['external_reference'] ?? ($hints['external_reference'] ?? null),
            'payment_type' => $paymentInfo['payment_type_id'] ?? ($hints['payment_type'] ?? null),
            'merchant_order_id' => $paymentInfo['order_id'] ?? ($hints['merchant_order_id'] ?? null),
            'preference_id' => $hints['preference_id'] ?? ($booking['id_preference_parcial'] ?? null),
            'site_id' => $hints['site_id'] ?? null,
            'processing_mode' => $hints['processing_mode'] ?? null,
            'merchant_account_id' => $hints['merchant_account_id'] ?? null,
        ]);

        $this->markRejectedAsApproved($bookingId, $paymentId);
        $this->sendBookingConfirmedEmails($this->bookingsModel->find($bookingId));
        log_message('info', 'Booking ' . $bookingId . ' approved from MP [' . $source . '] payment_id=' . $paymentId);

        return ['booking_id' => $bookingId, 'idempotent' => false, 'released_slots' => $releasedSlots];
    }

    private function markBookingAsExpired(array $booking, string $source): int
    {
        $bookingId = (int) $booking['id'];
        if ((int) ($booking['approved'] ?? 0) === 1 || (int) ($booking['annulled'] ?? 0) === 1) {
            return 0;
        }

        $this->bookingsModel->update($bookingId, [
            'approved' => 0,
            'annulled' => 1,
        ]);
        $releasedSlots = $this->releaseBookingSlots($bookingId);
        $this->upsertRejectedPaymentRecord($booking, [
            'status' => 'expired',
            'status_detail' => 'Reserva pendiente vencida por tiempo de pago',
            'preference_id' => $booking['id_preference_parcial'] ?? null,
        ], 'expired', true);
        log_message('info', 'Booking ' . $bookingId . ' expired by policy [' . $source . ']');
        return $releasedSlots;
    }

    private function releaseBookingSlots(int $bookingId): int
    {
        $slots = $this->bookingSlotsModel->where('booking_id', $bookingId)->where('active', 1)->findAll();
        if ($slots === []) {
            return 0;
        }

        $this->bookingSlotsModel->where('booking_id', $bookingId)->where('active', 1)->delete();
        return count($slots);
    }

    private function storeMercadoPagoLog(?int $bookingId, array $payload): void
    {
        $this->mercadoPagoModel->insert([
            'collection_id' => $payload['collection_id'] ?? null,
            'collection_status' => $payload['collection_status'] ?? null,
            'payment_id' => $payload['payment_id'] ?? null,
            'status' => $payload['status'] ?? null,
            'external_reference' => $payload['external_reference'] ?? null,
            'payment_type' => $payload['payment_type'] ?? null,
            'merchant_order_id' => $payload['merchant_order_id'] ?? null,
            'preference_id' => $payload['preference_id'] ?? null,
            'site_id' => $payload['site_id'] ?? null,
            'processing_mode' => $payload['processing_mode'] ?? null,
            'merchant_account_id' => $payload['merchant_account_id'] ?? null,
            'id_booking' => $bookingId,
            'annulled' => 0,
        ]);
    }

    private function upsertRejectedPaymentRecord(array $booking, array $paymentData, string $status, bool $notifyCustomer): ?array
    {
        $bookingId = (int) ($booking['id'] ?? 0);
        if ($bookingId <= 0) {
            return null;
        }

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
            'expires_at' => $this->getExpiresAtFromNow(),
            'updated_at' => date('Y-m-d H:i:s'),
        ];

        $existing = $this->rejectedPaymentsModel->where('booking_id', $bookingId)
            ->whereIn('payment_status', ['pending_retry', 'rejected', 'expired'])
            ->orderBy('id', 'DESC')
            ->first();

        if ($existing) {
            $alreadyNotified = !empty($existing['notified_at']);
            $this->rejectedPaymentsModel->update((int) $existing['id'], $recordData);
            $recordId = (int) $existing['id'];
            $saved = $this->rejectedPaymentsModel->find($recordId);
            if ($notifyCustomer && !$alreadyNotified && $saved) {
                $this->sendRetryPaymentEmail($booking, $saved);
                $this->rejectedPaymentsModel->update($recordId, ['notified_at' => date('Y-m-d H:i:s')]);
            }
            return $this->rejectedPaymentsModel->find($recordId) ?: null;
        }

        $recordData['created_at'] = date('Y-m-d H:i:s');
        $recordId = (int) $this->rejectedPaymentsModel->insert($recordData, true);
        $saved = $this->rejectedPaymentsModel->find($recordId);
        if ($notifyCustomer && $saved) {
            $this->sendRetryPaymentEmail($booking, $saved);
            $this->rejectedPaymentsModel->update($recordId, ['notified_at' => date('Y-m-d H:i:s')]);
        }
        return $this->rejectedPaymentsModel->find($recordId) ?: null;
    }

    private function resolveBookingEmailFromData(array $booking): string
    {
        $email = trim((string) ($booking['email'] ?? ''));
        if ($email !== '' && filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return $email;
        }
        return $this->resolveCustomerEmail($booking);
    }

    private function resolveCustomerEmail(array $booking): string
    {
        if (!empty($booking['id_customer'])) {
            $customer = $this->customersModel->find((int) $booking['id_customer']);
            $email = trim((string) ($customer['email'] ?? ''));
            if ($email !== '' && filter_var($email, FILTER_VALIDATE_EMAIL)) {
                return $email;
            }
        }

        $phone = trim((string) ($booking['phone'] ?? ''));
        if ($phone === '') {
            return '';
        }

        $customer = $this->customersModel->groupStart()
            ->where('phone', $phone)
            ->orWhere('complete_phone', $phone)
            ->groupEnd()
            ->first();

        $email = trim((string) ($customer['email'] ?? ''));
        if ($email !== '' && filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return $email;
        }

        return '';
    }

    private function ensureCustomerForBooking(array $booking): ?int
    {
        if (!empty($booking['id_customer'])) {
            return (int) $booking['id_customer'];
        }

        $phone = trim((string) ($booking['phone'] ?? ''));
        if ($phone === '') {
            return null;
        }

        $customer = $this->customersModel->groupStart()
            ->where('phone', $phone)
            ->orWhere('complete_phone', $phone)
            ->groupEnd()
            ->first();

        if (!$customer) {
            $this->customersModel->insert([
                'name' => $booking['name'] ?? 'Cliente',
                'phone' => $phone,
                'complete_phone' => $phone,
                'offer' => 0,
                'quantity' => 1,
            ]);
            return (int) $this->customersModel->getInsertID();
        }

        $customerId = (int) $customer['id'];
        $this->customersModel->update($customerId, [
            'name' => $booking['name'] ?? ($customer['name'] ?? 'Cliente'),
            'quantity' => ((int) ($customer['quantity'] ?? 0)) + 1,
        ]);
        return $customerId;
    }

    private function resolvePaymentUserId(): int
    {
        $sessionUserId = (int) (session()->get('id_user') ?? 0);
        if ($sessionUserId > 0) {
            return $sessionUserId;
        }
        $fallbackUser = $this->usersModel->select('id')->orderBy('id', 'ASC')->first();
        return (int) ($fallbackUser['id'] ?? 0);
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

        $html = $this->renderEmailCard([
            'eyebrow' => 'Pago pendiente',
            'title' => 'Tu reserva no fue confirmada',
            'intro' => 'No pudimos acreditar el pago dentro del tiempo permitido.',
            'details' => [
                'Nombre' => (string) ($booking['name'] ?? ''),
                'Fecha' => $this->formatBookingDate((string) ($booking['date'] ?? '')),
                'Horario' => trim(((string) ($booking['time_from'] ?? '')) . ' a ' . ((string) ($booking['time_until'] ?? ''))),
                'Total' => '$' . (string) ($booking['total'] ?? '0'),
            ],
            'messageHtml' => '<p>La reserva quedo sin confirmar por falta de aprobacion del pago. Si queres, podes iniciar nuevamente el pago desde el enlace.</p>',
            'primaryActionUrl' => $retryUrl,
            'primaryActionLabel' => 'Intentar pago de nuevo',
        ]);

        $subject = 'Tu reserva no fue confirmada';
        $sent = $this->sendEmailWithFallback($to, $subject, $html, true);
        if ($sent) {
            log_message('info', 'email_non_confirmed_sent booking_id=' . (int) ($booking['id'] ?? 0) . ' to=' . $to . ' subject="' . $subject . '"');
            return;
        }
        log_message('error', 'email_send_failed booking_id=' . (int) ($booking['id'] ?? 0) . ' to=' . $to . ' subject="' . $subject . '" context=non_confirmed');
    }

    private function sendBookingConfirmedEmails(array $booking): void
    {
        $uploadModel = new UploadModel();
        $config = $uploadModel->first();
        $notificationEmail = trim((string) ($config['notification_email'] ?? ''));
        $notificationEmailList = array_values(array_filter(array_map('trim', explode(';', $notificationEmail))));

        $formattedDate = $this->formatBookingDate((string) ($booking['date'] ?? ''));
        $subjectName = trim((string) ($booking['name'] ?? ''));
        $subjectTimeFrom = trim((string) ($booking['time_from'] ?? ''));
        $subject = "Reserva - Laberinto: {$subjectName} - {$formattedDate} {$subjectTimeFrom}";

        if ($notificationEmailList !== []) {
            $bookingUrl = site_url('MisReservas/' . rawurlencode($this->buildReservationAccessToken([
                'code' => (string) ($booking['code'] ?? ''),
            ])));
            $html = $this->renderEmailCard([
                'eyebrow' => 'Nueva reserva',
                'title' => $subjectName !== '' ? $subjectName . ' reservo una visita' : 'Se recibio una nueva reserva',
                'intro' => 'La reserva fue confirmada por Mercado Pago.',
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
            $sentInternal = $this->sendEmailWithFallback($notificationEmailList, $subject, $html, true);
            if ($sentInternal) {
                log_message('info', 'email_confirmation_sent booking_id=' . (int) ($booking['id'] ?? 0) . ' to=' . implode(';', $notificationEmailList) . ' subject="' . $subject . '" target=internal');
            } else {
                log_message('error', 'email_send_failed booking_id=' . (int) ($booking['id'] ?? 0) . ' to=' . implode(';', $notificationEmailList) . ' subject="' . $subject . '" context=confirmation_internal');
            }
        }

        $customerEmail = $this->resolveCustomerEmail($booking);
        if ($customerEmail === '') {
            return;
        }

        $customerName = trim((string) ($booking['name'] ?? 'Cliente'));
        $customerSubject = "Reserva confirmada - Laberinto: {$customerName}";
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
        ]);
        $sentCustomer = $this->sendEmailWithFallback($customerEmail, $customerSubject, $html, true);
        if ($sentCustomer) {
            log_message('info', 'email_confirmation_sent booking_id=' . (int) ($booking['id'] ?? 0) . ' to=' . $customerEmail . ' subject="' . $customerSubject . '" target=customer');
            return;
        }
        log_message('error', 'email_send_failed booking_id=' . (int) ($booking['id'] ?? 0) . ' to=' . $customerEmail . ' subject="' . $customerSubject . '" context=confirmation_customer');
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
                $email = Services::email();
                $email->SMTPTimeout = 8;
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
                log_message('error', 'Fallo envio SMTP central MP: ' . $e->getMessage());
            }
        }

        return false;
    }

    private function renderEmailCard(array $payload): string
    {
        if (function_exists('view')) {
            return view('emails/card_email', $payload);
        }
        return (string) ($payload['messageHtml'] ?? '');
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

    private function buildReservationAccessToken(array $payload): string
    {
        $code = trim((string) ($payload['code'] ?? ''));
        if ($code === '') {
            return '';
        }

        $tokenPayload = [
            'code' => $code,
            'phone' => trim((string) ($payload['phone'] ?? '')),
            'email' => trim((string) ($payload['email'] ?? '')),
            'ts' => time(),
        ];

        $json = json_encode($tokenPayload, JSON_UNESCAPED_UNICODE);
        return rtrim(strtr(base64_encode($json), '+/', '-_'), '=');
    }

    private function markRejectedAsApproved(int $bookingId, string $paymentId): void
    {
        $query = $this->rejectedPaymentsModel->where('booking_id', $bookingId);
        if ($paymentId !== '') {
            $query->groupStart()
                ->where('payment_id', $paymentId)
                ->orWhere('payment_id', null)
                ->groupEnd();
        }

        $item = $query->orderBy('id', 'DESC')->first();
        if (!$item) {
            return;
        }

        $this->rejectedPaymentsModel->update((int) $item['id'], [
            'payment_status' => 'approved',
            'closed_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
        ]);
    }

    private function extractPaymentIdFromWebhook(array $payload, array $query): string
    {
        $candidate = $query['data_id'] ?? $query['id'] ?? null;
        if ($candidate === null && isset($payload['data']) && is_array($payload['data'])) {
            $candidate = $payload['data']['id'] ?? null;
        }
        if ($candidate === null) {
            $candidate = $payload['id'] ?? null;
        }
        return trim((string) $candidate);
    }
}
