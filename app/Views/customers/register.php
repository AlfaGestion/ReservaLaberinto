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

            <div class="login-box-body d-flex flex-column justify-content-center align-items-center">
                <div class="login-logo">
                    <a href="<?= base_url() ?>"><img src="<?= isset($userData) ? base_url(PUBLIC_FOLDER . "assets/images/uploads/" . $userData['name']) : base_url(PUBLIC_FOLDER . "assets/images/sinlogo2.png") ?>" width="200px" alt=""></a>
                </div>

                <form action="" method="POST">

                    <?php if (session('msg')) : ?>
                        <div class="alert alert-<?= session('msg.type') ?> alert-dismissible fade show" role="alert">
                            <small> <?= session('msg.body') ?> </small>
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>
                    <?php endif; ?>

                    <h1 style="color: #595959; font-family:'Franklin Gothic Medium', 'Arial Narrow', Arial, sans-serif;" class="text-center">Registrate</h1>

                    <div class="form-group has-feedback mb-3 d-flex align-items-center justify-content-center">
                        <input type="text" name="name" class="form-control" placeholder="Nombre institución">
                    </div>

                    <!-- <div class="form-group has-feedback mb-3 d-flex align-items-center justify-content-center">
                        <input type="text" name="last_name" class="form-control" placeholder="Apellido">
                    </div> -->

                    <div class="form-group has-feedback mb-3 d-flex align-items-center justify-content-center">
                        <input type="text" name="dni" class="form-control" placeholder="CUIT/CUIL">
                    </div>

                    <div class="form-floating mb-3">
                        <select class="form-select" id="typeInstitution" name="type_institution" aria-label="Floating label select example">
                            <option value="">Seleccionar</option>
                            <?php foreach ($types as $type) : ?>
                                <option value="<?= $type['value'] ?>"><?= $type['name'] ?></option>
                            <?php endforeach; ?>
                        </select>
                        <label for="typeInstitution">Tipo de institución</label>
                    </div>

                    <div class="form-group has-feedback mb-3 d-flex align-items-center justify-content-center">
                        <input type="text" name="city" class="form-control" placeholder="Localidad">
                    </div>

                    <div class="form-group has-feedback mb-3 d-flex align-items-center justify-content-center">
                        <input type="text" name="email" class="form-control" placeholder="Email">
                    </div>

                    <div class="d-flex justify-content-center align-items-center flex-row" style="width: 100%;">
                        <div class="form-group has-feedback mb-3 d-flex align-items-center justify-content-center" style="width: 30%;">
                            <input type="text" name="areaCode" class="form-control" placeholder="Cod de área">
                        </div>
                        <div class="form-group has-feedback mb-3 d-flex align-items-center justify-content-center" style="width: 70%;">
                            <input type="text" name="phone" class="form-control" placeholder="Teléfono">
                        </div>
                    </div>

                    <div class="row d-flex align-items-center justify-content-center flex-nowrap flex-row">
                        <div class="col d-flex align-items-end justify-content-end">
                            <a href="<?= base_url('abmAdmin') ?>" style="color: #fff; background-color: <?= isset($userData) ? $userData['secondary_color'] : '#5a5a5a' ?>;" class="btn btn-block btn-flat me-2">Volver</a>
                            <button type="submit" class="btn btn-block btn-flat" style="color: #fff; background-color: <?= isset($userData) ? $userData['main_color'] : '#0064b0' ?>;" id="btn-login">Registrar</button>
                        </div>
                    </div>

                </form>
            </div>
        </div>
    </div>

</body>

</html>