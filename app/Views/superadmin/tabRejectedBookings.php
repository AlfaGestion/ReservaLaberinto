<div class="mt-3">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h5 class="mb-0">Reservas con pago rechazado o pendiente</h5>
        <button type="button" class="btn btn-outline-secondary btn-sm" id="reloadRejectedBookings">Actualizar</button>
    </div>

    <div class="table-responsive">
        <table class="table table-striped align-middle" id="rejectedBookingsTable">
            <thead>
                <tr>
                    <th>Fecha reserva</th>
                    <th>Horario</th>
                    <th>Cliente</th>
                    <th>Telefono</th>
                    <th>Email</th>
                    <th>Visitantes</th>
                    <th>Total</th>
                    <th>Estado</th>
                    <th>Motivo</th>
                    <th>Creacion</th>
                    <th>Notificado</th>
                    <th>Vence link</th>
                    <th>Acciones</th>
                </tr>
            </thead>
            <tbody id="rejectedBookingsBody">
                <tr><td colspan="13" class="text-center text-muted">Cargando...</td></tr>
            </tbody>
        </table>
    </div>
</div>
