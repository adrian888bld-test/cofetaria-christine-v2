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

function clean($v) {
  return trim(str_replace(["\r","\n"], ' ', (string)$v));
}

/* =========================
   Anti-spam: honeypot
   ========================= */
$hp = trim((string)($_POST['website'] ?? ''));
if ($hp !== '') {
  respond(false, ['error' => 'Spam detected'], 400);
}

/* =========================
   Anti-spam: time-check (min 8 sec)
   form_time trebuie setat în HTML/JS ca timestamp ms
   ========================= */
$start = (int)($_POST['form_time'] ?? 0);
if ($start > 0) {
  $elapsed = (int) round((microtime(true) * 1000) - $start);
  if ($elapsed < 8000) {
    respond(false, ['error' => 'Te rugăm să aștepți câteva secunde și să încerci din nou.'], 400);
  }
}

/* =========================
   Fields (NU schimbăm structura vizuală — doar citim ce trimiți deja)
   ========================= */
$nume        = clean($_POST['nume'] ?? '');
$telefon     = clean($_POST['telefon'] ?? '');
$email       = clean($_POST['email'] ?? '');              // optional
$eveniment   = clean($_POST['eveniment'] ?? '');
$data        = clean($_POST['data'] ?? '');
$data = date('d.m.Y', strtotime($data));
$nrPersoane  = clean($_POST['nrPersoane'] ?? '');
$tipTort     = clean($_POST['tipTort'] ?? '');
$platouOneBite = clean($_POST['platouOneBite'] ?? '');
$mesaj       = trim((string)($_POST['mesaj'] ?? ''));

/* =========================
   Validare
   ========================= */
$errors = [];

if ($nume === '')      $errors['nume'] = 'Te rugăm să completezi acest câmp.';
if ($telefon === '')   $errors['telefon'] = 'Te rugăm să completezi acest câmp.';
if ($email !== '' && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
  $errors['email'] = 'Te rugăm să introduci un email valid.';
}

if ($eveniment === '') $errors['eveniment'] = 'Te rugăm să completezi acest câmp.';
if ($data === '')      $errors['data'] = 'Te rugăm să completezi acest câmp.';
if ($mesaj === '')     $errors['mesaj'] = 'Te rugăm să completezi acest câmp.';

/* Extra: la Aniversare cerem nr persoane + tip tort + platou */
if ($eveniment === 'Aniversare') {
  if ($nrPersoane === '') $errors['nrPersoane'] = 'Te rugăm să completezi acest câmp.';
  if ($tipTort === '')    $errors['tipTort'] = 'Te rugăm să completezi acest câmp.';
  if ($platouOneBite === '') $errors['platouOneBite'] = 'Te rugăm să completezi acest câmp.';
}

if ($errors) {
  respond(false, ['errors' => $errors], 400);
}

/* =========================
   Subject: diferit pe selecție (exact cum ai cerut)
   ========================= */
$subjectPrefix = 'Solicitare';

if ($eveniment === 'Aniversare') $subjectPrefix = 'Comandă nouă';
elseif ($eveniment === 'Botez')  $subjectPrefix = 'Ofertă – Botez';
elseif ($eveniment === 'Nuntă')  $subjectPrefix = 'Ofertă – Nuntă';
elseif ($eveniment === 'Corporate') $subjectPrefix = 'Ofertă – Corporate';

$subject = $subjectPrefix . " | {$nume}";

/* =========================
   Construim “premium mail” (HTML) – aceeași idee ca la send-contact.php
   ========================= */
