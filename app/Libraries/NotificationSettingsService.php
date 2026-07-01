<?php

namespace App\Libraries;

use App\Models\UploadModel;

class NotificationSettingsService
{
    private ?array $cachedUpload = null;

    private function getUploadConfig(): array
    {
        if ($this->cachedUpload !== null) {
            return $this->cachedUpload;
        }

        $uploadModel = new UploadModel();
        $this->cachedUpload = $uploadModel->first() ?: [];

        return $this->cachedUpload;
    }

    public function getNotificationEmailString(): string
    {
        $config = $this->getUploadConfig();
        $value = trim((string) ($config['notification_email'] ?? ''));

        if ($value !== '') {
            return $value;
        }

        $legacy = trim((string) ($config['payment_support_email'] ?? ''));
        return $legacy !== '' ? $legacy : '';
    }

    public function getNotificationRecipients(): array
    {
        return $this->parseEmailList($this->getNotificationEmailString());
    }

    public function getSupportEmail(): string
    {
        $config = $this->getUploadConfig();
        $supportEmail = trim((string) ($config['payment_support_email'] ?? ''));
        if ($supportEmail !== '') {
            return $supportEmail;
        }

        return trim((string) ($config['support_email'] ?? ''));
    }

    public function getSupportPhone(): string
    {
        $config = $this->getUploadConfig();
        $supportPhone = trim((string) ($config['payment_support_phone'] ?? ''));
        if ($supportPhone !== '') {
            return $supportPhone;
        }

        return trim((string) ($config['support_phone'] ?? ''));
    }

    public function getInvoiceEmailSubject(): string
    {
        $config = $this->getUploadConfig();
        return trim((string) ($config['invoice_email_subject'] ?? 'Factura de reserva - Laberinto: {nombre}'));
    }

    public function getInvoiceEmailMessage(): string
    {
        $config = $this->getUploadConfig();
        return (string) ($config['invoice_email_message'] ?? "Hola {nombre},\n\nTe enviamos adjunto el comprobante de tu reserva.\n\nFecha: {fecha}\nHorario: {horario}\nCodigo: {codigo}\nPagado: {pagado}\n\nGracias.");
    }

    public function hasNotificationRecipients(): bool
    {
        return $this->getNotificationRecipients() !== [];
    }

    public function parseEmailList(string $raw): array
    {
        $chunks = preg_split('/[;,\n]+/', $raw) ?: [];
        $emails = [];

        foreach ($chunks as $chunk) {
            $email = strtolower(trim((string) $chunk));
            if ($email === '') {
                continue;
            }

            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                continue;
            }

            $emails[$email] = $email;
        }

        return array_values($emails);
    }
}
