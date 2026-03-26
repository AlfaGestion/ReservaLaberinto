<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Confirmacion de Reserva</title>
    <style>
        /* La fuente 'DejaVu Sans' se mantiene por compatibilidad con Dompdf y tildes */
        body {
            font-family: 'DejaVu Sans', sans-serif;
            margin: 0;
            padding: 0;
            background-color: #f8f8f8;
            /* Fondo muy claro */
            text-align: center;
        }

        /* Contenedor principal del documento, simula un recibo */
        .container {
            width: 90%;
            max-width: 600px;
            margin: 40px auto;
            padding: 30px;
            background-color: #ffffff;
            /* Fondo blanco para el contenido */
            border: 1px solid #ddd;
            border-radius: 8px;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.05);
            /* Sombra suave */
            text-align: left;
        }

        /* Estilo para el logo */
        .header {
            text-align: center;
            margin-bottom: 25px;
            padding-bottom: 15px;
            border-bottom: 2px solid #0d6a3a;
            /* Color primario azul */
        }

        .header img {
            width: 250px;
            height: auto;
        }

        /* Estilos de las secciones de datos */
        .section-title {
            color: #0d6a3a;
            /* Título en azul */
            font-size: 1.2em;
            margin-top: 20px;
            margin-bottom: 10px;
            border-bottom: 1px dashed #eee;
            padding-bottom: 5px;
            font-weight: bold;
        }

        .data-list {
            list-style: none;
            padding: 0;
            margin: 0;
        }

        .data-list li {
            margin: 8px 0;
            font-size: 16px;
            line-height: 1.5;
        }

        .data-list strong {
            display: inline-block;
            width: 45%;
            /* Espacio para alinear las etiquetas */
            color: #333;
            font-weight: 600;
        }

        /* Estilo específico para el estado de pago */
        .status-approved {
            color: #28a745;
            /* Verde */
            font-weight: bold;
        }

        .status-pending {
            color: #ffc107;
            /* Amarillo */
            font-weight: bold;
        }

        .status-rejected {
            color: #dc3545;
            /* Rojo */
            font-weight: bold;
        }

        .status-nc {
            color: #a9a3a4ff;
            /* Rojo */
            font-weight: bold;
        }

        /* Estilo para la política (sección inferior) */
        .policy {
            margin-top: 30px;
            padding-top: 20px;
            border-top: 1px solid #ddd;
            font-size: 13px;
            color: #666;
            text-align: justify;
        }
    </style>
</head>

<body>
    <div class="container">

        <div class="header">
            <img src="<?= base_url(PUBLIC_FOLDER . "assets/images/logo_pdf.png") ?>" style="width: 100px;" alt="Logo">
        </div>

        <div class="section-title">Detalles de la Reserva</div>
        <ul class="data-list">
            <li><strong>Nombre:</strong> <?= $data['nombre'] ?></li>
            <li><strong>Telefono:</strong> <?= $data['telefono'] ?></li>
            <hr style="border-top: 1px solid #eee; margin: 10px 0;">
            <li><strong>Fecha:</strong> <?= date('d/m/Y', strtotime($data['fecha'])) ?></li>
            <li><strong>Horario:</strong> <?= $data['horario'] ?></li>
            <li><strong>Servicio:</strong> <?= $data['servicio'] ?></li>
            <li><strong>Detalle:</strong> <?= $data['detalle'] == '' || $data['detalle'] == null ? 'Reserva Estandar' : $data['detalle'] ?></li>
        </ul>

        <div class="section-title">Informacion de Pago</div>
        <ul class="data-list">
            <li><strong>Valor Total:</strong> <?= $data['total_servicio'] ?></li>
            <li><strong>Pagado:</strong> <?= $data['pagado'] ?></li>
            <li><strong>Restan (Saldo):</strong> <?= $data['saldo'] ?></li>
            <hr style="border-top: 1px solid #eee; margin: 10px 0;">
            <li><strong>Codigo pago MP:</strong> <?= $data['id_mercado_pago'] ?? 'No corresponde' ?> </li>
            <li>
                <strong>Estado del Pago:</strong>
                <?php
                $statusClass = 'status-nc';
                $statusText = 'No corresponde';

                if (isset($data['estado_pago'])) {
                    if ($data['estado_pago'] == 'approved') {
                        $statusClass = 'status-approved';
                        $statusText = 'Aprobado';
                    } elseif ($data['estado_pago'] == 'pending') {
                        $statusClass = 'status-pending';
                        $statusText = 'Pendiente';
                    }
                }

                ?>
                <span class="<?= $statusClass ?>"><?= $statusText ?></span>
            </li>
        </ul>

        <div class="policy">
            **Terminos y Condiciones de la Reserva:**
            Al realizar el pago de una reserva (ya sea parcial o total), se asume el
            compromiso y la responsabilidad de asistir en el dia y horario acordados.
            En caso de inasistencia, no se realizaran devoluciones de dinero, y la
            reprogramacion quedara sujeta a disponibilidad.
        </div>
    </div>
</body>

</html>