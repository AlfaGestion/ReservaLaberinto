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
$paymentStatus = strtolower(trim((string) ($mercadoPago['status'] ?? 'approved')));

$statusMap = [
    'approved' => ['label' => 'Aprobado', 'title' => 'Pago confirmado con éxito', 'subtitle' => 'Tu reserva ya quedo registrada correctamente.', 'icon' => 'fa-circle-check', 'tone' => 'success'],
    'pending' => ['label' => 'Pendiente', 'title' => 'Pago pendiente', 'subtitle' => 'Estamos esperando la acreditacion final del pago.', 'icon' => 'fa-hourglass-half', 'tone' => 'warning'],
    'in_process' => ['label' => 'En proceso', 'title' => 'Pago en revision', 'subtitle' => 'Mercado Pago esta validando la operacion.', 'icon' => 'fa-clock', 'tone' => 'warning'],
];
$currentStatus = $statusMap[$paymentStatus] ?? ['label' => 'No aprobado', 'title' => 'Pago no aprobado', 'subtitle' => 'No pudimos acreditar el pago de esta reserva.', 'icon' => 'fa-circle-xmark', 'tone' => 'danger'];

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
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= esc($currentStatus['title']) ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://kit.fontawesome.com/9bae38f407.js" crossorigin="anonymous"></script>
</head>
<body class="bg-light">
<div class="container py-5">
    <div class="card border-0 shadow-sm rounded-4 overflow-hidden">
        <div class="card-body p-4 p-md-5">
            <div class="text-center mb-4">
                <img src="<?= esc($logoPath) ?>" alt="Laberinto Patagonia" style="max-height:68px;" class="mb-3">
                <div class="mb-2 text-<?= esc($currentStatus['tone']) ?>"><i class="fa-regular <?= esc($currentStatus['icon']) ?> fa-3x"></i></div>
                <h1 class="h3 mb-2"><?= esc($currentStatus['title']) ?></h1>
                <p class="text-muted mb-0"><?= esc($currentStatus['subtitle']) ?></p>
            </div>

            <div class="row g-3">
                <div class="col-md-6"><div class="p-3 rounded-3 bg-light border">Nombre<br><strong><?= esc((string) ($booking['name'] ?? '')) ?></strong></div></div>
                <div class="col-md-6"><div class="p-3 rounded-3 bg-light border">Telefono<br><strong><?= esc((string) ($booking['phone'] ?? '')) ?></strong></div></div>
                <div class="col-md-6"><div class="p-3 rounded-3 bg-light border">Fecha<br><strong><?= esc($formattedDate) ?></strong></div></div>
                <div class="col-md-6"><div class="p-3 rounded-3 bg-light border">Horario<br><strong><?= esc($schedule) ?></strong></div></div>
                <div class="col-md-6"><div class="p-3 rounded-3 bg-light border">Servicio<br><strong><?= esc($fieldName) ?></strong></div></div>
                <div class="col-md-6"><div class="p-3 rounded-3 bg-light border">Codigo de reserva<br><strong><?= esc((string) ($booking['code'] ?? '')) ?></strong></div></div>
            </div>

            <div class="row g-3 mt-2">
                <div class="col-md-3"><div class="p-3 rounded-3 border">Total<br><strong>$<?= esc($total) ?></strong></div></div>
                <div class="col-md-3"><div class="p-3 rounded-3 border">Pagado<br><strong>$<?= esc($payment) ?></strong></div></div>
                <div class="col-md-3"><div class="p-3 rounded-3 border">Saldo<br><strong>$<?= esc($difference) ?></strong></div></div>
                <div class="col-md-3"><div class="p-3 rounded-3 border">Estado<br><strong><?= esc($currentStatus['label']) ?></strong></div></div>
            </div>

            <div class="d-flex flex-wrap gap-2 justify-content-center mt-4">
                <a class="btn btn-outline-secondary" href="<?= base_url() ?>">Volver al inicio</a>
                <a class="btn btn-success" href="<?= base_url('bookingPdf/' . $bookingId) ?>">Descargar comprobante</a>
            </div>
        </div>
    </div>
</div>
</body>
</html>

