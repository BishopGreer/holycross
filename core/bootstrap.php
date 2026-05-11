<?php

declare(strict_types=1);

const CMS_ROOT = __DIR__ . '/..';
const CMS_VERSION = '1.7.2';

spl_autoload_register(function (string $class): void {
    $path = CMS_ROOT . '/core/' . $class . '.php';
    if (is_file($path)) {
        require_once $path;
    }
});

function cms_config_path(): string
{
    return CMS_ROOT . '/config/config.php';
}

function cms_is_installed(): bool
{
    return is_file(cms_config_path());
}

function cms_config(): array
{
    static $config = null;

    if ($config !== null) {
        return $config;
    }

    if (!cms_is_installed()) {
        return [];
    }

    $config = require cms_config_path();
    return is_array($config) ? $config : [];
}

function cms_base_url(string $path = ''): string
{
    $config = cms_config();
    $base = rtrim((string)($config['base_url'] ?? ''), '/');
    $path = '/' . ltrim($path, '/');

    return $base . ($path === '/' ? '' : $path);
}

function cms_page_url(string $slug): string
{
    $slug = trim($slug, '/');

    if ($slug === '' || $slug === 'home') {
        return cms_base_url('/');
    }

    return cms_base_url('/index.php?slug=' . rawurlencode($slug));
}

function cms_redirect(string $path): never
{
    header('Location: ' . cms_base_url($path));
    exit;
}

function cms_e(?string $value): string
{
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

function cms_current_path_slug(): string
{
    $slug = trim((string)($_GET['slug'] ?? ''), '/');
    if ($slug === '') {
        $slug = 'home';
    }

    return preg_replace('/[^a-zA-Z0-9\-\/]/', '', $slug) ?: 'home';
}

function cms_require_installation(): void
{
    if (!cms_is_installed()) {
        cms_redirect('/install/');
    }
}

function cms_start_session(): void
{
    if (session_status() === PHP_SESSION_ACTIVE) {
        return;
    }

    $config = cms_config();
    session_name((string)($config['session_name'] ?? 'php_page_cms'));
    session_start();
}
