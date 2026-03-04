<?php
header('Content-Type: application/json; charset=UTF-8');
ini_set('log_errors', '1');
ini_set('error_log', __DIR__ . '/php_errors.log');
error_reporting(E_ALL);

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require __DIR__ . '/phpmailer/src/PHPMailer.php';
require __DIR__ . '/phpmailer/src/SMTP.php';
require __DIR__ . '/phpmailer/src/Exception.php';

define('ALLOW_CONFIG', true);
$config = require __DIR__ . '/config.php';

function respond($ok, $extra = [], $code = 200) {
    http_response_code($code);
    echo json_encode(array_merge(['ok' => $ok], $extra), JSON_UNESCAPED_UNICODE);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    respond(false, ['error' => 'Method not allowed'], 405);
}
// Anti-spam: honeypot
$hp = trim((string)($_POST['website'] ?? ''));
if ($hp !== '') {
    respond(false, ['error' => 'Spam detected'], 400);
}
// Anti-spam: time-check (min 8 sec)
$start = (int)($_POST['form_time'] ?? 0);
if ($start > 0) {
    $elapsed = (int) round((microtime(true) * 1000) - $start);
    if ($elapsed < 8000) {
        respond(false, ['error' => 'Te rugăm să aștepți câteva secunde și să încerci din nou.'], 400);
    }
}

function clean($v) {
    return trim(str_replace(["\r","\n"], ' ', (string)$v));
}

// Preluare câmpuri
$nume       = clean($_POST['nume'] ?? '');
$telefon    = clean($_POST['telefon'] ?? '');
$email      = clean($_POST['email'] ?? '');
$eveniment  = clean($_POST['eveniment'] ?? '');
$data       = clean($_POST['data'] ?? '');
$tipTort    = clean($_POST['tipTort'] ?? '');
$nrPersoane = clean($_POST['nrPersoane'] ?? '');
$platou     = clean($_POST['platouOneBite'] ?? '');
$mesaj      = trim((string)($_POST['mesaj'] ?? ''));

// Validare
$errors = [];

if ($nume === '')      $errors['nume'] = 'Te rugăm să completezi acest câmp.';
if ($telefon === '')   $errors['telefon'] = 'Te rugăm să completezi acest câmp.';
if ($email !== '' && !filter_var($email, FILTER_VALIDATE_EMAIL))
    $errors['email'] = 'Te rugăm să introduci un email valid.';
if ($eveniment === '') $errors['eveniment'] = 'Te rugăm să completezi acest câmp.';
if ($data === '')      $errors['data'] = 'Te rugăm să completezi acest câmp.';
if ($mesaj === '')     $errors['mesaj'] = 'Te rugăm să completezi acest câmp.';

if ($errors) {
    respond(false, ['errors' => $errors], 400);
}

$mail = new PHPMailer(true);

try {

    $mail->isSMTP();
    $mail->Host       = $config['smtp_host'];
    $mail->SMTPAuth   = true;
    $mail->Username   = $config['smtp_user'];
    $mail->Password   = $config['smtp_pass'];
    $mail->Port       = $config['smtp_port'];

    if ($config['smtp_secure'] === 'ssl') {
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
    } else {
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
    }

    $mail->CharSet = 'UTF-8';

    $mail->setFrom($config['from_email'], $config['from_name']);
    $mail->addAddress($config['to_email']);

    $mail->Subject = "Comandă tort - {$eveniment} - {$nume}";

    $mail->Body =
        "Solicitare nouă - Cofetăria Christine\n"
        ."=====================================\n"
        ."Nume: {$nume}\n"
        ."Telefon: {$telefon}\n"
        ."Email: {$email}\n"
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