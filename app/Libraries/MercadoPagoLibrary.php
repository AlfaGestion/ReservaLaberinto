<?php

namespace App\Libraries;

use App\Models\MercadoPagoKeysModel;
// Quitamos la importación de MPApiException para evitar el error P1009
use MercadoPago\SDK; 
use MercadoPago\Preference;
use MercadoPago\Item;
use MercadoPago\Payment;
use MercadoPago\MerchantOrder;
// use MercadoPago\Exceptions\MPApiException; // <--- COMENTADO/ELIMINADO

class MercadoPagoLibrary
{
    public $preferenceId = null;

    function setPreference(string $bookingTitle, float $bookingAmount, int $quantity)
    {
        $mpKeysModel = new MercadoPagoKeysModel();
        $mpKeys = $mpKeysModel->first();

        if (empty($mpKeys) || empty($mpKeys['access_token'])) {
            throw new \Exception("Mercado Pago Access Token no encontrado.");
        }

        $caFile = $this->ensureCaBundle();
        $this->configureSdkHttpClient($caFile);
        SDK::setAccessToken($mpKeys['access_token']);

        try {
            // 1. Validar que el monto no sea cero o negativo (Causa común de fallo silencioso)
            if ($bookingAmount <= 0) {
                 throw new \Exception("El monto de la reserva debe ser un valor positivo.");
            }

            $preference = new Preference();

            $item = new Item();
            $item->title = $bookingTitle;
            $item->quantity = $quantity;
            $item->unit_price = $bookingAmount;
            $item->currency_id = 'ARS';

            $preference->items = [$item];
            $envBaseUrl = getenv('MP_BACK_URL_BASE');
            $appConfig = config('App');
            $baseUrl = rtrim($envBaseUrl ?: $appConfig->baseURL, '/') . '/';
            $preference->back_urls = [
            "success" => $baseUrl . 'payment/success',
            "failure" => $baseUrl . 'payment/failure',
            ];
            $preference->notification_url = $baseUrl . 'payment/webhook';

            $preference->auto_return = "approved";
            $preference->binary_mode = true;
            
            // Llamada a la API
            $preference->save();

            // 🚨 DIAGNÓSTICO CLAVE: Revisar si el ID es nulo
            if (empty($preference->id)) {
                
                // Muestra el objeto completo en el log de PHP para ver la respuesta de error de la API
                $logMessage = "FALLO SILENCIOSO MP: Preference ID es NULL. Objeto completo: " . print_r($preference, true);
                error_log($logMessage);

                // Lanza una excepción con un mensaje genérico, pero el detalle está en el log.
                throw new \Exception("La API de Mercado Pago devolvió un error (revisa los logs de PHP para ver la respuesta de validación).");
            }

            $this->preferenceId = $preference->id;
            
        } catch (\Exception $e) {
            // Captura cualquier error de la SDK, incluyendo la excepción que forzamos arriba.
            throw new \Exception("Error al crear la preferencia de pago: " . $e->getMessage());
        }
    }

    public function getPaymentById(string $paymentId): ?array
    {
        $result = $this->getPaymentByIdWithMeta($paymentId);
        return $result['data'] ?? null;
    }

