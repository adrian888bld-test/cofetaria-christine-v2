<?php
// send-contact.php
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

function clean($v) {
  return trim(str_replace(["\r","\n"], ' ', (string)$v));
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
  http_response_code(405);
  exit('Method not allowed');
}

/* Anti-spam: honeypot */
$hp = trim((string)($_POST['website'] ?? ''));
if ($hp !== '') {
  http_response_code(400);
  exit('Spam detected');
}

/* Anti-spam: time-check (min 8 sec) */
$start = (int)($_POST['form_time'] ?? 0);
if ($start > 0) {
  $elapsed = (int) round((microtime(true) * 1000) - $start);
  if ($elapsed < 8000) {
    http_response_code(400);
    exit('Te rugăm să aștepți câteva secunde și să încerci din nou.');
  }
}

/* Câmpuri */
$nume    = clean($_POST['nume'] ?? '');
$telefon = clean($_POST['telefon'] ?? '');
$email   = clean($_POST['email'] ?? '');
$mesaj   = trim((string)($_POST['mesaj'] ?? ''));

/* Validare minimă */
$errors = [];
if ($nume === '')    $errors[] = 'Nume lipsă';
if ($telefon === '') $errors[] = 'Telefon lipsă';
if ($email !== '' && !filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = 'Email invalid';
if ($mesaj === '')   $errors[] = 'Mesaj lipsă';

if ($errors) {
  http_response_code(400);
  exit('Date invalide');
}

/* Helpers pentru email HTML */
function esc($s) {
  return htmlspecialchars((string)$s, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}
function nl2br_safe($s) {
  return nl2br(esc($s));
}

$siteName = 'Cofetăria Christine';
$toEmail  = 'test@cofetaria-christine.ro'; // destinatar formular contact

// Subject elegant, scurt
$subject = "Mesaj nou (Contact) — {$nume}";

/* Construim email HTML (frumos) */
$preheader = "Mesaj nou primit din pagina Contact.";
$now = date('d.m.Y H:i');

$html = '<!doctype html>
<html lang="ro">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>'.esc($siteName).' — Mesaj contact</title>
</head>
<body style="margin:0; padding:0; background:#f6f1ea; font-family:Arial, Helvetica, sans-serif; color:#3b2a22;">
  <div style="display:none; max-height:0; overflow:hidden; opacity:0; mso-hide:all;">
    '.esc($preheader).'
  </div>

  <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="background:#f6f1ea; padding:24px 12px;">
    <tr>
      <td align="center">
        <table role="presentation" width="640" cellspacing="0" cellpadding="0" style="max-width:640px; width:100%;">
          <tr>
            <td style="padding:14px 18px; text-align:left;">
              <div style="font-size:13px; opacity:.75;">'.$siteName.'</div>
              <div style="font-size:22px; font-weight:700; letter-spacing:-.2px; margin-top:6px;">
                Mesaj nou din formularul de contact
              </div>
              <div style="font-size:12px; opacity:.7; margin-top:6px;">
                Trimisa la '.$now.'
              </div>
            </td>
          </tr>

          <tr>
            <td style="background:#ffffff; border:1px solid rgba(59,42,34,.12); border-radius:16px; overflow:hidden;">
              <table role="presentation" width="100%" cellspacing="0" cellpadding="0">
                <tr>
                  <td style="padding:16px 18px; background:rgba(255,255,255,.9); border-bottom:1px solid rgba(59,42,34,.08);">
                    <div style="font-size:14px; font-weight:700;">Detalii contact</div>
                  </td>
                </tr>

                <tr>
                  <td style="padding:14px 18px;">
                    <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="font-size:14px; line-height:1.5;">
                      <tr>
                        <td style="padding:6px 0; width:140px; opacity:.75;">Nume</td>
                        <td style="padding:6px 0; font-weight:600;">'.esc($nume).'</td>
                      </tr>
                      <tr>
                        <td style="padding:6px 0; width:140px; opacity:.75;">Telefon</td>
                        <td style="padding:6px 0; font-weight:600;">
                          <a href="tel:'.esc($telefon).'" style="color:#3b2a22; text-decoration:underline;">'.esc($telefon).'</a>
                        </td>
                      </tr>
                      <tr>
                        <td style="padding:6px 0; width:140px; opacity:.75;">Email</td>
                        <td style="padding:6px 0; font-weight:600;">'.($email !== '' ? '<a href="mailto:'.esc($email).'" style="color:#3b2a22; text-decoration:underline;">'.esc($email).'</a>' : '<span style="opacity:.7;">(nu a completat)</span>').'</td>
                      </tr>
                    </table>
                  </td>
                </tr>

                <tr>
                  <td style="padding:16px 18px; background:rgba(251,243,232,.55); border-top:1px solid rgba(59,42,34,.08);">
                    <div style="font-size:14px; font-weight:700; margin-bottom:10px;">Mesaj</div>
                    <div style="font-size:14px; line-height:1.65; white-space:normal;">
                      '.nl2br_safe($mesaj).'
                    </div>
                  </td>
                </tr>

                <tr>
                  <td style="padding:14px 18px; font-size:12px; opacity:.7;">
                    Acest email a fost generat automat din formularul de contact al site-ului '.$siteName.'.
                  </td>
                </tr>
              </table>
            </td>
          </tr>

          <tr>
            <td style="padding:14px 18px; font-size:12px; opacity:.7; text-align:center;">
              © '.date('Y').' '.$siteName.'
            </td>
          </tr>
        </table>
      </td>
    </tr>
  </table>
</body>
</html>';

/* AltBody (fallback text) */
$alt =
"Mesaj nou (Contact) - {$siteName}\n"
."Trimis la {$now}\n"
."==============================\n"
."Nume: {$nume}\n"
."Telefon: {$telefon}\n"
."Email: ".($email !== '' ? $email : '(nu a completat)')."\n\n"
."Mesaj:\n{$mesaj}\n";

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
  $mail->addAddress($toEmail);

  if ($email !== '') {
    $mail->addReplyTo($email, $nume);
  }

  $mail->Subject = $subject;

  // AICI e “mailul frumos”
  $mail->isHTML(true);
  $mail->Body    = $html;
  $mail->AltBody = $alt;

  $mail->send();

  header('Location: multumim-contact.html', true, 302);
  exit;

} catch (Exception $e) {
  http_response_code(500);
  exit('Eroare la trimitere');
}