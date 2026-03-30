<?php

use App\Models\FieldsModel;
use App\Models\UploadModel;

$fieldsModel = new FieldsModel();
$uploadModel = new UploadModel();
$branding = $uploadModel->first();
$logoFile = trim((string) ($branding['name'] ?? ''));
$logoPath = $logoFile !== ''
    ? base_url(PUBLIC_FOLDER . 'assets/images/uploads/' . $logoFile)
    : base_url(PUBLIC_FOLDER . 'assets/images/sinlogo2.png');

$field = $fieldsModel->find($booking['id_field'] ?? null);
$fieldName = trim((string) ($field['name'] ?? 'Reserva'));
$paymentStatus = trim((string) ($mercadoPago['status'] ?? ''));
$paymentStatusLabel = $paymentStatus === 'approved'
    ? 'Aprobado'
    : ($paymentStatus === 'pending' ? 'Pendiente' : 'Rechazado');

$rawDate = trim((string) ($booking['date'] ?? ''));
$formattedDate = $rawDate;
if ($rawDate !== '') {
    $parsedDate = DateTime::createFromFormat('Y-m-d', $rawDate)
        ?: DateTime::createFromFormat('d/m/Y', $rawDate)
        ?: date_create($rawDate);
    if ($parsedDate instanceof DateTimeInterface) {
        $formattedDate = $parsedDate->format('d/m/Y');
    }
}

$timeFrom = trim((string) ($booking['time_from'] ?? ''));
$timeUntil = trim((string) ($booking['time_until'] ?? ''));
$schedule = trim($timeFrom . ' a ' . $timeUntil);
$total = number_format((float) ($booking['total'] ?? 0), 0, ',', '.');
$payment = number_format((float) ($booking['payment'] ?? 0), 0, ',', '.');
$difference = number_format((float) ($booking['diference'] ?? 0), 0, ',', '.');
$description = trim((string) ($booking['description'] ?? ''));
if ($description === '') {
    $description = 'Reserva';
}
?>

<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pago aprobado</title>
    <script src="https://kit.fontawesome.com/9bae38f407.js" crossorigin="anonymous"></script>
    <link rel="stylesheet" href="<?= base_url(PUBLIC_FOLDER . 'assets/css/success.css') ?>">
</head>

<body>
    <div class="page">
        <section class="div-principal">
            <div class="brand brand-top">
                <img src="<?= esc($logoPath) ?>" alt="Laberinto Patagonia">
            </div>
            <h1 class="status-title">Reserva confirmada</h1>
            <p class="status-copy">El pago fue recibido correctamente y la reserva ya quedo registrada.</p>
            <i class="fa-regular fa-circle-check status-icon" aria-hidden="true"></i>
        </section>

        <section class="result">
            <div class="header">
                <h2>Detalle de la reserva</h2>
                <p>Te dejamos el resumen del pago y los datos principales de la visita.</p>
            </div>

            <div class="details-grid">
                <div class="detail-item">
                    <span class="detail-label">Nombre</span>
                    <span class="detail-value"><?= esc((string) ($booking['name'] ?? '')) ?></span>
                </div>
                <div class="detail-item">
                    <span class="detail-label">Telefono</span>
                    <span class="detail-value"><?= esc((string) ($booking['phone'] ?? '')) ?></span>
                </div>
                <div class="detail-item">
                    <span class="detail-label">Fecha</span>
                    <span class="detail-value"><?= esc($formattedDate) ?></span>
                </div>
                <div class="detail-item">
                    <span class="detail-label">Horario</span>
                    <span class="detail-value"><?= esc($schedule) ?></span>
                </div>
                <div class="detail-item">
                    <span class="detail-label">Servicio</span>
                    <span class="detail-value"><?= esc($fieldName) ?></span>
                </div>
                <div class="detail-item">
                    <span class="detail-label">Visitantes</span>
                    <span class="detail-value"><?= esc((string) ($booking['visitors'] ?? '')) ?></span>
                </div>
                <div class="detail-item">
                    <span class="detail-label">Codigo de reserva</span>
                    <span class="detail-value"><?= esc((string) ($booking['code'] ?? '')) ?></span>
                </div>
                <div class="detail-item">
                    <span class="detail-label">Detalle</span>
                    <span class="detail-value"><?= esc($description) ?></span>
                </div>
            </div>

            <hr>

            <div class="summary-grid">
                <div class="summary-card">
                    <span class="summary-label">Valor total</span>
                    <strong class="summary-value">$<?= esc($total) ?></strong>
                </div>
                <div class="summary-card">
                    <span class="summary-label">Pagado</span>
                    <strong class="summary-value">$<?= esc($payment) ?></strong>
                </div>
                <div class="summary-card">
                    <span class="summary-label">Resta</span>
                    <strong class="summary-value">$<?= esc($difference) ?></strong>
                </div>
                <div class="summary-card">
                    <span class="summary-label">Estado del pago</span>
                    <strong class="summary-value"><?= esc($paymentStatusLabel) ?></strong>
                </div>
            </div>

            <div class="payment-meta">
                <span class="payment-meta-label">Codigo de pago de Mercado Pago</span>
                <span class="payment-meta-value"><?= esc((string) ($mercadoPago['payment_id'] ?? 'No informado')) ?></span>
            </div>
        </section>

        <div class="cta-row">
            <a class="cta-link cta-secondary" href="<?= base_url() ?>">Volver a la pantalla principal</a>
            <a class="cta-link cta-primary" href="<?= base_url('bookingPdf/' . $bookingId) ?>">Descargar detalle de la reserva en PDF</a>
        </div>
    </div>
</body>

</html>
