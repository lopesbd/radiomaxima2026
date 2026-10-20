<?php
// Ações do painel chamadas por JavaScript (arrastar/ordenar, alternar, excluir, grade, uploads).
// Sempre POST com o token CSRF no cabeçalho X-CSRF ou no campo "csrf".
if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !csrf_ok($_SERVER['HTTP_X_CSRF'] ?? ($_POST['csrf'] ?? null))) {
    json_resposta(['erro' => 'Token de segurança inválido. Recarregue a página.'], 400);
}
$entrada = $_POST;
if (str_contains($_SERVER['CONTENT_TYPE'] ?? '', 'application/json')) $entrada = json_decode(file_get_contents('php://input'), true) ?: [];
$acao = (string)($entrada['acao'] ?? '');
$int = fn(string $k) => (int)($entrada[$k] ?? 0);

/** Verifica se o horário conflita com outro programa no mesmo dia. */
function conflito_grade(int $dia, int $ini, int $fim, int $ignorar = 0): ?string
{
    $c = q('SELECT p.nome, g.inicio, g.fim FROM grade g JOIN programas p ON p.id = g.programa_id
            WHERE g.dia = ? AND g.id <> ? AND g.inicio < ? AND g.fim > ? LIMIT 1', [$dia, $ignorar, $fim, $ini])->fetch();
    return $c ? sprintf('Conflito com "%s" (%s – %s) em %s.', $c['nome'], hora((int)$c['inicio']), hora((int)$c['fim']), DIAS[$dia]) : null;
}

function validar_horario(int $dia, int $ini, int $fim): void
{
    if ($dia < 0 || $dia > 6) json_resposta(['erro' => 'Dia inválido.'], 422);
    if ($ini < 0 || $fim > 1440 || $fim - $ini < 5) json_resposta(['erro' => 'Horário inválido: o fim deve ser depois do início (mínimo 5 minutos).'], 422);
}

try {
    switch ($acao) {
        case 'ordenar': {
            $tabelas = ['banners', 'patrocinadores', 'evento_fotos'];
            $t = (string)$entrada['tabela'];
            if (!in_array($t, $tabelas, true)) json_resposta(['erro' => 'Tabela inválida.'], 422);
            $ids = array_map('intval', (array)($entrada['ids'] ?? []));
            $st = db()->prepare("UPDATE $t SET ordem = ? WHERE id = ?");
            foreach ($ids as $i => $idItem) $st->execute([$i + 1, $idItem]);
            json_resposta(['ok' => true]);
        }
        case 'alternar': {
            $permitidos = ['banners' => ['ativo'], 'patrocinadores' => ['ativo'], 'noticias' => ['publicado', 'destaque'], 'eventos' => ['publicado'], 'mensagens' => ['lida']];
            $t = (string)$entrada['tabela'];
            $campo = (string)($entrada['campo'] ?? '') ?: ($permitidos[$t][0] ?? '');
            if (!isset($permitidos[$t]) || !in_array($campo, $permitidos[$t], true)) json_resposta(['erro' => 'Campo inválido.'], 422);
            q("UPDATE $t SET $campo = ? WHERE id = ?", [!empty($entrada['valor']) ? 1 : 0, $int('id')]);
            json_resposta(['ok' => true]);
        }
        case 'excluir': {
            $t = (string)$entrada['tabela'];
            $idItem = $int('id');
            $imagens = ['banners' => 'imagem', 'patrocinadores' => 'logo', 'noticias' => 'imagem', 'evento_fotos' => 'arquivo', 'programas' => 'foto'];
            if (!in_array($t, ['banners', 'patrocinadores', 'noticias', 'eventos', 'evento_fotos', 'mensagens', 'programas'], true)) json_resposta(['erro' => 'Tabela inválida.'], 422);
            if ($t === 'eventos') {
                foreach (q('SELECT arquivo FROM evento_fotos WHERE evento_id = ?', [$idItem])->fetchAll(PDO::FETCH_COLUMN) as $a) apagar_imagem($a);
            } elseif (isset($imagens[$t])) {
                apagar_imagem(q("SELECT {$imagens[$t]} FROM $t WHERE id = ?", [$idItem])->fetchColumn() ?: null);
            }
            if ($t === 'evento_fotos') q('UPDATE eventos SET capa_id = NULL WHERE capa_id = ?', [$idItem]);
            q("DELETE FROM $t WHERE id = ?", [$idItem]);
            json_resposta(['ok' => true]);
        }

        // ---------- fotos de eventos ----------
        case 'foto_enviar': {
            $eid = $int('evento_id');
            if (!q('SELECT id FROM eventos WHERE id = ?', [$eid])->fetch()) json_resposta(['erro' => 'Evento não encontrado.'], 404);
            if (empty($_FILES['foto'])) json_resposta(['erro' => 'Nenhum arquivo recebido (pode ser maior que o limite do servidor).'], 422);
            $arq = salvar_imagem($_FILES['foto']);
            $ordem = (int)q('SELECT COALESCE(MAX(ordem), 0) + 1 FROM evento_fotos WHERE evento_id = ?', [$eid])->fetchColumn();
            q('INSERT INTO evento_fotos (evento_id, arquivo, ordem) VALUES (?,?,?)', [$eid, $arq, $ordem]);
            json_resposta(['ok' => true, 'id' => (int)db()->lastInsertId(), 'miniatura' => midia($arq, true)]);
        }
        case 'foto_legenda': {
            q('UPDATE evento_fotos SET legenda = ? WHERE id = ?', [mb_substr(trim((string)$entrada['legenda']), 0, 255), $int('id')]);
            json_resposta(['ok' => true]);
        }
        case 'foto_capa': {
            q('UPDATE eventos e JOIN evento_fotos f ON f.evento_id = e.id SET e.capa_id = f.id WHERE f.id = ?', [$int('id')]);
            json_resposta(['ok' => true]);
        }

        // ---------- imagem dentro do texto (editor) ----------
        case 'imagem_editor': {
            if (empty($_FILES['imagem'])) json_resposta(['erro' => 'Nenhum arquivo recebido.'], 422);
            $arq = salvar_imagem($_FILES['imagem'], 1600);
            json_resposta(['ok' => true, 'url' => midia($arq)]);
        }

        // ---------- programas e grade ----------
        case 'programa_salvar': {
            $nome = mb_substr(trim((string)$entrada['nome']), 0, 120);
            if ($nome === '') json_resposta(['erro' => 'Informe o nome do programa.'], 422);
            $cor = preg_match('/^#[0-9a-f]{6}$/i', (string)$entrada['cor']) ? $entrada['cor'] : '#e3161b';
            $pid = $int('id');
            $atual = $pid ? q('SELECT * FROM programas WHERE id = ?', [$pid])->fetch() : null;
            $foto = $atual['foto'] ?? null;
            if (!empty($_FILES['foto']) && $_FILES['foto']['error'] !== UPLOAD_ERR_NO_FILE) { $novo = salvar_imagem($_FILES['foto'], 800, 400); apagar_imagem($foto); $foto = $novo; }
            elseif (!empty($entrada['foto_remover'])) { apagar_imagem($foto); $foto = null; }
            $vals = [$nome, mb_substr(trim((string)$entrada['apresentador']), 0, 160), trim((string)$entrada['descricao']), $cor, $foto];
            if ($atual) q('UPDATE programas SET nome=?, apresentador=?, descricao=?, cor=?, foto=? WHERE id=?', [...$vals, $pid]);
            else { q('INSERT INTO programas (nome, apresentador, descricao, cor, foto) VALUES (?,?,?,?,?)', $vals); $pid = (int)db()->lastInsertId(); }
            $p = q('SELECT p.*, (SELECT COUNT(*) FROM grade g WHERE g.programa_id = p.id) usos FROM programas p WHERE id = ?', [$pid])->fetch();
            json_resposta(['ok' => true, 'programa' => ['id' => (int)$p['id'], 'nome' => $p['nome'], 'apresentador' => $p['apresentador'], 'descricao' => (string)$p['descricao'], 'cor' => $p['cor'], 'foto' => $p['foto'] ? midia($p['foto'], true) : null, 'usos' => (int)$p['usos']]]);
        }
        case 'grade_salvar': {
            $gid = $int('id');
            $dia = $int('dia'); $ini = $int('inicio'); $fim = $int('fim'); $pid = $int('programa_id');
            validar_horario($dia, $ini, $fim);
            if (!q('SELECT id FROM programas WHERE id = ?', [$pid])->fetch()) json_resposta(['erro' => 'Programa não encontrado.'], 404);
            if (!empty($entrada['encaixar']) && !$gid) {
                // vindo da biblioteca: encurta para caber no espaço livre
                $prox = q('SELECT MIN(inicio) FROM grade WHERE dia = ? AND inicio >= ?', [$dia, $ini])->fetchColumn();
                if ($prox !== null && $prox !== false && (int)$prox < $fim) $fim = (int)$prox;
                if ($fim - $ini < 5) json_resposta(['erro' => 'Não há espaço livre nesse horário. Solte em um intervalo vazio ou ajuste os outros programas.'], 409);
            }
            if ($msg = conflito_grade($dia, $ini, $fim, $gid)) json_resposta(['erro' => $msg], 409);
            if ($gid) q('UPDATE grade SET programa_id=?, dia=?, inicio=?, fim=? WHERE id=?', [$pid, $dia, $ini, $fim, $gid]);
            else { q('INSERT INTO grade (programa_id, dia, inicio, fim) VALUES (?,?,?,?)', [$pid, $dia, $ini, $fim]); $gid = (int)db()->lastInsertId(); }
            json_resposta(['ok' => true, 'item' => ['id' => $gid, 'programa_id' => $pid, 'dia' => $dia, 'inicio' => $ini, 'fim' => $fim]]);
        }
        case 'grade_excluir': {
            q('DELETE FROM grade WHERE id = ?', [$int('id')]);
            json_resposta(['ok' => true]);
        }
        case 'grade_copiar_dia': {
            $de = $int('de');
            $para = array_values(array_unique(array_filter(array_map('intval', (array)($entrada['para'] ?? [])), fn($d) => $d >= 0 && $d <= 6 && $d !== $de)));
            if (!$para) json_resposta(['erro' => 'Escolha pelo menos um dia de destino diferente da origem.'], 422);
            $origem = q('SELECT programa_id, inicio, fim FROM grade WHERE dia = ?', [$de])->fetchAll();
            db()->beginTransaction();
            $ins = db()->prepare('INSERT INTO grade (programa_id, dia, inicio, fim) VALUES (?,?,?,?)');
            foreach ($para as $d) {
                q('DELETE FROM grade WHERE dia = ?', [$d]);
                foreach ($origem as $o) $ins->execute([$o['programa_id'], $d, $o['inicio'], $o['fim']]);
            }
            db()->commit();
            json_resposta(['ok' => true, 'grade' => array_map(fn($g) => array_map('intval', $g), q('SELECT id, programa_id, dia, inicio, fim FROM grade ORDER BY dia, inicio')->fetchAll())]);
        }
        default:
            json_resposta(['erro' => 'Ação desconhecida.'], 400);
    }
} catch (RuntimeException $e) {
    json_resposta(['erro' => $e->getMessage()], 422);
}
