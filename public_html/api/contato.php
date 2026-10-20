<?php
declare(strict_types=1);
// Recebe o formulário de contato / pedido de música e grava em `mensagens`.
require dirname(__DIR__) . '/app/bootstrap.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') json_resposta(['erro' => 'Método não permitido.'], 405);
if (!csrf_ok($_POST['csrf'] ?? null)) json_resposta(['erro' => 'Sessão expirada. Recarregue a página e tente de novo.'], 400);
if (!empty($_POST['site'])) json_resposta(['ok' => true]); // armadilha para robôs

$campo = fn(string $k, int $max) => mb_substr(trim((string)($_POST[$k] ?? '')), 0, $max);
$nome = $campo('nome', 120);
$email = $campo('email', 190);
$telefone = $campo('telefone', 40);
$cidade = $campo('cidade', 120);
$assunto = $campo('assunto', 60) ?: 'Contato';
$mensagem = $campo('mensagem', 3000);

if ($nome === '' || $mensagem === '') json_resposta(['erro' => 'Preencha seu nome e a mensagem.'], 422);
if ($email !== '' && !filter_var($email, FILTER_VALIDATE_EMAIL)) json_resposta(['erro' => 'E-mail inválido.'], 422);

$recentes = (int)q('SELECT COUNT(*) FROM mensagens WHERE ip = ? AND criado_em > NOW() - INTERVAL 10 MINUTE', [ip_cliente()])->fetchColumn();
if ($recentes >= 5) json_resposta(['erro' => 'Muitas mensagens em pouco tempo. Aguarde alguns minutos.'], 429);

q('INSERT INTO mensagens (nome, email, telefone, cidade, assunto, mensagem, ip) VALUES (?,?,?,?,?,?,?)',
  [$nome, $email, $telefone, $cidade, $assunto, $mensagem, ip_cliente()]);

json_resposta(['ok' => true, 'mensagem' => 'Recebemos sua mensagem! Obrigado por falar com a Máxima.']);
