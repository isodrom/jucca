<?php
/**
 * JUCCA LLC contact form handler.
 * Sends the form to the address below using the server's own mail (PHP mail()).
 * Works on GoDaddy Linux hosting and GoDaddy WordPress hosting. No account or API key needed.
 */

$TO        = 'juccallc@yahoo.com';        // where messages are delivered
$SITE_NAME = 'JUCCA LLC website';
$MAX_PER_HOUR = 5;                         // per visitor IP

header('Content-Type: application/json; charset=utf-8');
header('X-Content-Type-Options: nosniff');

function finish($ok, $code = 200, $error = '') {
    http_response_code($code);
    echo json_encode(array('success' => $ok, 'error' => $error));
    exit;
}
function clean_line($v, $max) {
    $v = trim(preg_replace('/[\r\n\t\0]+/', ' ', (string)$v));
    return function_exists('mb_substr') ? mb_substr($v, 0, $max, 'UTF-8') : substr($v, 0, $max);
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') { finish(false, 405, 'method'); }

// --- Spam checks -----------------------------------------------------------
// 1. Honeypot: real visitors never see or fill the "website" field.
// 2. Time trap: the page reports how long the visitor took. Bots submit instantly.
// Both pretend to succeed so bots do not learn they were blocked.
$elapsed = isset($_POST['elapsed']) ? (int)$_POST['elapsed'] : 0;
if (!empty($_POST['website']) || $elapsed < 3000) { finish(true); }

$name    = clean_line(isset($_POST['name']) ? $_POST['name'] : '', 120);
$company = clean_line(isset($_POST['company']) ? $_POST['company'] : '', 160);
$email   = clean_line(isset($_POST['email']) ? $_POST['email'] : '', 200);
$lang    = (isset($_POST['lang']) && $_POST['lang'] === 'es') ? 'es' : 'en';
$message = trim(str_replace("\0", '', isset($_POST['message']) ? (string)$_POST['message'] : ''));
if (strlen($message) > 6000) { $message = substr($message, 0, 6000); }

if ($name === '' || $message === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) { finish(false, 422, 'invalid'); }

// 3. Link flood: more than two links in a first message is almost always spam.
if (preg_match_all('#https?://|www\.#i', $message . ' ' . $name . ' ' . $company) > 2) { finish(true); }

// 4. Rate limit per IP.
$ip   = isset($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : 'unknown';
$file = rtrim(sys_get_temp_dir(), '/\\') . '/jucca_form_' . md5($ip) . '.txt';
$now  = time();
$hits = array();
if (is_readable($file)) {
    foreach (explode("\n", (string)@file_get_contents($file)) as $t) {
        if ((int)$t > $now - 3600) { $hits[] = (int)$t; }
    }
}
if (count($hits) >= $MAX_PER_HOUR) { finish(false, 429, 'rate'); }
$hits[] = $now;
@file_put_contents($file, implode("\n", $hits), LOCK_EX);

// --- Build and send --------------------------------------------------------
// The From address must be on this website's own domain, never the Yahoo address,
// or mail providers reject it. Replies go to the visitor through Reply-To.
$host = isset($_SERVER['SERVER_NAME']) ? strtolower($_SERVER['SERVER_NAME']) : 'localhost';
$host = preg_replace('/[^a-z0-9.\-]/', '', preg_replace('/^www\./', '', $host));
if ($host === '') { $host = 'localhost'; }
$from = 'website@' . $host;

$subject = 'New message from the JUCCA LLC website: ' . $name;
$body  = "Name: $name\n";
$body .= "Company: " . ($company !== '' ? $company : '-') . "\n";
$body .= "Email: $email\n";
$body .= "Page language: " . ($lang === 'es' ? 'Spanish' : 'English') . "\n\n";
$body .= "Message:\n$message\n\n";
$body .= "--\nSent from the contact form on $host\n";

$enc_subject = function_exists('mb_encode_mimeheader') ? mb_encode_mimeheader($subject, 'UTF-8', 'B', "\r\n") : $subject;
$headers  = "From: $SITE_NAME <$from>\r\n";
$headers .= "Reply-To: $email\r\n";
$headers .= "MIME-Version: 1.0\r\n";
$headers .= "Content-Type: text/plain; charset=UTF-8\r\n";
$headers .= "Content-Transfer-Encoding: 8bit\r\n";

$sent = @mail($TO, $enc_subject, $body, $headers, '-f' . $from);
if (!$sent) { $sent = @mail($TO, $enc_subject, $body, $headers); }

finish((bool)$sent, $sent ? 200 : 500, $sent ? '' : 'mail');
