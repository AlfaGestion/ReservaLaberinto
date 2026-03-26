<div class="fieldsButtons mt-3">
    <button type="submit" id="buttonCreateField" class="btn btn-success"><i class="fa-solid fa-plus me-1"></i>Crear servicio</button>
    <button type="submit" id="buttonEditField" class="btn btn-warning"><i class="fa-solid fa-pen-to-square me-1"></i>Editar</button>
</div>

<div class="enterFields d-none" id="enterFields">
    <form action="<?= base_url('saveField') ?>" method="POST">
        <div class="input-group mt-3 mb-3">
            <span class="input-group-text" id="basic-addon1">Nombre servicio</span>
            <input type="text" class="form-control" name="nombre" placeholder="Ingrese el nombre de la cancha" aria-label="Nombre servicio" aria-describedby="basic-addon1">
        </div>

        <!-- <div class="input-group mb-3">
            <span class="input-group-text">Valor sin iluminación</span>
            <input type="text" class="form-control" name="valor" placeholder="Ingrese valor por hora sin iluminación" aria-label="Valor">
        </div>

        <div class="input-group mb-3">
            <span class="input-group-text">Valor con iluminación</span>
            <input type="text" class="form-control" name="valorIluminacion" placeholder="Ingrese valor por hora con iluminación" aria-label="Valor">
        </div> -->

        <button type="submit" class="btn btn-success">Guardar</button>
        <a href="<?= base_url('abmAdmin') ?>" type="button" class="btn btn-danger">Cancelar</a>
    </form>
</div>

<div class="form-floating d-none mt-3" id="selectEditField">
    <select class="form-select" id="selectEditFields" aria-label="Floating label select example">
        <option value="">Seleccionar</option>
        <?php foreach ($fields as $field) : ?>
            <option value="<?= $field['id'] ?>"><?= $field['name'] ?></option>
        <?php endforeach; ?>
    </select>
    <label for="selectEditFields">Editar servicio</label>
</div>

<div id="editFieldDiv">

</div>