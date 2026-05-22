<?php

namespace App\Commands;

use App\Libraries\MercadoPagoReservationService;
use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;

class ExpirePendingMercadoPagoReservations extends BaseCommand
{
    protected $group = 'MercadoPago';
    protected $name = 'mp:expire-pending-reservations';
    protected $description = 'Ejecuta la expiracion centralizada de reservas pendientes de Mercado Pago.';

    public function run(array $params)
    {
        $startedAt = microtime(true);
        $service = new MercadoPagoReservationService();

        CLI::write('Iniciando expiracion centralizada de Mercado Pago...', 'yellow');

        try {
            $result = $service->expirePendingReservations([], 'spark_mp_expire_pending_reservations');
            $durationMs = (int) round((microtime(true) - $startedAt) * 1000);

            $summary = sprintf(
                'Resumen: revisadas=%d confirmadas=%d anuladas=%d slots_liberados=%d ignoradas=%d fallidas=%d duracion_ms=%d',
                (int) ($result['reviewed'] ?? 0),
                (int) ($result['confirmed'] ?? 0),
                (int) ($result['expired'] ?? 0),
                (int) ($result['released'] ?? 0),
                (int) ($result['ignored'] ?? 0),
                (int) ($result['failed'] ?? 0),
                $durationMs
            );

            CLI::write($summary, 'green');
            log_message('info', '[mp:expire-pending-reservations] ' . $summary);
        } catch (\Throwable $e) {
            $message = 'Error en expiracion centralizada MP: ' . $e->getMessage();
            CLI::error($message);
            log_message('error', '[mp:expire-pending-reservations] ' . $message);
            return EXIT_ERROR;
        }

        return EXIT_SUCCESS;
    }
}
