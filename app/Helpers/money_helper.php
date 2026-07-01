<?php

if (! function_exists('format_price_ar')) {
    function format_price_ar($value, string $fallback = 'No calculado'): string
    {
        if (is_string($value)) {
            $normalized = trim(str_replace(['$', ' '], '', $value));
            $normalized = str_replace(['.', ','], ['', '.'], $normalized);
            $value = is_numeric($normalized) ? (float) $normalized : $value;
        }

        if (! is_numeric($value)) {
            return $fallback;
        }

        $rounded = (int) round((float) $value, 0, PHP_ROUND_HALF_UP);

        return '$' . number_format($rounded, 0, ',', '.');
    }
}