function esc($s) {
  return htmlspecialchars((string)$s, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

$labelTip = $subjectPrefix;

$rowsHtml = '';
$rowsHtml .= '<tr><td style="padding:10px 0; color:#6f6258;">Nume</td><td style="padding:10px 0; color:#3b2a22; font-weight:600;">'.esc($nume).'</td></tr>';
$rowsHtml .= '<tr><td style="padding:10px 0; color:#6f6258;">Telefon</td><td style="padding:10px 0; color:#3b2a22; font-weight:600;">'.esc($telefon).'</td></tr>';
$rowsHtml .= '<tr><td style="padding:10px 0; color:#6f6258;">Email</td><td style="padding:10px 0; color:#3b2a22; font-weight:600;">'.esc($email ?: '—').'</td></tr>';
$rowsHtml .= '<tr><td style="padding:10px 0; color:#6f6258;">Tip</td><td style="padding:10px 0; color:#3b2a22; font-weight:600;">'.esc($labelTip).'</td></tr>';
$rowsHtml .= '<tr><td style="padding:10px 0; color:#6f6258;">Data evenimentului</td><td style="padding:10px 0; color:#3b2a22; font-weight:600;">'.esc($data).'</td></tr>';

if ($eveniment === 'Aniversare') {
  $rowsHtml .= '<tr><td style="padding:10px 0; color:#6f6258;">Număr persoane</td><td style="padding:10px 0; color:#3b2a22; font-weight:600;">'.esc($nrPersoane).'</td></tr>';
  $rowsHtml .= '<tr><td style="padding:10px 0; color:#6f6258;">Tip tort</td><td style="padding:10px 0; color:#3b2a22; font-weight:600;">'.esc($tipTort).'</td></tr>';
  $rowsHtml .= '<tr><td style="padding:10px 0; color:#6f6258;">Platou OneBite</td><td style="padding:10px 0; color:#3b2a22; font-weight:600;">'.esc($platouOneBite).'</td></tr>';
}

$emailHtml = '
<!doctype html>
<html lang="ro">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>'.esc($subject).'</title>
</head>
<body style="margin:0; padding:0; background:#fbfaf7; font-family: Inter, Arial, sans-serif; color:#3b2a22;">
  <div style="max-width:720px; margin:0 auto; padding:28px 18px;">
    
    <div style="background:rgba(255,255,255,0.85); border:1px solid rgba(28,16,10,0.08); border-radius:18px; padding:22px; box-shadow:0 12px 34px rgba(16,10,6,0.07);">
      
      <div style="display:flex; align-items:center; justify-content:space-between; gap:12px; border-bottom:1px solid rgba(28,16,10,0.08); padding-bottom:14px; margin-bottom:14px;">
        <div>
          <div style="font-family: Playfair Display, Georgia, serif; font-size:20px; font-weight:700; letter-spacing:-0.02em;">
            Cofetăria Christine
          </div>
          <div style="color:#6f6258; font-size:13px; margin-top:4px;">
            Solicitare primită de pe site
          </div>
        </div>
        <div style="font-size:12px; color:#6f6258; text-align:right;">
          '.esc(date('d.m.Y H:i')).'
        </div>
      </div>

      <div style="font-family: Playfair Display, Georgia, serif; font-size:18px; font-weight:700; margin:0 0 10px;">
        '.esc($subjectPrefix).'
      </div>

      <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="border-collapse:collapse; font-size:14px;">
        '.$rowsHtml.'
      </table>

      <div style="margin-top:14px; padding-top:14px; border-top:1px solid rgba(28,16,10,0.08);">
        <div style="color:#6f6258; font-size:13px; margin-bottom:6px;">Mesaj / Detalii</div>
        <div style="white-space:pre-wrap; line-height:1.55; font-size:14px; color:#3b2a22;">
          '.esc($mesaj).'
        </div>
      </div>

      <div style="margin-top:18px; color:#6f6258; font-size:12px; line-height:1.4;">
        Acest mesaj a fost generat automat de formularul de pe site.
      </div>

    </div>

    <div style="text-align:center; color:#8a7e75; font-size:12px; margin-top:14px;">
      © '.esc(date('Y')).' Cofetăria Christine
    </div>

  </div>
</body>
</html>
';

/* Text fallback (AltBody) */
$alt = $subjectPrefix . "\n"
  . "------------------------------\n"
  . "Nume: {$nume}\n"
  . "Telefon: {$telefon}\n"
  . "Email: " . ($email ?: "—") . "\n"
  . "Data: {$data}\n";

if ($eveniment === 'Aniversare') {
  $alt .= "Nr persoane: {$nrPersoane}\n"
       .  "Tip tort: {$tipTort}\n"
       .  "Platou OneBite: {$platouOneBite}\n";
}

$alt .= "\nMesaj:\n{$mesaj}\n";

/* =========================
   Trimite email (SMTP din config)
   ========================= */
$mail = new PHPMailer(true);

try {
  $mail->isSMTP();
  $mail->Host       = $config['smtp_host'];
  $mail->SMTPAuth   = true;
  $mail->Username   = $config['smtp_user'];
  $mail->Password   = $config['smtp_pass'];
  $mail->Port       = (int)$config['smtp_port'];

  if (($config['smtp_secure'] ?? '') === 'ssl') {
    $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
  } else {
    $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
  }

  $mail->CharSet = 'UTF-8';

  $mail->setFrom($config['from_email'], $config['from_name']);
  $mail->addAddress($config['to_email']);

  // dacă userul a pus email, îl folosim pentru Reply-To (super util)
  if ($email !== '' && filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $mail->addReplyTo($email, $nume ?: $email);
  }

  $mail->Subject = $subject;

  $mail->isHTML(true);
  $mail->Body    = $emailHtml;
  $mail->AltBody = $alt;

  $mail->send();

  respond(true);

} catch (Exception $e) {
  respond(false, ['error' => $mail->ErrorInfo], 500);
}