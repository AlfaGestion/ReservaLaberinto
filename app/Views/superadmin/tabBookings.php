<?php

use App\Models\UploadModel;

$uploadModel = new UploadModel();
$uploadData = $uploadModel->first();

?>

<div id="selectDateBooking"
    class="d-flex flex-column justify-content-center align-items-center"
    data-week-start="<?= esc($weekStart ?? date('Y-m-d')) ?>"
    data-latest-booking-date="<?= esc($latestBookingDate ?? date('Y-m-d')) ?>"
    data-invoice-email-subject="<?= esc($uploadData['invoice_email_subject'] ?? 'Factura de reserva - Laberinto: {nombre}') ?>"
    data-invoice-email-message="<?= esc($uploadData['invoice_email_message'] ?? "Hola {nombre},\n\nTe enviamos adjunto el comprobante de tu reserva.\n\nFecha: {fecha}\nHorario: {horario}\nCodigo: {codigo}\nPagado: {pagado}\n\nGracias.") ?>">

    <div class="d-flex justify-content-center align-items-center flex-row mt-3">
        <strong>Total de reservas para hoy:</strong> <strong id="totalReservasHoy"></strong>
    </div>


    <div class="d-flex justify-content-center align-items-center flex-row">
        <div class="form-floating mb-3 mt-3 me-2">
            <input type="date" name="fechaDesdeBooking" id="fechaDesdeBooking" class="form-control" value="" aria-label="date">
            <label for="fechaDesdeBooking">Desde</label>
        </div>

        <div class="form-floating mb-3 mt-3 me-2">
            <input type="date" name="fechaHastaBooking" id="fechaHastaBooking" class="form-control" value="" aria-label="date">
            <label for="fechaHastaBooking">Hasta</label>
        </div>
    </div>

    <div>
        <button type="button" id="searchBooking" class="btn btn-success">Buscar activas</button>
        <button type="button" id="searchAnnulledBooking" class="btn btn-danger">Buscar anuladas</button>
    </div>
</div>

<div class="modal fade" id="sendInvoiceEmailModal" tabindex="-1" aria-labelledby="sendInvoiceEmailModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h1 class="modal-title fs-5" id="sendInvoiceEmailModalLabel">Enviar factura</h1>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <input type="hidden" id="invoiceEmailBookingId">

                <div class="form-floating mb-3">
                    <input type="email" class="form-control" id="invoiceEmailTo" placeholder="cliente@dominio.com">
                    <label for="invoiceEmailTo">Enviar a</label>
                </div>

                <div class="form-floating mb-3">
                    <input type="text" class="form-control" id="invoiceEmailSubjectModal" placeholder="Asunto">
                    <label for="invoiceEmailSubjectModal">Asunto</label>
                </div>

                <div class="form-floating mb-3">
                    <textarea class="form-control" id="invoiceEmailMessageModal" placeholder="Mensaje" style="height: 220px;"></textarea>
                    <label for="invoiceEmailMessageModal">Mensaje</label>
                </div>

                <div class="form-check">
                    <input class="form-check-input" type="checkbox" value="1" id="invoiceEmailAttachPdf" checked>
                    <label class="form-check-label" for="invoiceEmailAttachPdf">
                        Adjuntar comprobante PDF
                    </label>
                </div>

                <div class="mt-3">
                    <label for="invoiceEmailPdfFile" class="form-label">PDF manual</label>
                    <input type="file" class="form-control" id="invoiceEmailPdfFile" accept="application/pdf,.pdf">
                    <small class="text-muted d-block mt-2">Si selecciona un PDF desde su PC, se adjunta ese archivo. Si no, se usa el PDF generado por el sistema.</small>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                <button type="button" class="btn btn-primary" id="confirmSendBookingInvoiceEmail">Enviar factura</button>
            </div>
        </div>
    </div>
</div>

<!-- Modal -->
<div class="modal fade" data-bs-backdrop="static" id="modalCambiarEstado" tabindex="-1" aria-labelledby="modalCambiarEstadoLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h1 class="modal-title fs-5" id="modalCambiarEstadoLabel">Cambiar estado</h1>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="form-check">
                    <input class="form-check-input" type="checkbox" value="" id="confirmarMPCheck">
                    <label class="form-check-label" for="confirmarMPCheck">
                        Confirmar pago
                    </label>
                </div>
            </div>

            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Volver</button>
                <button type="button" id="confirmarMP" class="btn btn-success">Confirmar</button>
            </div>
        </div>
    </div>
</div>

<!-- Modal -->
<div class="modal fade" id="completarPagoModal" tabindex="-1" aria-labelledby="completarPagoModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h1 class="modal-title fs-5" id="completarPagoModalLabel">Completar pago</h1>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label for="inputCompletarPagoReserva" class="form-label">Ingresar monto</label>
                    <input type="text" class="form-control" id="inputCompletarPagoReserva" name="inputCompletarPagoReserva" placeholder="">
                </div>

                <div class="form-floating">
                    <select class="form-select" id="medioPagoSelect" aria-label="Floating label select example">
                        <option value="">Seleccionar</option>
                        <option value="efectivo">Efectivo</option>
                        <option value="transferencia">Transferencia</option>
                        <option value="mercado_pago">Mercado Pago</option>
                    </select>
                    <label for="medioPagoSelect">Medio de pago</label>
                </div>
            </div>

            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Volver</button>
                <button type="button" id="botonCompletarPago" class="btn btn-primary">Pagar</button>
            </div>
        </div>
    </div>
</div>

