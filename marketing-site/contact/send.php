<?php
/**
 * ReviveGuard — Contact Form Mailer
 * POST-only endpoint. Returns JSON: {"ok": true} or {"ok": false, "message": "..."}
 *
 * Requirements: PHP 7.4+ with mail() enabled, or swap the send() call for PHPMailer/SMTP.
 * Place this file at /contact/send.php on the same server as the HTML page.
 */

declare(strict_types=1);

// ── Config ────────────────────────────────────────────────────────────────────
const TO_EMAIL      = 'hello@reviveguard.com';     // inbox that receives submissions
const TO_NAME       = 'ReviveGuard Team';
const FROM_EMAIL    = 'noreply@reviveguard.com';   // must be a verified sender on your host
const FROM_NAME     = 'ReviveGuard Contact Form';
const SUBJECT_PREFIX = '[ReviveGuard Contact]';

// Emergency copies — this address is CC'd when topic = emergency
const EMERGENCY_CC  = 'emergency@reviveguard.com';

// Allowed origin for CORS (set to your domain)
const ALLOWED_ORIGIN = 'https://reviveguard.com';
// ─────────────────────────────────────────────────────────────────────────────

// Only accept POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    exit;
}

// JSON response helper
function respond(bool $ok, string $message = ''): never
{
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(['ok' => $ok, 'message' => $message], JSON_UNESCAPED_UNICODE);
    exit;
}

// CORS — only allow requests from your own domain (or localhost for dev)
$origin = $_SERVER['HTTP_ORIGIN'] ?? '';
if ($origin !== ALLOWED_ORIGIN && !in_array($origin, ['http://localhost', 'http://127.0.0.1'], true)) {
    http_response_code(403);
    respond(false, 'Forbidden');
}
header('Access-Control-Allow-Origin: ' . ALLOWED_ORIGIN);

// ── Honeypot anti-spam check ──────────────────────────────────────────────────
// The "website_confirm" field is hidden from real users; bots fill it.
if (!empty($_POST['website_confirm'])) {
    // Silently succeed so bots don't know they were blocked
    respond(true);
}

// ── Input collection & sanitisation ──────────────────────────────────────────
$first_name    = trim(strip_tags($_POST['first_name']    ?? ''));
$last_name     = trim(strip_tags($_POST['last_name']     ?? ''));
$email         = trim($_POST['email']                    ?? '');
$website       = trim(strip_tags($_POST['website']       ?? ''));
$topic_select  = trim(strip_tags($_POST['topic_select']  ?? ''));
$plan_interest = trim(strip_tags($_POST['plan_interest'] ?? ''));
$client_id     = trim(strip_tags($_POST['client_id']     ?? ''));
$message       = trim(strip_tags($_POST['message']       ?? ''));

// ── Server-side validation ────────────────────────────────────────────────────
if ($first_name === '') {
    respond(false, 'First name is required.');
}

if (strlen($first_name) > 80 || strlen($last_name) > 80) {
    respond(false, 'Name is too long.');
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($email) > 254) {
    respond(false, 'Please provide a valid email address.');
}

$allowed_topics = [
    'general', 'pricing', 'evaluation', 'support',
    'emergency', 'alumni', 'partnership', 'other'
];
if (!in_array($topic_select, $allowed_topics, true)) {
    respond(false, 'Please select a valid topic.');
}

if ($message === '' || strlen($message) < 10) {
    respond(false, 'Message must be at least 10 characters.');
}

if (strlen($message) > 4000) {
    respond(false, 'Message is too long (max 4000 characters).');
}

// Validate website URL if provided
if ($website !== '' && !filter_var($website, FILTER_VALIDATE_URL)) {
    respond(false, 'Website URL doesn\'t look valid — please check the format (https://...).');
}

// Rate limiting via session (basic — swap for Redis/DB in production if needed)
session_start();
$now = time();
$window = 3600; // 1 hour
$max_per_window = 5;

