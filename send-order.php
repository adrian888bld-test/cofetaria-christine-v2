<?php
header('Content-Type: application/json; charset=UTF-8');

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require __DIR__ . '/phpmailer/src/PHPMailer.php';
require __DIR__ . '/phpmailer/src/SMTP.php';
require __DIR__ . '/phpmailer/src/Exception.php';

function respond($ok, $extra = [], $code = 200) {
  http_response_code($code);
  echo json_encode(array_merge(['ok' => $ok], $extra), JSON_UNESCAPED_UNICODE);
  exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
  respond(false, ['error' => 'Method not allowed'], 405);
}

function clean($v) {
  return trim(str_replace(["\r","\n"], ' ', (string)$v));
}

// Fields
$nume       = clean($_POST['nume'] ?? '');
$telefon    = clean($_POST['telefon'] ?? '');
$eveniment  = clean($_POST['eveniment'] ?? '');
$data       = clean($_POST['data'] ?? '');
$tipTort    = clean($_POST['tipTort'] ?? '');
$nrPersoane = clean($_POST['nrPersoane'] ?? '');
$platou     = clean($_POST['platouOneBite'] ?? '');
$mesaj      = trim((string)($_POST['mesaj'] ?? ''));

// UI-friendly errors
$errors = [];
if ($nume === '')      $errors['nume'] = 'Te rugăm să completezi acest câmp.';
if ($telefon === '')   $errors['telefon'] = 'Te rugăm să completezi acest câmp.';
if ($eveniment === '') $errors['eveniment'] = 'Te rugăm să completezi acest câmp.';
if ($data === '')      $errors['data'] = 'Te rugăm să completezi acest câmp.';
if ($mesaj === '')     $errors['mesaj'] = 'Te rugăm să completezi acest câmp.';

if ($errors) {
  respond(false, ['errors' => $errors], 400);
}

$mail = new PHPMailer(true);

try {
  $mail->isSMTP();
  $mail->Host       = 'mail.cofetaria-christine.ro';
  $mail->SMTPAuth   = true;
  $mail->Username   = 'test@cofetaria-christine.ro';
  $mail->Password   = 'T)x[RwqWWwT^uKG6'; // <- pune parola reală
  $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS; // SSL
  $mail->Port       = 465;

  $mail->CharSet = 'UTF-8';

  $mail->setFrom('test@cofetaria-christine.ro', 'Cofetăria Christine (TEST)');
  $mail->addAddress('test@cofetaria-christine.ro');

  $mail->Subject = "TEST | Comandă tort - {$eveniment} - {$nume}";

  $mail->Body =
    "Solicitare nouă (TEST)\n"
    ."=====================\n"
    ."Nume: {$nume}\n"
    ."Telefon: {$telefon}\n"
    ."Eveniment: {$eveniment}\n"
    ."Data: {$data}\n\n"
    ."Tip tort: {$tipTort}\n"
    ."Nr persoane: {$nrPersoane}\n"
    ."Platou OneBite: {$platou}\n\n"
    ."Mesaj:\n{$mesaj}\n";

  $mail->send();

  respond(true);

} catch (Exception $e) {
  respond(false, ['error' => $mail->ErrorInfo], 500);
}