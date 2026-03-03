<?php
// send-order.php

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
  http_response_code(405);
  exit('Method not allowed');
}

// === SETĂRI TEST ===
$to = 'test@cofetaria-christine.ro';
$from = 'test@cofetaria-christine.ro'; // mai sigur să fie de pe domeniul tău

// Helper
function clean($v) {
  return trim(str_replace(["\r","\n"], ' ', (string)$v));
}

// Preluare câmpuri (din formularul tău)
$nume       = clean($_POST['nume'] ?? '');
$telefon    = clean($_POST['telefon'] ?? '');
$eveniment  = clean($_POST['eveniment'] ?? '');
$data       = clean($_POST['data'] ?? '');
$tipTort    = clean($_POST['tipTort'] ?? '');
$nrPersoane = clean($_POST['nrPersoane'] ?? '');
$platou     = clean($_POST['platouOneBite'] ?? '');
$mesaj      = trim((string)($_POST['mesaj'] ?? ''));

// Validări minime
if ($nume === '' || $telefon === '' || $eveniment === '' || $data === '' || $mesaj === '') {
  header('Location: comanda-tort.html?sent=0');
  exit;
}

// Subiect + corp email
$subject = "TEST | Comandă tort - {$eveniment} - {$nume}";

$body =
"Solicitare nouă (TEST) - Comandă tort\n"
."============================\n"
."Nume: {$nume}\n"
."Telefon: {$telefon}\n"
."Tip eveniment: {$eveniment}\n"
."Data evenimentului: {$data}\n"
."\n"
."(Opțional) Tip tort: {$tipTort}\n"
."(Opțional) Nr persoane: {$nrPersoane}\n"
."(Opțional) Platou OneBite: {$platou}\n"
."\n"
."Mesaj / Detalii:\n{$mesaj}\n"
."\n"
."IP: " . ($_SERVER['REMOTE_ADDR'] ?? '-') . "\n"
."User-Agent: " . ($_SERVER['HTTP_USER_AGENT'] ?? '-') . "\n";

// Headere (simple și stabile)
$headers = [];
$headers[] = "MIME-Version: 1.0";
$headers[] = "Content-Type: text/plain; charset=UTF-8";
$headers[] = "From: Cofetăria Christine (TEST) <{$from}>";

$ok = mail($to, $subject, $body, implode("\r\n", $headers));

if ($ok) {
  header('Location: multumim.html');
} else {
  header('Location: comanda-tort.html?sent=0');
}
exit;