<?php

use App\Models\MercadoPagoKeysModel;
use App\Models\UploadModel;

$modelUploads = new UploadModel();
$userData = $modelUploads->first();

$mpKeysModel = new MercadoPagoKeysModel();
$mpKeys = $mpKeysModel->first();

?>

<?= $this->extend('templates/dashboard') ?>

<?= $this->section('title') ?>
<title>Ver mi reserva</title>
<?= $this->endSection() ?>

<?= $this->section('content') ?>

<style>
    .booking-lookups__intro {
        max-width: 760px;
        margin-bottom: 1.5rem;
    }

    .booking-search-card {
        border-radius: 24px;
        overflow: hidden;
    }

    .booking-search-card--secondary {
        background: linear-gradient(180deg, rgba(255, 255, 255, 0.96) 0%, rgba(244, 247, 252, 0.96) 100%);
    }

    html.theme-dark .booking-search-card--secondary {
        background: linear-gradient(180deg, rgba(16, 40, 68, 0.98) 0%, rgba(13, 33, 56, 0.98) 100%);
    }

    .booking-search-hint {
        color: var(--bs-secondary-color);
        max-width: 760px;
    }
</style>

<div class="container">
    <?= view('partials/customer_notice', ['customerNotice' => $customerNotice ?? null]) ?>

    <div
        id="bookingPrefill"
        class="d-none"
        data-code="<?= esc($prefill['code'] ?? '') ?>"
        data-phone="<?= esc($prefill['phone'] ?? '') ?>"
        data-email="<?= esc($prefill['email'] ?? '') ?>"></div>
    <div class="booking-lookups__intro">
        <h1 class="display-6 fw-bold mb-2">Mis reservas</h1>
        <p class="booking-search-hint mb-0">Primero consultá todas tus reservas con teléfono y email. Si buscás una reserva puntual, más abajo tenés la opción por código.</p>
    </div>

    <div class="card shadow-sm border-0 booking-search-card mb-4">
        <div class="card-body p-4 p-lg-5">
            <div class="mb-3">
                <span class="badge rounded-pill text-bg-success mb-2">Opción principal</span>
                <h2 class="h4 mb-2">Ver todas mis reservas</h2>
                <p class="booking-search-hint mb-0">Ingresá teléfono y email para buscar todas las reservas asociadas a ese cliente.</p>
            </div>

            <div class="row g-3">
                <div class="col-12 col-lg-6">
                    <div class="form-floating">
                        <input type="text" name="phoneSearch" id="phoneSearch" class="form-control" value="" aria-label="telefono">
                        <label for="phoneSearch">Telefono</label>
                    </div>
                </div>

                <div class="col-12 col-lg-6">
                    <div class="form-floating">
                        <input type="email" name="emailSearch" id="emailSearch" class="form-control" value="" aria-label="email">
                        <label for="emailSearch">Email</label>
                    </div>
                </div>
            </div>

            <div class="mt-3">
                <button type="button" class="btn booking-history-btn" id="searchCustomerBookings">Ver mis reservas</button>
            </div>
        </div>
    </div>

    <div class="card shadow-sm border-0 booking-search-card booking-search-card--secondary mb-3">
        <div class="card-body p-4 p-lg-5">
            <span class="badge rounded-pill text-bg-secondary mb-2">Opción secundaria</span>
            <h2 class="h5 mb-2">Buscar una reserva por código</h2>
            <p class="booking-search-hint mb-3">El código de reserva se envía por email cuando generás una reserva.</p>

            <div class="row g-3 align-items-end">
                <div class="col-12 col-lg-8">
                    <div class="form-floating">
                        <input type="text" name="code" id="code" class="form-control" value="" aria-label="codigo">
                        <label for="code">Codigo de reserva</label>
                    </div>
                </div>

                <div class="col-12 col-lg-4">
                    <button type="button" class="btn w-100" style="color: #fff; background-color: <?= isset($userData) ? $userData['secondary_color'] : '#0064b0' ?>;" id="searchBooking">Buscar reserva</button>
                </div>
            </div>
        </div>
    </div>

    <div class="d-none mt-4" id="bookingCardContainer"></div>
</div>

