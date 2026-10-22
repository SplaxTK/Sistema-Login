<?php
$iniciais = strtoupper(substr($usuario['nome'], 0, 1));
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sistema de Login</title>
    <link rel="stylesheet" href="assents/css/style.css">
</head>
<body>
<header class="topbar">
    <a class="topbar-brand" href="index.php">Meu App</a>
    <div class="user-menu" id="userMenuToggle">
        <span class="user-name"><?= htmlspecialchars($usuario['nome'], ENT_QUOTES, 'UTF-8') ?></span>
        <button class="avatar" type="button" aria-label="Abrir menu do perfil" aria-expanded="false"><?= htmlspecialchars($iniciais, ENT_QUOTES, 'UTF-8') ?></button>
        <nav class="dropdown-menu" id="dropdownMenu" aria-label="Menu do perfil">
            <?php if ($usuario['is_admin']): ?>
                <a href="painel.php">Painel administrativo</a>
            <?php endif; ?>
            <button type="button" onclick="confirmarLogout()">Sair</button>
        </nav>
    </div>
</header>

<div class="modal" id="modalLogout" role="dialog" aria-modal="true" aria-labelledby="logoutTitle">
    <div class="modal-content">
        <h2 id="logoutTitle">Confirmar saída</h2>
        <p>Deseja realmente sair da sua conta?</p>
        <div class="modal-actions">
            <button type="button" class="btn btn-secondary" onclick="fecharModal('modalLogout')">Cancelar</button>
            <a href="logout.php" class="btn btn-danger">Sair</a>
        </div>
    </div>
</div>
