<?php
require_once __DIR__ . '/config/auth.php';

if (isset($_COOKIE['remember_user']) && preg_match('/^([a-f0-9]{32}):[a-f0-9]{64}$/', $_COOKIE['remember_user'], $partes)) {
    $stmt = getDBConnection()->prepare('DELETE FROM remember_tokens WHERE selector = ?');
    $stmt->execute([$partes[1]]);
}

limparCookieLembrar();
$_SESSION = [];

if (ini_get('session.use_cookies')) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', [
        'expires' => time() - 3600,
        'path' => $params['path'],
        'domain' => $params['domain'],
        'secure' => $params['secure'],
        'httponly' => $params['httponly'],
        'samesite' => 'Lax',
    ]);
}

session_destroy();
header('Location: login.php');
exit;
