<?php

declare(strict_types=1);

$translations = [];

function loadTranslations(string $locale): void
{
    global $translations;
    $file = __DIR__ . '/lang/' . $locale . '.php';
    if (!file_exists($file)) {
        $file = __DIR__ . '/lang/en.php';
    }
    $translations = require $file;
}

function __(string $key, array $replace = []): string
{
    global $translations;
    $text = $translations[$key] ?? $key;
    foreach ($replace as $search => $value) {
        $text = str_replace(':' . $search, (string) $value, $text);
    }
    return $text;
}

function langUrl(string $locale): string
{
    $uri = $_SERVER['REQUEST_URI'] ?? '/';
    $uri = preg_replace('/([?&])lang=[^&]*/', '$1', $uri);
    $uri = rtrim($uri, '?&');
    $sep = str_contains($uri, '?') ? '&' : '?';
    return $uri . $sep . 'lang=' . $locale;
}
