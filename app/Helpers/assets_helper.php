<?php

use App\Models\UploadModel;

if (! function_exists('asset_url')) {
    function asset_url(string $path): string
    {
        $relativePath = ltrim($path, '/');
        $url = base_url($relativePath);
        $absolutePath = FCPATH . $relativePath;

        if (! is_file($absolutePath)) {
            return $url;
        }

        $separator = strpos($url, '?') !== false ? '&' : '?';

        return $url . $separator . 'v=' . filemtime($absolutePath);
    }
}

if (! function_exists('brand_logo_candidates')) {
    function brand_logo_candidates(): array
    {
        static $candidates = null;

        if ($candidates !== null) {
            return $candidates;
        }

        $candidates = [];

        try {
            $uploadModel = new UploadModel();
            $branding = $uploadModel->first() ?: [];
            $logoFile = trim((string) ($branding['name'] ?? ''));

            if ($logoFile !== '') {
                $candidates[] = [
                    'relative' => 'assets/images/uploads/' . $logoFile,
                    'absolute' => FCPATH . 'assets/images/uploads/' . $logoFile,
                ];
            }
        } catch (\Throwable $exception) {
            // If the database is unavailable, keep the static fallbacks below.
        }

        // Use a real brand image first. The favicon stays as a last-resort
        // fallback only when none of the logo assets are available.
        foreach ([
            'assets/images/uploads/68d6a48d53bc6.png',
            'assets/images/sinlogo2.png',
            'assets/images/logo_pdf.png',
            'assets/images/favicon.ico',
        ] as $relativePath) {
            $candidates[] = [
                'relative' => $relativePath,
                'absolute' => FCPATH . $relativePath,
            ];
        }

        return $candidates;
    }
}

if (! function_exists('brand_logo_is_valid_file')) {
    function brand_logo_is_valid_file(string $absolutePath): bool
    {
        if (! is_file($absolutePath)) {
            return false;
        }

        $extension = strtolower(pathinfo($absolutePath, PATHINFO_EXTENSION));
        if ($extension === 'ico') {
            return filesize($absolutePath) > 0;
        }

        if (! in_array($extension, ['jpg', 'jpeg', 'gif', 'png', 'webp'], true)) {
            return false;
        }

        return @getimagesize($absolutePath) !== false;
    }
}

if (! function_exists('brand_logo_url')) {
    function brand_logo_url(): string
    {
        foreach (brand_logo_candidates() as $candidate) {
            if (brand_logo_is_valid_file($candidate['absolute'])) {
                return asset_url($candidate['relative']);
            }
        }

        return asset_url('assets/images/sinlogo2.png');
    }
}

if (! function_exists('brand_logo_data_uri')) {
    function brand_logo_data_uri(): string
    {
        foreach (brand_logo_candidates() as $candidate) {
            if (! brand_logo_is_valid_file($candidate['absolute'])) {
                continue;
            }

            $extension = strtolower(pathinfo($candidate['absolute'], PATHINFO_EXTENSION));

            switch ($extension) {
                case 'jpg':
                case 'jpeg':
                    $mimeType = 'image/jpeg';
                    break;
                case 'gif':
                    $mimeType = 'image/gif';
                    break;
                case 'ico':
                    $mimeType = 'image/x-icon';
                    break;
                case 'webp':
                    $mimeType = 'image/webp';
                    break;
                default:
                    $mimeType = 'image/png';
                    break;
            }

            $binary = @file_get_contents($candidate['absolute']);
            if ($binary === false) {
                continue;
            }

            return 'data:' . $mimeType . ';base64,' . base64_encode($binary);
        }

        return '';
    }
}

if (! function_exists('brand_logo_src')) {
    function brand_logo_src(): string
    {
        return brand_logo_url();
    }
}
