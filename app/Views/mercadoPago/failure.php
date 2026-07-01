<?php
$status = strtolower(trim((string) ($status ?? 'cancelled')));
$title = trim((string) ($title ?? 'Pago cancelado'));
$subtitle = trim((string) ($subtitle ?? 'No se registro ningun cobro y tu reserva no fue confirmada.'));
$message = trim((string) ($message ?? 'Podés volver al inicio o intentar nuevamente si queres conservar el horario.'));
$tone = trim((string) ($tone ?? 'secondary'));
$icon = trim((string) ($icon ?? 'fa-circle-pause'));
$homeUrl = trim((string) ($homeUrl ?? base_url()));
$retryUrl = trim((string) ($retryUrl ?? ''));
$logoPath = trim((string) ($logoPath ?? base_url(PUBLIC_FOLDER . 'assets/images/sinlogo2.png')));
$booking = is_array($booking ?? null) ? $booking : null;
$hasRetryUrl = !empty($hasRetryUrl);

$details = [];
if ($booking) {
    $details = [
        'Nombre' => trim((string) ($booking['name'] ?? '-')),
        'Telefono' => trim((string) ($booking['phone'] ?? '-')),
        'Fecha' => trim((string) ($booking['date'] ?? '-')),
        'Horario' => trim((string) ($booking['time'] ?? '-')),
        'Servicio' => trim((string) ($booking['field'] ?? '-')),
        'Codigo' => trim((string) ($booking['code'] ?? '-')),
        'Total' => '$' . trim((string) ($booking['total'] ?? '0')),
        'Pagado' => '$' . trim((string) ($booking['payment'] ?? '0')),
        'Saldo' => '$' . trim((string) ($booking['difference'] ?? '0')),
    ];
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= esc($title) ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://kit.fontawesome.com/9bae38f407.js" crossorigin="anonymous"></script>
    <script>
        (function () {
            try {
                var theme = localStorage.getItem('reservas_theme');
                document.documentElement.classList.add(theme === 'dark' ? 'theme-dark' : 'theme-light');
                sessionStorage.setItem('bookingMercadoPagoReturnStatus', <?= json_encode($status) ?>);
            } catch (error) {
                document.documentElement.classList.add('theme-light');
            }
        })();
    </script>
    <style>
        :root {
            --mp-bg: radial-gradient(circle at top, rgba(243, 147, 35, 0.12), transparent 28%), linear-gradient(180deg, #f7f4ef 0%, #eef3f8 100%);
            --mp-surface: rgba(255, 255, 255, 0.95);
            --mp-surface-strong: #ffffff;
            --mp-surface-soft: #f5f8fb;
            --mp-text: #16304f;
            --mp-text-muted: #5f7186;
            --mp-border: rgba(22, 48, 79, 0.10);
            --mp-shadow: 0 22px 54px rgba(8, 20, 34, 0.12);
            --mp-accent: #0f6ad8;
            --mp-accent-strong: #0a4ea8;
        }

        html.theme-dark {
            --mp-bg: radial-gradient(circle at top, rgba(102, 156, 255, 0.12), transparent 28%), linear-gradient(180deg, #081726 0%, #102945 100%);
            --mp-surface: rgba(11, 28, 49, 0.98);
            --mp-surface-strong: #0d243d;
            --mp-surface-soft: #133357;
            --mp-text: #eef4fb;
            --mp-text-muted: #a9bfd7;
            --mp-border: rgba(142, 182, 229, 0.14);
            --mp-shadow: 0 22px 54px rgba(4, 14, 28, 0.34);
            --mp-accent: #f39323;
            --mp-accent-strong: #ffb44d;
        }

        body {
            margin: 0;
            min-height: 100vh;
            background: var(--mp-bg);
            color: var(--mp-text);
            font-family: "Segoe UI", Tahoma, sans-serif;
        }

        .mp-result-shell {
            min-height: 100vh;
            display: grid;
            place-items: center;
            padding: 24px 14px 36px;
        }

        .mp-result-card {
            width: min(920px, 100%);
            background: var(--mp-surface);
            color: var(--mp-text);
            border: 1px solid var(--mp-border);
            border-radius: 28px;
            box-shadow: var(--mp-shadow);
            overflow: hidden;
        }

        .mp-result-hero {
            padding: 26px 26px 22px;
            text-align: center;
            background:
                radial-gradient(circle at top, rgba(243, 147, 35, 0.14), transparent 35%),
                linear-gradient(180deg, var(--mp-surface-strong) 0%, var(--mp-surface) 100%);
            border-bottom: 1px solid var(--mp-border);
        }

        .mp-result-brand {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            padding: 12px 18px;
            border-radius: 20px;
            background: linear-gradient(180deg, var(--mp-surface-strong), var(--mp-surface-soft));
            border: 1px solid var(--mp-border);
            margin-bottom: 14px;
        }

        .mp-result-brand img {
            max-height: 66px;
            max-width: min(260px, 58vw);
            object-fit: contain;
            display: block;
            filter: drop-shadow(0 8px 18px rgba(0, 0, 0, 0.12));
        }

        .mp-result-icon {
            width: 72px;
            height: 72px;
            border-radius: 22px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 14px;
            background: color-mix(in srgb, var(--mp-accent) 14%, transparent);
            color: var(--mp-accent);
            font-size: 2rem;
        }

        .mp-result-title {
            margin: 0 0 8px;
            font-size: clamp(1.7rem, 3vw, 2.35rem);
            line-height: 1.05;
            font-weight: 800;
            letter-spacing: -0.04em;
        }

        .mp-result-subtitle {
            margin: 0 auto;
            max-width: 680px;
            color: var(--mp-text-muted);
            font-size: 1.02rem;
            line-height: 1.55;
        }

        .mp-result-body {
            padding: 22px 26px 26px;
        }

        .mp-result-callout {
            border-radius: 18px;
            padding: 16px 18px;
            background: linear-gradient(180deg, color-mix(in srgb, var(--mp-accent) 12%, var(--mp-surface-soft)) 0%, var(--mp-surface-soft) 100%);
            border: 1px solid color-mix(in srgb, var(--mp-accent) 22%, var(--mp-border));
            color: var(--mp-text);
            margin-bottom: 18px;
        }

        .mp-result-grid {
            display: grid;
            grid-template-columns: repeat(3, minmax(0, 1fr));
            gap: 12px;
        }

        .mp-result-item {
            padding: 14px 16px;
            border-radius: 16px;
            background: var(--mp-surface-soft);
            border: 1px solid var(--mp-border);
            min-height: 86px;
        }

        .mp-result-item__label {
            display: block;
            font-size: 0.79rem;
            font-weight: 700;
            letter-spacing: 0.04em;
            text-transform: uppercase;
            color: var(--mp-text-muted);
            margin-bottom: 8px;
        }

        .mp-result-item__value {
            display: block;
            font-size: 1.02rem;
            font-weight: 700;
            word-break: break-word;
        }

        .mp-result-actions {
            display: flex;
            flex-wrap: wrap;
            justify-content: center;
            gap: 12px;
            margin-top: 22px;
        }

        .mp-result-btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            min-height: 48px;
            padding: 0 18px;
            border-radius: 14px;
            font-weight: 700;
            text-decoration: none;
            border: 1px solid transparent;
            transition: transform 0.15s ease, box-shadow 0.15s ease, background-color 0.15s ease;
        }

        .mp-result-btn:hover {
            transform: translateY(-1px);
        }

        .mp-result-btn--secondary {
            background: transparent;
            color: var(--mp-text);
            border-color: var(--mp-border);
        }

        .mp-result-btn--primary {
            background: linear-gradient(180deg, var(--mp-accent) 0%, var(--mp-accent-strong) 100%);
            color: #fff;
            box-shadow: 0 14px 30px rgba(15, 106, 216, 0.22);
        }

        html.theme-dark .mp-result-btn--primary {
            color: #102945;
        }

        @media (max-width: 900px) {
            .mp-result-grid {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }
        }

        @media (max-width: 640px) {
            .mp-result-shell {
                padding: 16px 10px 22px;
            }

            .mp-result-hero,
            .mp-result-body {
                padding-left: 16px;
                padding-right: 16px;
            }

            .mp-result-grid {
                grid-template-columns: 1fr;
            }

            .mp-result-item {
                min-height: auto;
            }
        }
    </style>
</head>
<body>
<main class="mp-result-shell">
    <section class="mp-result-card" aria-labelledby="mpResultTitle">
        <div class="mp-result-hero">
            <div class="mp-result-brand">
                <img src="<?= esc($logoPath) ?>" alt="Laberinto Patagonia">
            </div>
            <div class="mp-result-icon" aria-hidden="true">
                <i class="fa-solid <?= esc($icon) ?>"></i>
            </div>
            <h1 class="mp-result-title" id="mpResultTitle"><?= esc($title) ?></h1>
            <p class="mp-result-subtitle"><?= esc($subtitle) ?></p>
        </div>

        <div class="mp-result-body">
            <div class="mp-result-callout">
                <?= esc($message) ?>
            </div>

            <?php if ($booking && $details !== []) : ?>
                <div class="mp-result-grid" role="list" aria-label="Detalle de la reserva">
                    <?php foreach ($details as $label => $value) : ?>
                        <div class="mp-result-item" role="listitem">
                            <span class="mp-result-item__label"><?= esc($label) ?></span>
                            <span class="mp-result-item__value"><?= esc($value) ?></span>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

            <div class="mp-result-actions">
                <a class="mp-result-btn mp-result-btn--secondary" href="<?= esc($homeUrl) ?>">Volver al inicio</a>
                <?php if ($hasRetryUrl && $retryUrl !== '') : ?>
                    <a class="mp-result-btn mp-result-btn--primary" href="<?= esc($retryUrl) ?>">Intentar nuevamente</a>
                <?php endif; ?>
            </div>
        </div>
    </section>
</main>
</body>
</html>