<!-- Modal eliminar reserva-->
<div class="modal fade" data-bs-backdrop="static" id="anularReservaModal" tabindex="-1" aria-labelledby="anularReservaModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-body">
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                <div class="d-flex justify-content-center align-items-center flex-column text-center">
                    <h6>&iquest;Est&aacute; seguro que desea anular la reserva?</h6>
                    <div class="d-flex justify-content-center align-items-center">
                        <button type="button" id="confirmCancelBooking" class="btn btn-success me-3">Confirmar</button>
                        <button type="button" id="cancelCancelBooking" class="btn btn-danger">Cancelar</button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal editar reserva-->
<div class="modal fade" data-bs-backdrop="static" id="editarReservaModal" tabindex="-1" aria-labelledby="editarReservaModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-body">
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>

                <div class="form-floating mb-3 mt-3">
                    <input type="date" name="fecha" id="fecha" class="form-control" value="" aria-label="date">
                    <label for="fecha">Fecha</label>
                </div>

                <div class="horario d-flex flex-row">
                    <div class="form-floating" id="div-time-h" style="width: 50%;">
                        <select class="form-select mb-3" name="horarioDesde" id="horarioDesde" aria-label="l">
                            <option value="">Seleccionar</option>

                            <?php if (!empty($openingTime) && is_array($openingTime)): ?>
                                <?php
                                $totalHours = count($openingTime);
                                foreach ($openingTime as $key => $hour):
                                    if ($key !== $totalHours - 1):
                                ?>
                                        <option value="<?= $hour ?>"><?= $hour ?></option>
                                <?php
                                    endif;
                                endforeach;
                                ?>
                            <?php else: ?>
                                <option value="">No hay horarios disponibles</option>
                            <?php endif; ?>

                        </select>
                        <label for="horarioDesde">Horario desde</label>
                    </div>


                    <div class="form-floating  ms-4" id="div-time" style="width: 50%;">
                        <select class="form-select mb-3" name="horarioHasta" id="horarioHasta" aria-label="" disabled>
                            <option value="">Seleccionar</option>
                            <?php if (!empty($openingTime) && is_array($openingTime)): ?>
                                <?php foreach ($openingTime as $hour): ?>
                                    <option value="<?= $hour ?>"><?= $hour ?></option>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <option value="">No hay horarios disponibles</option>
                            <?php endif; ?>
                        </select>
                        <label for="horarioHasta">Horario hasta</label>
                    </div>

                </div>

                <div class="d-flex flex-row">
                    <div class="form-floating" id="divSelectCancha" style="width: 50%;">
                        <select class="form-select mb-3" name="cancha" id="cancha" aria-label="Default floating label" disabled>
                            <option value="">Servicios disponibles</option>
                            <?php foreach ($fields as $field) : ?>
                                <option value="<?= $field['id'] ?>"><?= $field['name'] ?></option>
                            <?php endforeach; ?>
                        </select>
                        <label for="cancha">Seleccionar servicio</label>
                    </div>

                    <div class="form-floating flex-nowrap mb-3 ms-4" style="width: 50%;" id="div-qtyvisitors">
                        <input type="text" class="form-control" name="inputqtyvisitors" id="inputqtyvisitors" value="0" aria-label="name">
                        <label for="inputqtyvisitors">Cantidad de personas</label>
                    </div>
                </div>

                <div class="form-floating flex-nowrap mb-3" id="div-monto">
                    <input type="text" class="form-control" name="inputMonto" id="inputMonto" value="0" aria-label="name">
                    <label for="inputMonto">Monto</label>
                </div>

                <div class="form-floating flex-nowrap mb-3 d-flex align-items-center justify-content-center flex-row">
                    <input type="number" class="form-control" name="telefono" id="telefono" placeholder="Ingrese el tel&eacute;fono" aria-label="name">
                    <label for="telefono">Tel&eacute;fono</label>
                </div>


                <div class="form-floating flex-nowrap mb-3">
                    <input type="text" class="form-control" name="nombre" id="nombre" placeholder="Ingrese el nombre" aria-label="name" disabled>
                    <label for="nombre">Nombre</label>
                </div>


                <button type="button" class="btn btn-success" id="actualizarReserva">Actualizar reserva</button>
                <!-- <button type="button" class="btn btn-danger" id="cancelarReserva">Cancelar</button> -->
            </div>

        </div>
    </div>
</div>

<!-- modal spinner -->
<div class="modal fade" id="modalSpinner" tabindex="-1" data-bs-backdrop="static" aria-labelledby="modalSpinnerLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered d-flex justify-content-center">

        <div class="d-flex justify-content-center align-items-center">
            <div class="spinner-border" style="width: 4rem; height: 4rem; color: <?= isset($userData) ? $userData['main_color'] : '#5a5a5a' ?>" role="status">
                <span class="   -hidden">Procesando reserva...</span>
            </div>
        </div>

    </div>
</div>


<div class="table-responsive">
    <table class="table align-middle table-striped-columns mt-2">
        <thead>
            <tr>
                <th scope="col">Fecha</th>
                <th scope="col">Servicio</th>
                <th scope="col">Horario</th>
                <th scope="col">Nombre</th>
                <th scope="col">Tel&eacute;fono</th>
                <th scope="col">Visitantes</th>
                <th scope="col">Pagado</th>
                <th scope="col">Total</th>
                <th scope="col">Saldo</th>
                <th scope="col">M&eacute;todo de pago</th>
                <th scope="col">Descripci&oacute;n</th>
                <th scope="col">Estado de MP</th>
                <th scope="col">Estado</th>
                <th scope="col">C&oacute;digo</th>
                <th scope="col">Factura cliente</th>
                <th scope="col">Acciones</th>
            </tr>
        </thead>


        <tbody class="divBookings">

        </tbody>
    </table>
</div>
