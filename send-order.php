<?php
header('Content-Type: application/json; charset=UTF-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
  http_response_code(405);
  echo json_encode(['ok' => false, 'error' => 'Method not allowed']);
  exit;
}

// DESTINATAR TEST
$to   = 'test@cofetaria-christine.ro';
$from = 'test@cofetaria-christine.ro';

function clean($v){ return trim(str_replace(["\r","\n"], ' ', (string)$v)); }

$nume       = clean($_POST['nume'] ?? '');
$telefon    = clean($_POST['telefon'] ?? '');
$eveniment  = clean($_POST['eveniment'] ?? '');
$data       = clean($_POST['data'] ?? '');
$tipTort    = clean($_POST['tipTort'] ?? '');
$nrPersoane = clean($_POST['nrPersoane'] ?? '');
$platou     = clean($_POST['platouOneBite'] ?? '');
$mesaj      = trim((string)($_POST['mesaj'] ?? ''));

if ($nume === '' || $telefon === '' || $eveniment === '' || $data === '' || $mesaj === '') {
  http_response_code(400);
  echo json_encode(['ok' => false, 'error' => 'Câmpuri lipsă']);
  exit;
}

$subject = "TEST | Comandă tort - {$eveniment} - {$nume}";

$body =
"Solicitare nouă (TEST) - Comandă tort\n"
."============================\n"
."Nume: {$nume}\n"
."Telefon: {$telefon}\n"
."Tip eveniment: {$eveniment}\n"
."Data evenimentului: {$data}\n\n"
."Tip tort: {$tipTort}\n"
."Nr persoane: {$nrPersoane}\n"
."Platou OneBite: {$platou}\n\n"
."Mesaj / Detalii:\n{$mesaj}\n";

$headers = [];
$headers[] = "MIME-Version: 1.0";
$headers[] = "Content-Type: text/plain; charset=UTF-8";
$headers[] = "From: Cofetăria Christine (TEST) <{$from}>";

$ok = mail($to, $subject, $body, implode("\r\n", $headers));

if ($ok) {
  echo json_encode(['ok' => true, 'redirect' => 'multumim.html']);
} else {
  http_response_code(500);
  echo json_encode(['ok' => false, 'error' => 'mail() a eșuat']);
}