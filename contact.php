<?php
header('Access-Control-Allow-Origin: https://webnetzone.github.io');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['ok' => false, 'error' => 'Method not allowed']);
    exit;
}

$name     = trim(strip_tags($_POST['name'] ?? ''));
$schule   = trim(strip_tags($_POST['schule'] ?? ''));
$email    = trim($_POST['email'] ?? '');
$nachricht = trim(strip_tags($_POST['nachricht'] ?? ''));
$consent  = $_POST['consent'] ?? '';

if (!$name || !$schule || !$email || !$consent) {
    http_response_code(400);
    echo json_encode(['ok' => false, 'error' => 'Pflichtfelder fehlen']);
    exit;
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    http_response_code(400);
    echo json_encode(['ok' => false, 'error' => 'Ungültige E-Mail-Adresse']);
    exit;
}

$to      = 'info@webnetzone.de';
$subject = 'Demo-Anfrage Schulinventar: ' . $name;
$body    = "Name: $name\nSchule: $schule\nE-Mail: $email\n\nNachricht:\n$nachricht";
$headers = "From: noreply@webnetzone.de\r\nReply-To: $email\r\nX-Mailer: PHP";

if (mail($to, $subject, $body, $headers)) {
    echo json_encode(['ok' => true]);
} else {
    http_response_code(500);
    echo json_encode(['ok' => false, 'error' => 'Mail konnte nicht gesendet werden']);
}
