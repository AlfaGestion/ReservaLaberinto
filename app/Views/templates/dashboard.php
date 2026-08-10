<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <?php echo $this->renderSection('title') ?>
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

    <link rel="icon" href="<?= asset_url('assets/images/favicon.ico') ?>" type="image/x-icon">
    <link rel="stylesheet" href="<?= asset_url('assets/vendor/bootstrap/css/bootstrap.min.css') ?>">
    <link rel="stylesheet" href="<?= asset_url('assets/vendor/flatpickr/css/flatpickr.min.css') ?>">
    <script src="<?= asset_url('assets/vendor/bootstrap/js/bootstrap.bundle.min.js') ?>"></script>
    <script src="<?= asset_url('assets/vendor/flatpickr/js/flatpickr.min.js') ?>"></script>
    <link rel="stylesheet" href="<?= asset_url('assets/css/styles.css') ?>">
    <link rel="stylesheet" href="<?= asset_url('assets/css/admin-theme.css') ?>">
    <script src="https://kit.fontawesome.com/9bae38f407.js" crossorigin="anonymous"></script>
</head>

<?php

use App\Models\UploadModel;

$modelUploads = new UploadModel();
$userBackground = '';

$userData = $modelUploads->first();

?>

<?php if (session()->logueado) : ?>

    <body class="admin-page">
<?php elseif ($userBackground) : ?>

    <body style="background: url(<?= asset_url('assets/images/uploads/' . $userBackground['name']) ?>);">
    <?php else : ?>

        <body>
        <?php endif; ?>

        <?php echo $this->renderSection('navbar') ?>
        <?php if ($userBackground) : ?>

            <nav class="navbar navbar-expand-lg site-topbar" style="background: url(<?= asset_url('assets/images/uploads/' . $userBackground['name']) ?>);">
            <?php else : ?>

                <nav class="navbar navbar-expand-lg site-topbar" style="background: var(--theme-surface); border-bottom: 1px solid var(--theme-border-soft); color: var(--theme-text);">
                <?php endif; ?>

                <div class="container-fluid site-topbar__inner">
                    <div class="mx-auto site-topbar__brand-wrap">
                        <a class="navbar-brand site-navbar-brand" href="<?= base_url() ?>">
                            <img
                                class="site-navbar-logo"
                                src="<?= isset($userData) ? asset_url('assets/images/uploads/' . $userData['name']) : asset_url('assets/images/sinlogo2.png') ?>"
                                alt="Laberinto Patagonia">
                        </a>
                    </div>

                    <div class="site-topbar-actions d-flex align-items-center gap-1 ms-auto">
                        <button type="button" id="adminThemeToggle" class="admin-theme-toggle site-theme-toggle me-1" aria-label="Cambiar tema" title="Cambiar tema">
                            <i data-theme-icon class="fa-solid fa-moon" aria-hidden="true"></i>
                        </button>

                        <?php if (session()->logueado) : ?>
                            <span class="site-topbar__user me-1"><?= session()->name ?></span>
                            <a href="<?= base_url('auth/logOut') ?>" class="btn btn-danger me-1 site-topbar__logout" type="button" aria-label="Cerrar sesión">
                                <i class="fa-solid fa-plug-circle-xmark"></i>
                            </a>
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
                                    <small>&copy; 2025 - Powered by Alfanet</small>
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
    <script src="<?= asset_url('assets/js/config.js') ?>"></script>
    <script src="<?= asset_url('assets/js/price-format.js') ?>"></script>
    <script src="<?= asset_url('assets/js/admin-theme.js') ?>"></script>

                <?php echo $this->renderSection('scripts') ?>
        </body>

</html>
