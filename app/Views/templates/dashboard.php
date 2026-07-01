<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <?php

    use App\Models\UploadModel;

    echo $this->renderSection('title') ?>
    <title>Laberinto Patagonia - Reservas</title>

    <script>
        (function () {
            var forceLightTheme = <?= json_encode(!empty($forceLightTheme)) ?>;
            try {
                if (forceLightTheme) {
                    document.documentElement.dataset.forceLightTheme = '1';
                    document.documentElement.classList.add('theme-light');
                    return;
                }

                var theme = localStorage.getItem('reservas_theme');
                document.documentElement.classList.add(theme === 'dark' ? 'theme-dark' : 'theme-light');
            } catch (error) {
                document.documentElement.classList.add('theme-light');
            }
        })();
    </script>

    <link rel="icon" href="<?= base_url(PUBLIC_FOLDER . "assets/images/favicon.ico") ?>" type="image/x-icon">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-rbsA2VBKQhggwzxH7pPCaAqO46MgnOM80zW1RWuH61DGLwZJEdK2Kadq2F9CUG65" crossorigin="anonymous">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-kenU1KFdBIe4zVF0s0G1M5b4hcpxyD9F7jL+jjXkk+Q2h455rYXK/7HAuoJl+0I4" crossorigin="anonymous"></script>
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.11.6/dist/umd/popper.min.js" integrity="sha384-oBqDVmMz9ATKxIep9tiCxS/Z9fNfEXiDAYTujMAeBAsjFuCZSmKbSSUnQlmh/jp3" crossorigin="anonymous"></script>
    <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
    <link rel="stylesheet" href="<?= base_url(PUBLIC_FOLDER . "assets/css/styles-20260428.css?v=20260701-4") ?>">
    <link rel="stylesheet" href="<?= base_url(PUBLIC_FOLDER . "assets/css/admin-theme.css?v=20260630-7") ?>">
    <script src="https://kit.fontawesome.com/9bae38f407.js" crossorigin="anonymous"></script>
</head>

<?php

$modelUploads = new UploadModel();
$userBackground = '';

$userData = $modelUploads->first();


?>

<?php if (session()->logueado) : ?>

    <body class="admin-page">
<?php elseif ($userBackground) : ?>

    <body style="background: url(<?= base_url(PUBLIC_FOLDER . "assets/images/uploads/" . $userBackground['name']) ?>);">
    <?php else : ?>

        <body>
        <?php endif; ?>

        <?php echo $this->renderSection('navbar') ?>
        <?php if ($userBackground) : ?>

            <nav class="navbar navbar-expand-lg" style="background: url(<?= base_url(PUBLIC_FOLDER . "assets/images/uploads" . $userBackground['name']) ?>);">
            <?php else : ?>

                <nav class="navbar navbar-expand-lg" style="background: var(--theme-surface); border-bottom: 1px solid var(--theme-border-soft); color: var(--theme-text);">
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


                    <button type="button" id="adminThemeToggle" class="admin-theme-toggle site-theme-toggle me-1" aria-label="Cambiar tema" title="Cambiar tema">
                        <i data-theme-icon class="fa-solid fa-moon" aria-hidden="true"></i>
                    </button>

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
                    <footer class="my-4 py-4 px-3 rounded-3" style="color: var(--theme-text); background-color: var(--theme-surface-strong) !important;">
                        <div class="d-flex flex-column flex-md-row justify-content-center justify-content-md-between align-items-center">

                            <div class="mb-3 mb-md-0">
                                <a href="https://alfa-net-plus-soluciones-informaticas.odoo.com/" target="_blank" class="text-decoration-none" style="color: var(--theme-text);">
                                    <small>© 2025 - Powered by Alfanet</small>
                                </a>
                            </div>

                            <ul class="nav">
                                <li class="nav-item">
                                    <a href="<?= base_url('MisReservas') ?>" class="nav-link px-2" style="color: var(--theme-text);"><i class="fa-solid fa-calendar-days me-1"></i>Ver mi reserva</a>
                                </li>
                                <?php if (session()->logueado) : ?>
                                    <li class="nav-item">
                                        <a href="<?= base_url('abmAdmin') ?>" class="nav-link px-2" style="color: var(--theme-text);"><i class="fa-solid fa-tablet-screen-button me-1"></i>Panel</a>
                                    </li>
                                    <li class="nav-item">
                                        <a href="<?= base_url('auth/logOut') ?>" class="nav-link px-2" style="color: var(--theme-text);"><i class="fa-solid fa-plug-circle-xmark me-1"></i>Cerrar sesión</a>
                                    </li>
                                <?php else : ?>
                                    <li class="nav-item">
                                        <a href="<?= base_url('auth/login') ?>" class="nav-link px-2" style="color: var(--theme-text);"><i class="fa-solid fa-user me-1"></i>Ingreso Admin</a>
                                    </li>
                                    <li class="nav-item">
                                        <a href="<?= base_url('Registrarme') ?>" class="nav-link px-2" style="color: var(--theme-text);"><i class="fa-solid fa-user-plus me-1"></i>Registrarme</a>
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
    <script src="<?= base_url(PUBLIC_FOLDER . "assets/js/config.js?v=20260521-1325") ?>"></script>
    <script src="<?= base_url(PUBLIC_FOLDER . "assets/js/price-format.js?v=20260701-1") ?>"></script>
    <script src="<?= base_url(PUBLIC_FOLDER . "assets/js/admin-theme.js?v=20260630-7") ?>"></script>

                <?php echo $this->renderSection('scripts') ?>
        </body>

</html>
