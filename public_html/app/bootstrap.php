<?php
declare(strict_types=1);

// Núcleo do site da Rádio Máxima: configuração, banco, sessão e funções de apoio.

define('RAIZ', dirname(__DIR__));          // public_html
define('APP', __DIR__);

$configs = [dirname(RAIZ) . '/config.php', APP . '/config.php'];
$CONFIG = null;
foreach ($configs as $arq) {
    if (is_file($arq)) { $CONFIG = require $arq; break; }
}
if (!is_array($CONFIG)) {
    http_response_code(500);
    exit('Configuração não encontrada. Copie app/config.example.php para config.php.');
}

date_default_timezone_set($CONFIG['timezone'] ?? 'America/Sao_Paulo');
mb_internal_encoding('UTF-8');

if (!empty($CONFIG['debug'])) {
    ini_set('display_errors', '1');
    error_reporting(E_ALL);
} else {
    ini_set('display_errors', '0');
}

function config_app(string $chave, $padrao = null)
{
    global $CONFIG;
    return $CONFIG[$chave] ?? $padrao;
}

function db(): PDO
{
    static $pdo = null;
    if ($pdo) return $pdo;
    $c = config_app('db');
    $dsn = sprintf('mysql:host=%s;port=%d;dbname=%s;charset=utf8mb4', $c['host'], $c['port'] ?? 3306, $c['nome']);
    $pdo = new PDO($dsn, $c['usuario'], $c['senha'], [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ]);
    $pdo->exec("SET time_zone = '" . date('P') . "'");
    return $pdo;
}

function q(string $sql, array $params = []): PDOStatement
{
    $st = db()->prepare($sql);
    $st->execute($params);
    return $st;
}

// ---------- configurações editáveis (tabela config) ----------
function cfg(string $chave, string $padrao = ''): string
{
    static $cache = null;
    if ($cache === null) {
        $cache = [];
        foreach (q('SELECT chave, valor FROM config')->fetchAll() as $l) $cache[$l['chave']] = $l['valor'];
    }
    return $cache[$chave] ?? $padrao;
}

function cfg_lista(string $chave): array
{
    return array_values(array_filter(array_map('trim', preg_split('/\r?\n/', cfg($chave)))));
}

