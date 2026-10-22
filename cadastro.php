<?php
require_once __DIR__ . '/config/auth.php';

if (usuarioLogado()) {
    header('Location: index.php');
    exit;
}

$erro = '';
$sucesso = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nome = trim($_POST['nome_completo'] ?? '');
    $usuario = trim($_POST['usuario'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $senha = $_POST['senha'] ?? '';
    $confirmarSenha = $_POST['confirmar_senha'] ?? '';

    if (!validarTokenCsrf()) {
        $erro = 'A solicitação expirou. Atualize a página e tente novamente.';
    } elseif ($nome === '' || $usuario === '' || $email === '' || $senha === '' || $confirmarSenha === '') {
        $erro = 'Preencha todos os campos.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $erro = 'Informe um e-mail válido.';
    } elseif ($senha !== $confirmarSenha) {
        $erro = 'As senhas não coincidem.';
    } else {
        $pdo = getDBConnection();
        $stmt = $pdo->prepare('SELECT id FROM usuarios WHERE usuario = ? OR email = ? LIMIT 1');
        $stmt->execute([$usuario, $email]);

        if ($stmt->fetch()) {
            $erro = 'Nome de usuário ou e-mail já cadastrado.';
        } else {
            $senhaHash = password_hash($senha, PASSWORD_DEFAULT);
            $isAdmin = emailEhAdmin($email) ? 1 : 0;
            $stmt = $pdo->prepare(
                'INSERT INTO usuarios (nome_completo, usuario, email, senha, is_admin, status)
                 VALUES (?, ?, ?, ?, ?, 1)'
            );

            if ($stmt->execute([$nome, $usuario, $email, $senhaHash, $isAdmin])) {
                $sucesso = 'Conta criada com sucesso. <a href="login.php">Clique aqui para entrar</a>.';
            } else {
                $erro = 'Não foi possível realizar o cadastro.';
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cadastro - Sistema</title>
    <link rel="stylesheet" href="assents/css/style.css">
</head>
<body>
    <main class="auth-container">
        <h2>Criar conta</h2>

        <?php if ($erro !== ''): ?>
            <div class="alert alert-danger"><?= htmlspecialchars($erro, ENT_QUOTES, 'UTF-8') ?></div>
        <?php endif; ?>
        <?php if ($sucesso !== ''): ?>
            <div class="alert alert-success"><?= $sucesso ?></div>
        <?php endif; ?>

        <form method="POST" action="cadastro.php">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(criarTokenCsrf(), ENT_QUOTES, 'UTF-8') ?>">
            <div class="form-group">
                <label for="nome_completo">Nome completo</label>
                <input id="nome_completo" type="text" name="nome_completo" maxlength="150" autocomplete="name" required>
            </div>
            <div class="form-group">
                <label for="usuario">Nome de usuário</label>
                <input id="usuario" type="text" name="usuario" maxlength="50" autocomplete="username" required>
            </div>
            <div class="form-group">
                <label for="email">E-mail</label>
                <input id="email" type="email" name="email" maxlength="100" autocomplete="email" required>
            </div>
            <div class="form-group">
                <label for="senha">Senha</label>
                <input id="senha" type="password" name="senha" autocomplete="new-password" required>
            </div>
            <div class="form-group">
                <label for="confirmar_senha">Confirmar senha</label>
                <input id="confirmar_senha" type="password" name="confirmar_senha" autocomplete="new-password" required>
            </div>
            <button type="submit" class="btn">Registrar</button>
        </form>

        <p class="auth-link">Já tem uma conta? <a href="login.php">Fazer login</a></p>
    </main>
</body>
</html>
