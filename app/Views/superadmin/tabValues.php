<div class="admin-toolbar">
    <div class="admin-toolbar__actions">
        <button type="button" id="buttonCreateValue" class="btn btn-success"><i class="fa-solid fa-plus me-1"></i>Crear</button>
    </div>
</div>

<div class="valuesList mt-3">
    <h4>Tipos de valores</h4>
    <div class="table-responsive-sm">
        <table class="table table-striped">
            <thead>
                <tr>
                    <th scope="col">Nombre</th>
                    <th scope="col">Dto %</th>
                    <th scope="col">Valor</th>
                    <th scope="col">Importe</th>
                    <th scope="col">Acciones</th>
                </tr>
            </thead>
            <tbody id="valuesTableBody">
                <?php foreach ($values as $value) : ?>
                    <?php
                    $amount = isset($value['amount']) ? (float) $value['amount'] : 0;
                    $discountPercentage = isset($value['discount_percentage']) ? (float) $value['discount_percentage'] : 0;
                    $finalAmount = $amount - (($amount * $discountPercentage) / 100);
                    ?>
                    <tr id="value-row-<?= $value['id'] ?>">
                        <td><?= isset($value['name']) ? $value['name'] : 'No indicado' ?></td>
                        <td><?= isset($value['discount_percentage']) ? $value['discount_percentage'] : '0' ?>%</td>
                        <td><?= isset($value['amount']) ? $value['amount'] : 'No indicado' ?></td>
                        <td><?= number_format($finalAmount, 2, '.', '') ?></td>
                        <td>
                            <button
                                type="button"
                                class="btn btn-primary btn-sm mb-1 value-edit-trigger"
                                data-id="<?= $value['id'] ?>"
                                data-name="<?= $value['name'] ?>"
                                data-amount="<?= $value['amount'] ?>"
                                data-discount-percentage="<?= isset($value['discount_percentage']) ? $value['discount_percentage'] : '0' ?>"
                                data-value="<?= $value['value'] ?>"
                                >
                                <i class="fa-solid fa-pen-to-square"></i>
                            </button>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<div class="modal fade" id="valueModal" tabindex="-1" aria-labelledby="valueModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <style>
                #valueModal .input-group-text {
                    width: 96px;
                    justify-content: flex-start;
                }
            </style>
            <form action="<?= base_url('saveValue') ?>" method="POST" id="valueForm">
                <div class="modal-header">
                    <h5 class="modal-title" id="valueModalLabel">Valor</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="input-group mt-3 mb-3">
                        <span class="input-group-text">Nombre</span>
                        <input type="text" class="form-control" id="serviceName" name="serviceName" placeholder="Ingrese el nombre" aria-label="Nombre servicio">
                    </div>

                    <div class="input-group mt-3 mb-3">
                        <span class="input-group-text">Dto %</span>
                        <input type="number" class="form-control" id="serviceDiscountPercentage" name="serviceDiscountPercentage" placeholder="Ingrese el dto" aria-label="Dto servicio" min="0" max="100" step="0.01">
                    </div>

                    <div class="input-group mt-3 mb-3">
                        <span class="input-group-text">Valor</span>
                        <input type="text" class="form-control" id="serviceAmount" name="serviceAmount" placeholder="Ingrese el valor" aria-label="Valor servicio">
                    </div>

                    <div class="input-group mt-3 mb-3">
                        <span class="input-group-text">Importe</span>
                        <input type="text" class="form-control" id="serviceFinalAmount" placeholder="" aria-label="Importe final" readonly>
                    </div>

                    <input type="hidden" class="form-control" id="serviceValue" name="serviceValue" aria-label="Valor">
                    <input type="hidden" class="form-control" id="idValue" name="idValue" aria-label="Valor">
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-success">Guardar</button>
                </div>
            </form>
        </div>
    </div>
</div>
