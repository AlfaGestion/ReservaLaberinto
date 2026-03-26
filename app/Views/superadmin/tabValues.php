<div class="fieldsButtons mt-3">
    <button type="submit" id="buttonCreateValue" class="btn btn-success"><i class="fa-solid fa-plus me-1"></i>Crear</button>
    <!-- <button type="submit" id="buttonEditValue" class="btn btn-warning"><i class="fa-solid fa-pen-to-square me-1"></i>Editar</button> -->
</div>

<div class="enterValues d-none" id="enterValues">
    <form action="<?= base_url('saveValue') ?>" method="POST">
        <div class="input-group mt-3 mb-3">
            <span class="input-group-text" id="basic-addon1">Nombre</span>
            <input type="text" class="form-control" id="serviceName" name="serviceName" placeholder="Ingrese el nombre" aria-label="Nombre servicio" aria-describedby="basic-addon1">
        </div>

        <div class="input-group mt-3 mb-3">
            <span class="input-group-text" id="basic-addon1">Valor</span>
            <input type="text" class="form-control" id="serviceAmount" name="serviceAmount" placeholder="Ingrese el valor" aria-label="Valor servicio" aria-describedby="basic-addon1">
        </div>

        <div class="input-group mt-3 mb-3">
            <input type="hidden" class="form-control" id="serviceValue" name="serviceValue" aria-label="Valor" aria-describedby="basic-addon1">
        </div>
        <div class="input-group mt-3 mb-3">
            <input type="hidden" class="form-control" id="idValue" name="idValue" aria-label="Valor" aria-describedby="basic-addon1">
        </div>


        <button type="submit" class="btn btn-success">Guardar</button>
        <a href="<?= base_url('abmAdmin') ?>" type="button" class="btn btn-danger">Cancelar</a>
    </form>
</div>


<div id="editValueDiv"></div>

<div class="valuesList mt-3">
    <h4>Tipos de valores</h4>
    <table class="table table-striped">
        <thead>
            <tr>
                <th scope="col">Nombre</th>
                <th scope="col">Valor</th>
                <th scope="col">Acciones</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($values as $value) : ?>
                <tr>
                    <td><?= isset($value['name']) ? $value['name'] : 'No indicado' ?></td>
                    <td><?= isset($value['amount']) ? $value['amount'] : 'No indicado' ?></td>
                    <td>
                        <button id="editValue" data-id="<?= $value['id'] ?>" data-name="<?= $value['name'] ?>" data-amount="<?= $value['amount'] ?>" class="btn btn-primary btn-sm mb-1"><i class="fa-solid fa-pen-to-square"></i></button>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>