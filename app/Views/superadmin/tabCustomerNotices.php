<?php

$customerNotices = $customerNotices ?? [];
$today = date('Y-m-d');

?>

<div class="admin-section-card">
    <div class="d-flex flex-column flex-lg-row align-items-lg-center justify-content-between gap-2 mb-3">
        <div>
            <h5 class="mb-1">Avisos para clientes</h5>
            <p class="text-muted mb-0">Carga mensajes informativos que se muestran automaticamente segun el rango de fechas.</p>
        </div>
        <span class="badge text-bg-light border align-self-start align-self-lg-center">Sin activo/inactivo</span>
    </div>

    <form id="customerNoticeForm" autocomplete="off">
        <div class="row g-3">
            <div class="col-12">
                <div class="form-floating">
                    <textarea class="form-control" id="customerNoticeMessage" placeholder="Mensaje para clientes" style="height: 130px;" required></textarea>
                    <label for="customerNoticeMessage">Mensaje</label>
                </div>
            </div>
            <div class="col-md-4">
                <div class="form-floating">
                    <select class="form-select" id="customerNoticeType" required>
                        <option value="info">Informacion</option>
                        <option value="warning">Advertencia</option>
                        <option value="important">Importante</option>
                        <option value="success">Exito</option>
                    </select>
                    <label for="customerNoticeType">Tipo / icono</label>
                </div>
            </div>
            <div class="col-md-4">
                <div class="form-floating">
                    <input type="date" class="form-control" id="customerNoticeDateFrom" value="<?= esc($today) ?>" required>
                    <label for="customerNoticeDateFrom">Fecha desde</label>
                </div>
            </div>
            <div class="col-md-4">
                <div class="form-floating">
                    <input type="date" class="form-control" id="customerNoticeDateUntil" value="<?= esc($today) ?>" required>
                    <label for="customerNoticeDateUntil">Fecha hasta</label>
                </div>
            </div>
        </div>

        <div class="d-flex flex-wrap gap-2 align-items-center mt-3">
            <button type="submit" class="btn btn-primary" id="saveCustomerNotice">
                <i class="fa-solid fa-bullhorn me-1"></i>Guardar aviso
            </button>
            <small class="text-muted">Crear un aviso nuevo mantiene el historial anterior.</small>
        </div>
    </form>

    <div class="mt-4">
        <div class="d-flex align-items-center justify-content-between gap-2 mb-2">
            <h6 class="mb-0">Historial</h6>
            <small class="text-muted">Vigente, programado o vencido</small>
        </div>
        <div class="table-responsive">
            <table class="table align-middle table-striped-columns mb-0">
                <thead>
                    <tr>
                        <th scope="col">Aviso</th>
                        <th scope="col">Tipo</th>
                        <th scope="col">Desde</th>
                        <th scope="col">Hasta</th>
                        <th scope="col">Estado</th>
                        <th scope="col">Acciones</th>
                    </tr>
                </thead>
                <tbody id="customerNoticesTableBody">
                    <?php if (!empty($customerNotices)) : ?>
                        <?php foreach ($customerNotices as $notice) : ?>
                            <tr id="customer-notice-row-<?= esc($notice['id']) ?>">
                                <td style="min-width: 260px;"><?= nl2br(esc($notice['message'])) ?></td>
                                <td><?= esc($notice['type']) ?></td>
                                <td><?= esc(date('d/m/Y', strtotime($notice['date_from']))) ?></td>
                                <td><?= esc(date('d/m/Y', strtotime($notice['date_until']))) ?></td>
                                <td>
                                    <span class="badge customer-notice-status customer-notice-status--<?= esc($notice['status']) ?>">
                                        <?= esc(ucfirst($notice['status'])) ?>
                                    </span>
                                </td>
                                <td>
                                    <button type="button" class="btn btn-danger btn-sm customer-notice-delete-trigger" data-id="<?= esc($notice['id']) ?>">
                                        <i class="fa-solid fa-trash"></i>
                                    </button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else : ?>
                        <tr id="customerNoticesEmptyRow">
                            <td colspan="6" class="text-center text-muted">Todavia no hay avisos cargados.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
