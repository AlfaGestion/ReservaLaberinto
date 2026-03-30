<?php

use App\Models\UploadModel;

$uploadModel = new UploadModel();
$branding = $uploadModel->first();
$logoFile = trim((string) ($branding['name'] ?? ''));

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

    $mimeType = match ($extension) {
        'jpg', 'jpeg' => 'image/jpeg',
        'gif' => 'image/gif',
        'webp' => 'image/webp',
        default => 'image/png',
    };
    $binary = @file_get_contents($candidatePath);
    if ($binary === false) {
        continue;
    }

    $logoDataUri = 'data:' . $mimeType . ';base64,' . base64_encode($binary);
    break;
}

$fechaReserva = trim((string) ($data['fecha'] ?? ''));
$fechaReservaFormateada = $fechaReserva;
if ($fechaReserva !== '') {
    $parsedDate = DateTime::createFromFormat('Y-m-d', $fechaReserva)
        ?: DateTime::createFromFormat('d/m/Y', $fechaReserva)
        ?: date_create($fechaReserva);

    if ($parsedDate instanceof DateTimeInterface) {
        $fechaReservaFormateada = $parsedDate->format('d/m/Y');
    }
}

$estadoPagoRaw = trim((string) ($data['estado_pago'] ?? ''));
$estadoPagoTexto = 'No corresponde';
$estadoPagoClase = 'status-na';

if ($estadoPagoRaw === 'approved') {
    $estadoPagoTexto = 'Aprobado';
    $estadoPagoClase = 'status-approved';
} elseif ($estadoPagoRaw === 'pending') {
    $estadoPagoTexto = 'Pendiente';
    $estadoPagoClase = 'status-pending';
} elseif ($estadoPagoRaw !== '') {
    $estadoPagoTexto = ucfirst($estadoPagoRaw);
    $estadoPagoClase = 'status-rejected';
}
?>

<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Datos de la reserva</title>
    <style>
        * {
            box-sizing: border-box;
        }

        body {
            font-family: 'DejaVu Sans', sans-serif;
            background-color: #f4f7f5;
            margin: 0;
            padding: 28px;
            color: #15251b;
        }

        .card {
            background: #ffffff;
            border-radius: 12px;
            padding: 24px 28px;
            border: 1px solid #dfe8e2;
        }

        .header {
            text-align: center;
            margin-bottom: 18px;
        }

        .logo {
            width: 170px;
            margin: 0 auto 12px;
            display: block;
        }

        .title {
            font-size: 21px;
            font-weight: bold;
            color: #0d6a3a;
            margin: 0;
        }

        .subtitle {
            font-size: 12px;
            color: #56685d;
            margin: 6px 0 0;
        }

        .section {
            margin-top: 16px;
            padding-top: 14px;
            border-top: 1px solid #e3e9e6;
        }

        .section-title {
            margin: 0 0 12px;
            font-size: 18px;
            font-weight: bold;
            color: #0d6a3a;
        }

        ul {
            list-style: none;
            padding: 0;
            margin: 0;
        }

        li {
            margin: 8px 0;
            font-size: 14px;
            line-height: 1.5;
        }

        .label {
            display: inline-block;
            width: 44%;
            font-weight: bold;
            color: #1f6e43;
        }

        .status-approved {
            color: #118447;
            font-weight: bold;
        }

        .status-pending {
            color: #c68d08;
            font-weight: bold;
        }

        .status-rejected {
            color: #c73d4a;
            font-weight: bold;
        }

        .status-na {
            color: #6d7a72;
            font-weight: bold;
        }

        .footer {
            margin-top: 18px;
            padding-top: 14px;
            border-top: 1px solid #e3e9e6;
            font-size: 12px;
            line-height: 1.5;
            color: #425249;
            text-align: justify;
        }
    </style>
</head>

<body>
    <div class="card">
        <div class="header">
            <?php if ($logoDataUri !== '') : ?>
                <img class="logo" src="<?= $logoDataUri ?>" alt="Logo">
            <?php endif; ?>
            <p class="title">Detalle de la reserva</p>
            <p class="subtitle">Comprobante de pago y datos de la visita</p>
        </div>

        <div class="section">
            <p class="section-title">Detalles de la reserva</p>
            <ul>
                <li><span class="label">Nombre:</span> <?= esc((string) ($data['nombre'] ?? '')) ?></li>
                <li><span class="label">Telefono:</span> <?= esc((string) ($data['telefono'] ?? '')) ?></li>
                <li><span class="label">Fecha:</span> <?= esc($fechaReservaFormateada) ?></li>
                <li><span class="label">Horario:</span> <?= esc((string) ($data['horario'] ?? '')) ?></li>
                <li><span class="label">Servicio:</span> <?= esc((string) ($data['servicio'] ?? '')) ?></li>
                <li><span class="label">Detalle:</span> <?= esc(((string) ($data['detalle'] ?? '')) !== '' ? (string) $data['detalle'] : 'Reserva Estandar') ?></li>
            </ul>
        </div>

        <div class="section">
            <p class="section-title">Informacion de pago</p>
            <ul>
                <li><span class="label">Valor total:</span> <?= esc((string) ($data['total_servicio'] ?? '')) ?></li>
                <li><span class="label">Pagado:</span> <?= esc((string) ($data['pagado'] ?? '')) ?></li>
                <li><span class="label">Saldo:</span> <?= esc((string) ($data['saldo'] ?? '')) ?></li>
                <li><span class="label">Codigo pago MP:</span> <?= esc((string) ($data['id_mercado_pago'] ?? 'No corresponde')) ?></li>
                <li>
                    <span class="label">Estado del pago:</span>
                    <span class="<?= esc($estadoPagoClase) ?>"><?= esc($estadoPagoTexto) ?></span>
                </li>
            </ul>
        </div>

        <div class="footer">
            Al realizar el pago de una reserva, se asume el compromiso y la responsabilidad de asistir en el dia y horario acordados.
            En caso de inasistencia, no se realizaran devoluciones de dinero y la reprogramacion quedara sujeta a disponibilidad.
        </div>
    </div>
</body>

</html>
