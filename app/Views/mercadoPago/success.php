<?php

use App\Models\FieldsModel;

$fieldsModel = new FieldsModel()

?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pago aprobado!</title>
    <script src="https://kit.fontawesome.com/9bae38f407.js" crossorigin="anonymous"></script>
    <link rel="stylesheet" href="<?= base_url(PUBLIC_FOLDER . "assets/css/success.css") ?>">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-rbsA2VBKQhggwzxH7pPCaAqO46MgnOM80zW1RWuH61DGLwZJEdK2Kadq2F9CUG65" crossorigin="anonymous">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-kenU1KFdBIe4zVF0s0G1M5b4hcpxyD9F7jL+jjXkk+Q2h455rYXK/7HAuoJl+0I4" crossorigin="anonymous"></script>
</head>

<body>
    <div class=" d-flex align-items-center justify-content-center flex-column">
        <div class="div-principal d-flex justify-content-center align-items-center flex-column">
            <h4 class="mb-5">Reserva confirmada!</h4>
            <i class="fa-regular fa-circle-check fa-2xl"></i>
            <a class="mt-4" href="<?= base_url() ?>">Volver a la pantalla principal</a>
            <a class="mt-4" href="<?= base_url('bookingPdf/' . $bookingId) ?>">Descargar detalle de la reserva en PDF</a>
        </div>

        <div class="result">
            <div class="header d-flex align-items-center justify-content-center flex-column">
                <h5 class="text-center">Detalle de la reserva</h5>
            </div>

            <hr>
            <ul>
                <li><strong>Nombre:</strong> <?= $booking['name'] ?></li>
                <li><strong>Teléfono:</strong> <?= $booking['phone'] ?></li>
                <li><strong>Fecha:</strong> <?= date('d/m/Y', strtotime($booking['date'])) ?></li>
                <li><strong>Horario:</strong> <?= $booking['time_from'] . 'hs' . ' ' . $booking['time_until'] . 'hs' ?></li>
                <li><strong>Servicio:</strong> <?= $fieldsModel->getField($booking['id_field'])['name'] ?></li>
                <li><strong>Visitantes:</strong> <?= $booking['visitors'] ?></li>
                <li><strong>Código de reserva:</strong> <?= $booking['code'] ?></li>
                <hr>
                <li><strong>Valor total de la reserva:</strong> $<?= $booking['total'] ?></li>
                <li><strong>Pagado:</strong> $<?= $booking['payment'] ?></li>
                <li><strong>Restan:</strong> $<?= $booking['diference'] ?></li>
                <li><strong>Detalle:</strong> <?= $booking['description']  == '' || $booking['description'] == null ? 'Reserva' : $booking['description'] ?></li>
                <hr>

                <li><strong>Código de pago de Mercado Pago:</strong> <?= $mercadoPago['payment_id'] ?> </li>
                <li><strong>Estado del pago:</strong> <?= $mercadoPago['status'] == 'approved' ? 'Aprobado' : ($mercadoPago['status'] == 'pending' ? 'Pendiente' : 'Rechazado') ?></li>
            </ul>
            <hr>

        </div>
    </div>
</body>

</html>