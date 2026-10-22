<?php
require_once __DIR__ . '/config/auth.php';
$usuarioAtual = verificarSessao();

if (!$usuarioAtual['is_admin']) {
    header('Location: index.php');
    exit;
}

$pdo = getDBConnection();
$mensagem = '';
$erro = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!validarTokenCsrf()) {
        $erro = 'A solicitação expirou. Atualize a página e tente novamente.';
    } else {
        $acao = $_POST['acao'] ?? '';
        $id = filter_var($_POST['id_usuario'] ?? null, FILTER_VALIDATE_INT);

        if (!$id || $id < 1) {
            $erro = 'Usuário inválido.';
        } elseif ($acao === 'status') {
            if ($id === $usuarioAtual['id']) {
                $erro = 'Você não pode desativar sua própria conta.';
            } else {
                $stmt = $pdo->prepare('UPDATE usuarios SET status = IF(status = 1, 0, 1) WHERE id = ?');
                $stmt->execute([$id]);
                $mensagem = 'Status da conta atualizado.';
            }
        } elseif ($acao === 'deletar') {
            if ($id === $usuarioAtual['id']) {
                $erro = 'Você não pode excluir sua própria conta.';
            } else {
                $stmt = $pdo->prepare('DELETE FROM usuarios WHERE id = ?');
                $stmt->execute([$id]);
                $stmt = $pdo->prepare('DELETE FROM remember_tokens WHERE user_id = ?');
                $stmt->execute([$id]);
                $mensagem = 'Usuário excluído com sucesso.';
            }
        } elseif ($acao === 'editar') {
            $nome = trim($_POST['nome_completo'] ?? '');
            $nomeUsuario = trim($_POST['usuario'] ?? '');
            $email = trim($_POST['email'] ?? '');

            if ($nome === '' || $nomeUsuario === '' || $email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $erro = 'Informe nome, usuário e um e-mail válido.';
            } else {
                $stmt = $pdo->prepare('SELECT id FROM usuarios WHERE (usuario = ? OR email = ?) AND id <> ? LIMIT 1');
                $stmt->execute([$nomeUsuario, $email, $id]);

                if ($stmt->fetch()) {
                    $erro = 'Nome de usuário ou e-mail já está sendo usado.';
                } else {
                    $isAdmin = emailEhAdmin($email) ? 1 : 0;
                    $stmt = $pdo->prepare(
                        'UPDATE usuarios
                         SET nome_completo = ?, usuario = ?, email = ?, is_admin = ?
                         WHERE id = ?'
                    );
                    $stmt->execute([$nome, $nomeUsuario, $email, $isAdmin, $id]);
                    $mensagem = 'Dados do usuário atualizados.';
                }
            }
        } else {
            $erro = 'Ação inválida.';
        }
    }
}

$usuarios = $pdo->query(
    'SELECT id, nome_completo, usuario, email, status FROM usuarios ORDER BY id DESC'
)->fetchAll();

foreach ($usuarios as &$item) {
    $item['is_admin'] = emailEhAdmin($item['email']) ? 1 : 0;
}
unset($item);

