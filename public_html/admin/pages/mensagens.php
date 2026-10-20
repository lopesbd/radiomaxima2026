<?php
// Mensagens enviadas pelo formulário de contato (pedidos de música, recados, comercial).
$filtro = $_GET['filtro'] ?? '';
$where = $filtro === 'nao-lidas' ? 'WHERE lida = 0' : ($filtro !== '' && $filtro !== 'todas' ? 'WHERE assunto = ?' : '');
$params = ($where && $filtro !== 'nao-lidas') ? [$filtro] : [];
$lista = q("SELECT * FROM mensagens $where ORDER BY criado_em DESC LIMIT 200", $params)->fetchAll();
$assuntos = q('SELECT assunto, COUNT(*) n, SUM(lida = 0) nl FROM mensagens GROUP BY assunto ORDER BY n DESC')->fetchAll();
admin_inicio('Mensagens', 'mensagens');
?>
<div class="chips-admin">
  <a href="?" class="<?= $filtro === '' ? 'ativo' : '' ?>">Todas</a>
  <a href="?filtro=nao-lidas" class="<?= $filtro === 'nao-lidas' ? 'ativo' : '' ?>">Não lidas</a>
  <?php foreach ($assuntos as $a): ?><a href="?filtro=<?= rawurlencode($a['assunto']) ?>" class="<?= $filtro === $a['assunto'] ? 'ativo' : '' ?>"><?= e($a['assunto']) ?> <small><?= (int)$a['n'] ?></small></a><?php endforeach; ?>
</div>
<?php if (!$lista): ?><p class="vazio">Nenhuma mensagem aqui.</p><?php endif; ?>
<div class="mensagens">
  <?php foreach ($lista as $m):
      $fone = preg_replace('/\D/', '', $m['telefone']);
      if ($fone && strlen($fone) <= 11) $fone = '55' . $fone; ?>
    <article class="mensagem<?= $m['lida'] ? '' : ' nao-lida' ?>" id="m<?= (int)$m['id'] ?>" data-id="<?= (int)$m['id'] ?>">
      <header>
        <div><b><?= e($m['nome']) ?></b> <span class="etiqueta"><?= e($m['assunto']) ?></span></div>
        <time><?= date('d/m/Y H:i', strtotime($m['criado_em'])) ?></time>
      </header>
      <p><?= nl2br(e($m['mensagem'])) ?></p>
      <footer>
        <span class="contatos-msg">
          <?php if ($m['cidade']): ?><span><?= icone('pin') ?> <?= e($m['cidade']) ?></span><?php endif; ?>
          <?php if ($m['email']): ?><a href="mailto:<?= e($m['email']) ?>"><?= icone('mail') ?> <?= e($m['email']) ?></a><?php endif; ?>
          <?php if ($fone): ?><a href="https://wa.me/<?= e($fone) ?>" target="_blank" rel="noopener"><?= icone('whatsapp') ?> <?= e($m['telefone']) ?></a><?php endif; ?>
        </span>
        <span class="item-acoes">
          <label class="interruptor"><input type="checkbox" data-alternar="mensagens" data-campo="lida" data-id="<?= (int)$m['id'] ?>"<?= $m['lida'] ? ' checked' : '' ?>><span></span> <em>Lida</em></label>
          <button class="btn-ic perigo" type="button" data-excluir="mensagens" data-id="<?= (int)$m['id'] ?>" title="Excluir"><?= icone('trash') ?></button>
        </span>
      </footer>
    </article>
  <?php endforeach; ?>
</div>
<?php admin_fim();
