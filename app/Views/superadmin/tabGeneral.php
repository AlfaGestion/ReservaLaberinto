<div id="generalButtons" class="mt-3">
    <?php if (session()->superadmin) : ?>
        <a type="button" href="<?= base_url('auth/register') ?>" class="btn btn-success mt-2 mb-2" id=""><i class="fa-solid fa-user-plus me-1"></i>Crear usuario</a>
        <button type="button" class="btn btn-warning mt-2 mb-2" id="openRateModal" data-bs-toggle="modal" data-bs-target="#rateModal"><i class="fa-solid fa-percent me-1"></i>Editar porcentaje de reserva</button>
        <!-- <button type="button" class="btn btn-primary mt-2 mb-2" id="openOfferRateModal" data-bs-toggle="modal" data-bs-target="#offerRateModal"><i class="fa-solid fa-percent me-1"></i>Editar porcentaje de oferta</button> -->
        <!-- <a href="<?= base_url('upload') ?>" type="button" class="btn btn-info mt-2 mb-2" id=""><i class="fa-solid fa-file-arrow-up me-1"></i>Cambiar fondo</a> -->
        <button type="button" class="btn btn-primary mt-2 mb-2" id="openVisitorsQtyModal" data-bs-toggle="modal" data-bs-target="#visitorsQtyModal"><i class="fa-solid fa-users"></i>Min visitantes</button>
        <a href="<?= base_url('uploadLogo') ?>" type="button" class="btn btn-info mt-2 mb-2" id=""><i class="fa-solid fa-file-arrow-up me-1"></i>Personalizar</a>
        <a href="<?= base_url('configMpView') ?>" type="button" class="btn btn-light mt-2 mb-2" id=""><img src="<?= base_url(PUBLIC_FOLDER . 'assets/images/mercado-pago.jfif') ?>" alt="Icono Mercado Pago" width="10%" height="5%">Configurar Mercado Pago</a>
    <?php endif; ?>
</div>

<?php if (!session()->superadmin) : ?>
    <div class="table-responsive-sm">
        <table class="table align-middle table-striped-columns mt-2">
            <thead>
                <tr>
                    <th scope="col">Porcentaje de reserva</th>
                    <!-- <th scope="col">Porcentaje de oferta</th> -->
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td><?= $rate['value'] ?>%</td>
                </tr>
            </tbody>
        </table>
    </div>
<?php endif; ?>

<!-- Modal rate -->
<div class="modal fade" id="rateModal" tabindex="-1" aria-labelledby="rateModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h1 class="modal-title fs-5" id="rateModalLabel">Porcentaje de reserva</h1>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="input-group mb-3">
                    <span class="input-group-text" id="basic-addon1">%</span>
                    <?php if ($rate) : ?>
                        <input type="text" class="form-control" placeholder="Ingresar porcentaje" name="rate" id="rate" aria-label="rate" aria-describedby="basic-addon1" value="<?= $rate['value'] ?>">
                    <?php else : ?>
                        <input type="text" class="form-control" placeholder="Ingresar porcentaje" name="rate" id="rate" aria-label="rate" aria-describedby="basic-addon1">
                    <?php endif; ?>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Volver</button>
                <button type="button" class="btn btn-primary" id="saveRate">Guardar</button>
            </div>
        </div>
    </div>
</div>

<!-- Modal offer rate -->
<div class="modal fade" id="offerRateModal" tabindex="-1" aria-labelledby="offerRateModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h1 class="modal-title fs-5" id="offerRateModalLabel">Porcentaje de oferta</h1>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="input-group mb-3">
                    <span class="input-group-text" id="basic-addon1">%</span>
                    <?php if ($offerRate) : ?>
                        <input type="text" class="form-control" placeholder="Ingresar porcentaje" name="offerRate" id="offerRate" aria-label="offerRate" aria-describedby="basic-addon1" value="<?= $offerRate['value'] ?>">
                    <?php else : ?>
                        <input type="text" class="form-control" placeholder="Ingresar porcentaje" name="offerRate" id="offerRate" aria-label="offerRate" aria-describedby="basic-addon1">
                    <?php endif; ?>
                </div>

                <div class="form-floating">
                    <textarea class="form-control" placeholder="Leave a comment here" id="descriptionOffer"></textarea>
                    <label for="descriptionOffer">Añadir una descripción a la oferta</label>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Volver</button>
                <button type="button" class="btn btn-primary" id="saveOfferRate">Guardar</button>
            </div>
        </div>
    </div>
</div>

<!-- Modal visitors qty -->
<div class="modal fade" id="visitorsQtyModal" tabindex="-1" aria-labelledby="visitorsQtyModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h1 class="modal-title fs-5" id="visitorsQtyModalLabel">Máximo de visitantes</h1>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="input-group mb-3">
                    <?php if ($offerRate) : ?>
                        <input type="text" class="form-control" placeholder="Máximo de visitantes" name="visitors" id="visitors" aria-label="visitors" aria-describedby="basic-addon1" value="<?= $rate['qty_visitors'] ?>">
                    <?php else : ?>
                        <input type="text" class="form-control" placeholder="Máximo de visitantes" name="visitors" id="visitors" aria-label="visitors" aria-describedby="basic-addon1">
                    <?php endif; ?>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Volver</button>
                <button type="button" class="btn btn-primary" id="saveRate">Guardar</button>
            </div>
        </div>
    </div>
</div>


<?php if (session()->superadmin) : ?>
    <div class="table-responsive-sm" id="tableCustomers">
        <table class="table align-middle table-striped-columns mt-2">
            <thead>
                <tr>
                    <th scope="col">Usuario</th>
                    <th scope="col">Nombre</th>
                    <th scope="col">Superadmin</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($users as $user) : ?>
                    <?php if ($user['user'] !== 'testuser') : ?>
                        <tr>
                            <td><?= $user['user'] ?></td>
                            <td><?= $user['name'] ?></td>
                            <td><?= $user['superadmin'] == 1 ? 'Si' : 'No' ?></td>
                        </tr>
                    <?php endif; ?>
                <?php endforeach; ?>

            </tbody>
        </table>
    </div>
<?php endif; ?>