<?php

namespace App\Controllers;

use App\Models\BookingsActionModel;
use App\Models\BookingsModel;
use App\Models\CustomersModel;
use App\Models\UploadModel;
use App\Models\ValuesModel;
use CodeIgniter\Controller;
use CodeIgniter\HTTP\CLIRequest;
use CodeIgniter\HTTP\IncomingRequest;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;
use Psr\Log\LoggerInterface;

/**
 * Class BaseController
 *
 * BaseController provides a convenient place for loading components
 * and performing functions that are needed by all your controllers.
 * Extend this class in any new controllers:
 *     class Home extends BaseController
 *
 * For security be sure to declare any new methods as protected or private.
 */
abstract class BaseController extends Controller
{
    protected $session;
    protected string $reservationAccessSecret = 'reserva_laberinto_booking_access_v1';

    /**
     * Instance of the main Request object.
     *
     * @var CLIRequest|IncomingRequest
     */
    protected $request;

    /**
     * An array of helpers to be loaded automatically upon
     * class instantiation. These helpers will be available
     * to all other controllers that extend BaseController.
     *
     * @var array
     */
    protected $helpers = [];

    /**
     * Be sure to declare properties for any property fetch you initialized.
     * The creation of dynamic property is deprecated in PHP 8.2.
     */
    // protected $session;

    /**
     * @return void
     */
    public function initController(RequestInterface $request, ResponseInterface $response, LoggerInterface $logger)
    {
        // Do Not Edit This Line
        parent::initController($request, $response, $logger);

        // Preload any models, libraries, etc, here.
        $this->session = \Config\Services::session();
    }

    protected function buildReservationAccessToken(array $data): string
    {
        $code = trim((string) ($data['code'] ?? ''));
        $payload = array_filter($data, static fn($value) => $value !== null && $value !== '');

        if (count($payload) === 1 && isset($payload['code'])) {
            return $code;
        }

        if ($payload === []) {
            return '';
        }

        $encodedPayload = rtrim(strtr(base64_encode(json_encode($payload)), '+/', '-_'), '=');
        $signature = hash_hmac('sha256', $encodedPayload, $this->reservationAccessSecret);

        return $encodedPayload . '.' . $signature;
    }

    protected function parseReservationAccessToken(?string $token): array
    {
        $token = trim((string) $token);
        if ($token === '') {
            return [];
        }

        if (strpos($token, '.') !== false) {
            [$encodedPayload, $signature] = explode('.', $token, 2);
            $expectedSignature = hash_hmac('sha256', $encodedPayload, $this->reservationAccessSecret);

            if (!hash_equals($expectedSignature, $signature)) {
                return [];
            }

            $decoded = base64_decode(strtr($encodedPayload, '-_', '+/'));
            if ($decoded === false) {
                return [];
            }

            $payload = json_decode($decoded, true);
            return is_array($payload) ? $payload : [];
        }

        if (preg_match('/^[A-Z0-9]{6,20}$/', $token) === 1) {
            return ['code' => $token];
        }

        $lastDashPosition = strrpos($token, '-');
        if ($lastDashPosition === false) {
            return [];
        }

        $code = substr($token, 0, $lastDashPosition);
        $signature = substr($token, $lastDashPosition + 1);
        $expectedSignature = substr(hash_hmac('sha256', $code, $this->reservationAccessSecret), 0, 16);

        if ($code === '' || !hash_equals($expectedSignature, $signature)) {
            return [];
        }

        return ['code' => $code];
    }

    protected function getEmailBranding(): array
    {
        $uploadModel = new UploadModel();
        $config = $uploadModel->first() ?? [];
        $logoFile = trim((string) ($config['name'] ?? ''));
        $candidatePaths = [];

        if ($logoFile !== '') {
            $candidatePaths[] = FCPATH . 'assets/images/uploads/' . $logoFile;
        }

        $candidatePaths[] = FCPATH . 'assets/images/logo_pdf.png';
        $candidatePaths[] = FCPATH . 'assets/images/sinlogo2.png';

        $logoDataUri = '';
        foreach ($candidatePaths as $candidatePath) {
            if (!is_file($candidatePath)) {
                continue;
            }

            $extension = strtolower(pathinfo($candidatePath, PATHINFO_EXTENSION));
            if (!extension_loaded('gd') && !in_array($extension, ['jpg', 'jpeg'], true)) {
                continue;
            }

            switch ($extension) {
                case 'jpg':
                case 'jpeg':
                    $mimeType = 'image/jpeg';
                    break;
                case 'gif':
                    $mimeType = 'image/gif';
                    break;
                case 'webp':
                    $mimeType = 'image/webp';
                    break;
                default:
                    $mimeType = 'image/png';
                    break;
            }

            $binary = @file_get_contents($candidatePath);
            if ($binary === false) {
                continue;
            }

            $logoDataUri = 'data:' . $mimeType . ';base64,' . base64_encode($binary);
            break;
        }

        return [
            'brandName' => 'Laberinto Patagonia',
            'accentColor' => $config['main_color'] ?? '#0d6a3a',
            'secondaryColor' => $config['secondary_color'] ?? '#f39323',
            'logoUrl' => $logoDataUri,
        ];
    }

    protected function renderEmailCard(array $data): string
    {
        return view('emails/card_email', array_merge($this->getEmailBranding(), $data));
    }

