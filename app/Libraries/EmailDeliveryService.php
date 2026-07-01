<?php

namespace App\Libraries;

use Config\Services;

class EmailDeliveryService
{
    private function createEmailService()
    {
        $email = Services::email();
        $email->SMTPTimeout = 8;

        return $email;
    }

    private function normalizeRecipients($to): array
    {
        if (is_array($to)) {
            $rawList = $to;
        } else {
            $rawList = preg_split('/[;,\n]+/', trim((string) $to)) ?: [];
        }

        $recipients = [];
        foreach ($rawList as $item) {
            $email = strtolower(trim((string) $item));
            if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
                continue;
            }

            $recipients[$email] = $email;
        }

        return array_values($recipients);
    }

    public function send($to, string $subject, string $message, bool $isHtml = false, array $attachments = [], array $context = []): bool
    {
        $recipients = $this->normalizeRecipients($to);
        if ($recipients === []) {
            log_message('error', 'Fallo envio email: destinatarios invalidos o vacios' . $this->formatContext($context, $subject));
            return false;
        }

        $emailConfig = config('Email');
        $accounts = $emailConfig->accounts ?? [];

        if ($accounts === []) {
            $accounts = [[
                'fromEmail' => $emailConfig->fromEmail,
                'fromName' => $emailConfig->fromName,
                'SMTPUser' => $emailConfig->SMTPUser,
                'SMTPPass' => $emailConfig->SMTPPass,
            ]];
        }

        foreach ($accounts as $account) {
            $tempFiles = [];
            try {
                $email = $this->createEmailService();
                $email->fromEmail = $account['fromEmail'] ?? $emailConfig->fromEmail;
                $email->fromName = $account['fromName'] ?? $emailConfig->fromName;
                $email->SMTPUser = $account['SMTPUser'] ?? $emailConfig->SMTPUser;
                $email->SMTPPass = $account['SMTPPass'] ?? $emailConfig->SMTPPass;
                $email->setFrom($email->fromEmail, $email->fromName);
                $email->setTo($recipients);
                $email->setSubject($subject);
                $email->setMailType($isHtml ? 'html' : 'text');
                $email->setMessage($message);

                foreach ($attachments as $attachment) {
                    if (is_string($attachment) && $attachment !== '' && is_file($attachment)) {
                        $email->attach($attachment);
                        continue;
                    }

                    if (!is_array($attachment)) {
                        continue;
                    }

                    $content = (string) ($attachment['content'] ?? '');
                    $name = trim((string) ($attachment['name'] ?? 'attachment.bin'));
                    $mimeType = trim((string) ($attachment['mimeType'] ?? 'application/octet-stream'));

                    if ($content === '') {
                        continue;
                    }

                    $tempPath = rtrim(WRITEPATH, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . uniqid('mail_attach_', true) . '_' . preg_replace('/[^a-zA-Z0-9._-]/', '_', $name);
                    file_put_contents($tempPath, $content);
                    $tempFiles[] = $tempPath;
                    $email->attach($tempPath, 'attachment', $name, $mimeType);
                }

                if ($email->send()) {
                    return true;
                }

                $debug = $email->printDebugger(['headers']);
                log_message('error', 'Fallo envio SMTP' . $this->formatContext($context, $subject, $recipients) . ': ' . $debug);
            } catch (\Throwable $e) {
                log_message('error', 'Fallo envio SMTP' . $this->formatContext($context, $subject, $recipients) . ': ' . $e->getMessage());
            } finally {
                foreach ($tempFiles as $tempPath) {
                    if (is_file($tempPath)) {
                        @unlink($tempPath);
                    }
                }
            }
        }

        return false;
    }

    private function formatContext(array $context, string $subject = '', array $recipients = []): string
    {
        $parts = [];
        if ($subject !== '') {
            $parts[] = ' subject="' . $subject . '"';
        }
        if ($recipients !== []) {
            $parts[] = ' to=' . implode(';', $recipients);
        }

        foreach (['booking_id', 'bookingId', 'payment_id', 'preference_id'] as $key) {
            if (!empty($context[$key])) {
                $parts[] = ' ' . $key . '=' . $context[$key];
            }
        }

        if (!empty($context['context'])) {
            $parts[] = ' context=' . $context['context'];
        }

        return $parts !== [] ? implode('', $parts) : '';
    }
}
