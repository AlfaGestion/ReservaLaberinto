<?php

namespace App\Libraries;

use Dompdf\Dompdf;

class PrintBookings
{
    public function renderBooking($data)
    {
        $htmlContent = view('domPdf/bookingPdf', ['data' => $data]);
        $name = date('Ymd_Hi') . '_reserva_' . $data['telefono'] . '.pdf';

        $dompdf = new Dompdf();
        $options = $dompdf->getOptions();
        $options->set('isHtml5ParserEnabled', true);
        $options->set('defaultFont', 'DejaVu Sans');
        $dompdf->setOptions($options);

        $dompdf->loadHtml($htmlContent, 'UTF-8');
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();

        return [
            'name' => $name,
            'content' => $dompdf->output(),
        ];
    }

    public function renderReports($data)
    {
        $name = time() . 'reporte.pdf';
        $dompdf = new Dompdf();
        $dompdf->loadHtml(view('domPdf/reportPdf', ['data' => $data]));
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();

        return [
            'name' => $name,
            'content' => $dompdf->output(),
        ];
    }

    public function renderPaymentsReports($data)
    {
        $name = time() . 'reporte_de_ingresos_mercado_pago.pdf';
        $dompdf = new Dompdf();
        $dompdf->loadHtml(view('domPdf/reportPaymentPdf', ['data' => $data]));
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();

        return [
            'name' => $name,
            'content' => $dompdf->output(),
        ];
    }
}
