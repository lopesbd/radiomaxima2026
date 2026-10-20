<?php
// Layout público. O conteúdo de cada página fica em <main id="app">; o player fica FORA dele,
// por isso a navegação via fetch (assets/js/site.js) troca só o <main> e o áudio não para.

final class NaoEncontrado extends Exception {}

function nao_encontrado(): void
{
    throw new NaoEncontrado();
}

function menu_principal(): array
{
    return [
        'home' => ['Início', '/'],
        'sobre' => ['A Rádio', '/a-radio'],
        'programacao' => ['Programação', '/programacao'],
        'noticias' => ['Notícias', '/noticias'],
        'eventos' => ['Eventos', '/eventos'],
        'contato' => ['Contato', '/contato'],
    ];
}

function link_whatsapp(string $texto = ''): string
{
    $n = preg_replace('/\D/', '', cfg('whatsapp'));
    if (!$n) return '';
    return 'https://wa.me/' . $n . ($texto ? '?text=' . rawurlencode($texto) : '');
}

function redes_sociais(): string
{
    $html = '';
    foreach (['instagram' => 'Instagram', 'facebook' => 'Facebook', 'youtube' => 'YouTube'] as $k => $rot) {
        if (cfg($k)) $html .= '<a href="' . e(cfg($k)) . '" target="_blank" rel="noopener" aria-label="' . $rot . '">' . icone($k) . '</a>';
    }
    if ($w = link_whatsapp()) $html .= '<a href="' . e($w) . '" target="_blank" rel="noopener" aria-label="WhatsApp">' . icone('whatsapp') . '</a>';
    return $html;
}

/** Botões das lojas de aplicativo. Sem link cadastrado, aparece como "em breve". */
function botoes_lojas(string $classe = ''): string
{
    $lojas = [
        ['app_ios', 'apple', 'Baixar na', 'App Store', 'iPhone'],
        ['app_android', 'play-store', 'Disponível no', 'Google Play', 'Android'],
    ];
    $html = '<div class="lojas ' . e($classe) . '">';
    foreach ($lojas as [$chave, $ic, $pre, $loja, $sistema]) {
        $link = cfg($chave);
        $html .= $link
            ? '<a class="loja" href="' . e($link) . '" target="_blank" rel="noopener" aria-label="' . e("$pre $loja ($sistema)") . '">'
            : '<span class="loja em-breve" aria-label="' . e("App para $sistema em breve") . '">';
        $html .= icone($ic, 'ic loja-ic') . '<span><small>' . ($link ? e($pre) : 'Em breve na') . '</small><b>' . e($loja) . '</b></span>';
        $html .= $link ? '</a>' : '</span>';
    }
    return $html . '</div>';
}

/** Faixa "Baixe o aplicativo" com celular ilustrado. */
function secao_app(?array $atual): string
{
    ob_start(); ?>
<section class="secao secao-app" id="baixe-o-app" aria-labelledby="tituloApp">
  <div class="container app-caixa">
    <div class="app-luzes" aria-hidden="true"><span></span><span></span></div>
    <div class="app-txt">
      <span class="app-selo"><?= icone('smartphone') ?> App oficial · grátis</span>
      <h2 id="tituloApp">A Máxima <em>no seu bolso</em></h2>
      <p>Baixe o aplicativo da <?= e(cfg('nome')) ?> e leve a rádio com você: no trabalho, no carro, na academia ou onde estiver.</p>
      <ul class="app-vantagens">
        <li><?= icone('radio') ?> Ao vivo 24 horas com um toque</li>
        <li><?= icone('volume') ?> Continua tocando com a tela bloqueada</li>
        <li><?= icone('music') ?> Peça música e participe da programação</li>
      </ul>
      <?= botoes_lojas() ?>
    </div>
    <div class="app-celular" aria-hidden="true">
      <span class="nota n1">♪</span><span class="nota n2">♫</span><span class="nota n3">♪</span>
      <div class="celular">
        <div class="celular-tela">
          <div class="celular-ilha"></div>
          <small class="tela-topo">AO VIVO · <?= e(cfg('frequencia')) ?> FM</small>
          <div class="tela-disco"><img src="<?= e(url('assets/img/logo.png')) ?>" alt="" loading="lazy"></div>
          <b class="tela-prog"><?= e($atual['nome'] ?? cfg('nome')) ?></b>
          <small class="tela-apres"><?= e($atual['apresentador'] ?? cfg('slogan')) ?></small>
          <div class="tela-eq"><i></i><i></i><i></i><i></i><i></i><i></i><i></i></div>
          <div class="tela-controles"><span></span><span class="tela-play"><?= icone('pause') ?></span><span></span></div>
        </div>
      </div>
    </div>
  </div>
</section>
<?php
    return ob_get_clean();
}

