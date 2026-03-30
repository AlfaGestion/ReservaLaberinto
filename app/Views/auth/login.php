<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ingreso Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-rbsA2VBKQhggwzxH7pPCaAqO46MgnOM80zW1RWuH61DGLwZJEdK2Kadq2F9CUG65" crossorigin="anonymous">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-kenU1KFdBIe4zVF0s0G1M5b4hcpxyD9F7jL+jjXkk+Q2h455rYXK/7HAuoJl+0I4" crossorigin="anonymous"></script>
    <script src="https://kit.fontawesome.com/9bae38f407.js" crossorigin="anonymous"></script>
    <link rel="icon" href="<?= base_url(PUBLIC_FOLDER . "assets/images/favicon.ico") ?>" type="image/x-icon">
    <style>
        body.login-page {
            min-height: 100vh;
            background:
                radial-gradient(circle at top, rgba(233, 133, 33, 0.18), transparent 28%),
                linear-gradient(180deg, #f7f3ec 0%, #eef3ee 100%);
            color: #243127;
        }

        .login-shell {
            min-height: 100vh;
            padding: 40px 16px;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .login-card {
            width: min(100%, 520px);
            background: rgba(255, 255, 255, 0.96);
            border: 1px solid rgba(13, 106, 58, 0.12);
            border-radius: 24px;
            box-shadow: 0 24px 60px rgba(21, 36, 24, 0.12);
            overflow: hidden;
        }

        .login-card__hero {
            padding: 28px 28px 12px;
            text-align: center;
        }

        .login-logo {
            width: min(100%, 260px);
            max-height: 140px;
            object-fit: contain;
            display: inline-block;
        }

        .login-title {
            margin: 18px 0 8px;
            font-size: clamp(2rem, 3vw, 2.8rem);
            line-height: 1;
            font-weight: 800;
            letter-spacing: -0.04em;
            color: #163625;
        }

        .login-subtitle {
            margin: 0 auto;
            max-width: 380px;
            color: #5d6e63;
            font-size: 1rem;
        }

        .login-card__body {
            padding: 20px 28px 32px;
        }

        .login-field {
            position: relative;
            margin-bottom: 16px;
        }

        .login-field-label {
            display: block;
            margin: 0 0 8px;
            font-size: 0.95rem;
            font-weight: 700;
            color: #486255;
        }

        .login-control {
            position: relative;
        }

        .login-icon {
            position: absolute;
            top: 50%;
            left: 18px;
            transform: translateY(-50%);
            color: #607567;
            font-size: 0.95rem;
            z-index: 2;
        }

        .login-input {
            min-height: 58px;
            border-radius: 16px;
            border: 1px solid #d4ddd4;
            padding: 14px 16px 14px 48px;
            font-size: 1rem;
            box-shadow: none !important;
        }

        .login-input:focus {
            border-color: #0d6a3a;
        }

        .login-actions {
            margin-top: 24px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 12px;
            flex-wrap: wrap;
        }

        .login-back-link {
            color: #486255;
            font-weight: 700;
            padding: 8px 4px;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }

        .login-back-link:hover {
            color: #163625;
        }

        .login-btn {
            min-width: 150px;
            min-height: 52px;
            border: 0;
            border-radius: 14px;
            font-weight: 700;
            padding: 0 20px;
            color: #fff;
        }

        .login-alert {
            border-radius: 16px;
            padding: 14px 16px;
        }

        @media (max-width: 768px) {
            .login-card__hero {
                padding: 24px 20px 10px;
            }

            .login-card__body {
                padding: 18px 20px 24px;
            }

            .login-actions {
                flex-direction: column-reverse;
                align-items: stretch;
            }

            .login-btn {
                width: 100%;
            }

            .login-back-link {
                justify-content: center;
            }
        }
    </style>
</head>

<?php

use App\Models\UploadModel;

$modelUploads = new UploadModel();
$userData = $modelUploads->first();

?>

<body class="login-page">
    <div class="login-shell">
        <section class="login-card">
            <div class="login-card__hero">
                <a href="<?= base_url() ?>">
                    <img
                        src="<?= isset($logo) ? base_url(PUBLIC_FOLDER . "assets/images/uploads/" . $logo['name']) : base_url(PUBLIC_FOLDER . "assets/images/sinlogo2.png") ?>"
                        alt="Laberinto Patagonia"
                        class="login-logo">
                </a>
                <h1 class="login-title">Ingreso Admin</h1>
                <p class="login-subtitle">Accede con tu usuario y contrasena para administrar reservas y configuraciones.</p>
            </div>

            <div class="login-card__body">
                <form action="" method="POST">

                    <?php if (session('msg')) : ?>
                        <div class="alert alert-<?= session('msg.type') ?> alert-dismissible fade show login-alert mb-4" role="alert">
                            <small><?= session('msg.body') ?></small>
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>
                    <?php endif; ?>

                    <div class="login-field">
                        <label class="login-field-label" for="user">Usuario</label>
                        <div class="login-control">
                            <span class="login-icon"><i class="fa-solid fa-user"></i></span>
                            <input type="text" class="form-control login-input" id="user" name="user" placeholder="Usuario">
                        </div>
                    </div>

                    <div class="login-field">
                        <label class="login-field-label" for="password">Contrasena</label>
                        <div class="login-control">
                            <span class="login-icon"><i class="fa-solid fa-lock"></i></span>
                            <input type="password" class="form-control login-input" id="password" name="password" placeholder="Contrasena">
                        </div>
                    </div>

                    <div class="login-actions">
                        <a href="/" class="login-back-link">
                            <i class="fa-solid fa-arrow-left"></i>
                            <span>Volver</span>
                        </a>
                        <button type="submit" class="btn login-btn" style="background-color: <?= isset($userData) ? $userData['main_color'] : '#0064b0' ?>;">
                            Ingresar
                        </button>
                    </div>
                </form>
            </div>
        </section>
    </div>
</body>

</html>