$csrfToken = criarTokenCsrf();
$usuario = $usuarioAtual;
include __DIR__ . '/includes/header.php';
?>
<main class="container">
    <section class="card table-responsive">
        <h1>Painel administrativo</h1>

        <?php if ($mensagem !== ''): ?>
            <div class="alert alert-success"><?= htmlspecialchars($mensagem, ENT_QUOTES, 'UTF-8') ?></div>
        <?php endif; ?>
        <?php if ($erro !== ''): ?>
            <div class="alert alert-danger"><?= htmlspecialchars($erro, ENT_QUOTES, 'UTF-8') ?></div>
        <?php endif; ?>

        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Nome completo</th>
                    <th>Usuário</th>
                    <th>E-mail</th>
                    <th>Tipo</th>
                    <th>Status</th>
                    <th>Ações</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($usuarios as $item): ?>
                    <tr>
                        <td><?= (int) $item['id'] ?></td>
                        <td><?= htmlspecialchars($item['nome_completo'], ENT_QUOTES, 'UTF-8') ?></td>
                        <td><?= htmlspecialchars($item['usuario'], ENT_QUOTES, 'UTF-8') ?></td>
                        <td><?= htmlspecialchars($item['email'], ENT_QUOTES, 'UTF-8') ?></td>
                        <td><?= $item['is_admin'] ? 'Administrador' : 'Comum' ?></td>
                        <td>
                            <form method="POST" class="inline-form">
                                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8') ?>">
                                <input type="hidden" name="acao" value="status">
                                <input type="hidden" name="id_usuario" value="<?= (int) $item['id'] ?>">
                                <button type="submit" class="badge <?= (int) $item['status'] === 1 ? 'badge-success' : 'badge-danger' ?>">
                                    <?= (int) $item['status'] === 1 ? 'Ativo' : 'Inativo' ?>
                                </button>
                            </form>
                        </td>
                        <td>
                            <div class="table-actions">
                                <button type="button" class="btn btn-small" onclick='abrirModalEditar(<?= json_encode($item, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT) ?>)'>Editar</button>
                                <?php if ((int) $item['id'] !== $usuarioAtual['id']): ?>
                                    <button type="button" class="btn btn-small btn-danger" onclick="confirmarDeletar(<?= (int) $item['id'] ?>)">Deletar</button>
                                <?php endif; ?>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </section>
</main>

<div class="modal" id="modalEditar" role="dialog" aria-modal="true" aria-labelledby="editTitle">
    <div class="modal-content">
        <h2 id="editTitle">Editar usuário</h2>
        <form method="POST">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8') ?>">
            <input type="hidden" name="acao" value="editar">
            <input type="hidden" name="id_usuario" id="edit_id">
            <div class="form-group">
                <label for="edit_nome">Nome completo</label>
                <input type="text" name="nome_completo" id="edit_nome" maxlength="150" required>
            </div>
            <div class="form-group">
                <label for="edit_usuario">Usuário</label>
                <input type="text" name="usuario" id="edit_usuario" maxlength="50" required>
            </div>
            <div class="form-group">
                <label for="edit_email">E-mail</label>
                <input type="email" name="email" id="edit_email" maxlength="100" required>
            </div>
            <p class="help-text">A senha não pode ser alterada pelo painel administrativo. O acesso de administrador depende somente do arquivo de configuração.</p>
            <div class="modal-actions">
                <button type="button" class="btn btn-secondary" onclick="fecharModal('modalEditar')">Cancelar</button>
                <button type="submit" class="btn">Salvar alterações</button>
            </div>
        </form>
    </div>
</div>

<div class="modal" id="modalDelete" role="dialog" aria-modal="true" aria-labelledby="deleteTitle">
    <div class="modal-content">
        <h2 id="deleteTitle">Confirmar exclusão</h2>
        <p>Tem certeza de que deseja excluir permanentemente esta conta?</p>
        <form method="POST">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8') ?>">
            <input type="hidden" name="acao" value="deletar">
            <input type="hidden" name="id_usuario" id="deleteUserId">
            <div class="modal-actions">
                <button type="button" class="btn btn-secondary" onclick="fecharModal('modalDelete')">Cancelar</button>
                <button type="submit" class="btn btn-danger">Excluir</button>
            </div>
        </form>
    </div>
</div>

<script src="assents/js/script.js"></script>
<script>
function abrirModalEditar(usuario) {
    document.getElementById('edit_id').value = usuario.id;
    document.getElementById('edit_nome').value = usuario.nome_completo;
    document.getElementById('edit_usuario').value = usuario.usuario;
    document.getElementById('edit_email').value = usuario.email;
    abrirModal('modalEditar');
}
</script>
</body>
</html>
