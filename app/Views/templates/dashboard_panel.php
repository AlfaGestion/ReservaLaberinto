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

    <link rel="icon" href="<?= base_url(PUBLIC_FOLDER . "assets/images/favicon.ico") ?>" type="image/x-icon">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-rbsA2VBKQhggwzxH7pPCaAqO46MgnOM80zW1RWuH61DGLwZJEdK2Kadq2F9CUG65" crossorigin="anonymous">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-kenU1KFdBIe4zVF0s0G1M5b4hcpxyD9F7jL+jjXkk+Q2h455rYXK/7HAuoJl+0I4" crossorigin="anonymous"></script>
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.11.6/dist/umd/popper.min.js" integrity="sha384-oBqDVmMz9ATKxIep9tiCxS/Z9fNfEXiDAYTujMAeBAsjFuCZSmKbSSUnQlmh/jp3" crossorigin="anonymous"></script>
    <!-- <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/js/bootstrap.min.js" integrity="sha384-cuYeSxntonz0PPNlHhBs68uyIAVpIIOZZ5JqeqvYYIcEL727kskC66kF92t6Xl2V" crossorigin="anonymous"></script> -->
    <link rel="stylesheet" href="<?= base_url(PUBLIC_FOLDER . "assets/css/styles-20260428.css?v=20260701-4") ?>">
    <link rel="stylesheet" href="<?= base_url(PUBLIC_FOLDER . "assets/css/admin-theme.css?v=20260630-7") ?>">
    <script src="https://kit.fontawesome.com/9bae38f407.js" crossorigin="anonymous"></script>
    <style>
        body.admin-page {
            min-height: 100vh;
            background: var(--theme-page-bg);
            color: var(--theme-text);
        }

        .admin-topbar {
            background:
                linear-gradient(180deg, color-mix(in srgb, var(--theme-surface-strong) 84%, #081726 16%) 0%, var(--theme-surface-strong) 100%);
            border-bottom: 1px solid var(--theme-border-soft);
            box-shadow: var(--theme-shadow-soft);
        }

        .admin-topbar .container-fluid {
            display: block;
            width: 100%;
        }

        .admin-topbar-inner {
            min-height: 84px;
            display: grid;
            grid-template-columns: 1fr auto 1fr;
            align-items: center;
            padding: 10px 20px;
            width: 100%;
        }

        .admin-topbar-spacer {
            min-width: 0;
        }

        .admin-topbar .navbar-brand {
            justify-self: center;
            margin: 0;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            padding: 0;
            border-radius: 0;
            background: transparent;
            border: 0;
            box-shadow: none;
            position: relative;
            overflow: visible;
        }

        .admin-topbar .navbar-brand img {
            max-height: 60px;
            max-width: min(180px, 42vw);
            width: auto;
            object-fit: contain;
            display: block;
            position: relative;
            z-index: 1;
            filter: drop-shadow(0 3px 6px rgba(0, 0, 0, 0.12));
        }

        .admin-shell {
            width: calc(100% - 32px);
            max-width: none;
            margin: 0 auto;
            padding: 28px 0 40px;
        }

        .admin-card {
            background: var(--theme-surface);
            border: 1px solid var(--theme-border);
            border-radius: 28px;
            box-shadow: var(--theme-shadow);
            padding: 24px;
            color: var(--theme-text);
        }

        .admin-alert {
            border-radius: 16px;
            padding: 14px 16px;
            border: 0;
            box-shadow: var(--theme-shadow-soft);
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
            background: var(--theme-surface);
            border: 1px solid var(--theme-border);
            box-shadow: var(--theme-shadow-soft);
            color: var(--theme-text);
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
            color: var(--theme-text-muted);
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
            background: var(--theme-surface-soft);
            border: 1px solid var(--theme-border-soft);
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
            background: var(--theme-surface);
            border: 1px solid var(--theme-border-soft);
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

        .admin-booking-row {
            transition: background-color 0.2s ease;
        }

        .admin-pane .table tbody tr.admin-booking-row--paid > * {
            background: #e7f5ea !important;
            color: #235135;
            border-color: #cfe4d5;
        }

        .admin-pane .table tbody tr.admin-booking-row--paid:hover > * {
            background: #dcefe1 !important;
        }

        .admin-pane .table tbody tr.admin-booking-row--pending-mp > * {
            background: #fff8e7 !important;
            color: #7a5600;
            border-color: #f1d59a;
        }

        .admin-pane .table tbody tr.admin-booking-row--pending-mp:hover > * {
            background: #fdf1cf !important;
        }

        .admin-pane .booking-payment-badge {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            min-width: 108px;
            padding: 0.5rem 0.7rem;
            border-radius: 999px;
            font-weight: 800;
            font-size: 0.78rem;
            line-height: 1;
            letter-spacing: 0.01em;
            border: 1px solid transparent;
            white-space: nowrap;
        }

        .admin-pane .booking-payment-state {
            display: flex;
            flex-direction: column;
            gap: 0.35rem;
        }

        .admin-pane .booking-payment-badge__note {
            color: #5f6e7f;
            font-size: 0.72rem;
            line-height: 1.25;
            max-width: 18rem;
        }

        .admin-pane .booking-payment-badge--pending-mp {
            background: #fff3cd;
            color: #8a6100;
            border-color: #efcf8b;
        }

        .admin-pane .booking-payment-badge--approved {
            background: #e6f6eb;
            color: #17663a;
            border-color: #bfe6cb;
        }

        .admin-pane .booking-payment-badge--partial {
            background: #fff0d8;
            color: #955f00;
            border-color: #efcf9a;
        }

        .admin-pane .booking-payment-badge--registered {
            background: #eef2f7;
            color: #445468;
            border-color: #d5dce4;
        }

        .admin-pane .booking-payment-badge--cancelled {
            background: #fde2e1;
            color: #9c2e29;
            border-color: #f1b7b4;
        }

        .admin-pane .booking-payment-badge--na {
            background: #f7f7f8;
            color: #6b7280;
            border-color: #e1e4e8;
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
            color: var(--theme-text);
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

        #selectDateBooking,
        #selectDateReport {
            background:
                linear-gradient(180deg, rgba(248, 251, 248, 0.98) 0%, rgba(239, 246, 240, 0.98) 100%);
            border: 1px solid rgba(13, 106, 58, 0.12);
            border-radius: 22px;
            padding: 22px 20px;
            margin-bottom: 18px;
            box-shadow: inset 0 1px 0 rgba(255, 255, 255, 0.9);
        }

        #selectDateBooking {
            background:
                radial-gradient(circle at top right, rgba(233, 133, 33, 0.12), transparent 28%),
                linear-gradient(180deg, rgba(248, 251, 248, 0.98) 0%, rgba(239, 246, 240, 0.98) 100%);
        }

        #selectDateReport {
            background:
                radial-gradient(circle at top left, rgba(13, 106, 58, 0.1), transparent 30%),
                linear-gradient(180deg, rgba(249, 250, 246, 0.98) 0%, rgba(241, 245, 238, 0.98) 100%);
        }

        #selectDateBooking > .d-flex:first-child,
        #selectDateReport > .form-check,
        #selectDateReport .form-check-label {
            color: #355040;
        }

        #selectDateBooking strong,
        #selectDateReport .form-check-label {
            font-weight: 700;
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
                padding: 10px 14px 12px;
                gap: 8px;
            }

            .admin-topbar .navbar-brand img {
                max-height: 46px;
                max-width: min(150px, 46vw);
            }

            .admin-topbar .navbar-brand {
                justify-self: center;
            }

            .admin-userbar {
                justify-self: center;
                gap: 8px;
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
                        <button type="button" id="adminThemeToggle" class="admin-theme-toggle" aria-label="Cambiar tema" title="Cambiar tema">
                            <i data-theme-icon class="fa-solid fa-moon" aria-hidden="true"></i>
                        </button>
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
        <footer class="my-4 py-4 px-3 rounded-3" style="color: var(--theme-text); background-color: var(--theme-surface-strong) !important;">
            <div class="d-flex flex-column flex-md-row justify-content-center justify-content-md-between align-items-center">

                <div class="mb-3 mb-md-0">
                    <a href="https://alfa-net-plus-soluciones-informaticas.odoo.com/" target="_blank" class="text-decoration-none" style="color: var(--theme-text);">
                        <small>© 2025 - Powered by Alfanet</small>
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
                            <a href="/customers/register" class="nav-link px-2" style="color: var(--theme-text);"><i class="fa-solid fa-user-plus me-1"></i>Registrarme</a>
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

