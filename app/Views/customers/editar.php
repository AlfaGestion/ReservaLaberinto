<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Editar cliente</title>
    <link rel="stylesheet" href="<?= base_url(PUBLIC_FOLDER . "assets/css/styles.css") ?>">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-rbsA2VBKQhggwzxH7pPCaAqO46MgnOM80zW1RWuH61DGLwZJEdK2Kadq2F9CUG65" crossorigin="anonymous">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-kenU1KFdBIe4zVF0s0G1M5b4hcpxyD9F7jL+jjXkk+Q2h455rYXK/7HAuoJl+0I4" crossorigin="anonymous"></script>
    <script src="https://kit.fontawesome.com/9bae38f407.js" crossorigin="anonymous"></script>
    <link rel="icon" href="<?= base_url(PUBLIC_FOLDER . "assets/images/favicon.ico") ?>" type="image/x-icon">
    <style>
        body.customer-edit-page {
            min-height: 100vh;
            background:
                radial-gradient(circle at top, rgba(233, 133, 33, 0.16), transparent 28%),
                linear-gradient(180deg, #f7f3ec 0%, #eef3ee 100%);
            color: #243127;
        }

        body.customer-edit-page.customer-edit-page--embedded {
            min-height: auto;
            background: transparent;
        }

        .customer-edit-shell {
            min-height: 100vh;
            padding: 40px 16px;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .customer-edit-page--embedded .customer-edit-shell {
            min-height: auto;
            padding: 20px;
        }

        .customer-edit-card {
            width: min(100%, 760px);
            background: rgba(255, 255, 255, 0.96);
            border: 1px solid rgba(13, 106, 58, 0.12);
            border-radius: 24px;
            box-shadow: 0 24px 60px rgba(21, 36, 24, 0.12);
            overflow: hidden;
        }

        .customer-edit-page--embedded .customer-edit-card {
            width: 100%;
            border-radius: 20px;
            box-shadow: none;
        }

        .customer-edit-card__hero {
            padding: 28px 28px 12px;
            text-align: center;
        }

        .customer-edit-logo {
            width: min(100%, 260px);
            max-height: 140px;
            object-fit: contain;
            display: inline-block;
        }

        .customer-edit-title {
            margin: 18px 0 8px;
            font-size: clamp(2rem, 3vw, 2.6rem);
            line-height: 1;
            font-weight: 800;
            letter-spacing: -0.04em;
            color: #163625;
        }

        .customer-edit-card__body {
            padding: 20px 28px 32px;
        }

        .customer-edit-grid {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 16px;
        }

        .customer-edit-field--full {
            grid-column: 1 / -1;
        }

        .customer-edit-label {
            display: block;
            margin-bottom: 8px;
            font-size: 0.92rem;
            font-weight: 700;
            color: #274536;
        }

        .customer-edit-input,
        .customer-edit-select {
            min-height: 56px;
            border-radius: 16px;
            border: 1px solid #d4ddd4;
            padding: 14px 16px;
            font-size: 1rem;
            box-shadow: none !important;
        }

        .customer-edit-input:focus,
        .customer-edit-select:focus {
            border-color: #0d6a3a;
        }

        .customer-edit-actions {
            margin-top: 24px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 12px;
            flex-wrap: wrap;
        }

        .customer-edit-btn {
            min-width: 150px;
            min-height: 52px;
            border: 0;
            border-radius: 14px;
            font-weight: 700;
            padding: 0 20px;
        }

        .customer-edit-back-link {
            color: #486255;
            font-weight: 700;
            padding: 8px 4px;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }

        .customer-edit-back-link:hover {
            color: #163625;
        }

        .customer-edit-alert {
            border-radius: 16px;
            padding: 14px 16px;
        }

        @media (max-width: 768px) {
            .customer-edit-card__hero {
                padding: 24px 20px 10px;
            }

            .customer-edit-card__body {
                padding: 18px 20px 24px;
            }

            .customer-edit-grid {
                grid-template-columns: 1fr;
            }

            .customer-edit-actions {
                flex-direction: column-reverse;
                align-items: stretch;
            }

            .customer-edit-btn {
                width: 100%;
            }

            .customer-edit-back-link {
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

<body class="customer-edit-page<?= !empty($isEmbedded) ? ' customer-edit-page--embedded' : '' ?>">
    <div class="customer-edit-shell">
        <section class="customer-edit-card">
            <div class="customer-edit-card__hero">
                <a href="<?= base_url() ?>">
                    <img
                        src="<?= isset($userData) ? base_url(PUBLIC_FOLDER . "assets/images/uploads/" . $userData['name']) : base_url(PUBLIC_FOLDER . "assets/images/sinlogo2.png") ?>"
                        alt="Laberinto Patagonia"
                        class="customer-edit-logo">
                </a>
                <h1 class="customer-edit-title">Editar un cliente</h1>
            </div>

            <div class="customer-edit-card__body">
                <form action="<?= base_url('customers/editCustomer') ?>" method="POST">
                    <input type="hidden" value="<?= $customer['id'] ?>" name="idCustomer">
                    <?php if (!empty($isEmbedded)) : ?>
                        <input type="hidden" name="embed" value="1">
                    <?php endif; ?>

                    <?php if (session('msg')) : ?>
                        <div class="alert alert-<?= session('msg.type') ?> alert-dismissible fade show customer-edit-alert mb-4" role="alert">
                            <small><?= session('msg.body') ?></small>
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>
                    <?php endif; ?>

                    <div class="customer-edit-grid">
                        <div class="customer-edit-field customer-edit-field--full">
                            <label class="customer-edit-label" for="name">Nombre institucion</label>
                            <input type="text" id="name" name="name" class="form-control customer-edit-input" value="<?= esc($customer['name']) ?>">
                        </div>

                        <div class="customer-edit-field">
                            <label class="customer-edit-label" for="dni">CUIT/CUIL</label>
                            <input type="text" id="dni" name="dni" class="form-control customer-edit-input" value="<?= esc($customer['dni']) ?>">
                        </div>

                        <div class="customer-edit-field">
                            <label class="customer-edit-label" for="offer">Descuento (%)</label>
                            <input type="number" id="offer" name="offer" class="form-control customer-edit-input" value="<?= esc($customer['offer']) ?>" min="0" max="100" step="1">
                        </div>

                        <div class="customer-edit-field">
                            <label class="customer-edit-label" for="typeInstitution">Tipo de institucion</label>
                            <select class="form-select customer-edit-select" id="typeInstitution" name="type_institution">
                                <option value="">Seleccionar</option>
                                <?php foreach ($types as $type) : ?>
                                    <option value="<?= $type['value'] ?>" <?= $customer['type_institution'] == $type['value'] ? 'selected' : '' ?>>
                                        <?= $type['name'] ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="customer-edit-field">
                            <label class="customer-edit-label" for="phone">Telefono</label>
                            <input type="text" id="phone" name="phone" class="form-control customer-edit-input" value="<?= esc($customer['phone']) ?>">
                        </div>

                        <div class="customer-edit-field customer-edit-field--full">
                            <label class="customer-edit-label" for="email">Email</label>
                            <input type="text" id="email" name="email" class="form-control customer-edit-input" value="<?= esc($customer['email']) ?>">
                        </div>

                        <div class="customer-edit-field customer-edit-field--full">
                            <label class="customer-edit-label" for="city">Localidad</label>
                            <input type="text" id="city" name="city" class="form-control customer-edit-input" value="<?= esc($customer['city']) ?>">
                        </div>
                    </div>

                    <div class="customer-edit-actions">
                        <?php if (empty($isEmbedded)) : ?>
                            <a href="<?= base_url('abmAdmin') ?>" class="customer-edit-back-link">
                                <i class="fa-solid fa-arrow-left"></i>
                                <span>Volver</span>
                            </a>
                        <?php else : ?>
                            <span></span>
                        <?php endif; ?>
                        <button type="submit" class="btn customer-edit-btn" style="color: #fff; background-color: <?= isset($userData) ? $userData['main_color'] : '#0064b0' ?>;">Guardar</button>
                    </div>
                </form>
            </div>
        </section>
    </div>

</body>

</html>