function renderizar(string $pagina, ?string $param): void
{
    $csrf = csrf_token(); // inicia a sessão antes de qualquer saída (usado pelo chat)
    $meta = ['titulo' => '', 'descricao' => cfg('nome') . ' ' . cfg('frequencia') . ' — ' . cfg('slogan') . ' Ouça ao vivo, confira a programação, notícias e eventos.', 'imagem' => null, 'menu' => $pagina];
    ob_start();
    try {
        require APP . "/pages/$pagina.php";
    } catch (NaoEncontrado $e) {
        ob_clean();
        $pagina = '404';
        $meta['menu'] = '';
        require APP . '/pages/404.php';
    }
    $conteudo = ob_get_clean();
    if ($pagina === '404') http_response_code(404);

    $nomeRadio = cfg('nome', 'Rádio Máxima FM');
    $tituloCompleto = ($meta['titulo'] ? $meta['titulo'] . ' | ' : '') . $nomeRadio . ' ' . cfg('frequencia');
    $main = '<main id="app" data-page="' . e($pagina) . '" data-menu="' . e($meta['menu']) . '" tabindex="-1">' . $conteudo . '</main>';

    header('Vary: X-Pjax');
    if (($_SERVER['HTTP_X_PJAX'] ?? '') === '1') {
        // navegação interna: devolve só o título e o conteúdo
        echo '<title>' . e($tituloCompleto) . '</title><meta name="description" content="' . e($meta['descricao']) . '">' . $main;
        return;
    }

    $agora = no_ar();
    $atual = $agora['atual'];
    $imagemOg = url_absoluta($meta['imagem'] ?: 'assets/img/logo.png');
    ?>
<!doctype html>
<html lang="pt-BR">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
<title><?= e($tituloCompleto) ?></title>
<meta name="description" content="<?= e($meta['descricao']) ?>">
<meta name="theme-color" content="#0b0b0d">
<meta property="og:title" content="<?= e($tituloCompleto) ?>">
<meta property="og:description" content="<?= e($meta['descricao']) ?>">
<meta property="og:image" content="<?= e($imagemOg) ?>">
<meta property="og:type" content="website">
<link rel="icon" href="<?= e(url('assets/img/logo.png')) ?>">
<link rel="apple-touch-icon" href="<?= e(url('assets/img/logo.png')) ?>">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Anton&family=Outfit:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
<link rel="stylesheet" href="<?= e(asset('css/site.css')) ?>">
</head>
<body>
<a class="pular" href="#app">Pular para o conteúdo</a>
<div class="fundo-fx" aria-hidden="true"><span></span><span></span><span></span></div>

<header class="topo" id="topo">
  <div class="topo-in">
    <a class="marca" href="<?= e(url('/')) ?>" aria-label="<?= e($nomeRadio) ?> — início">
      <img src="<?= e(url('assets/img/logo.png')) ?>" alt="" width="56" height="56">
      <span class="marca-txt"><b>Máxima</b><small>FM <?= e(cfg('frequencia')) ?></small></span>
    </a>
    <nav class="menu" id="menu" aria-label="Menu principal">
      <?php foreach (menu_principal() as $chave => [$rotulo, $href]): ?>
        <a href="<?= e(url($href)) ?>" data-menu="<?= $chave ?>"<?= $meta['menu'] === $chave ? ' class="ativo" aria-current="page"' : '' ?>><?= e($rotulo) ?></a>
      <?php endforeach; ?>
      <div class="menu-redes"><?= redes_sociais() ?></div>
    </nav>
    <button class="btn-ao-vivo" type="button" data-play aria-label="Ouvir ao vivo">
      <span class="pulso"></span><span class="txt-ao-vivo">Ao vivo</span>
    </button>
    <button class="btn-menu" id="btnMenu" type="button" aria-label="Abrir menu" aria-expanded="false" aria-controls="menu"><?= icone('menu') ?></button>
  </div>
</header>

<?= $main ?>

<?php $patro = q('SELECT nome, logo, link FROM patrocinadores WHERE ativo = 1 ORDER BY ordem, id')->fetchAll(); ?>
<?php if ($patro): ?>
<section class="faixa-patro" aria-label="Patrocinadores">
  <div class="faixa-patro-tit"><span>Patrocínio</span></div>
  <div class="marquee" data-marquee>
    <div class="marquee-trilho">
      <?php for ($rep = 0; $rep < 2; $rep++): foreach ($patro as $p): ?>
        <a class="patro-item" href="<?= e(url($p['link'] ?: '/patrocinadores')) ?>"<?= preg_match('#^https?://#', $p['link']) ? ' target="_blank" rel="noopener"' : '' ?><?= $rep ? ' aria-hidden="true" tabindex="-1"' : '' ?>>
          <?php if ($p['logo']): ?><img src="<?= e(midia($p['logo'], true)) ?>" alt="<?= e($p['nome']) ?>" loading="lazy"><?php else: ?><span><?= e($p['nome']) ?></span><?php endif; ?>
        </a>
      <?php endforeach; endfor; ?>
    </div>
  </div>
</section>
<?php endif; ?>

<footer class="rodape">
  <div class="rodape-in">
    <div class="rodape-marca">
      <img src="<?= e(url('assets/img/logo.png')) ?>" alt="<?= e($nomeRadio) ?>" width="90" height="90" loading="lazy">
      <p class="slogan"><?= e(cfg('slogan')) ?></p>
      <div class="redes"><?= redes_sociais() ?></div>
      <p class="rodape-app"><?= icone('smartphone') ?> Baixe o app da Máxima</p>
      <?= botoes_lojas('pequenas') ?>
    </div>
    <div>
      <h4>Navegue</h4>
      <ul>
        <?php foreach (menu_principal() as [$rotulo, $href]): ?><li><a href="<?= e(url($href)) ?>"><?= e($rotulo) ?></a></li><?php endforeach; ?>
        <li><a href="<?= e(url('/patrocinadores')) ?>">Anuncie / Patrocinadores</a></li>
      </ul>
    </div>
    <div>
      <h4>Fale com a Máxima</h4>
      <ul class="contatos">
        <?php if (cfg('telefone')): ?><li><?= icone('phone') ?><a href="tel:<?= e(preg_replace('/\D/', '', cfg('telefone'))) ?>"><?= e(cfg('telefone')) ?></a></li><?php endif; ?>
        <?php if ($w = link_whatsapp()): ?><li><?= icone('whatsapp') ?><a href="<?= e($w) ?>" target="_blank" rel="noopener">WhatsApp</a></li><?php endif; ?>
        <?php if (cfg('email')): ?><li><?= icone('mail') ?><a href="mailto:<?= e(cfg('email')) ?>"><?= e(cfg('email')) ?></a></li><?php endif; ?>
        <?php if (cfg('endereco')): ?><li><?= icone('pin') ?><span><?= e(cfg('endereco')) ?></span></li><?php endif; ?>
      </ul>
    </div>
    <div class="rodape-freq">
      <span class="freq-num"><?= e(cfg('frequencia')) ?></span>
      <span class="freq-fm">FM</span>
      <small><?= e(cfg('cidade')) ?></small>
    </div>
  </div>
  <div class="rodape-base">© <?= date('Y') ?> <?= e($nomeRadio) ?> · Todos os direitos reservados</div>
</footer>

<div class="player" id="player" data-stream="<?= e(cfg('stream_url')) ?>" data-agora="<?= e(url('api/agora.php')) ?>" data-logo="<?= e(url('assets/img/logo.png')) ?>">
  <div class="player-in">
    <div class="player-capa" id="playerCapa" style="--cor: <?= e($atual['cor'] ?? '#e3161b') ?>">
      <img id="playerFoto" src="<?= e($atual && $atual['foto'] ? midia($atual['foto'], true) : url('assets/img/logo.png')) ?>" alt="">
    </div>
    <div class="player-info">
      <span class="selo-ao-vivo"><i></i>AO VIVO · <?= e(cfg('frequencia')) ?> FM</span>
      <strong id="playerPrograma"><?= e($atual['nome'] ?? cfg('nome')) ?></strong>
      <small id="playerApresentador"><?= e($atual ? trim($atual['apresentador'] . ' · ' . hora((int)$atual['inicio']) . ' – ' . hora((int)$atual['fim']), ' ·') : cfg('slogan')) ?></small>
    </div>
    <div class="eq" aria-hidden="true"><i></i><i></i><i></i><i></i><i></i></div>
    <button class="player-play" id="playerPlay" type="button" aria-label="Tocar a rádio ao vivo">
      <span class="ic-play"><?= icone('play') ?></span><span class="ic-pause"><?= icone('pause') ?></span><span class="carregando"></span>
    </button>
    <div class="player-vol">
      <button type="button" id="playerMudo" aria-label="Silenciar"><span class="ic-vol"><?= icone('volume') ?></span><span class="ic-mudo"><?= icone('mute') ?></span></button>
      <input type="range" id="playerVolume" min="0" max="1" step="0.01" value="1" aria-label="Volume">
    </div>
  </div>
  <div class="player-aviso" id="playerAviso" hidden>
    <button type="button" data-play><?= icone('play') ?> Toque aqui para ouvir a Máxima ao vivo</button>
    <button type="button" class="fechar" id="avisoFechar" aria-label="Fechar aviso"><?= icone('close') ?></button>
  </div>
  <audio id="radio" preload="none" playsinline></audio>
</div>

<div class="chat" id="chat"
     data-api="<?= e(url('api/contato.php')) ?>" data-csrf="<?= e($csrf) ?>"
     data-whats="<?= e(link_whatsapp()) ?>" data-alcance="<?= e(cfg('alcance')) ?>" data-localidades="<?= e(cfg('localidades')) ?>"
     data-formatos="<?= e(json_encode(cfg_lista('formatos'), JSON_UNESCAPED_UNICODE)) ?>" data-anuncie="<?= e(url('/patrocinadores')) ?>">
  <div class="chat-balao" id="chatBalao" hidden>
    <button type="button" class="chat-balao-txt" data-abrir-chat="musica">🎶 Peça sua música aqui!</button>
    <button type="button" class="chat-balao-x" id="chatBalaoX" aria-label="Fechar aviso"><?= icone('close') ?></button>
  </div>
  <section class="chat-janela" id="chatJanela" role="dialog" aria-label="Chat com a Rádio Máxima" aria-hidden="true">
    <header class="chat-cab">
      <div class="chat-avatar"><img src="<?= e(url('assets/img/logo.png')) ?>" alt="" width="42" height="42"><i></i></div>
      <div class="chat-cab-txt"><b>Rádio Máxima</b><small>online · fale com o estúdio</small></div>
      <button type="button" class="chat-cab-btn" id="chatReiniciar" aria-label="Recomeçar conversa" title="Recomeçar"><?= icone('refresh') ?></button>
      <button type="button" class="chat-cab-btn" id="chatFechar" aria-label="Fechar chat" title="Fechar"><?= icone('close') ?></button>
    </header>
    <div class="chat-msgs" id="chatMsgs" aria-live="polite"></div>
    <div class="chat-opcoes" id="chatOpcoes"></div>
    <form class="chat-entrada" id="chatForm" autocomplete="on">
      <input type="text" id="chatInput" placeholder="Escolha uma opção acima" aria-label="Sua mensagem" maxlength="1000" disabled>
      <button type="submit" id="chatEnviar" aria-label="Enviar" disabled><?= icone('send') ?></button>
    </form>
  </section>
  <button type="button" class="chat-botao" id="chatBotao" aria-expanded="false" aria-controls="chatJanela" aria-label="Abrir chat com a Rádio Máxima">
    <span class="chat-botao-ic abrir"><?= icone('chat') ?></span>
    <span class="chat-botao-ic fechar"><?= icone('close') ?></span>
    <span class="chat-ponto" aria-hidden="true"></span>
  </button>
</div>

<div class="lightbox" id="lightbox" hidden>
  <button class="lb-fechar" type="button" aria-label="Fechar"><?= icone('close') ?></button>
  <button class="lb-ant" type="button" aria-label="Anterior"><?= icone('left') ?></button>
  <figure><img alt=""><figcaption></figcaption></figure>
  <button class="lb-prox" type="button" aria-label="Próxima"><?= icone('right') ?></button>
</div>

<script src="<?= e(asset('js/site.js')) ?>" defer></script>
<script src="<?= e(asset('js/chat.js')) ?>" defer></script>
</body>
</html>
<?php
}
