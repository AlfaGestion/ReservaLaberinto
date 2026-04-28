<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <?php

    use App\Models\UploadModel;

    echo $this->renderSection('title') ?>
    <title>Laberinto Patagonia - Reservas</title>

    <link rel="icon" href="<?= base_url(PUBLIC_FOLDER . "assets/images/favicon.ico") ?>" type="image/x-icon">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-rbsA2VBKQhggwzxH7pPCaAqO46MgnOM80zW1RWuH61DGLwZJEdK2Kadq2F9CUG65" crossorigin="anonymous">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-kenU1KFdBIe4zVF0s0G1M5b4hcpxyD9F7jL+jjXkk+Q2h455rYXK/7HAuoJl+0I4" crossorigin="anonymous"></script>
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.11.6/dist/umd/popper.min.js" integrity="sha384-oBqDVmMz9ATKxIep9tiCxS/Z9fNfEXiDAYTujMAeBAsjFuCZSmKbSSUnQlmh/jp3" crossorigin="anonymous"></script>
    <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
    <link rel="stylesheet" href="<?= base_url(PUBLIC_FOLDER . "assets/css/styles-20260428.css?v=20260428-1820") ?>">
    <script src="https://kit.fontawesome.com/9bae38f407.js" crossorigin="anonymous"></script>
</head>

<?php

$modelUploads = new UploadModel();
$userBackground = '';

$userData = $modelUploads->first();


?>

<?php if ($userBackground) : ?>

    <body style="background: url(<?= base_url(PUBLIC_FOLDER . "assets/images/uploads/" . $userBackground['name']) ?>);">
    <?php else : ?>

        <body>
        <?php endif; ?>

        <?php echo $this->renderSection('navbar') ?>
        <?php if ($userBackground) : ?>

            <nav class="navbar navbar-expand-lg" style="background: url(<?= base_url(PUBLIC_FOLDER . "assets/images/uploads" . $userBackground['name']) ?>);">
            <?php else : ?>

                <nav class="navbar navbar-expand-lg" style="background-color: #ffffff;">
                <?php endif; ?>

                <div class="container-fluid">

                    <?php if ($userBackground) : ?>

                        <div class="mx-auto d-lg-none"> <!-- Centra en dispositivos móviles -->
                            <a class="navbar-brand" href="<?= base_url() ?>">
                                <img src="<?= isset($userData) ? base_url(PUBLIC_FOLDER . "assets/images/uploads/" . $userData['name']) : base_url(PUBLIC_FOLDER . "assets/images/sinlogo2.png") ?>" width="250px" alt="">
                            </a>
                        </div>

                        <div class="mx-auto d-none d-lg-block"> <!-- Centra en pantalla grande -->
                            <a class="navbar-brand" href="<?= base_url() ?>">
                                <img src="<?= isset($userData) ? base_url(PUBLIC_FOLDER . "assets/images/uploads/" . $userData['name']) : base_url(PUBLIC_FOLDER . "assets/images/sinlogo2.png") ?>" width="250px" alt="">
                            </a>
                        </div>

                    <?php else : ?>

                        <div class="mx-auto d-lg-none"> <!-- Centra en dispositivos móviles -->
                            <a class="navbar-brand" href="<?= base_url() ?>">
                                <img src="<?= isset($userData) ? base_url(PUBLIC_FOLDER . "assets/images/uploads/" . $userData['name']) : base_url(PUBLIC_FOLDER . "assets/images/sinlogo2.png") ?>" width="250px" alt="">
                            </a>
                        </div>

                        <div class="mx-auto d-none d-lg-block"> <!-- Centra en pantalla grande -->
                            <a class="navbar-brand" href="<?= base_url() ?>">
                                <img src="<?= isset($userData) ? base_url(PUBLIC_FOLDER . "assets/images/uploads/" . $userData['name']) : base_url(PUBLIC_FOLDER . "assets/images/sinlogo2.png") ?>" width="250px" alt="">
                            </a>
                        </div>
                    <?php endif; ?>


                    <?php if (session()->logueado) : ?>
                        <span class="me-1"><?= session()->name ?></span>
                        <a href="<?= base_url('auth/logOut') ?>" class="btn btn-danger me-1" type="button" id=""><i class="fa-solid fa-plug-circle-xmark"></i></a>
                    <?php endif; ?>
                </div>
                </div>
                </nav>


                <?php echo $this->renderSection('content') ?>


                <?php echo $this->renderSection('footer') ?>

                <div class="container-fluid">
                    <footer class="my-4 py-4 px-3 rounded-3" style="color: #fff; background-color: <?= (isset($userData) ? $userData['main_color'] : '#0064b0') ?> !important;">
                        <div class="d-flex flex-column flex-md-row justify-content-center justify-content-md-between align-items-center">

                            <div class="mb-3 mb-md-0">
                                <a href="https://alfa-net-plus-soluciones-informaticas.odoo.com/" target="_blank" class="text-white text-decoration-none">
                                    <small>© 2025 - Powered by Alfanet</small>
                                </a>
                            </div>

                            <ul class="nav">
                                <li class="nav-item">
                                    <a href="<?= base_url('MisReservas') ?>" class="nav-link px-2 text-white"><i class="fa-solid fa-calendar-days me-1"></i>Ver mi reserva</a>
                                </li>
                                <?php if (session()->logueado) : ?>
                                    <li class="nav-item">
                                        <a href="<?= base_url('abmAdmin') ?>" class="nav-link px-2 text-white"><i class="fa-solid fa-tablet-screen-button me-1"></i>Panel</a>
                                    </li>
                                    <li class="nav-item">
                                        <a href="<?= base_url('auth/logOut') ?>" class="nav-link px-2 text-white"><i class="fa-solid fa-plug-circle-xmark me-1"></i>Cerrar sesión</a>
                                    </li>
                                <?php else : ?>
                                    <li class="nav-item">
                                        <a href="<?= base_url('auth/login') ?>" class="nav-link px-2 text-white"><i class="fa-solid fa-user me-1"></i>Ingreso Admin</a>
                                    </li>
                                    <li class="nav-item">
                                        <a href="<?= base_url('Registrarme') ?>" class="nav-link px-2 text-white"><i class="fa-solid fa-user-plus me-1"></i>Registrarme</a>
                                    </li>
                                <?php endif; ?>
                            </ul>

                        </div>
                    </footer>
                </div>

                <script>
                    window.appBaseUrl = <?= json_encode(rtrim(site_url('/'), '/') . '/') ?>;
                    let sessionUserId = <?= json_encode(session()->id_user) ?>;
                    let sessionUserLogued = <?= json_encode(session()->logueado) ?>;
                    let sessionUserSuperadmin = <?= json_encode(session()->superadmin) ?>;
                </script>
                <script src="<?= base_url(PUBLIC_FOLDER . "assets/js/config.js") ?>"></script>

                <?php echo $this->renderSection('scripts') ?>
        </body>

</html>
