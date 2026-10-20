-- Conteúdo inicial / de EXEMPLO. Programação, notícias, eventos e patrocinadores
-- abaixo são fictícios: edite ou apague pelo painel (/admin).
SET NAMES utf8mb4;
SET time_zone = '-03:00'; -- datas de exemplo no horário de Brasília

INSERT INTO config (chave, valor) VALUES
('nome', 'Rádio Máxima FM'),
('frequencia', '97,1'),
('slogan', 'É tudo de bom!'),
('cidade', 'Bom Despacho/MG'),
('stream_url', 'https://maxima.streams.com.br/stream'),
('telefone', '(37) 3522-4444'),
('whatsapp', '553735224444'),
('email', 'maxima@radiomaxima.com.br'),
('endereco', 'Bom Despacho - MG'),
('instagram', ''),
('facebook', 'https://www.facebook.com/R%C3%A1dio-M%C3%A1xima-FM-971-171132503022443'),
('youtube', ''),
('app_android', ''),
('app_ios', ''),
('alcance', '1,4 milhão'),
('localidades', '75'),
('sobre_titulo', 'A voz do Centro-Oeste mineiro'),
('sobre_texto', '<p>A <strong>Rádio Máxima FM 97,1</strong> fica em Bom Despacho, no Centro-Oeste de Minas Gerais — a região de maior densidade populacional do estado. Falamos todos os dias com aproximadamente <strong>1,4 milhão de pessoas</strong>.</p><p>Nossa programação tem estilo <strong>Popular Hits</strong>: sucessos da atualidade, sertanejo, MPB, axé e flash back, para o público jovem e adulto de todas as classes. Além da música, levamos informação, prestação de serviço e entretenimento para mais de 75 localidades.</p><h3>Para os anunciantes</h3><p>Com a Máxima você atinge todo o Centro-Oeste mineiro com o melhor custo-benefício e formatos flexíveis na programação comercial:</p><ul><li>Promoções com artistas</li><li>Brincadeiras, sorteios e concursos</li><li>Ações promocionais e projetos especiais</li><li>Shows e eventos</li><li>Pacotes especiais e flashes ao vivo</li></ul>'),
('cidades', 'Abaeté\nAraújos\nArcos\nBambuí\nBom Despacho\nCarmo do Cajuru\nDivinópolis\nDores do Indaiá\nIgaratinga\nItaguara\nItaúna\nLagoa da Prata\nLeandro Ferreira\nLuz\nMartinho Campos\nMedeiros\nMorada Nova de Minas\nNova Serrana\nPará de Minas\nPerdigão\nPitangui\nPompéu\nSanto Antônio do Monte\nSão Gonçalo do Pará\nSão Gotardo\nTiros'),
('formatos', 'Promoções com artistas\nSorteios e concursos\nAções promocionais\nProjetos especiais\nShows e eventos\nFlashes ao vivo');

INSERT INTO programas (id, nome, apresentador, descricao, cor) VALUES
(1, 'Madrugada Máxima', 'Equipe Máxima', 'Música sem parar para quem trabalha ou curte a noite.', '#6d28d9'),
(2, 'Despertar Máxima', 'Equipe Máxima', 'Comece o dia com boa música, hora certa e previsão do tempo.', '#f59e0b'),
(3, 'Bom Dia Máxima', 'Equipe Máxima', 'Notícias da região, trânsito, entrevistas e a sua participação ao vivo.', '#eab308'),
(4, 'Máxima Hits', 'Equipe Máxima', 'Os maiores sucessos do momento, um atrás do outro.', '#e3161b'),
(5, 'Jornal da Máxima', 'Equipe Máxima', 'O resumo das notícias de Bom Despacho, do Centro-Oeste e do Brasil.', '#0ea5e9'),
(6, 'Tarde Máxima', 'Equipe Máxima', 'Pedidos musicais, promoções e muita interação com o ouvinte.', '#f97316'),
(7, 'Happy Hour Máxima', 'Equipe Máxima', 'Fim de expediente com os hits para relaxar.', '#ec4899'),
(8, 'Sertanejo Máximo', 'Equipe Máxima', 'O melhor do sertanejo, do raiz ao universitário.', '#16a34a'),
(9, 'Flash Back Máxima', 'Equipe Máxima', 'Os clássicos que marcaram época.', '#8b5cf6'),
(10, 'Sábado Máximo', 'Equipe Máxima', 'Sábado animado com sucessos e participação dos ouvintes.', '#dc2626'),
(11, 'Balada Máxima', 'Equipe Máxima', 'Esquenta para a noite de sábado.', '#db2777'),
(12, 'Domingão Máximo', 'Equipe Máxima', 'Domingo em família com música para todos os gostos.', '#ca8a04');

