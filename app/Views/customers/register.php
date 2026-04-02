<?php
$backQuery = array_filter([
    'phone' => $prefillPhone ?? null,
    'email' => $prefillEmail ?? null,
    'returnValidate' => !empty($returnValidate) ? 1 : null,
]);
$backHref = base_url('') . ($backQuery ? '?' . http_build_query($backQuery) : '');
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registro</title>
    <link rel="stylesheet" href="<?= base_url(PUBLIC_FOLDER . "assets/css/styles.css") ?>">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-rbsA2VBKQhggwzxH7pPCaAqO46MgnOM80zW1RWuH61DGLwZJEdK2Kadq2F9CUG65" crossorigin="anonymous">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-kenU1KFdBIe4zVF0s0G1M5b4hcpxyD9F7jL+jjXkk+Q2h455rYXK/7HAuoJl+0I4" crossorigin="anonymous"></script>
    <script src="https://kit.fontawesome.com/9bae38f407.js" crossorigin="anonymous"></script>
    <link rel="icon" href="<?= base_url(PUBLIC_FOLDER . "assets/images/favicon.ico") ?>" type="image/x-icon">
    <style>
        body.register-page {
            min-height: 100vh;
            background:
                radial-gradient(circle at top, rgba(233, 133, 33, 0.16), transparent 28%),
                linear-gradient(180deg, #f7f3ec 0%, #eef3ee 100%);
            color: #243127;
        }

        body.register-page.register-page--embedded {
            min-height: auto;
            background: transparent;
        }

        .register-shell {
            min-height: 100vh;
            padding: 40px 16px;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .register-page--embedded .register-shell {
            min-height: auto;
            padding: 20px;
        }

        .register-page--embedded .register-card {
            width: 100%;
            border-radius: 20px;
            box-shadow: none;
        }

        .register-card {
            width: min(100%, 760px);
            background: rgba(255, 255, 255, 0.96);
            border: 1px solid rgba(13, 106, 58, 0.12);
            border-radius: 24px;
            box-shadow: 0 24px 60px rgba(21, 36, 24, 0.12);
            overflow: hidden;
        }

        .register-card__hero {
            padding: 28px 28px 12px;
            text-align: center;
        }

        .register-logo {
            width: min(100%, 260px);
            max-height: 140px;
            object-fit: contain;
            display: inline-block;
        }

        .register-title {
            margin: 18px 0 8px;
            font-size: clamp(2rem, 3vw, 2.8rem);
            line-height: 1;
            font-weight: 800;
            letter-spacing: -0.04em;
            color: #163625;
        }

        .register-subtitle {
            margin: 0 auto;
            max-width: 560px;
            color: #5d6e63;
            font-size: 1rem;
        }

        .register-card__body {
            padding: 20px 28px 32px;
        }

        .register-grid {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 16px;
        }

        .register-grid .register-field--full {
            grid-column: 1 / -1;
        }

        .register-label {
            display: block;
            margin-bottom: 8px;
            font-size: 0.92rem;
            font-weight: 700;
            color: #274536;
        }

        .register-input,
        .register-select {
            min-height: 56px;
            border-radius: 16px;
            border: 1px solid #d4ddd4;
            padding: 14px 16px;
            font-size: 1rem;
            box-shadow: none !important;
        }

        .register-input:focus,
        .register-select:focus {
            border-color: #0d6a3a;
        }

        .register-actions {
            margin-top: 24px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 12px;
            flex-wrap: wrap;
        }

        .register-btn {
            min-width: 150px;
            min-height: 52px;
            border: 0;
            border-radius: 14px;
            font-weight: 700;
            padding: 0 20px;
        }

        .register-back-link {
            color: #486255;
            font-weight: 700;
            padding: 8px 4px;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }

        .register-back-link:hover {
            color: #163625;
        }

        .register-btn--ghost {
            background: #f3f5f3;
            color: #2d4739;
            border: 1px solid #d2dbd2;
        }

        .register-btn--primary {
            color: #fff;
        }

        .register-alert {
            border-radius: 16px;
            padding: 14px 16px;
        }

        @media (max-width: 768px) {
            .register-card__hero {
                padding: 24px 20px 10px;
            }

            .register-card__body {
                padding: 18px 20px 24px;
            }

            .register-grid {
                grid-template-columns: 1fr;
            }

            .register-actions {
                flex-direction: column-reverse;
                align-items: stretch;
            }

            .register-btn {
                width: 100%;
            }

            .register-back-link {
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

<body class="register-page<?= !empty($isEmbedded) ? ' register-page--embedded' : '' ?>">
    <div class="register-shell">
        <section class="register-card">
            <div class="register-card__hero">
                <a href="<?= base_url() ?>">
                    <img
                        src="<?= isset($userData) ? base_url(PUBLIC_FOLDER . "assets/images/uploads/" . $userData['name']) : base_url(PUBLIC_FOLDER . "assets/images/sinlogo2.png") ?>"
                        alt="Laberinto Patagonia"
                        class="register-logo">
                </a>
                <h1 class="register-title">Registrate</h1>
                <p class="register-subtitle">Completa los datos de la institucion para poder avanzar con la validacion y las reservas.</p>
            </div>

            <div class="register-card__body">
                <form action="<?= site_url('Registrarme') ?>" method="POST">
                    <?php if (!empty($isEmbedded)) : ?>
                        <input type="hidden" name="embed" value="1">
                    <?php endif; ?>

                    <?php if (session('msg')) : ?>
                        <div class="alert alert-<?= session('msg.type') ?> alert-dismissible fade show register-alert mb-4" role="alert">
                            <small><?= session('msg.body') ?></small>
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>
                    <?php endif; ?>

                    <div class="register-grid">
                        <div class="register-field register-field--full">
                            <label class="register-label" for="name">Nombre de la institucion</label>
                            <input type="text" id="name" name="name" class="form-control register-input" placeholder="Ej. Escuela Rural Marcos Paz" value="<?= esc(old('name')) ?>">
                        </div>

                        <div class="register-field">
                            <label class="register-label" for="dni">CUIT/CUIL</label>
                            <input type="text" id="dni" name="dni" class="form-control register-input" placeholder="Ingresa el CUIT o CUIL" value="<?= esc(old('dni')) ?>">
                        </div>

                        <div class="register-field">
                            <label class="register-label" for="phone">Telefono</label>
                            <input type="text" id="phone" name="phone" class="form-control register-input" placeholder="Ingresa un telefono de contacto" value="<?= esc(old('phone', $prefillPhone ?? '')) ?>">
                        </div>

                        <div class="register-field">
                            <label class="register-label" for="typeInstitution">Tipo de institucion</label>
                            <select class="form-select register-select" id="typeInstitution" name="type_institution" aria-label="Tipo de institucion">
                                <option value="">Seleccionar</option>
                                <?php foreach ($types as $type) : ?>
                                    <option value="<?= $type['value'] ?>" <?= old('type_institution') === $type['value'] ? 'selected' : '' ?>><?= $type['name'] ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="register-field">
                            <label class="register-label" for="city">Localidad</label>
                            <input type="text" id="city" name="city" class="form-control register-input" placeholder="Ingresa la localidad" value="<?= esc(old('city')) ?>">
                        </div>

                        <div class="register-field register-field--full">
                            <label class="register-label" for="email">Email</label>
                            <input type="text" id="email" name="email" class="form-control register-input" placeholder="nombre@institucion.com" value="<?= esc(old('email', $prefillEmail ?? '')) ?>">
                        </div>
                    </div>

                    <div class="register-actions">
                        <?php if (empty($isEmbedded)) : ?>
                            <a href="<?= esc($backHref) ?>" class="register-back-link">
                                <i class="fa-solid fa-arrow-left"></i>
                                <span>Volver</span>
                            </a>
                        <?php else : ?>
                            <span></span>
                        <?php endif; ?>
                        <button
                            type="submit"
                            class="btn register-btn register-btn--primary"
                            style="background-color: <?= isset($userData) ? $userData['main_color'] : '#0064b0' ?>;">
                            Registrar
                        </button>
                    </div>
                </form>
            </div>
        </section>
    </div>
</body>

</html>
