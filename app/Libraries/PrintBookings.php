<?php

namespace App\Libraries;

use Dompdf\Dompdf;

class PrintBookings
{
    public function printBooking($data)
    {
        // 1. Carga el contenido de la vista directamente, SIN CONVERSIÓN
        $html_content = view('domPdf/bookingPdf', ['data' => $data]);

        // ... tu código para el nombre y headers ...
        $name = date('Ymd_Hi') . '_reserva_' . $data['telefono'] . '.pdf';
        header('Content-Type: application/pdf');
        header('Content-Disposition: attachment; filename="' . $name . '"');

        $dompdf = new Dompdf();

        // 2. ¡CRUCIAL! Asegura que Dompdf use las configuraciones necesarias
        $options = $dompdf->getOptions();
        $options->set('isHtml5ParserEnabled', true); // Recomendado para HTML5
        $options->set('defaultFont', 'DejaVu Sans'); // Fuerza la fuente Unicode
        $dompdf->setOptions($options);

        // 3. Carga el HTML sin modificar
        $dompdf->loadHtml($html_content, 'UTF-8'); // Especifica UTF-8 en la carga

        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();
        $dompdf->stream($name);
        exit();
    }

    public function printReports($data)
    {
        $name = time() . 'reporte.pdf';
        header('Content-Type: application/pdf');
        header('Content-Disposition: attachment; filename="' . $name . '"');

        $dompdf = new Dompdf();
        $dompdf->loadHtml(view('domPdf/reportPdf', ['data' => $data]));
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();
        $dompdf->stream($name);
        exit();
    }

    public function printPaymentsReports($data)
    {
        $name = time() . 'reporte_de_ingresos_mercado_pago.pdf';
        header('Content-Type: application/pdf');
        header('Content-Disposition: attachment; filename="' . $name . '"');

        $dompdf = new Dompdf();
        $dompdf->loadHtml(view('domPdf/reportPaymentPdf', ['data' => $data]));
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();
        $dompdf->stream($name);
        exit();
    }
}
