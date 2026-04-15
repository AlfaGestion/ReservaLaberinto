<?php
$eyebrow = trim((string) ($eyebrow ?? ''));
$title = trim((string) ($title ?? ''));
$intro = trim((string) ($intro ?? ''));
$messageHtml = (string) ($messageHtml ?? '');
$details = is_array($details ?? null) ? $details : [];
$primaryActionUrl = trim((string) ($primaryActionUrl ?? ''));
$primaryActionLabel = trim((string) ($primaryActionLabel ?? ''));
$secondaryActionUrl = trim((string) ($secondaryActionUrl ?? ''));
$secondaryActionLabel = trim((string) ($secondaryActionLabel ?? ''));
$supportText = trim((string) ($supportText ?? ''));
$brandName = trim((string) ($brandName ?? 'Laberinto Patagonia'));
$logoUrl = trim((string) ($logoUrl ?? ''));
$accentColor = trim((string) ($accentColor ?? '#0d6a3a'));
$secondaryColor = trim((string) ($secondaryColor ?? '#f39323'));
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= esc($title !== '' ? $title : $brandName) ?></title>
</head>
<body style="margin:0;padding:0;background:#f4f6f8;font-family:Arial,Helvetica,sans-serif;color:#1f2933;">
    <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="background:#f4f6f8;padding:24px 12px;">
        <tr>
            <td align="center">
                <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="max-width:680px;background:#ffffff;border:1px solid #dde5eb;">
                    <tr>
                        <td bgcolor="<?= esc($accentColor) ?>" style="padding:28px 32px;background:<?= esc($accentColor) ?>;color:#ffffff;">
                            <?php if ($logoUrl !== '') : ?>
                                <img src="<?= esc($logoUrl) ?>" alt="<?= esc($brandName) ?>" style="max-width:140px;max-height:64px;display:block;margin-bottom:18px;">
                            <?php endif; ?>
                            <?php if ($eyebrow !== '') : ?>
                                <div style="font-size:12px;letter-spacing:0.12em;text-transform:uppercase;color:#d9f2e2;margin-bottom:10px;"><?= esc($eyebrow) ?></div>
                            <?php endif; ?>
                            <div style="font-size:28px;line-height:1.2;font-weight:700;margin:0 0 10px;"><?= esc($title) ?></div>
                            <?php if ($intro !== '') : ?>
                                <div style="font-size:15px;line-height:1.6;max-width:540px;color:#f4fff7;"><?= esc($intro) ?></div>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php if ($details !== []) : ?>
                        <tr>
                            <td style="padding:24px 32px 8px;">
                                <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="border-collapse:separate;background:#f7faf8;border:1px solid #e5ece8;border-radius:18px;">
                                    <tr>
                                        <td colspan="2" style="padding:16px 18px 8px;font-size:12px;font-weight:700;letter-spacing:0.12em;text-transform:uppercase;color:<?= esc($accentColor) ?>;">
                                            Resumen de la reserva
                                        </td>
                                    </tr>
                                    <?php $detailIndex = 0; ?>
                                    <?php foreach ($details as $label => $value) : ?>
                                        <?php $detailIndex++; ?>
                                        <tr>
                                            <td style="width:36%;padding:12px 18px;color:#5f7468;font-size:12px;font-weight:700;text-transform:uppercase;letter-spacing:0.05em;<?= $detailIndex !== 1 ? 'border-top:1px solid #e5ece8;' : '' ?>">
                                                <?= esc((string) $label) ?>
                                            </td>
                                            <td style="padding:12px 18px;color:#1f2933;font-size:16px;font-weight:600;<?= $detailIndex !== 1 ? 'border-top:1px solid #e5ece8;' : '' ?>">
                                                <?= esc((string) $value) ?>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </table>
                            </td>
                        </tr>
                    <?php endif; ?>
                    <?php if ($messageHtml !== '') : ?>
                        <tr>
                            <td style="padding:8px 32px 0;font-size:15px;line-height:1.7;color:#334155;">
                                <?= $messageHtml ?>
                            </td>
                        </tr>
                    <?php endif; ?>
                    <?php if ($primaryActionUrl !== '' || $secondaryActionUrl !== '') : ?>
                        <tr>
                            <td style="padding:28px 32px 8px;">
                                <?php if ($primaryActionUrl !== '' && $primaryActionLabel !== '') : ?>
                                    <a href="<?= esc($primaryActionUrl) ?>" style="display:inline-block;padding:14px 24px;background:<?= esc($accentColor) ?>;border:1px solid <?= esc($accentColor) ?>;border-radius:999px;color:#ffffff;text-decoration:none;font-weight:700;margin-right:10px;margin-bottom:10px;"><?= esc($primaryActionLabel) ?></a>
                                <?php endif; ?>
                                <?php if ($secondaryActionUrl !== '' && $secondaryActionLabel !== '') : ?>
                                    <a href="<?= esc($secondaryActionUrl) ?>" style="display:inline-block;padding:14px 24px;background:#edf4ef;border:1px solid #d6e6dc;border-radius:999px;color:<?= esc($accentColor) ?>;text-decoration:none;font-weight:700;margin-bottom:10px;"><?= esc($secondaryActionLabel) ?></a>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endif; ?>
                    <tr>
                        <td style="padding:24px 32px 32px;font-size:13px;line-height:1.7;color:#64748b;">
                            <?= esc($supportText !== '' ? $supportText : 'Se asume el compromiso de asistir en la fecha y horario acordados. La reprogramacion queda sujeta a disponibilidad.') ?>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>