INSERT INTO grade (programa_id, dia, inicio, fim) VALUES
-- segunda a sexta
(1,1,0,300),(2,1,300,420),(3,1,420,540),(4,1,540,720),(5,1,720,780),(6,1,780,1020),(7,1,1020,1140),(8,1,1140,1260),(9,1,1260,1440),
(1,2,0,300),(2,2,300,420),(3,2,420,540),(4,2,540,720),(5,2,720,780),(6,2,780,1020),(7,2,1020,1140),(8,2,1140,1260),(9,2,1260,1440),
(1,3,0,300),(2,3,300,420),(3,3,420,540),(4,3,540,720),(5,3,720,780),(6,3,780,1020),(7,3,1020,1140),(8,3,1140,1260),(9,3,1260,1440),
(1,4,0,300),(2,4,300,420),(3,4,420,540),(4,4,540,720),(5,4,720,780),(6,4,780,1020),(7,4,1020,1140),(8,4,1140,1260),(9,4,1260,1440),
(1,5,0,300),(2,5,300,420),(3,5,420,540),(4,5,540,720),(5,5,720,780),(6,5,780,1020),(7,5,1020,1140),(8,5,1140,1260),(9,5,1260,1440),
-- sábado
(1,6,0,360),(2,6,360,540),(10,6,540,780),(4,6,780,1080),(11,6,1080,1320),(9,6,1320,1440),
-- domingo
(1,0,0,420),(2,0,420,600),(12,0,600,840),(4,0,840,1140),(9,0,1140,1440);

INSERT INTO banners (titulo, subtitulo, link, botao, ordem) VALUES
('É tudo de bom!', 'Máxima FM 97,1 — a rádio que mais toca no Centro-Oeste mineiro. Ouça agora, ao vivo.', '#ouvir', 'Ouvir ao vivo', 1),
('Anuncie na Máxima', 'Sua marca falando com 1,4 milhão de pessoas em mais de 75 localidades.', '/contato', 'Quero anunciar', 2),
('Confira a programação', 'Veja o que está no ar agora e tudo o que vem por aí, dia a dia.', '/programacao', 'Ver programação', 3);

INSERT INTO noticias (titulo, slug, resumo, conteudo, categoria, destaque, publicado_em) VALUES
('Novo site da Máxima FM está no ar', 'novo-site-da-maxima-fm-esta-no-ar', 'Agora você ouve a Máxima ao vivo enquanto navega, confere a programação em tempo real e acompanha tudo o que acontece na região.', '<p>A Rádio Máxima FM 97,1 estreia um site totalmente novo. O player ao vivo continua tocando enquanto você navega entre as páginas, e a programação mostra automaticamente o que está no ar.</p><p>Também tem notícias, galerias de fotos dos eventos e um canal direto para você falar com a gente.</p><p><em>Esta é uma notícia de exemplo — edite ou apague pelo painel.</em></p>', 'Máxima', 1, NOW()),
('Agenda cultural do fim de semana no Centro-Oeste', 'agenda-cultural-do-fim-de-semana', 'Shows, feiras e eventos esportivos movimentam as cidades da região neste fim de semana.', '<p>Confira a programação cultural das cidades da nossa região e aproveite o fim de semana.</p><p><em>Notícia de exemplo.</em></p>', 'Cultura', 0, NOW() - INTERVAL 1 DAY),
('Dicas para economizar na conta de luz', 'dicas-para-economizar-na-conta-de-luz', 'Pequenas mudanças de hábito podem reduzir bastante o valor da fatura no fim do mês.', '<p>Desligar aparelhos da tomada, aproveitar a luz natural e usar o chuveiro na posição verão são algumas atitudes simples que fazem diferença.</p><p><em>Notícia de exemplo.</em></p>', 'Serviço', 0, NOW() - INTERVAL 2 DAY),
('Campanha de vacinação continua nos postos de saúde', 'campanha-de-vacinacao-continua', 'Procure a unidade de saúde mais próxima com documento e cartão de vacina.', '<p>As secretarias municipais de saúde reforçam a importância de manter a caderneta de vacinação em dia.</p><p><em>Notícia de exemplo.</em></p>', 'Saúde', 0, NOW() - INTERVAL 3 DAY);

INSERT INTO eventos (titulo, slug, descricao, local, data_evento) VALUES
('Máxima no Parque', 'maxima-no-parque', 'Tarde de música ao vivo, brincadeiras e sorteios com a equipe da Máxima. (Evento de exemplo — envie as fotos pelo painel.)', 'Bom Despacho - MG', CURDATE() - INTERVAL 10 DAY),
('Show de Aniversário da Máxima', 'show-de-aniversario-da-maxima', 'Uma noite especial para comemorar com os ouvintes. (Evento de exemplo.)', 'Bom Despacho - MG', CURDATE() - INTERVAL 40 DAY);

INSERT INTO patrocinadores (nome, link, ordem) VALUES
('Seu negócio aqui', '/contato', 1),
('Anuncie na Máxima', '/contato', 2),
('Sua marca aqui', '/contato', 3),
('Patrocinador', '/contato', 4);
