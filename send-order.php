<?php
header('Content-Type: application/json; charset=utf-8');

function clean_one_line($s) {
  $s = (string)$s;
  $s = str_replace(["\r", "\n"], " ", $s); // anti header-injection
  return trim($s);
}

function get_post($key) {
  return isset($_POST[$key]) ? $_POST[$key] : '';
}

$eveniment = clean_one_line(get_post('eveniment'));
$nume      = clean_one_line(get_post('nume'));
$telefon   = clean_one_line(get_post('telefon'));
$data      = clean_one_line(get_post('data'));
$tipTort   = clean_one_line(get_post('tipTort'));
$nrPers    = clean_one_line(get_post('nrPersoane'));
$platou    = clean_one_line(get_post('platouOneBite'));
$mesaj     = trim((string)get_post('mesaj'));

$errors = [];

if ($nume === '')      $errors['nume'] = 'Te rugăm să completezi numele.';
if ($telefon === '')   $errors['telefon'] = 'Te rugăm să completezi telefonul.';
if ($eveniment === '') $errors['eveniment'] = 'Te rugăm să alegi tipul evenimentului.';
if ($data === '')      $errors['data'] = 'Te rugăm să alegi data evenimentului.';
if ($mesaj === '')     $errors['mesaj'] = 'Te rugăm să completezi mesajul / detaliile.';

// Dacă e Aniversare, acestea sunt required (conform cerinței tale)
if ($eveniment === 'Aniversare') {
  if ($tipTort === '')  $errors['tipTort'] = 'Te rugăm să alegi tipul de tort.';
  if ($nrPers === '')   $errors['nrPersoane'] = 'Te rugăm să completezi numărul de persoane.';
  if ($platou === '')   $errors['platouOneBite'] = 'Te rugăm să alegi platoul OneBite.';
}

if (!empty($errors)) {
  http_response_code(422);
  echo json_encode(['ok' => false, 'errors' => $errors], JSON_UNESCAPED_UNICODE);
  exit;
}

// Routing destinatari:
$to = 'comenzi@cofetaria-christine.ro'; // Aniversare + Corporate
if ($eveniment === 'Botez' || $eveniment === 'Nuntă') {
  $to = 'adrian@cofetaria-christine.ro';
}

// Subiect + body
$subject = "Solicitare — {$eveniment} — {$nume}";

$body  = "Tip eveniment: {$eveniment}\n";
$body .= "Nume: {$nume}\n";
$body .= "Telefon: {$telefon}\n";
$body .= "Data evenimentului: {$data}\n";

if ($eveniment === 'Aniversare') {
  $body .= "Tip tort: {$tipTort}\n";
  $body .= "Număr persoane: {$nrPers}\n";
  $body .= "Platou OneBite: {$platou}\n";
}

$body .= "\nMesaj / Detalii:\n{$mesaj}\n";

// Headers (ajustează domeniul dacă ai alt email de pe domeniu)
$headers = [];
$headers[] = 'MIME-Version: 1.0';
$headers[] = 'Content-Type: text/plain; charset=UTF-8';
$headers[] = 'From: Cofetăria Christine <no-reply@cofetaria-christine.ro>';
$headers[] = 'Reply-To: comenzi@cofetaria-christine.ro';

// Subject UTF-8 safe
$encodedSubject = "=?UTF-8?B?" . base64_encode($subject) . "?=";

$sent = @mail($to, $encodedSubject, $body, implode("\r\n", $headers));

if (!$sent) {
  http_response_code(500);
  echo json_encode(['ok' => false, 'error' => 'Mail failed'], JSON_UNESCAPED_UNICODE);
  exit;
}

echo json_encode(['ok' => true], JSON_UNESCAPED_UNICODE);