    protected function getPayByEntriesConfig(): array
    {
        $uploadModel = new UploadModel();
        $config = $uploadModel->first() ?? [];

        return [
            'enabled' => !empty($config['enable_pay_by_entries']),
            'min_entries' => max(0, (int) ($config['pay_by_entries_min_entries'] ?? 0)),
            'min_days_before_booking' => max(0, (int) ($config['pay_by_entries_min_days_before_booking'] ?? 0)),
            'default_percentage' => ($config['pay_by_entries_default_percentage'] ?? null) !== null
                ? min(100, max(1, (int) $config['pay_by_entries_default_percentage']))
                : 50,
        ];
    }

    protected function canPayByEntries(int $entries, ?string $bookingDate): bool
    {
        $config = $this->getPayByEntriesConfig();
        if (! $config['enabled'] || $entries <= 0 || $entries < $config['min_entries']) {
            return false;
        }

        $bookingDate = trim((string) $bookingDate);
        if ($bookingDate === '') {
            return false;
        }

        try {
            $today = new \DateTimeImmutable(date('Y-m-d'));
            $bookingDay = new \DateTimeImmutable($bookingDate);
        } catch (\Throwable $e) {
            return false;
        }

        $daysBeforeBooking = (int) $today->diff($bookingDay)->format('%r%a');

        return $daysBeforeBooking >= $config['min_days_before_booking'];
    }

    protected function generateBookingOrderId(): string
    {
        $bookingsModel = new BookingsModel();

        for ($attempt = 0; $attempt < 5; $attempt++) {
            $candidate = 'RES-' . date('YmdHis') . '-' . substr(str_shuffle('0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZ'), 0, 6);
            if (! $bookingsModel->where('IdPedido', $candidate)->first()) {
                return $candidate;
            }
        }

        return 'RES-' . date('YmdHis') . '-' . uniqid();
    }

    protected function ensureBookingOrderId(array $booking): string
    {
        $orderId = trim((string) ($booking['IdPedido'] ?? ''));
        if ($orderId !== '') {
            return $orderId;
        }

        $bookingId = (int) ($booking['id'] ?? 0);
        $orderId = $bookingId > 0
            ? 'RES-' . str_pad((string) $bookingId, 8, '0', STR_PAD_LEFT)
            : $this->generateBookingOrderId();

        if ($bookingId > 0) {
            try {
                (new BookingsModel())->update($bookingId, ['IdPedido' => $orderId]);
            } catch (\Throwable $e) {
                log_message('error', 'No se pudo completar IdPedido de reserva: ' . $e->getMessage());
            }
        }

        return $orderId;
    }

    protected function getAuditUserLabel(?string $fallback = null): string
    {
        $session = session();
        $name = trim((string) ($session->get('name') ?? ''));
        $user = trim((string) ($session->get('user') ?? ''));
        $id = trim((string) ($session->get('id_user') ?? ''));

        if ($name !== '') {
            return $name;
        }

        if ($user !== '') {
            return $user;
        }

        if ($id !== '') {
            return 'Usuario #' . $id;
        }

        return $fallback ?? 'CLIENTE';
    }

    protected function logBookingAction(?string $orderId, string $action, string $observation, ?string $user = null): void
    {
        $orderId = trim((string) $orderId);
        $action = strtoupper(substr(trim($action), 0, 1));

        if ($orderId === '' || $action === '') {
            return;
        }

        try {
            (new BookingsActionModel())->insert([
                'IdPedido' => $orderId,
                'fechaHora' => date('Y-m-d H:i:s'),
                'Accion' => $action,
                'observacion' => $observation,
                'usuario' => $user ?? $this->getAuditUserLabel(),
            ]);
        } catch (\Throwable $e) {
            log_message('error', 'No se pudo registrar historial de reserva: ' . $e->getMessage());
        }
    }

    protected function formatAuditMoney(float $amount): string
    {
        return '$' . number_format($amount, 2, ',', '.');
    }

    protected function resolveCurrentUnitPriceForBooking(array $booking): float
    {
        $customer = null;
        $customerId = (int) ($booking['id_customer'] ?? 0);

        if ($customerId > 0) {
            $customer = (new CustomersModel())->find($customerId);
        }

        if (! $customer && !empty($booking['phone'])) {
            $customer = (new CustomersModel())->groupStart()
                ->where('phone', $booking['phone'])
                ->orWhere('complete_phone', $booking['phone'])
                ->groupEnd()
                ->first();
        }

        $institutionType = trim((string) ($customer['type_institution'] ?? ''));
        if ($institutionType !== '') {
            $value = (new ValuesModel())->where('value', $institutionType)->where('disabled', 0)->first();
            $amount = (float) ($value['amount'] ?? 0);
            $discount = (float) ($value['discount_percentage'] ?? 0);

            if ($amount > 0) {
                return max(0, $amount - (($amount * $discount) / 100));
            }
        }

        $visitors = (int) ($booking['visitors'] ?? 0);
        $total = (float) ($booking['total'] ?? 0);

        return $visitors > 0 && $total > 0 ? $total / $visitors : 0.0;
    }

    protected function getBookingEntryPaymentSummary(array $booking): array
    {
        $totalEntries = max(0, (int) ($booking['visitors'] ?? 0));
        $paidEntries = max(0, (int) ($booking['paid_entries'] ?? 0));
        $pendingEntries = max(0, $totalEntries - $paidEntries);
        $unitPrice = $this->resolveCurrentUnitPriceForBooking($booking);

        return [
            'total_entries' => $totalEntries,
            'paid_entries' => $paidEntries,
            'pending_entries' => $pendingEntries,
            'unit_price' => $unitPrice,
            'pending_amount' => $pendingEntries * $unitPrice,
        ];
    }
}
