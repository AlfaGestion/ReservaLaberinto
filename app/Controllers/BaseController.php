<?php

namespace App\Controllers;

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

        return $code;
    }

    protected function parseReservationAccessToken(?string $token): array
    {
        $token = trim((string) $token);
        if ($token === '') {
            return [];
        }

        if (str_contains($token, '.')) {
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
}
