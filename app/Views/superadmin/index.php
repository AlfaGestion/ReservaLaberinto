<?php echo $this->extend('templates/dashboard_panel') ?>

<?php echo $this->section('title') ?>
<title>Panel</title>
<?php echo $this->endSection() ?>


<?php echo $this->section('content') ?>

<?php

use App\Models\UploadModel;

$modelUploads = new UploadModel();
$userData = $modelUploads->first();


?>

<?php if (session('msg')) : ?>
    <div class="alert alert-<?= session('msg.type') ?> alert-dismissible fade show" role="alert">
        <small> <?= session('msg.body') ?> </small>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
<?php endif; ?>

<div class="container">
    <div class="row">
        <div class="col-12">
            <nav>
                <div class="nav nav-tabs mt-3" id="nav-tab" role="tablist">
                    <button class="nav-link active" id="nav-bookings-tab" data-bs-toggle="tab" data-bs-target="#nav-bookings" type="button" role="tab" aria-controls="nav-bookings" aria-selected="false"><i class="fa-regular fa-calendar-days"></i> Reservas</button>
                    <button class="nav-link" id="nav-general-tab" data-bs-toggle="tab" data-bs-target="#nav-general" type="button" role="tab" aria-controls="nav-general" aria-selected="false"><i class="fa-solid fa-gear"></i> General</button>
                    <button class="nav-link" id="nav-reports-tab" data-bs-toggle="tab" data-bs-target="#nav-reports" type="button" role="tab" aria-controls="nav-reports" aria-selected="true"><i class="fa-solid fa-file-lines"></i> Reportes de cobro</button>
                    <button class="nav-link d-none" id="nav-fields-tab" data-bs-toggle="tab" data-bs-target="#nav-fields" type="button" role="tab" aria-controls="nav-fields" aria-selected="false"><i class="fa-brands fa-magento"></i> Laberinto</button>
                    <button class="nav-link" id="nav-values-tab" data-bs-toggle="tab" data-bs-target="#nav-values" type="button" role="tab" aria-controls="nav-values" aria-selected="false"><i class="fa-solid fa-dollar-sign"></i> Valores</button>

                    <?php if (session()->superadmin) : ?>
                        <button class="nav-link" id="nav-fields-tab" data-bs-toggle="tab" data-bs-target="#nav-fields" type="button" role="tab" aria-controls="nav-fields" aria-selected="false"><i class="fa-brands fa-magento"></i> Laberinto</button>
                        <button class="nav-link" id="nav-time-tab" data-bs-toggle="tab" data-bs-target="#nav-time" type="button" role="tab" aria-controls="nav-time" aria-selected="false"><i class="fa-regular fa-clock"></i> Horarios</button>
                        <button class="nav-link" id="nav-customers-tab" data-bs-toggle="tab" data-bs-target="#nav-customers" type="button" role="tab" aria-controls="nav-customers" aria-selected="false"><i class="fa-solid fa-user"></i> Clientes</button>
                    <?php endif; ?>

                </div>
            </nav>

            <div class="tab-content" id="nav-tabContent">

                <div class="tab-pane fade  show active" id="nav-bookings" role="tabpanel" aria-labelledby="nav-bookings-tab" tabindex="0">
                    <?= view('superadmin/tabBookings', ['bookings' => $bookings]) ?>
                </div>

                <div class="tab-pane fade" id="nav-general" role="tabpanel" aria-labelledby="nav-general-tab" tabindex="0">
                    <?= view('superadmin/tabGeneral', ['users' => $users]) ?>
                </div>

                <div class="tab-pane fade" id="nav-fields" role="tabpanel" aria-labelledby="nav-fields-tab" tabindex="0">
                    <?= view('superadmin/tabFields', ['fields' => $fields]) ?>
                </div>

                <div class="tab-pane fade" id="nav-values" role="tabpanel" aria-labelledby="nav-values-tab" tabindex="0">
                    <?= view('superadmin/tabValues', ['values' => $values]) ?>
                </div>

                <?php if (session()->superadmin) : ?>
                    <div class="tab-pane fade" id="nav-fields" role="tabpanel" aria-labelledby="nav-fields-tab" tabindex="0">
                        <?= view('superadmin/tabFields', ['fields' => $fields]) ?>
                    </div>

                    <div class="tab-pane fade" id="nav-time" role="tabpanel" aria-labelledby="nav-time-tab" tabindex="0">
                        <?= view('superadmin/tabTime') ?>
                    </div>

                    <div class="tab-pane fade" id="nav-customers" role="tabpanel" aria-labelledby="nav-customers-tab" tabindex="0">
                        <?= view('superadmin/tabCustomers', ['customers' => $customers]) ?>
                    </div>
                <?php endif; ?>

                <div class="tab-pane fade" id="nav-reports" role="tabpanel" aria-labelledby="nav-reports-tab" tabindex="0">
                    <?= view('superadmin/tabReports', ['users' => $users]) ?>
                </div>

            </div>
        </div>
    </div>
</div>

<!-- modal spinner -->
<div class="modal fade" id="spinnerCompletarPago" tabindex="-1" data-bs-backdrop="static" aria-labelledby="spinnerCompletarPagoLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered d-flex justify-content-center">

        <div class="d-flex justify-content-center align-items-center">
            <div class="spinner-border" style="width: 4rem; height: 4rem; color: #f39323" role="status">
                <span class="visually-hidden">Guardando pago...</span>
            </div>
        </div>
    </div>
</div>

<!-- Modal result payment-->
<div class="modal fade" id="modalResultPayment" tabindex="-1" data-bs-backdrop="static" aria-labelledby="modalResultPaymentLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content" id="paymentResult">

        </div>
    </div>
</div>

<!-- Modal generar reporte-->
<div class="modal fade" id="generateReportModal" data-bs-backdrop="static" tabindex="-1" aria-labelledby="generateReportModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h1 class="modal-title fs-5" id="generateReportModalLabel">Resumen</h1>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="paymentsMethodsResume">

                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Volver</button>
            </div>
        </div>
    </div>
</div>

<!-- Modal result -->
<div class="modal fade" id="modalResult" tabindex="-1" data-bs-backdrop="static" aria-labelledby="modalResultLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content" id="bookingEditResult">

        </div>
    </div>
</div>

<?php echo $this->endSection() ?>

<?php echo $this->section('footer') ?>
<?php echo $this->endSection() ?>

<?php echo $this->section('scripts') ?>

<script src="<?= base_url(PUBLIC_FOLDER . "assets/js/abmSuperadmin.js") ?>"></script>
<script src="<?= base_url(PUBLIC_FOLDER . "assets/js/searchBookings.js") ?>"></script>
<script src="<?= base_url(PUBLIC_FOLDER . "assets/js/searchReports.js") ?>"></script>
<script src="<?= base_url(PUBLIC_FOLDER . "assets/js/editReserva.js") ?>"></script>
<script src="<?= base_url(PUBLIC_FOLDER . "assets/js/customers.js") ?>"></script>

<?php echo $this->endSection() ?>