<div class="admin-toolbar">
    <div class="admin-toolbar__actions">
        <button type="button" id="buttonCreateField" class="btn btn-success"><i class="fa-solid fa-plus me-1"></i>Crear servicio</button>
    </div>
</div>

<div class="fieldsList mt-3">
    <h4>Servicios</h4>
    <div class="table-responsive-sm">
        <table class="table table-striped">
            <thead>
                <tr>
                    <th scope="col">Nombre</th>
                    <th scope="col">Estado</th>
                    <th scope="col">Acciones</th>
                </tr>
            </thead>
            <tbody id="fieldsTableBody">
                <?php foreach ($fields as $field) : ?>
                    <tr id="field-row-<?= $field['id'] ?>">
                        <td><?= isset($field['name']) ? $field['name'] : 'No indicado' ?></td>
                        <td><?= !empty($field['disabled']) ? 'Deshabilitado' : 'Activo' ?></td>
                        <td>
                            <button
                                type="button"
                                class="btn btn-primary btn-sm mb-1 field-edit-trigger"
                                data-id="<?= $field['id'] ?>"
                                data-name="<?= $field['name'] ?>"
                                data-disabled="<?= !empty($field['disabled']) ? 1 : 0 ?>">
                                <i class="fa-solid fa-pen-to-square"></i>
                            </button>
                            <button
                                type="button"
                                class="btn btn-danger btn-sm mb-1 field-delete-trigger"
                                data-id="<?= $field['id'] ?>">
                                <i class="fa-solid fa-trash"></i>
                            </button>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<div class="modal fade" id="fieldModal" tabindex="-1" aria-labelledby="fieldModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <form action="<?= base_url('saveField') ?>" method="POST" id="fieldForm">
                <div class="modal-header">
                    <h5 class="modal-title" id="fieldModalLabel">Servicio</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="input-group mt-3 mb-3">
                        <span class="input-group-text">Nombre</span>
                        <input type="text" class="form-control" id="fieldName" name="nombre" placeholder="Ingrese el nombre del servicio" aria-label="Nombre servicio">
                    </div>

                    <div class="form-check form-switch mt-4 mb-4">
                        <input class="form-check-input" type="checkbox" role="switch" name="disabled" id="disableField">
                        <label class="form-check-label" for="disableField">Deshabilitar</label>
                    </div>

                    <input type="hidden" name="medidas" value="-">
                    <input type="hidden" name="tipoPiso" value="-">
                    <input type="hidden" name="tipoCancha" value="-">
                    <input type="hidden" name="valor" value="0">
                    <input type="hidden" name="valorIluminacion" value="0">
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-success">Guardar</button>
                </div>
            </form>
        </div>
    </div>
</div>
