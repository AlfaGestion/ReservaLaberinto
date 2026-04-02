<?php

use App\Models\MercadoPagoKeysModel;
use App\Models\UploadModel;

$mpKeysModel = new MercadoPagoKeysModel();
$mpKeys = $mpKeysModel->first();

$uploadModel = new UploadModel();
$uploadData = $uploadModel->first();

?>

<div class="nav nav-tabs admin-subtabs" id="general-subtab" role="tablist">
    <button class="nav-link active" id="general-users-tab" data-bs-toggle="tab" data-bs-target="#general-users-pane" type="button" role="tab" aria-controls="general-users-pane" aria-selected="true">Usuarios</button>
    <button class="nav-link" id="general-config-tab" data-bs-toggle="tab" data-bs-target="#general-config-pane" type="button" role="tab" aria-controls="general-config-pane" aria-selected="false">Config web</button>
</div>

<div class="tab-content">
    <div class="tab-pane fade show active" id="general-users-pane" role="tabpanel" aria-labelledby="general-users-tab" tabindex="0">
        <div class="admin-toolbar">
            <div class="admin-toolbar__actions">
                <?php if (session()->superadmin) : ?>
                    <button type="button" class="btn btn-success" id="buttonCreateUser"><i class="fa-solid fa-user-plus me-1"></i>Crear usuario</button>
                <?php endif; ?>
            </div>
        </div>

        <?php if (session()->superadmin) : ?>
            <div class="table-responsive-sm" id="tableUsers">
                <table class="table align-middle table-striped-columns mt-2">
                    <thead>
                        <tr>
                            <th scope="col">Usuario</th>
                            <th scope="col">Nombre</th>
                            <th scope="col">Superadmin</th>
                            <th scope="col">Estado</th>
                            <th scope="col">Acciones</th>
                        </tr>
                    </thead>
                    <tbody id="usersTableBody">
                        <?php foreach ($users as $user) : ?>
                            <?php if ($user['user'] !== 'testuser') : ?>
                                <tr id="user-row-<?= $user['id'] ?>">
                                    <td><?= $user['user'] ?></td>
                                    <td><?= $user['name'] ?></td>
                                    <td><?= $user['superadmin'] == 1 ? 'Si' : 'No' ?></td>
                                    <td><?= !empty($user['active']) ? 'Activo' : 'Inactivo' ?></td>
                                    <td>
                                        <button
                                            type="button"
                                            class="btn btn-primary btn-sm mb-1 user-edit-trigger"
                                            data-id="<?= $user['id'] ?>"
                                            data-user="<?= esc($user['user']) ?>"
                                            data-name="<?= esc($user['name']) ?>"
                                            data-superadmin="<?= $user['superadmin'] == 1 ? 1 : 0 ?>"
                                            data-active="<?= !empty($user['active']) ? 1 : 0 ?>">
                                            <i class="fa-solid fa-pen-to-square"></i>
                                        </button>
                                        <button
                                            type="button"
                                            class="btn btn-danger btn-sm mb-1 user-delete-trigger"
                                            data-id="<?= $user['id'] ?>">
                                            <i class="fa-solid fa-trash"></i>
                                        </button>
                                    </td>
                                </tr>
                            <?php endif; ?>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>

    <div class="tab-pane fade" id="general-config-pane" role="tabpanel" aria-labelledby="general-config-tab" tabindex="0">
        <div class="nav nav-tabs admin-subtabs" id="config-web-subtab" role="tablist">
            <button class="nav-link active" id="config-rate-tab" data-bs-toggle="tab" data-bs-target="#config-rate-pane" type="button" role="tab" aria-controls="config-rate-pane" aria-selected="true">Porcentaje de reserva</button>
            <button class="nav-link" id="config-mp-tab" data-bs-toggle="tab" data-bs-target="#config-mp-pane" type="button" role="tab" aria-controls="config-mp-pane" aria-selected="false">Mercado Pago</button>
            <button class="nav-link" id="config-branding-tab" data-bs-toggle="tab" data-bs-target="#config-branding-pane" type="button" role="tab" aria-controls="config-branding-pane" aria-selected="false">Personalizar</button>
            <button class="nav-link" id="config-general-tab" data-bs-toggle="tab" data-bs-target="#config-general-pane" type="button" role="tab" aria-controls="config-general-pane" aria-selected="false">General</button>
        </div>

        <div class="tab-content">
            <div class="tab-pane fade show active" id="config-rate-pane" role="tabpanel" aria-labelledby="config-rate-tab" tabindex="0">
                <div class="admin-section-card">
                    <h5 class="mb-2">Porcentaje de reserva</h5>
                    <p class="text-muted mb-3">Define que porcentaje del total se cobra como reserva inicial cuando un cliente confirma una visita.</p>

                    <div class="input-group mb-3" style="max-width: 320px;">
                        <span class="input-group-text">%</span>
                        <?php if ($rate) : ?>
                            <input type="text" class="form-control" placeholder="Ingresar porcentaje" name="rate" id="rate" aria-label="rate" value="<?= $rate['value'] ?>">
                        <?php else : ?>
                            <input type="text" class="form-control" placeholder="Ingresar porcentaje" name="rate" id="rate" aria-label="rate">
                        <?php endif; ?>
                    </div>

                    <button type="button" class="btn btn-primary" id="saveRateSettings">Guardar</button>
                </div>
            </div>

            <div class="tab-pane fade" id="config-mp-pane" role="tabpanel" aria-labelledby="config-mp-tab" tabindex="0">
                <div class="admin-section-card">
                    <h5 class="mb-3">Mercado Pago</h5>
                    <form action="<?= base_url('configMp') ?>" method="POST">
                        <div class="form-floating flex-nowrap mb-3">
                            <input type="text" class="form-control" name="publicKeyMp" id="publicKeyMpGeneral" placeholder="" value="<?= !is_null($mpKeys) ? $mpKeys['public_key'] : '' ?>" aria-label="Public Key" required>
                            <label for="publicKeyMpGeneral">Public Key</label>
                        </div>

                        <div class="form-floating flex-nowrap mb-3">
                            <input type="text" class="form-control" name="accesTokenMp" id="accesTokenMpGeneral" placeholder="" value="<?= !is_null($mpKeys) ? $mpKeys['access_token'] : '' ?>" aria-label="Access Token" required>
                            <label for="accesTokenMpGeneral">Access Token</label>
                        </div>

                        <button type="submit" class="btn btn-primary">Guardar</button>
                    </form>
                </div>
            </div>

            <div class="tab-pane fade" id="config-branding-pane" role="tabpanel" aria-labelledby="config-branding-tab" tabindex="0">
                <div class="admin-section-card">
                    <h5 class="mb-3">Personalizar</h5>
                    <form action="<?= base_url('upload/upload') ?>" method="POST" enctype="multipart/form-data">
                        <div class="row g-3 mb-3">
                            <div class="col-md-6">
                                <label class="form-label">Color primario</label>
                                <input type="color" class="form-control form-control-color" name="mainColor" value="<?= isset($uploadData['main_color']) ? $uploadData['main_color'] : '#ff0000' ?>" title="Color primario">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Color secundario</label>
                                <input type="color" class="form-control form-control-color" name="secondaryColor" value="<?= isset($uploadData['secondary_color']) ? $uploadData['secondary_color'] : '#ff0000' ?>" title="Color secundario">
                            </div>
                        </div>

                        <div id="formUpload">
                            <input type="file" name="userfile" size="20" class="form-control">
                            <label for="userfile">Seleccione un archivo o arrastre la imagen dentro de la linea punteada</label>
                        </div>

                        <div class="mt-4">
                            <button type="submit" class="btn btn-primary">Guardar</button>
                        </div>
                    </form>
                </div>
            </div>

            <div class="tab-pane fade" id="config-general-pane" role="tabpanel" aria-labelledby="config-general-tab" tabindex="0">
                <div class="admin-section-card">
                    <h5 class="mb-2">General</h5>
                    <p class="text-muted mb-3">Configura la cantidad minima de visitantes para habilitar una reserva y los emails donde se enviaran las reservas recibidas.</p>

                    <div class="input-group mb-3" style="max-width: 320px;">
                        <?php if (is_array($rate) && isset($rate['qty_visitors'])) : ?>
                            <input type="text" class="form-control" placeholder="Min visitantes" name="visitors" id="visitors" aria-label="visitors" value="<?= esc($rate['qty_visitors']) ?>">
                        <?php else : ?>
                            <input type="text" class="form-control" placeholder="Min visitantes" name="visitors" id="visitors" aria-label="visitors">
                        <?php endif; ?>
                    </div>

                    <div class="form-floating mb-2" style="max-width: 520px;">
                        <input type="text" class="form-control" id="notificationEmail" placeholder="email1@dominio.com; email2@dominio.com" value="<?= esc($uploadData['notification_email'] ?? '') ?>">
                        <label for="notificationEmail">Emails para reservas recibidas</label>
                    </div>
                    <small class="text-muted d-block mb-3">Podes cargar uno o varios emails separados por ;</small>

                    <button type="button" class="btn btn-primary" id="saveGeneralSettings">Guardar</button>
                </div>
            </div>
        </div>
    </div>
