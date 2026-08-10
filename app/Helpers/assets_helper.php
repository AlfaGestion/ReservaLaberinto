<?php

if (! function_exists('asset_url')) {
    function asset_url(string $path): string
    {
        $relativePath = ltrim($path, '/');
        $url = base_url($relativePath);
        $absolutePath = FCPATH . $relativePath;

        if (! is_file($absolutePath)) {
            return $url;
        }

        $separator = str_contains($url, '?') ? '&' : '?';

        return $url . $separator . 'v=' . filemtime($absolutePath);
    }
}
