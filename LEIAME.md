# Site da Rádio Máxima FM 97,1

PHP 8 + MySQL, sem etapa de build (feito para hospedagem compartilhada como a Hostinger).

## O que tem

**Site público**
- **Player ao vivo fixo no rodapé**, que continua tocando ao trocar de página: a navegação interna troca só o conteúdo (`<main>`) via `fetch`, e o `<audio>` nunca é recarregado. O botão voltar do navegador também funciona.
- **Toca sozinho ao abrir o site.** Os navegadores (Chrome, Safari, Firefox) bloqueiam som automático de sites que o visitante ainda não usou. Quando isso acontece, aparece o aviso “Toque aqui para ouvir” e a rádio começa no **primeiro clique ou toque em qualquer lugar** da página. Se o visitante pausar, o site respeita a escolha até ele fechar a aba.
- Controles na tela de bloqueio do celular (Media Session) com o nome do programa no ar.
- **Programação** por dia da semana, abrindo no dia de hoje e destacando o programa **no ar agora** com barra de progresso. O horário vem do servidor (fuso de Brasília), então funciona mesmo que o relógio do visitante esteja errado, e atualiza sozinho na troca de programa.
- **Banner de destaque** (carrossel) na página inicial, “No ar agora / A seguir”, últimas notícias, eventos, números da rádio e chamadas para pedir música e anunciar.
- **Notícias** com categorias, busca, paginação e compartilhamento (WhatsApp/Facebook/copiar link).
- **Eventos** com galeria de fotos em mosaico e visualizador em tela cheia (setas, teclado e deslizar no celular).
- **Patrocinadores**: faixa rolando em todas as páginas + página `/patrocinadores` com os formatos para anunciantes.
- **Chat “Fale com a Máxima”** no canto inferior direito, em todas as páginas: atendimento guiado para **pedir música** (música, nome, cidade, recado e WhatsApp), **anunciar** (mostra alcance e formatos e coleta os dados para o comercial) e **falar com a rádio**. Valida telefone/e-mail, mostra um resumo para confirmar e grava em *Mensagens* no painel. Continua aberto ao trocar de página e lembra o nome do ouvinte.
- **Baixe o app**: faixa de destaque na página inicial com celular ilustrado e botões App Store (iPhone) e Google Play (Android), também no rodapé. Os links são cadastrados em *Configurações → Aplicativo*; sem link, o botão aparece como “Em breve”.
- **A Rádio** (texto editável, cidades da área de cobertura) e **Contato** (pedido de música, recado, anunciar…), que grava as mensagens no painel.

**Painel (`/admin`)**
- **Programação estilo agenda**: arraste um programa da lista para o dia/horário, arraste os blocos para mudar dia e hora, puxe as bordas para mudar a duração, `Ctrl` + arrastar copia. Conflitos de horário são recusados. Também dá para copiar um dia inteiro para outros dias (ex.: segunda → terça a sexta).
- **Banners** e **patrocinadores**: ordem por arrastar, ligar/desligar, imagem por arrastar-e-soltar.
- **Notícias**: editor visual (negrito, títulos, listas, citação, links, imagens arrastadas para o texto, vídeo do YouTube), capa, categoria, destaque, agendamento por data.
- **Eventos**: envie dezenas de fotos de uma vez arrastando para a página (com barra de progresso), reordene arrastando, escolha a capa e escreva legendas direto na foto. As imagens são reduzidas e convertidas para WebP automaticamente, com miniaturas.
- **Mensagens** do formulário (com atalho para responder no WhatsApp), **Configurações** (contatos, redes, endereço do stream, textos) e **Usuários**.

Segurança: senhas com `password_hash`, limite de tentativas de login, token CSRF em todas as ações, HTML das notícias filtrado no servidor, uploads validados pelo conteúdo e sem execução de scripts na pasta `uploads/`, cabeçalhos CSP.

## Estrutura

```
radio-maxima/
├── database/
│   ├── schema.sql      estrutura das tabelas
│   └── seed.sql        conteúdo inicial (programação, notícias, eventos e patrocinadores são EXEMPLOS)
└── public_html/        ← vai inteiro para a hospedagem
    ├── index.php       rotas do site
    ├── admin/          painel
    ├── api/            no ar agora (JSON) e formulário de contato
    ├── app/            núcleo, layout e páginas (bloqueado ao acesso direto)
    ├── assets/         css, js, logo
    └── uploads/        imagens enviadas pelo painel
```

## Publicar na Hostinger

1. **Banco**: no hPanel → *Bancos de dados MySQL*, crie o banco e o usuário. No phpMyAdmin importe `database/schema.sql` e depois `database/seed.sql`.
2. **Arquivos**: envie o conteúdo de `public_html/` para o `public_html` do domínio. **Não envie** `app/config.php` (é o de desenvolvimento).
3. **Configuração**: copie `public_html/app/config.example.php` para **um nível acima** do `public_html` (ex.: `~/domains/radiomaxima.com.br/config.php`) e preencha os dados do banco. Assim a senha fica fora da pasta pública.
4. **Primeiro usuário do painel**: no terminal SSH da Hostinger gere o hash da senha
   ```
   php -r "echo password_hash('SUA-SENHA-FORTE', PASSWORD_DEFAULT), PHP_EOL;"
   ```
   e no phpMyAdmin rode
   ```sql
   INSERT INTO usuarios (nome, email, senha_hash) VALUES ('Seu nome', 'seu@email.com', 'HASH-GERADO');
   ```
   Os demais usuários podem ser criados pelo próprio painel.
5. Acesse `https://seudominio/admin`, troque a programação, notícias, eventos e patrocinadores de exemplo pelos reais e confira os contatos em *Configurações*.
6. Se o site ficar numa subpasta, ajuste `base_path` no `config.php`. Para forçar HTTPS, descomente as duas linhas indicadas no `.htaccess`.

Requisitos: PHP 8.0+ com `pdo_mysql`, `gd` (com WebP) e `fileinfo`; MySQL 5.7+ ou MariaDB 10.3+. Para enviar muitas fotos grandes de uma vez, `upload_max_filesize` ≥ 16M (o painel envia uma foto por requisição).

## Desenvolvimento local

- Servidor: entrada `radio-maxima` no `.claude/launch.json` (`php -S localhost:9015 -t public_html`).
- MySQL local próprio na porta **3309**, dados em `C:/Users/samue/.radio-maxima-mysql/data`. Para iniciar:
  `"C:/Program Files/MySQL/MySQL Server 8.4/bin/mysqld" --datadir="C:/Users/samue/.radio-maxima-mysql/data" --port=3309 --mysqlx=OFF`
- Login local do painel em `database/.credenciais-locais.txt`.