</div>

<?php if (session()->superadmin) : ?>
    <div class="modal fade" id="userModal" tabindex="-1" aria-labelledby="userModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h1 class="modal-title fs-5" id="userModalLabel">Usuario</h1>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form id="userForm">
                        <input type="hidden" id="userIdField">

                        <div class="input-group mb-3">
                            <span class="input-group-text" style="min-width: 120px;">Usuario</span>
                            <input type="text" class="form-control" id="userUsername" placeholder="Ingresar usuario">
                        </div>

                        <div class="input-group mb-3">
                            <span class="input-group-text" style="min-width: 120px;">Nombre</span>
                            <input type="text" class="form-control" id="userDisplayName" placeholder="Ingresar nombre">
                        </div>

                        <div class="input-group mb-3">
                            <span class="input-group-text" style="min-width: 120px;">Contrasena</span>
                            <input type="password" class="form-control" id="userPassword" placeholder="Ingresar contrasena">
                        </div>

                        <div class="input-group mb-3" id="userRepeatPasswordGroup">
                            <span class="input-group-text" style="min-width: 120px;">Repetir</span>
                            <input type="password" class="form-control" id="userRepeatPassword" placeholder="Repetir contrasena">
                        </div>

                        <div class="form-check form-switch mb-2">
                            <input class="form-check-input" type="checkbox" id="userSuperadmin">
                            <label class="form-check-label" for="userSuperadmin">Superadmin</label>
                        </div>

                        <div class="form-check form-switch">
                            <input class="form-check-input" type="checkbox" id="userActive" checked>
                            <label class="form-check-label" for="userActive">Activo</label>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="button" class="btn btn-primary" id="saveUserButton">Guardar</button>
                </div>
            </div>
        </div>
    </div>
<?php endif; ?>
