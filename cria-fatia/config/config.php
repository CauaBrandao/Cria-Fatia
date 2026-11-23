<?php
if (!defined('BASE_URL')) {
    $forwardedProto = $_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '';
    $protocol = ($forwardedProto === 'https' || (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')) ? 'https://' : 'http://';
    $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
    $configuredUrl = getenv('APP_URL');
    define('BASE_URL', $configuredUrl ? rtrim($configuredUrl, '/') . '/' : $protocol . $host . '/');
}
