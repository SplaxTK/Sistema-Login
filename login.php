<?php
require_once __DIR__ . '/config/auth.php';

if (usuarioLogado()) {
    header('Location: index.php');
    exit;
}

$erro = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $login = trim($_POST['login'] ?? '');
    $senha = $_POST['senha'] ?? '';

    if (!validarTokenCsrf()) {
        $erro = 'A solicitação expirou. Atualize a página e tente novamente.';
    } elseif ($login === '' || $senha === '') {
        $erro = 'Preencha todos os campos.';
    } else {
        $stmt = getDBConnection()->prepare(
            'SELECT id, nome_completo, usuario, email, senha, status
             FROM usuarios
             WHERE usuario = ? OR email = ?
             LIMIT 1'
        );
        $stmt->execute([$login, $login]);
        $usuario = $stmt->fetch();

        if ($usuario && password_verify($senha, $usuario['senha'])) {
            if ((int) $usuario['status'] !== 1) {
                $erro = 'Esta conta foi desativada pelo administrador.';
            } else {
                salvarSessaoUsuario($usuario);
                criarCookieLembrar((int) $usuario['id']);
                header('Location: index.php');
                exit;
            }
        } else {
            $erro = 'Usuário/e-mail ou senha incorretos.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Sistema</title>
    <link rel="stylesheet" href="assents/css/style.css">
</head>
<body>
    <main class="auth-container">
        <h2>Entrar na conta</h2>

        <?php if ($erro !== ''): ?>
            <div class="alert alert-danger"><?= htmlspecialchars($erro, ENT_QUOTES, 'UTF-8') ?></div>
        <?php endif; ?>

        <form method="POST" action="login.php">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(criarTokenCsrf(), ENT_QUOTES, 'UTF-8') ?>">
            <div class="form-group">
                <label for="login">Usuário ou e-mail</label>
                <input id="login" type="text" name="login" autocomplete="username" required>
            </div>
            <div class="form-group">
                <label for="senha">Senha</label>
                <input id="senha" type="password" name="senha" autocomplete="current-password" required>
            </div>
            <button type="submit" class="btn">Entrar</button>
        </form>

        <p class="auth-link">Não possui uma conta? <a href="cadastro.php">Registrar-se</a></p>
    </main>
</body>
</html>