// ---------- saída / URLs ----------
function e(?string $s): string
{
    return htmlspecialchars((string)$s, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function base_path(): string
{
    return rtrim((string)config_app('base_path', ''), '/');
}

function url(string $caminho = '/'): string
{
    if (preg_match('#^(https?:|mailto:|tel:|\#)#', $caminho)) return $caminho;
    return base_path() . '/' . ltrim($caminho, '/');
}

function url_absoluta(string $caminho = '/'): string
{
    if (preg_match('#^https?://#', $caminho)) return $caminho;
    $https = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') || (($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https');
    $host = preg_replace('/[^\w.:-]/', '', (string)($_SERVER['HTTP_HOST'] ?? 'localhost'));
    $rel = url($caminho);
    return ($https ? 'https://' : 'http://') . $host . (str_starts_with($rel, '/') ? '' : '/') . $rel;
}

function asset(string $caminho): string
{
    $arq = RAIZ . '/assets/' . $caminho;
    $v = is_file($arq) ? filemtime($arq) : 1;
    return url('assets/' . $caminho) . '?v=' . $v;
}

function midia(?string $caminho, bool $miniatura = false): string
{
    if (!$caminho) return '';
    if ($miniatura) {
        $t = preg_replace('/(\.\w+)$/', '_t$1', $caminho);
        if (is_file(RAIZ . '/' . $t)) $caminho = $t;
    }
    return url($caminho);
}

function slugify(string $texto): string
{
    $t = mb_strtolower(trim($texto));
    $t = strtr($t, ['á'=>'a','à'=>'a','â'=>'a','ã'=>'a','ä'=>'a','é'=>'e','è'=>'e','ê'=>'e','ë'=>'e','í'=>'i','ì'=>'i','î'=>'i','ï'=>'i','ó'=>'o','ò'=>'o','ô'=>'o','õ'=>'o','ö'=>'o','ú'=>'u','ù'=>'u','û'=>'u','ü'=>'u','ç'=>'c','ñ'=>'n']);
    $t = preg_replace('/[^a-z0-9]+/', '-', $t);
    return trim(substr($t, 0, 200), '-') ?: 'item';
}

function slug_unico(string $tabela, string $titulo, int $ignorarId = 0): string
{
    $base = slugify($titulo);
    $slug = $base;
    for ($i = 2; ; $i++) {
        $existe = q("SELECT id FROM $tabela WHERE slug = ? AND id <> ?", [$slug, $ignorarId])->fetchColumn();
        if (!$existe) return $slug;
        $slug = $base . '-' . $i;
    }
}

const MESES = ['', 'janeiro', 'fevereiro', 'março', 'abril', 'maio', 'junho', 'julho', 'agosto', 'setembro', 'outubro', 'novembro', 'dezembro'];
const MESES_CURTOS = ['', 'JAN', 'FEV', 'MAR', 'ABR', 'MAI', 'JUN', 'JUL', 'AGO', 'SET', 'OUT', 'NOV', 'DEZ'];
const DIAS = ['Domingo', 'Segunda', 'Terça', 'Quarta', 'Quinta', 'Sexta', 'Sábado'];
const DIAS_CURTOS = ['Dom', 'Seg', 'Ter', 'Qua', 'Qui', 'Sex', 'Sáb'];

function data_extenso(string $data): string
{
    $t = strtotime($data);
    return date('j', $t) . ' de ' . MESES[(int)date('n', $t)] . ' de ' . date('Y', $t);
}

function hora(int $min): string
{
    return sprintf('%02d:%02d', intdiv($min, 60), $min % 60);
}

function hora_para_min(string $hhmm): ?int
{
    if (!preg_match('/^(\d{1,2}):(\d{2})$/', trim($hhmm), $m)) return null;
    $v = (int)$m[1] * 60 + (int)$m[2];
    return ($m[2] < 60 && $v <= 1440) ? $v : null;
}

// ---------- programação ----------
function grade_do_dia(int $dia): array
{
    return q('SELECT g.id, g.dia, g.inicio, g.fim, p.id AS programa_id, p.nome, p.apresentador, p.descricao, p.foto, p.cor
              FROM grade g JOIN programas p ON p.id = g.programa_id
              WHERE g.dia = ? ORDER BY g.inicio', [$dia])->fetchAll();
}

/** Programa no ar agora + os próximos (podem atravessar para o dia seguinte). */
function no_ar(int $quantosProximos = 3): array
{
    $dia = (int)date('w');
    $min = (int)date('G') * 60 + (int)date('i');
    $hoje = grade_do_dia($dia);
    $atual = null;
    $proximos = [];
    foreach ($hoje as $g) {
        if ($g['inicio'] <= $min && $min < $g['fim']) $atual = $g;
        elseif ($g['inicio'] > $min) $proximos[] = $g + ['amanha' => false];
    }
    if (count($proximos) < $quantosProximos) {
        foreach (grade_do_dia(($dia + 1) % 7) as $g) $proximos[] = $g + ['amanha' => true];
    }
    return [
        'atual' => $atual,
        'proximos' => array_slice($proximos, 0, $quantosProximos),
        'dia' => $dia,
        'minuto' => $min,
        'segundo' => (int)date('s'),
    ];
}

function programa_json(?array $g): ?array
{
    if (!$g) return null;
    return [
        'id' => (int)$g['programa_id'],
        'nome' => $g['nome'],
        'apresentador' => $g['apresentador'],
        'foto' => $g['foto'] ? midia($g['foto'], true) : null,
        'cor' => $g['cor'],
        'inicio' => (int)$g['inicio'],
        'fim' => (int)$g['fim'],
        'horario' => hora((int)$g['inicio']) . ' – ' . hora((int)$g['fim']),
        'amanha' => !empty($g['amanha']),
    ];
}

// ---------- sessão e segurança ----------
function iniciar_sessao(): void
{
    if (session_status() === PHP_SESSION_ACTIVE) return;
    $https = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') || (($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https');
    session_name('maxima_sess');
    session_set_cookie_params(['lifetime' => 0, 'path' => base_path() . '/', 'secure' => $https, 'httponly' => true, 'samesite' => 'Lax']);
    session_start();
}

function csrf_token(): string
{
    iniciar_sessao();
    if (empty($_SESSION['csrf'])) $_SESSION['csrf'] = bin2hex(random_bytes(32));
    return $_SESSION['csrf'];
}

function csrf_ok(?string $token): bool
{
    iniciar_sessao();
    return is_string($token) && !empty($_SESSION['csrf']) && hash_equals($_SESSION['csrf'], $token);
}

function ip_cliente(): string
{
    return substr((string)($_SERVER['REMOTE_ADDR'] ?? ''), 0, 45);
}

function json_resposta($dados, int $status = 200): void
{
    http_response_code($status);
    header('Content-Type: application/json; charset=utf-8');
    header('Cache-Control: no-store');
    echo json_encode($dados, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

function cabecalhos_seguranca(): void
{
    header('X-Content-Type-Options: nosniff');
    header('Referrer-Policy: strict-origin-when-cross-origin');
    header('X-Frame-Options: SAMEORIGIN');
    header("Content-Security-Policy: default-src 'self'; img-src 'self' data: https:; media-src 'self' https: http:; style-src 'self' 'unsafe-inline' https://fonts.googleapis.com; font-src 'self' https://fonts.gstatic.com; script-src 'self'; connect-src 'self'; frame-src 'self' https://www.youtube.com https://www.youtube-nocookie.com https://www.google.com; base-uri 'self'; form-action 'self'");
}

// ---------- HTML de notícias (editor do painel) ----------
/** Mantém só tags e atributos seguros do conteúdo vindo do editor. */
function limpar_html(string $html): string
{
    $permitidas = ['p','br','strong','b','em','i','u','s','h2','h3','h4','ul','ol','li','blockquote','a','img','figure','figcaption','hr','span','div','iframe'];
    $doc = new DOMDocument();
    libxml_use_internal_errors(true);
    $doc->loadHTML('<?xml encoding="utf-8"?><div id="raiz">' . $html . '</div>', LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);
    libxml_clear_errors();
    $raiz = $doc->getElementById('raiz');
    if (!$raiz) return '';
    $limpar = function (DOMNode $no) use (&$limpar, $permitidas) {
        for ($i = $no->childNodes->length - 1; $i >= 0; $i--) {
            $f = $no->childNodes->item($i);
            if ($f instanceof DOMComment) { $no->removeChild($f); continue; }
            if (!($f instanceof DOMElement)) continue;
            $tag = strtolower($f->tagName);
            if (!in_array($tag, $permitidas, true)) {
                if (in_array($tag, ['script','style','object','embed','form','input','button','textarea','select'], true)) {
                    $no->removeChild($f);
                } else {
                    $limpar($f);
                    while ($f->firstChild) $no->insertBefore($f->firstChild, $f);
                    $no->removeChild($f);
                }
                continue;
            }
            foreach (iterator_to_array($f->attributes) as $at) {
                $nome = strtolower($at->name);
                $ok = ($tag === 'a' && $nome === 'href') || ($tag === 'img' && in_array($nome, ['src','alt'], true)) || ($tag === 'iframe' && $nome === 'src');
                if (!$ok) { $f->removeAttribute($at->name); continue; }
                $v = trim($at->value);
                if ($tag === 'iframe' && !preg_match('#^https://(www\.)?(youtube\.com|youtube-nocookie\.com)/embed/[\w-]+#', $v)) { $no->removeChild($f); continue 2; }
                if (in_array($nome, ['href','src'], true) && !preg_match('#^(https?://|/|mailto:|tel:|\#)#i', $v)) $f->removeAttribute($at->name);
            }
            if ($tag === 'a') { $f->setAttribute('rel', 'noopener'); if (preg_match('#^https?://#', $f->getAttribute('href'))) $f->setAttribute('target', '_blank'); }
            if ($tag === 'img') $f->setAttribute('loading', 'lazy');
            if ($tag === 'iframe') { $f->setAttribute('allowfullscreen', ''); $f->setAttribute('loading', 'lazy'); }
            $limpar($f);
        }
    };
    $limpar($raiz);
    $out = '';
    foreach ($raiz->childNodes as $c) $out .= $doc->saveHTML($c);
    return trim($out);
}

// ---------- upload de imagens ----------
/**
 * Salva uma imagem enviada (redimensiona e converte para WebP quando possível).
 * Retorna o caminho relativo (ex.: uploads/2026/10/abc.webp) e cria a miniatura _t.
 */
function salvar_imagem(array $arquivo, int $larguraMax = 1920, int $larguraMini = 640): string
{
    if (($arquivo['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
        throw new RuntimeException('Falha no envio do arquivo (código ' . ($arquivo['error'] ?? '?') . ').');
    }
    if ($arquivo['size'] > 15 * 1024 * 1024) throw new RuntimeException('Imagem maior que 15 MB.');
    $tipo = (new finfo(FILEINFO_MIME_TYPE))->file($arquivo['tmp_name']);
    $carregar = ['image/jpeg' => 'imagecreatefromjpeg', 'image/png' => 'imagecreatefrompng', 'image/webp' => 'imagecreatefromwebp', 'image/gif' => 'imagecreatefromgif'];
    if (!isset($carregar[$tipo])) throw new RuntimeException('Formato não aceito. Use JPG, PNG, WebP ou GIF.');
    $img = @$carregar[$tipo]($arquivo['tmp_name']);
    if (!$img) throw new RuntimeException('Não foi possível ler a imagem.');

    if ($tipo === 'image/jpeg' && function_exists('exif_read_data')) {
        $exif = @exif_read_data($arquivo['tmp_name']);
        $rot = [3 => 180, 6 => -90, 8 => 90][$exif['Orientation'] ?? 0] ?? 0;
        if ($rot) $img = imagerotate($img, $rot, 0);
    }

    $pasta = 'uploads/' . date('Y/m');
    if (!is_dir(RAIZ . '/' . $pasta)) mkdir(RAIZ . '/' . $pasta, 0755, true);
    $usarWebp = function_exists('imagewebp');
    $ext = $usarWebp ? 'webp' : ($tipo === 'image/png' ? 'png' : 'jpg');
    $nome = bin2hex(random_bytes(10));
    $caminho = "$pasta/$nome.$ext";

    $gravar = function ($im, string $destino) use ($ext) {
        if ($ext === 'webp') imagewebp($im, $destino, 82);
        elseif ($ext === 'png') imagepng($im, $destino, 7);
        else imagejpeg($im, $destino, 85);
    };
    $redim = function ($im, int $max) {
        $w = imagesx($im); $h = imagesy($im);
        if ($w <= $max) { $c = imagecreatetruecolor($w, $h); imagealphablending($c, false); imagesavealpha($c, true); imagecopy($c, $im, 0, 0, 0, 0, $w, $h); return $c; }
        $nh = (int)round($h * $max / $w);
        $c = imagecreatetruecolor($max, $nh);
        imagealphablending($c, false); imagesavealpha($c, true);
        imagecopyresampled($c, $im, 0, 0, 0, 0, $max, $nh, $w, $h);
        return $c;
    };
    if ($tipo === 'image/png' || $tipo === 'image/webp' || $tipo === 'image/gif') { imagepalettetotruecolor($img); imagealphablending($img, false); imagesavealpha($img, true); }
    $grande = $redim($img, $larguraMax);
    $gravar($grande, RAIZ . '/' . $caminho);
    $mini = $redim($img, $larguraMini);
    $gravar($mini, RAIZ . "/$pasta/{$nome}_t.$ext");
    imagedestroy($img); imagedestroy($grande); imagedestroy($mini);
    return $caminho;
}

function apagar_imagem(?string $caminho): void
{
    if (!$caminho || !str_starts_with($caminho, 'uploads/') || str_contains($caminho, '..')) return;
    @unlink(RAIZ . '/' . $caminho);
    @unlink(RAIZ . '/' . preg_replace('/(\.\w+)$/', '_t$1', $caminho));
}

/** Reorganiza uploads do PHP com múltiplos arquivos (name="fotos[]"). */
function arquivos_multiplos(array $campo): array
{
    $lista = [];
    if (!is_array($campo['name'] ?? null)) return [$campo];
    foreach ($campo['name'] as $i => $n) {
        $lista[] = ['name' => $n, 'type' => $campo['type'][$i], 'tmp_name' => $campo['tmp_name'][$i], 'error' => $campo['error'][$i], 'size' => $campo['size'][$i]];
    }
    return $lista;
}
