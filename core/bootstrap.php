<?php

declare(strict_types=1);

const CMS_ROOT = __DIR__ . '/..';
const CMS_VERSION = '1.10.4';

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

function cms_public_nav(array $pages): string
{
    return cms_public_nav_items(cms_page_tree($pages), 0)
        . '<div class="nav-item has-children">'
        . '<button class="nav-parent nav-menu-button" type="button" aria-haspopup="true">'
        . '<span>Forms</span><span class="nav-indicator" aria-hidden="true">+</span></button>'
        . '<div class="nav-submenu" role="menu">'
        . '<a href="' . cms_e(cms_base_url('/contact.php')) . '">Contact</a>'
        . '<a href="' . cms_e(cms_base_url('/membership.php')) . '">Membership</a>'
        . '</div></div>';
}

function cms_page_tree(array $pages): array
{
    $byId = [];
    $tree = [];

    foreach ($pages as $page) {
        $page['children'] = [];
        $byId[(int)$page['id']] = $page;
    }

    foreach ($byId as $id => &$page) {
        $parentId = (int)($page['parent_id'] ?? 0);
        if ($parentId > 0 && isset($byId[$parentId])) {
            $byId[$parentId]['children'][] = &$page;
            continue;
        }

        $tree[] = &$page;
    }
    unset($page);

    return $tree;
}

function cms_public_nav_items(array $pages, int $level): string
{
    $html = '';

    foreach ($pages as $page) {
        $children = $page['children'] ?? [];
        if (!$children) {
            $html .= '<a href="' . cms_e(cms_page_url((string)$page['slug'])) . '">' . cms_e($page['title']) . '</a>';
            continue;
        }

        $html .= '<div class="nav-item has-children">'
            . '<a class="nav-parent" href="' . cms_e(cms_page_url((string)$page['slug'])) . '" aria-haspopup="true">'
            . '<span>' . cms_e($page['title']) . '</span><span class="nav-indicator" aria-hidden="true">+</span></a>'
            . '<div class="nav-submenu" role="menu">'
            . cms_public_nav_items($children, $level + 1)
            . '</div></div>';
    }

    return $html;
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
