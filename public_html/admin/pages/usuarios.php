<?php
// Usuários do painel e troca da própria senha.
$eu = usuario();
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    exigir_post_csrf();
    $acao = $_POST['acao'] ?? '';
    if ($acao === 'novo') {
        $nome = mb_substr(trim((string)$_POST['nome']), 0, 120);
        $email = mb_strtolower(trim((string)$_POST['email']));
        $senha = (string)$_POST['senha'];
        if ($nome === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) throw new RuntimeException('Informe nome e e-mail válidos.');
        if (mb_strlen($senha) < 8) throw new RuntimeException('A senha precisa ter pelo menos 8 caracteres.');
        if (q('SELECT id FROM usuarios WHERE email = ?', [$email])->fetch()) throw new RuntimeException('Já existe um usuário com esse e-mail.');
        q('INSERT INTO usuarios (nome, email, senha_hash) VALUES (?,?,?)', [$nome, $email, password_hash($senha, PASSWORD_DEFAULT)]);
        flash('Usuário criado.');
    } elseif ($acao === 'senha') {
        $u = q('SELECT senha_hash FROM usuarios WHERE id = ?', [$eu['id']])->fetch();
        if (!password_verify((string)$_POST['atual'], $u['senha_hash'])) throw new RuntimeException('Senha atual incorreta.');
        if (mb_strlen((string)$_POST['nova']) < 8) throw new RuntimeException('A nova senha precisa ter pelo menos 8 caracteres.');
        q('UPDATE usuarios SET senha_hash = ?, sessao_versao = sessao_versao + 1 WHERE id = ?', [password_hash((string)$_POST['nova'], PASSWORD_DEFAULT), $eu['id']]);
        $_SESSION['sv'] = (int)q('SELECT sessao_versao FROM usuarios WHERE id = ?', [$eu['id']])->fetchColumn();
        flash('Senha alterada. Outras sessões abertas foram encerradas.');
    } elseif ($acao === 'excluir') {
        $alvo = (int)$_POST['id'];
        if ($alvo === (int)$eu['id']) throw new RuntimeException('Você não pode excluir o seu próprio usuário.');
        q('DELETE FROM usuarios WHERE id = ?', [$alvo]);
        flash('Usuário excluído.');
    }
    redirecionar('admin/usuarios');
}
$lista = q('SELECT id, nome, email, criado_em FROM usuarios ORDER BY nome')->fetchAll();
admin_inicio('Usuários', 'usuarios');
?>
<div class="grid-2">
  <section class="caixa">
    <div class="caixa-cab"><h2>Quem acessa o painel</h2></div>
    <ul class="lista-usuarios">
      <?php foreach ($lista as $l): ?>
        <li>
          <span class="avatar"><?= e(mb_strtoupper(mb_substr($l['nome'], 0, 1))) ?></span>
          <div><b><?= e($l['nome']) ?><?= (int)$l['id'] === (int)$eu['id'] ? ' <em>(você)</em>' : '' ?></b><small><?= e($l['email']) ?></small></div>
          <?php if ((int)$l['id'] !== (int)$eu['id']): ?>
            <form method="post" data-confirmar="Excluir o usuário <?= e($l['nome']) ?>?"><?= campo_csrf() ?><input type="hidden" name="acao" value="excluir"><input type="hidden" name="id" value="<?= (int)$l['id'] ?>"><button class="btn-ic perigo" type="submit" title="Excluir"><?= icone('trash') ?></button></form>
          <?php endif; ?>
        </li>
      <?php endforeach; ?>
    </ul>
    <h3 class="sub">Adicionar usuário</h3>
    <form method="post" class="pilha">
      <?= campo_csrf() ?><input type="hidden" name="acao" value="novo">
      <label class="campo">Nome<input type="text" name="nome" required maxlength="120"></label>
      <label class="campo">E-mail<input type="email" name="email" required maxlength="190"></label>
      <label class="campo">Senha inicial (mín. 8 caracteres)<input type="password" name="senha" required minlength="8" autocomplete="new-password"></label>
      <button class="btn btn-primario" type="submit"><?= icone('plus') ?> Adicionar</button>
    </form>
  </section>
  <section class="caixa">
    <div class="caixa-cab"><h2>Trocar minha senha</h2></div>
    <form method="post" class="pilha">
      <?= campo_csrf() ?><input type="hidden" name="acao" value="senha">
      <label class="campo">Senha atual<input type="password" name="atual" required autocomplete="current-password"></label>
      <label class="campo">Nova senha<input type="password" name="nova" required minlength="8" autocomplete="new-password"></label>
      <button class="btn btn-primario" type="submit"><?= icone('check') ?> Alterar senha</button>
    </form>
  </section>
</div>
<?php admin_fim();
