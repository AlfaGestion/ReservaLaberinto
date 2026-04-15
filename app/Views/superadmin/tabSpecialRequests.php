<div
    id="specialRequestsPanel"
    class="container-fluid px-0"
    data-special-reply-subject="Confirma tu reserva - {fecha}"
    data-special-reply-message="Hola {nombre},\n\nVimos tu solicitud para reservar el {fecha} a las {horario}.\n\nSi quer&eacute;s avanzar, us&aacute; el bot&oacute;n que aparece en este email para confirmar la reserva y completar el pago.\n\nGracias.">

    <div class="card border-0 shadow-sm mt-3 mb-3">
        <div class="card-body">
            <div class="d-flex flex-column flex-lg-row justify-content-between align-items-lg-center gap-3">
                <div>
                    <div class="d-flex align-items-center gap-2 mb-2">
                        <h5 class="mb-0">Solicitudes de reserva</h5>
                        <span class="badge rounded-pill text-bg-danger d-none" id="specialRequestsUnreadBadge">0 nuevas</span>
                    </div>
                    <p class="text-muted mb-0">Consultas por reservas con menos de 48 hs. Desde ac&aacute; pod&eacute;s ver el pedido completo y responderle al cliente.</p>
                </div>
                <button type="button" class="btn btn-outline-dark" id="refreshSpecialRequests">Actualizar</button>
            </div>

            <div id="specialRequestsList" class="mt-3">
                <div class="text-muted">Cargando solicitudes especiales...</div>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="specialRequestViewModal" tabindex="-1" aria-labelledby="specialRequestViewModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h1 class="modal-title fs-5" id="specialRequestViewModalLabel">Solicitud especial</h1>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body" id="specialRequestViewContent"></div>
        </div>
    </div>
</div>

<div class="modal fade" id="specialRequestReplyModal" tabindex="-1" aria-labelledby="specialRequestReplyModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h1 class="modal-title fs-5" id="specialRequestReplyModalLabel">Enviar email de confirmaci&oacute;n</h1>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <input type="hidden" id="specialRequestReplyId">

                <div class="form-floating mb-3">
                    <input type="email" class="form-control" id="specialRequestReplyTo" placeholder="cliente@dominio.com">
                    <label for="specialRequestReplyTo">Enviar a</label>
                </div>

                <div class="form-floating mb-3">
                    <input type="text" class="form-control" id="specialRequestReplySubject" placeholder="Asunto">
                    <label for="specialRequestReplySubject">Asunto</label>
                </div>

                <div class="form-floating mb-2">
                    <textarea class="form-control" id="specialRequestReplyMessage" placeholder="Mensaje" style="height: 220px;"></textarea>
                    <label for="specialRequestReplyMessage">Mensaje</label>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                <button type="button" class="btn btn-primary" id="confirmReplySpecialRequest">Enviar email</button>
            </div>
        </div>
    </div>
</div>
