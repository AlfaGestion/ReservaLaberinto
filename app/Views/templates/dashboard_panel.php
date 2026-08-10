<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <?php echo $this->renderSection('title') ?>
    <title>Home</title>

    <script>
        (function () {
            try {
                var theme = localStorage.getItem('reservas_theme');
                document.documentElement.classList.add(theme === 'dark' ? 'theme-dark' : 'theme-light');
            } catch (error) {
                document.documentElement.classList.add('theme-light');
            }
        })();
    </script>

    <link rel="icon" href="<?= asset_url('assets/images/favicon.ico') ?>" type="image/x-icon">
    <link rel="stylesheet" href="<?= asset_url('assets/vendor/bootstrap/css/bootstrap.min.css') ?>">
    <script src="<?= asset_url('assets/vendor/bootstrap/js/bootstrap.bundle.min.js') ?>"></script>
    <link rel="stylesheet" href="<?= asset_url('assets/css/styles.css') ?>">
    <link rel="stylesheet" href="<?= asset_url('assets/css/admin-theme.css') ?>">
    <script src="https://kit.fontawesome.com/9bae38f407.js" crossorigin="anonymous"></script>
</head>

<?php

use App\Models\UploadModel;

$modelUploads = new UploadModel();
$userLogo = $modelUploads->first();

?>

<body class="admin-page">
    <?php echo $this->renderSection('navbar') ?>

    <nav class="navbar navbar-expand-lg admin-topbar">
        <div class="container-fluid">
            <div class="admin-topbar-inner">
                <div class="admin-topbar-spacer" aria-hidden="true"></div>
                <div class="admin-branding">
                    <a class="navbar-brand m-0 admin-branding__logo" href="<?= base_url() ?>">
                        <img
                            src="<?= isset($userLogo['name']) ? asset_url('assets/images/uploads/' . $userLogo['name']) : asset_url('assets/images/sinlogo2.png') ?>"
                            alt="">
                    </a>
                    <span class="admin-branding__title">Administración</span>
                </div>

                <?php if (session()->logueado) : ?>
                    <div class="admin-userbar">
                        <button type="button" id="adminThemeToggle" class="admin-theme-toggle" aria-label="Cambiar tema" title="Cambiar tema">
                            <i data-theme-icon class="fa-solid fa-moon" aria-hidden="true"></i>
                        </button>
                        <span class="admin-userbar__name"><?= session()->name ?></span>
                        <a href="<?= base_url('auth/logOut') ?>" class="btn btn-danger admin-userbar__logout" type="button" aria-label="Cerrar sesión">
                            <i class="fa-solid fa-plug-circle-xmark"></i>
                        </a>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </nav>

    <div id="adminNoticeContainer" class="admin-notice-container" aria-live="polite" aria-atomic="true"></div>

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
                    <?php if (session()->logueado) : ?>
                        <li class="nav-item">
                            <a href="<?= base_url('auth/logOut') ?>" class="nav-link px-2" style="color: var(--theme-text);"><i class="fa-solid fa-plug-circle-xmark me-1"></i>Cerrar sesión</a>
                        </li>
                        <li class="nav-item">
                            <a href="<?= base_url('abmAdmin') ?>" class="nav-link px-2" style="color: var(--theme-text);"><i class="fa-solid fa-tablet-screen-button me-1"></i>Panel</a>
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