if (!isset($_SESSION['rg_contact_sends'])) {
    $_SESSION['rg_contact_sends'] = [];
}
// Remove timestamps outside the window
$_SESSION['rg_contact_sends'] = array_filter(
    $_SESSION['rg_contact_sends'],
    fn($t) => $now - $t < $window
);
if (count($_SESSION['rg_contact_sends']) >= $max_per_window) {
    respond(false, 'You have sent too many messages recently. Please wait a while before trying again.');
}
$_SESSION['rg_contact_sends'][] = $now;

// ── Build email ───────────────────────────────────────────────────────────────
$full_name = $first_name . ($last_name !== '' ? ' ' . $last_name : '');

$topic_labels = [
    'general'     => 'General Question',
    'pricing'     => 'Pricing / Plans',
    'evaluation'  => 'Request Evaluation',
    'support'     => 'Client Support',
    'emergency'   => 'SITE EMERGENCY',
    'alumni'      => 'WaybackRevive Alumni — Priority Invite',
    'partnership' => 'Partnership / Referral',
    'other'       => 'Other',
];
$topic_label = $topic_labels[$topic_select] ?? $topic_select;

$subject = SUBJECT_PREFIX . ' [' . $topic_label . '] from ' . $full_name;

// Plain-text body
$body  = "New contact form submission from ReviveGuard.com\n";
$body .= str_repeat('=', 55) . "\n\n";
$body .= "Topic:      {$topic_label}\n";
$body .= "Name:       {$full_name}\n";
$body .= "Email:      {$email}\n";

if ($website !== '') {
    $body .= "Website:    {$website}\n";
}
if ($plan_interest !== '') {
    $plan_labels = [
        'monitor'  => 'Monitor — $49/mo',
        'guard'    => 'Guard — $99/mo',
        'shield'   => 'Shield — $179/mo',
        'multiple' => 'Multiple sites / custom',
    ];
    $body .= "Plan Interest: " . ($plan_labels[$plan_interest] ?? $plan_interest) . "\n";
}
if ($client_id !== '') {
    $body .= "Account ID: {$client_id}\n";
}

$body .= "\nMessage:\n" . str_repeat('-', 40) . "\n{$message}\n" . str_repeat('-', 40) . "\n";
$body .= "\n-- Sent via reviveguard.com/contact/\n";
$body .= "Submitter IP: " . ($_SERVER['REMOTE_ADDR'] ?? 'unknown') . "\n";
$body .= "Submitted at: " . date('Y-m-d H:i:s T') . "\n";

// ── Headers ───────────────────────────────────────────────────────────────────
$headers  = "From: " . FROM_NAME . " <" . FROM_EMAIL . ">\r\n";
$headers .= "Reply-To: {$full_name} <{$email}>\r\n";
$headers .= "MIME-Version: 1.0\r\n";
$headers .= "Content-Type: text/plain; charset=UTF-8\r\n";
$headers .= "Content-Transfer-Encoding: 8bit\r\n";
$headers .= "X-Mailer: ReviveGuard-ContactForm/1.0\r\n";

// CC emergency inbox on site emergencies
if ($topic_select === 'emergency') {
    $headers .= "Cc: " . EMERGENCY_CC . "\r\n";
}

// ── Send ─────────────────────────────────────────────────────────────────────
$to = TO_NAME . ' <' . TO_EMAIL . '>';

// Encode subject for UTF-8 safety
$encoded_subject = '=?UTF-8?B?' . base64_encode($subject) . '?=';

$sent = mail($to, $encoded_subject, $body, $headers);

if (!$sent) {
    // Log to server error log — do not expose details to the client
    error_log('[ReviveGuard Contact] mail() failed for submission from ' . $email);
    respond(false, 'We couldn\'t send your message due to a server issue. Please email us directly at hello@reviveguard.com.');
}

respond(true);