<div class="modal fade" id="modalSpinner" tabindex="-1" data-bs-backdrop="static" aria-labelledby="modalSpinnerLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered d-flex justify-content-center">
        <div class="d-flex justify-content-center align-items-center">
            <div class="spinner-border" style="width: 4rem; height: 4rem; color: <?= isset($userData) ? $userData['main_color'] : '#0064b0' ?>;" role="status">
                <span class="visually-hidden">Buscando reserva...</span>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="modalResult" tabindex="-1" data-bs-backdrop="static" aria-labelledby="modalResultLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content" id="bookingEditResult"></div>
    </div>
</div>

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
                        <select class="form-select mb-3" name="horarioDesde" id="horarioDesde" aria-label="horario desde">
                            <option value="">Seleccionar</option>
                            <?php if (!empty($openingTime) && is_array($openingTime)): ?>
                                <?php $totalHours = count($openingTime); ?>
                                <?php foreach ($openingTime as $key => $hour): ?>
                                    <?php if ($key !== $totalHours - 1): ?>
                                        <option value="<?= $hour ?>"><?= $hour ?></option>
                                    <?php endif; ?>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <option value="">No hay horarios disponibles</option>
                            <?php endif; ?>
                        </select>
                        <label for="horarioDesde">Horario desde</label>
                    </div>

                    <div class="form-floating ms-4" id="div-time" style="width: 50%;">
                        <select class="form-select mb-3" name="horarioHasta" id="horarioHasta" aria-label="horario hasta" disabled>
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

                <div style="width: 50%;" class="mb-3">
                    <button type="button" class="btn btn-sm" style="color: #fff; background-color: <?= isset($userData) ? $userData['secondary_color'] : '#5a5a5a' ?>;" id="showAvailability">Ver disponibilidad <i class="fa-solid fa-arrow-right"></i></button>
                </div>

                <div class="d-flex flex-row">
                    <div class="form-floating" id="divSelectCancha" style="width: 50%;">
                        <select class="form-select mb-3" name="cancha" id="cancha" aria-label="servicios disponibles" disabled>
                            <option value="">Servicios disponibles</option>
                            <?php foreach ($fields as $field) : ?>
                                <option value="<?= $field['id'] ?>"><?= $field['name'] ?></option>
                            <?php endforeach; ?>
                        </select>
                        <label for="cancha">Seleccionar servicio</label>
                    </div>

                    <div class="form-floating flex-nowrap mb-3 ms-4" style="width: 50%;" id="div-qtyvisitors">
                        <input type="text" class="form-control" name="inputqtyvisitors" id="inputqtyvisitors" value="0" aria-label="cantidad de personas">
                        <label for="inputqtyvisitors">Cantidad de personas</label>
                    </div>
                </div>

                <div class="form-floating flex-nowrap mb-3" id="div-monto">
                    <input type="text" class="form-control" name="inputMonto" id="inputMonto" value="0" aria-label="monto" disabled>
                    <label for="inputMonto">Monto</label>
                </div>

                <div class="form-floating flex-nowrap mb-3 d-flex align-items-center justify-content-center flex-row">
                    <input type="number" class="form-control" name="telefono" id="telefono" placeholder="Ingrese el telefono" aria-label="telefono" disabled>
                    <label for="telefono">Telefono</label>
                </div>

                <div class="form-floating flex-nowrap mb-3">
                    <input type="text" class="form-control" name="nombre" id="nombre" placeholder="Ingrese el nombre" aria-label="nombre" disabled>
                    <label for="nombre">Nombre</label>
                </div>

                <button type="button" class="btn btn-success" id="actualizarReserva">Actualizar reserva</button>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="modalAvailability" tabindex="-1" data-bs-backdrop="static" aria-labelledby="modalAvailabilityLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modalAvailabilityLabel">Disponibilidad</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body" id="availabilityResult" style="background-color: #f8f9fa; max-height: 60vh; overflow-y: auto;"></div>
        </div>
    </div>
</div>

<?= $this->endSection() ?>

<?= $this->section('footer') ?>
<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<script>
    let esDomingo = <?php echo json_encode($esDomingo); ?>;
</script>

<script>
    const time = <?= json_encode((new \App\Models\TimeModel())->schedules); ?>;
</script>

<script src="https://sdk.mercadopago.com/js/v2"></script>
<script src="<?= base_url(PUBLIC_FOLDER . "assets/js/showCustomerBooking.js?v=20260813-1") ?>"></script>
<?= $this->endSection() ?>
