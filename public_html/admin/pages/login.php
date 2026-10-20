<?php
if (usuario()) redirecionar('admin');
$erro = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_ok($_POST['csrf'] ?? null)) {
        $erro = 'Sessão expirada. Tente novamente.';
    } else {
        $ip = ip_cliente();
        q('DELETE FROM login_tentativas WHERE momento < NOW() - INTERVAL 1 DAY');
        $tent = (int)q('SELECT COUNT(*) FROM login_tentativas WHERE ip = ? AND momento > NOW() - INTERVAL 15 MINUTE', [$ip])->fetchColumn();
        if ($tent >= 6) {
            $erro = 'Muitas tentativas. Aguarde 15 minutos.';
        } else {
            $u = q('SELECT * FROM usuarios WHERE email = ?', [mb_strtolower(trim((string)($_POST['email'] ?? '')))])->fetch();
            if ($u && password_verify((string)($_POST['senha'] ?? ''), $u['senha_hash'])) {
                session_regenerate_id(true);
                $_SESSION['uid'] = (int)$u['id'];
                $_SESSION['sv'] = (int)$u['sessao_versao'];
                q('DELETE FROM login_tentativas WHERE ip = ?', [$ip]);
                if (password_needs_rehash($u['senha_hash'], PASSWORD_DEFAULT)) q('UPDATE usuarios SET senha_hash = ? WHERE id = ?', [password_hash($_POST['senha'], PASSWORD_DEFAULT), $u['id']]);
                redirecionar('admin');
            }
            q('INSERT INTO login_tentativas (ip) VALUES (?)', [$ip]);
            $erro = 'E-mail ou senha incorretos.';
        }
    }
}
?>
<!doctype html>
<html lang="pt-BR">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Entrar · Painel Máxima</title>
<meta name="robots" content="noindex">
<link rel="icon" href="<?= e(url('assets/img/logo.png')) ?>">
<link href="https://fonts.googleapis.com/css2?family=Anton&family=Outfit:wght@400;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="<?= e(asset('css/admin.css')) ?>">
</head>
<body class="tela-login">
  <form class="login-caixa" method="post">
    <img src="<?= e(url('assets/img/logo.png')) ?>" alt="Rádio Máxima FM" width="96" height="96">
    <h1>Painel da Máxima</h1>
    <p>Entre para gerenciar o site.</p>
    <?php if ($erro): ?><div class="aviso aviso-erro"><?= e($erro) ?></div><?php endif; ?>
    <?= campo_csrf() ?>
    <label>E-mail<input type="email" name="email" required autofocus autocomplete="username" value="<?= e($_POST['email'] ?? '') ?>"></label>
    <label>Senha<input type="password" name="senha" required autocomplete="current-password"></label>
    <button class="btn btn-primario" type="submit">Entrar</button>
    <a class="voltar-site" href="<?= e(url('/')) ?>">← Voltar ao site</a>
  </form>
</body>
</html>
