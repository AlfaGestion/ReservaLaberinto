<?php
$status = strtolower(trim((string) ($status ?? 'cancelled')));

$map = [
    'pending' => ['title' => 'Pago pendiente', 'text' => 'Tu pago esta en proceso. Cuando se acredite te vamos a confirmar la reserva.', 'icon' => 'fa-hourglass-half', 'tone' => 'warning'],
    'in_process' => ['title' => 'Pago en revision', 'text' => 'Mercado Pago esta revisando la operacion. Te avisaremos cuando haya una definicion.', 'icon' => 'fa-clock', 'tone' => 'warning'],
    'cancelled' => ['title' => 'Pago cancelado', 'text' => 'No se completo el pago. Si queres retomar la reserva, podes volver a intentarlo.', 'icon' => 'fa-circle-pause', 'tone' => 'secondary'],
    'rejected' => ['title' => 'Pago rechazado', 'text' => 'No pudimos acreditar el pago de tu reserva. Podes volver a intentarlo desde el link de pago.', 'icon' => 'fa-circle-xmark', 'tone' => 'danger'],
    'error' => ['title' => 'No pudimos confirmar el pago', 'text' => 'Tuvimos un problema para validar el estado del pago. Intenta nuevamente en unos minutos.', 'icon' => 'fa-triangle-exclamation', 'tone' => 'danger'],
];

$data = $map[$status] ?? $map['rejected'];
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= esc($data['title']) ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://kit.fontawesome.com/9bae38f407.js" crossorigin="anonymous"></script>
</head>
<body class="bg-light">
<div class="container py-5">
    <div class="card border-0 shadow-sm rounded-4 mx-auto" style="max-width: 680px;">
        <div class="card-body p-4 p-md-5 text-center">
            <div class="text-<?= esc($data['tone']) ?> mb-3"><i class="fa-regular <?= esc($data['icon']) ?> fa-3x"></i></div>
            <h1 class="h3 mb-3"><?= esc($data['title']) ?></h1>
            <p class="text-muted mb-4"><?= esc($data['text']) ?></p>
            <div class="d-flex flex-wrap justify-content-center gap-2">
                <a class="btn btn-outline-secondary" href="<?= base_url() ?>">Volver al inicio</a>
                <a class="btn btn-success" href="<?= base_url('MisReservas') ?>">Ver mis reservas</a>
            </div>
        </div>
    </div>
</div>
</body>
</html>

