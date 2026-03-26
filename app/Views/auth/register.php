<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registro</title>
    <link rel="stylesheet" href="<?= base_url(PUBLIC_FOLDER . "assets/css/styles.css") ?>">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-rbsA2VBKQhggwzxH7pPCaAqO46MgnOM80zW1RWuH61DGLwZJEdK2Kadq2F9CUG65" crossorigin="anonymous">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-kenU1KFdBIe4zVF0s0G1M5b4hcpxyD9F7jL+jjXkk+Q2h455rYXK/7HAuoJl+0I4" crossorigin="anonymous"></script>
    <script src="https://kit.fontawesome.com/9bae38f407.js" crossorigin="anonymous"></script>
    <link rel="icon" href="<?= base_url(PUBLIC_FOLDER . "assets/images/favicon.ico") ?>" type="image/x-icon">


</head>

<?php

use App\Models\UploadModel;

$modelUploads = new UploadModel();
$userData = $modelUploads->first();

?>

<body style="background-color: #f8f9fa;">
    <div class="container login-page d-flex justify-content-center align-items-center">
        <div class="login-box">

            <div class="login-box-body">
                <div class="login-logo d-flex justify-content-center align-items-center">
                    <a href="<?= base_url() ?>"><img src="<?= isset($userData['name']) ? base_url(PUBLIC_FOLDER . "assets/images/uploads/" . $userData['name']) : base_url(PUBLIC_FOLDER . "assets/images/sinlogo2.png") ?>" width="300px" alt=""></a>
                </div>

                <select class="form-select mt-3 mb-3" name="users" id="selectUser" aria-label="Select Usuarios">
                    <option>Seleccionar para editar</option>
                    <?php if (isset($users)) : ?>
                        <?php foreach ($users as $user) : ?>
                            <option value="<?= $user['id'] ?>"><?= $user['name'] ?></option>
                        <?php endforeach; ?>
                    <?php endif; ?>

                </select>

                <form action="" method="POST" id="formUsers" class="">

                    <?php if (session('msg')) : ?>
                        <div class="alert alert-<?= session('msg.type') ?> alert-dismissible fade show" role="alert">
                            <small> <?= session('msg.body') ?> </small>
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>
                    <?php endif; ?>

                    <div class="form-group has-feedback mb-3 d-flex align-items-center justify-content-center">
                        <input type="text" name="user" class="form-control" placeholder="Usuario">
                    </div>

                    <div class="form-group has-feedback mb-3 d-flex align-items-center justify-content-center">
                        <input type="password" name="password" class="form-control" placeholder="Contraseña">
                    </div>

                    <div class="form-group has-feedback mb-3 d-flex align-items-center justify-content-center">
                        <input type="password" name="repeat_password" class="form-control" placeholder="Repetir contraseña">
                    </div>

                    <div class="form-group has-feedback mb-3 d-flex align-items-center justify-content-center">
                        <input type="text" name="name" class="form-control" placeholder="Nombre">
                    </div>

                    <div class="form-check mb-2">
                        <input class="form-check-input" type="checkbox" name="superadmin" id="superadminRadio">
                        <label class="form-check-label" for="superadmin">
                            Superadmin
                        </label>
                    </div>

                    <div class="row d-flex align-items-center justify-content-center flex-nowrap flex-row">
                        <div class="col d-flex align-items-center justify-content-center">
                            <a href="<?= base_url('abmAdmin') ?>" style="color: #fff; background-color: <?= isset($userData) ? $userData['main_color'] : '#0064b0' ?>;" class="btn btn-block btn-flat me-2">Volver</a>
                            <button type="submit" class="btn btn-block btn-flat" style="color: #fff; background-color: <?= isset($userData) ? $userData['secondary_color'] : '#0064b0' ?>;" id="btn-login">Registrar</button>
                        </div>
                    </div>

                </form>

                <form action="" method="POST" id="formselectUser">


                </form>

                <div class="row d-flex align-items-center justify-content-center flex-nowrap flex-row d-none" id="formButtons">
                    <div class="col d-flex align-items-center justify-content-center">
                        <button type="submit" class="btn btn-block btn-flat me-2" id="buttonEdit" style="color: #fff; background-color: <?= isset($userData) ? $userData['main_color'] : '#0064b0' ?>;" id="btn-login">Actualizar</button>
                        <a href="<?= base_url('abmAdmin') ?>" style="color: #fff; background-color: <?= isset($userData) ? $userData['secondary_color'] : '#0064b0' ?>;" class="btn btn-block btn-flat me-2">Volver</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <script src="<?= base_url(PUBLIC_FOLDER . "assets/js/config.js") ?>"></script>
    <script src="<?= base_url(PUBLIC_FOLDER . "assets/js/users.js") ?>"></script>


</body>

</html>