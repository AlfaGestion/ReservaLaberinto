<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <?php echo $this->renderSection('title') ?>
    <title>Home</title>

    <link rel="icon" href="<?= base_url(PUBLIC_FOLDER . "assets/images/favicon.ico") ?>" type="image/x-icon">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-rbsA2VBKQhggwzxH7pPCaAqO46MgnOM80zW1RWuH61DGLwZJEdK2Kadq2F9CUG65" crossorigin="anonymous">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-kenU1KFdBIe4zVF0s0G1M5b4hcpxyD9F7jL+jjXkk+Q2h455rYXK/7HAuoJl+0I4" crossorigin="anonymous"></script>
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.11.6/dist/umd/popper.min.js" integrity="sha384-oBqDVmMz9ATKxIep9tiCxS/Z9fNfEXiDAYTujMAeBAsjFuCZSmKbSSUnQlmh/jp3" crossorigin="anonymous"></script>
    <!-- <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/js/bootstrap.min.js" integrity="sha384-cuYeSxntonz0PPNlHhBs68uyIAVpIIOZZ5JqeqvYYIcEL727kskC66kF92t6Xl2V" crossorigin="anonymous"></script> -->
    <link rel="stylesheet" href="<?= base_url(PUBLIC_FOLDER . "assets/css/styles.css") ?>">
    <script src="https://kit.fontawesome.com/9bae38f407.js" crossorigin="anonymous"></script>
    <style>
        body.admin-page {
            min-height: 100vh;
            background:
                radial-gradient(circle at top, rgba(233, 133, 33, 0.12), transparent 24%),
                linear-gradient(180deg, #f7f3ec 0%, #eef3ee 100%);
            color: #243127;
        }

        .admin-topbar {
            background: rgba(255, 255, 255, 0.94);
            border-bottom: 1px solid rgba(13, 106, 58, 0.1);
            box-shadow: 0 12px 30px rgba(21, 36, 24, 0.08);
        }

        .admin-topbar .container-fluid {
            display: block;
            width: 100%;
        }

        .admin-topbar-inner {
            min-height: 112px;
            display: grid;
            grid-template-columns: 1fr auto 1fr;
            align-items: center;
            padding: 12px 20px;
            width: 100%;
        }

        .admin-topbar-spacer {
            min-width: 0;
        }

        .admin-topbar .navbar-brand {
            justify-self: center;
            margin: 0;
        }

        .admin-topbar .navbar-brand img {
            max-height: 118px;
            width: auto;
        }

        .admin-shell {
            width: calc(100% - 32px);
            max-width: none;
            margin: 0 auto;
            padding: 28px 0 40px;
        }

        .admin-card {
            background: rgba(255, 255, 255, 0.96);
            border: 1px solid rgba(13, 106, 58, 0.1);
            border-radius: 28px;
            box-shadow: 0 24px 60px rgba(21, 36, 24, 0.12);
            padding: 24px;
        }

        .admin-alert {
            border-radius: 16px;
            padding: 14px 16px;
            border: 0;
            box-shadow: 0 14px 30px rgba(21, 36, 24, 0.08);
        }

        .admin-notice-container {
            position: fixed;
            top: 128px;
            right: 24px;
            z-index: 1085;
            width: min(380px, calc(100vw - 32px));
            display: flex;
            flex-direction: column;
            gap: 12px;
            pointer-events: none;
        }

        .admin-notice {
            display: flex;
            align-items: flex-start;
            gap: 12px;
            padding: 14px 16px;
            border-radius: 18px;
            background: rgba(255, 255, 255, 0.98);
            border: 1px solid rgba(13, 106, 58, 0.12);
            box-shadow: 0 18px 36px rgba(21, 36, 24, 0.12);
            color: #243127;
            pointer-events: auto;
        }

        .admin-notice--success {
            border-color: rgba(13, 106, 58, 0.18);
        }

        .admin-notice--error {
            border-color: rgba(220, 53, 69, 0.18);
        }

        .admin-notice--info {
            border-color: rgba(13, 110, 253, 0.18);
        }

        .admin-notice__icon {
            width: 36px;
            height: 36px;
            border-radius: 12px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
            background: #edf7f0;
            color: #0d6a3a;
        }

        .admin-notice--error .admin-notice__icon {
            background: #fdecee;
            color: #dc3545;
        }

        .admin-notice--info .admin-notice__icon {
            background: #eef4ff;
            color: #0d6efd;
        }

        .admin-notice__content {
            min-width: 0;
        }

        .admin-notice__title {
            display: block;
            font-weight: 700;
            margin-bottom: 2px;
        }

        .admin-notice__message {
            display: block;
            color: #53685b;
            line-height: 1.45;
        }

        .admin-tabs {
            gap: 10px;
            border: 0;
            margin: 0 0 18px;
        }

        .admin-tabs .nav-link {
            border: 0;
            border-radius: 999px;
            background: #edf2ed;
            color: #466050;
            font-weight: 700;
            padding: 10px 16px;
        }

        .admin-tabs .nav-link.active {
            background: #0d6a3a;
            color: #fff;
            box-shadow: 0 16px 30px rgba(13, 106, 58, 0.18);
        }

        .admin-tabs .nav-link i {
            margin-right: 8px;
        }

        .admin-subtabs {
            gap: 10px;
            border: 0;
            margin: 0 0 18px;
        }

        .admin-subtabs .nav-link {
            border: 0;
            border-radius: 999px;
            background: #f1f5f1;
            color: #4f6659;
            font-weight: 700;
            padding: 8px 14px;
        }

        .admin-subtabs .nav-link.active {
            background: #dceadf;
            color: #163625;
        }

        .admin-section-card {
            background: #f8fbf8;
            border: 1px solid #e0e7e0;
            border-radius: 18px;
            padding: 18px;
        }

        .admin-section-card .text-muted,
        .admin-pane .text-muted {
            color: #6b7d71 !important;
        }

        .admin-section-card small.text-muted,
        .admin-pane small.text-muted {
            color: #6b7d71 !important;
            font-size: 0.9rem;
        }

        .admin-section-card + .admin-section-card {
            margin-top: 16px;
        }

        .admin-pane {
            background: #fff;
            border: 1px solid #e2e9e2;
            border-radius: 22px;
            padding: 20px;
        }

        .admin-pane .table {
            margin-bottom: 0;
        }

        .admin-pane .table thead th {
            border-bottom-width: 1px;
            color: #486255;
            font-size: 0.82rem;
            letter-spacing: 0.03em;
            text-transform: uppercase;
        }

        .admin-pane .table > :not(caption) > * > * {
            padding: 0.9rem 1rem;
        }

        .admin-pane .btn {
            border-radius: 14px;
            font-weight: 700;
        }

        .admin-pane .form-control,
        .admin-pane .form-select,
        .admin-pane textarea,
        .admin-pane .input-group-text {
            border-radius: 14px;
        }

        .admin-userbar {
            display: flex;
            align-items: center;
            gap: 10px;
            justify-self: end;
        }

        .admin-userbar__name {
            font-weight: 700;
            color: #355040;
        }

        .admin-userbar__logout {
            width: 44px;
            height: 44px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            border-radius: 12px;
            border: 0;
        }

        #generalButtons {
            display: flex;
            flex-wrap: wrap;
            gap: 12px;
        }

        #generalButtons .btn {
            margin: 0 !important;
        }

        .admin-toolbar {
            display: flex;
            flex-wrap: wrap;
            align-items: center;
            justify-content: space-between;
            gap: 14px;
            margin-top: 12px;
            margin-bottom: 18px;
        }

        .admin-toolbar__actions,
        .admin-toolbar__search {
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .admin-toolbar__search .form-floating {
            min-width: 220px;
            margin-bottom: 0 !important;
        }

        @media (max-width: 768px) {
            .admin-shell {
                width: calc(100% - 20px);
                padding: 20px 0 32px;
            }

            .admin-notice-container {
                top: 112px;
                left: 10px;
                right: 10px;
                width: auto;
            }

            .admin-card {
                padding: 18px;
                border-radius: 22px;
            }

            .admin-topbar-inner {
                min-height: auto;
                padding: 12px 16px 18px;
                flex-direction: column;
                gap: 10px;
            }

            .admin-topbar .navbar-brand img {
                max-height: 104px;
            }

            .admin-topbar .navbar-brand {
                justify-self: center;
            }

            .admin-userbar {
                justify-self: center;
            }

            .admin-pane {
                padding: 16px;
            }

            .admin-tabs {
                flex-wrap: nowrap;
                overflow-x: auto;
                padding-bottom: 6px;
            }

            .admin-tabs .nav-link {
                white-space: nowrap;
            }

            #generalButtons .btn {
                width: 100%;
            }

            .admin-toolbar,
            .admin-toolbar__actions,
            .admin-toolbar__search {
                flex-direction: column;
                align-items: stretch;
            }

            .admin-toolbar__search .form-floating {
                min-width: 100%;
            }
        }
    </style>

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
                <a class="navbar-brand m-0" href="<?= base_url() ?>">
                    <img src="<?= isset($userLogo['name']) ? base_url(PUBLIC_FOLDER . "assets/images/uploads/" . $userLogo['name']) : base_url(PUBLIC_FOLDER . "assets/images/sinlogo2.png") ?>" alt="">
                </a>

                <?php if (session()->logueado) : ?>
                    <div class="admin-userbar">
                        <span class="admin-userbar__name"><?= session()->name ?></span>
                        <a href="<?= base_url('auth/logOut') ?>" class="btn btn-danger admin-userbar__logout" type="button"><i class="fa-solid fa-plug-circle-xmark"></i></a>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </nav>

    <div id="adminNoticeContainer" class="admin-notice-container" aria-live="polite" aria-atomic="true"></div>


    <?php echo $this->renderSection('content') ?>


    <?php echo $this->renderSection('footer') ?>
    <div class="container-fluid">
        <footer class="my-4 py-4 px-3 rounded-3" style="color: #fff; background-color: <?= (isset($userLogo['main_color']) ? $userLogo['main_color'] : '#0064b0') ?> !important;">
            <div class="d-flex flex-column flex-md-row justify-content-center justify-content-md-between align-items-center">

                <div class="mb-3 mb-md-0">
                    <a href="https://alfa-net-plus-soluciones-informaticas.odoo.com/" target="_blank" class="text-white text-decoration-none">
                        <small>© 2025 - Powered by Alfanet</small>
                    </a>
                </div>

                <ul class="nav">
                    <?php if (session()->logueado) : ?>
                        <li class="nav-item">
                            <a href="<?= base_url('auth/logOut') ?>" class="nav-link px-2 text-white"><i class="fa-solid fa-plug-circle-xmark me-1"></i>Cerrar sesión</a>
                        </li>
                        <li class="nav-item">
                            <a href="<?= base_url('abmAdmin') ?>" class="nav-link px-2 text-white"><i class="fa-solid fa-tablet-screen-button me-1"></i>Panel</a>
                        </li>
                    <?php else : ?>
                        <li class="nav-item">
                            <a href="<?= base_url('auth/login') ?>" class="nav-link px-2 text-white"><i class="fa-solid fa-user me-1"></i>Ingreso Admin</a>
                        </li>
                        <li class="nav-item">
                            <a href="/customers/register" class="nav-link px-2 text-white"><i class="fa-solid fa-user-plus me-1"></i>Registrarme</a>
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