    public function getPaymentByIdWithMeta(string $paymentId): array
    {
        $paymentId = trim($paymentId);
        if ($paymentId === '') {
            return [
                'api_reachable' => true,
                'found' => false,
                'data' => null,
                'error' => 'missing_payment_id',
            ];
        }

        $mpKeysModel = new MercadoPagoKeysModel();
        $mpKeys = $mpKeysModel->first();
        if (empty($mpKeys) || empty($mpKeys['access_token'])) {
            return [
                'api_reachable' => false,
                'found' => false,
                'data' => null,
                'error' => 'missing_access_token',
            ];
        }

        $caFile = $this->ensureCaBundle();
        $this->configureSdkHttpClient($caFile);
        SDK::setAccessToken($mpKeys['access_token']);

        try {
            $payment = Payment::find_by_id($paymentId);
            if (!$payment) {
                return [
                    'api_reachable' => true,
                    'found' => false,
                    'data' => null,
                    'error' => 'payment_not_found',
                ];
            }

            return [
                'api_reachable' => true,
                'found' => true,
                'error' => null,
                'data' => [
                'id' => $payment->id ?? null,
                'status' => $payment->status ?? null,
                'status_detail' => $payment->status_detail ?? null,
                'external_reference' => $payment->external_reference ?? null,
                'transaction_amount' => $payment->transaction_amount ?? null,
                'payment_type_id' => $payment->payment_type_id ?? null,
                'order_id' => $payment->order->id ?? null,
                ],
            ];
        } catch (\Throwable $e) {
            log_message('error', 'No se pudo consultar payment en MP [' . $paymentId . ']: ' . $e->getMessage());
            return [
                'api_reachable' => false,
                'found' => false,
                'data' => null,
                'error' => $e->getMessage(),
            ];
        }
    }

    public function getMerchantOrderById(string $merchantOrderId): ?array
    {
        $merchantOrderId = trim($merchantOrderId);
        if ($merchantOrderId === '') {
            return null;
        }

        $mpKeysModel = new MercadoPagoKeysModel();
        $mpKeys = $mpKeysModel->first();
        if (empty($mpKeys) || empty($mpKeys['access_token'])) {
            return null;
        }

        $caFile = $this->ensureCaBundle();
        $this->configureSdkHttpClient($caFile);
        SDK::setAccessToken($mpKeys['access_token']);

        try {
            $order = MerchantOrder::find_by_id($merchantOrderId);
            if (!$order) {
                return null;
            }

            $payments = [];
            if (!empty($order->payments) && is_array($order->payments)) {
                foreach ($order->payments as $item) {
                    $payments[] = [
                        'id' => $item->id ?? null,
                        'status' => $item->status ?? null,
                        'status_detail' => $item->status_detail ?? null,
                    ];
                }
            }

            return [
                'id' => $order->id ?? null,
                'status' => $order->order_status ?? null,
                'payments' => $payments,
            ];
        } catch (\Throwable $e) {
            log_message('error', 'No se pudo consultar merchant order en MP [' . $merchantOrderId . ']: ' . $e->getMessage());
            return null;
        }
    }

    private function ensureCaBundle()
    {
        $caFile = ini_get('curl.cainfo');
        if (!$caFile) {
            $caFile = ini_get('openssl.cafile');
        }
        if (!$caFile) {
            $candidates = [
                'C:\\php\\cacert.pem',
                'C:\\app\\ReservasLaBarca\\cacert.pem',
            ];

            foreach ($candidates as $candidate) {
                if (is_file($candidate)) {
                    $caFile = $candidate;
                    break;
                }
            }
        }

        if ($caFile && is_file($caFile)) {
            ini_set('curl.cainfo', $caFile);
            ini_set('openssl.cafile', $caFile);
            putenv("CURL_CA_BUNDLE={$caFile}");
            putenv("SSL_CERT_FILE={$caFile}");
        }

        return $caFile;
    }

    private function configureSdkHttpClient($caFile): void
    {
        SDK::initialize();

        try {
            $reflection = new \ReflectionClass(SDK::class);
            $restClientProperty = $reflection->getProperty('_restClient');
            $restClientProperty->setAccessible(true);
            $restClient = $restClientProperty->getValue();

            if (!$restClient) {
                return;
            }

            if (ENVIRONMENT !== 'production') {
                $restClient->setHttpParam('use_ssl', false);
                $restClient->setHttpParam('verify_mode', 0);
                return;
            }

            if ($caFile && is_file($caFile)) {
                $restClient->setHttpParam('ca_file', $caFile);
                $restClient->setHttpParam('use_ssl', true);
                $restClient->setHttpParam('verify_mode', 2);
            }
        } catch (\Throwable $e) {
            log_message('error', 'No se pudo configurar el cliente HTTP de Mercado Pago: ' . $e->getMessage());
        }
    }
}
