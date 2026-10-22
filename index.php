<?php
require_once __DIR__ . '/config/auth.php';
$usuario = verificarSessao();
include __DIR__ . '/includes/header.php';
?>

<main class="container">
    <section class="card welcome-card">
        <h1>Bem-vindo(a), <?= htmlspecialchars($usuario['nome'], ENT_QUOTES, 'UTF-8') ?>!</h1>
        <p>Você está conectado ao sistema.</p>
    </section>
</main>

<script src="assents/js/script.js"></script>
</body>
</html>
