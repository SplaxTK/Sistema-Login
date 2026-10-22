<?php

require_once __DIR__ . '/database.php';
require_once __DIR__ . '/admini_acounts.php';

if (session_status() !== PHP_SESSION_ACTIVE) {
    $https = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off';
    session_set_cookie_params([
        'httponly' => true,
        'secure' => $https,
        'samesite' => 'Lax',
        'path' => '/',
    ]);
    session_start();
}

function emailEhAdmin(string $email): bool
{
    foreach (ADMIN_EMAILS as $emailAdmin) {
        if (strcasecmp(trim($email), trim($emailAdmin)) === 0) {
            return true;
        }
    }

    return false;
}

function limparCookieLembrar(): void
{
    $https = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off';
    setcookie('remember_user', '', [
        'expires' => time() - 3600,
        'path' => '/',
        'secure' => $https,
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
    unset($_COOKIE['remember_user']);
}

function salvarSessaoUsuario(array $usuario): void
{
    session_regenerate_id(true);
    $_SESSION['usuario_id'] = (int) $usuario['id'];
    $_SESSION['usuario_nome'] = $usuario['nome_completo'];
    $_SESSION['usuario_login'] = $usuario['usuario'];
    $_SESSION['usuario_email'] = $usuario['email'];
}

function criarCookieLembrar(int $usuarioId): void
{
    $pdo = getDBConnection();
    $selector = bin2hex(random_bytes(16));
    $validator = bin2hex(random_bytes(32));
    $expiracao = time() + (7 * 86400);
    $hash = hash('sha256', $validator);

    $stmt = $pdo->prepare(
        'INSERT INTO remember_tokens (selector, token_hash, user_id, expires_at) VALUES (?, ?, ?, ?)'
    );
    $stmt->execute([
        $selector,
        $hash,
        $usuarioId,
        date('Y-m-d H:i:s', $expiracao),
    ]);

    $https = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off';
    setcookie('remember_user', $selector . ':' . $validator, [
        'expires' => $expiracao,
        'path' => '/',
        'secure' => $https,
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
}

function restaurarSessaoPorCookie(): void
{
    if (isset($_SESSION['usuario_id']) || empty($_COOKIE['remember_user'])) {
        return;
    }

    if (!preg_match('/^([a-f0-9]{32}):([a-f0-9]{64})$/', $_COOKIE['remember_user'], $partes)) {
        limparCookieLembrar();
        return;
    }

    $pdo = getDBConnection();
    $stmt = $pdo->prepare(
        'SELECT rt.selector, rt.token_hash, u.id, u.nome_completo, u.usuario, u.email, u.status
         FROM remember_tokens rt
         INNER JOIN usuarios u ON u.id = rt.user_id
         WHERE rt.selector = ? AND rt.expires_at > NOW()
         LIMIT 1'
    );
    $stmt->execute([$partes[1]]);
    $registro = $stmt->fetch();

    if (
        !$registro
        || !hash_equals($registro['token_hash'], hash('sha256', $partes[2]))
        || (int) $registro['status'] !== 1
    ) {
        $delete = $pdo->prepare('DELETE FROM remember_tokens WHERE selector = ?');
        $delete->execute([$partes[1]]);
        limparCookieLembrar();
        return;
    }

    $delete = $pdo->prepare('DELETE FROM remember_tokens WHERE selector = ?');
    $delete->execute([$partes[1]]);
    salvarSessaoUsuario($registro);
    criarCookieLembrar((int) $registro['id']);
}

function usuarioLogado(): ?array
{
    restaurarSessaoPorCookie();

    if (empty($_SESSION['usuario_id'])) {
        return null;
    }

    $stmt = getDBConnection()->prepare(
        'SELECT id, nome_completo, usuario, email, status FROM usuarios WHERE id = ? LIMIT 1'
    );
    $stmt->execute([(int) $_SESSION['usuario_id']]);
    $usuario = $stmt->fetch();

    if (!$usuario || (int) $usuario['status'] !== 1) {
        $_SESSION = [];
        session_destroy();
        limparCookieLembrar();
        return null;
    }

    $_SESSION['usuario_nome'] = $usuario['nome_completo'];
    $_SESSION['usuario_login'] = $usuario['usuario'];
    $_SESSION['usuario_email'] = $usuario['email'];

    return [
        'id' => (int) $usuario['id'],
        'nome' => $usuario['nome_completo'],
        'usuario' => $usuario['usuario'],
        'email' => $usuario['email'],
        'is_admin' => emailEhAdmin($usuario['email']),
    ];
}

function verificarSessao(): array
{
    $usuario = usuarioLogado();

    if (!$usuario) {
        header('Location: login.php');
        exit;
    }

    return $usuario;
}

function criarTokenCsrf(): string
{
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }

    return $_SESSION['csrf_token'];
}

function validarTokenCsrf(): bool
{
    $token = $_POST['csrf_token'] ?? '';

    return is_string($token)
        && isset($_SESSION['csrf_token'])
        && hash_equals($_SESSION['csrf_token'], $token);
}
