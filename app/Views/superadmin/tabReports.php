<?php if (session('msg')) : ?>
    <div class="alert alert-<?= session('msg.type') ?> alert-dismissible fade show" role="alert">
        <small> <?= session('msg.body') ?> </small>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
<?php endif; ?>

<div id="selectDateReport" class="d-flex flex-column justify-content-center align-items-center">

    <div class="form-check form-switch mt-3">
        <input class="form-check-input" type="checkbox" role="switch" id="checkPaymetsMp">
        <label class="form-check-label" for="checkPaymetsMp">Ver ingresos de reservas</label>
    </div>

    <div class="d-flex justify-content-center align-items-center flex-lg-row flex-column">

        <div class="form-floating mb-3 mt-3 me-2">
            <select class="form-select" id="selectDateRange" aria-label="Floating label select example">
                <option value="">Seleccionar</option>
                <option value="FD">Fecha del día</option>
                <option value="MA">Mes actual</option>
                <option value="MP">Mes pasado</option>
                <option value="SA">Semana actual</option>
                <option value="SP">Semana pasada</option>
            </select>
            <label for="selectDateRange">Seleccione rango</label>
        </div>

        <div class="d-flex justify-content-center align-items-center flex-lg-row flex-row">

            <div class="form-floating mb-3 mt-3 me-2">
                <input type="date" name="buscarFechaDesde" id="buscarFechaDesde" class="form-control" value="" aria-label="date">
                <label for="buscarFechaDesde">Desde</label>
            </div>

            <div class="form-floating mb-3 mt-3 me-2">
                <input type="date" name="buscarFechaHasta" id="buscarFechaHasta" class="form-control" value="" aria-label="date">
                <label for="buscarFechaHasta">Hasta</label>
            </div>

        </div>

        <div class="d-flex justify-content-center align-items-center flex-lg-row flex-row">

            <div class="form-floating mb-3 mt-3 me-2">
                <select class="form-select" id="selectUserReport" aria-label="Floating label select example">
                    <option value="">Todos</option>
                    <?php foreach ($users as $user) : ?>
                        <option value="<?= $user['id'] ?>"><?= $user['name'] ?></option>
                    <?php endforeach; ?>
                </select>
                <label for="selectUserReport">Usuario</label>
            </div>

            <div>
                <button type="button" id="searchReports" class="btn btn-success btn-sm ms-2">Buscar</button>
            </div>
            <div>
                <button type="button" id="reservePayments" class="btn btn-primary btn-sm ms-2 d-none">Buscar</button>
            </div>

        </div>
        <div>
            <!-- <button type="button" id="generateReport" data-bs-toggle="modal" data-bs-target="#generateReportModal" class="btn btn-warning btn-sm ms-2 d-none">Ver resumen</button> -->
            <button type="button" id="downloadReport" class="btn btn-danger btn-sm ms-2 d-none">Descargar PDF</button>
            <button type="button" id="downloadPaymentsReport" class="btn btn-danger btn-sm ms-2 d-none">Descargar PDF</button>
        </div>
    </div>
</div>

<div class="table-responsive d-none" id="tableReports">
    <table class="table align-middle table-striped-columns mt-2">
        <thead>
            <tr>
                <th scope="col">Fecha</th>
                <th scope="col">Usuario</th>
                <th scope="col">Pago</th>
                <th scope="col">Método de pago</th>
                <th scope="col">Nombre</th>
                <th scope="col">Teléfono</th>
            </tr>
        </thead>
        <tbody class="divTr">

        </tbody>
    </table>
</div>

<div class="table-responsive d-none" id="tableReservations">
    <table class="table align-middle table-striped-columns mt-2">
        <thead>
            <tr>
                <th scope="col">Fecha</th>
                <th scope="col">Total reservas del día</th>
            </tr>
        </thead>
        <tbody class="divReservas">

        </tbody>
    </table>
</div>