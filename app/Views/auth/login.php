<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login</title>
    <!-- <link rel="stylesheet" href="<?= base_url(PUBLIC_FOLDER . "assets/css/login-page.css") ?>"> -->
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

<body style="background-color: #ffffff;">

    <div class="container login-page d-flex justify-content-center align-items-center">

        <div class="login-box d-flex justify-content-center flex-column align-items-center" style="margin-top: 10%;">
            <div class="login-logo">
                <a href="<?= base_url() ?>"><img src="<?= isset($logo) ? base_url(PUBLIC_FOLDER . "assets/images/uploads/" . $logo['name']) : base_url(PUBLIC_FOLDER . "assets/images/sinlogo2.png") ?>" width="300px" alt=""></a>
            </div>
            <h1 style="font-family:'Franklin Gothic Medium', 'Arial Narrow', Arial, sans-serif; color: #595959">Inicio de sesión</h1>


            <div class="login-box-body">
                <form action="" method="POST">

                    <?php if (session('msg')) : ?>
                        <div class="alert alert-<?= session('msg.type') ?> alert-dismissible fade show" role="alert">
                            <small> <?= session('msg.body') ?> </small>
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>
                    <?php endif; ?>

                    <div class="form-floating  has-feedback mb-3 d-flex align-items-center justify-content-center">
                        <input type="text" class="form-control" name="user" placeholder="Usuario" style="width: 300px;">
                        <label for="floatingInput">Usuario</label>
                        <span class="ms-2"><i class="fa-solid fa-user"></i></span>
                    </div>

                    <div class="form-floating  has-feedback mb-3 d-flex align-items-center justify-content-center">
                        <input type="password" name="password" class="form-control" placeholder="Contraseña">
                        <label for="floatingInput">Contraseña</label>
                        <span class="ms-2"><i class="fa-solid fa-lock"></i></span>
                    </div>

                    <div class="row d-flex align-items-center justify-content-center flex-nowrap flex-row">
                        <!-- <div class="col">
                            <div class="checkbox icheck">
                                <label class="">
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" value="" id="flexCheckDefault">
                                        <label class="form-check-label" for="flexCheckDefault">Recordarme </label>
                                    </div>
                                </label>
                            </div>
                        </div> -->

                        <div class="col d-flex align-items-center justify-content-center flex-column mb-4   ">
                            <button type="submit" class="btn btn-block btn-flat" style="color: #fff; background-color: <?= isset($userData) ? $userData['main_color'] : '#0064b0' ?>;" id="btn-login">Ingresar</button>
                        </div>

                    </div>

                </form>
            </div>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.4.1.slim.min.js" integrity="sha384-J6qa4849blE2+poT4WnyKhv5vZF5SrPo0iEjwBvKU7imGFAV0wwj1yYfoRSJoZ+n" crossorigin="anonymous"></script>
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.11.6/dist/umd/popper.min.js" integrity="sha384-oBqDVmMz9ATKxIep9tiCxS/Z9fNfEXiDAYTujMAeBAsjFuCZSmKbSSUnQlmh/jp3" crossorigin="anonymous"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/js/bootstrap.min.js" integrity="sha384-cuYeSxntonz0PPNlHhBs68uyIAVpIIOZZ5JqeqvYYIcEL727kskC66kF92t6Xl2V" crossorigin="anonymous"></script>
</body>

</html>