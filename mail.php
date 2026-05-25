<?php
// ─── NASTAVENIA ───────────────────────────────────────────
$prijemca       = 'info@mizaprojects.sk';
$predmet_prefix = '[stavBOOK] Nový záujem od: ';
// ──────────────────────────────────────────────────────────

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['ok' => false, 'message' => 'Metóda nie je povolená.']);
    exit;
}

function clean($value) {
    return htmlspecialchars(strip_tags(trim($value)), ENT_QUOTES, 'UTF-8');
}

$meno      = clean($_POST['meno']      ?? '');
$email     = clean($_POST['email']     ?? '');
$telefon   = clean($_POST['telefon']   ?? '');
$zamestnanci = clean($_POST['zamestnanci'] ?? '');
$gdpr      = clean($_POST['gdpr']      ?? '');

$chyby = [];
if (empty($meno))   $chyby[] = 'Meno je povinné.';
if (empty($email) || !filter_var($_POST['email'], FILTER_VALIDATE_EMAIL)) $chyby[] = 'Zadajte platný e-mail.';
if ($gdpr !== '1')  $chyby[] = 'Súhlas s ochranou osobných údajov je povinný.';

if (!empty($chyby)) {
    http_response_code(422);
    echo json_encode(['ok' => false, 'message' => implode(' ', $chyby)]);
    exit;
}

$predmet = $predmet_prefix . $meno;

$telo  = "Nový záujem zo stránky stavbook.sk\n";
$telo .= "==========================================\n\n";
$telo .= "Meno:             " . $meno                        . "\n";
$telo .= "E-mail:           " . $email                       . "\n";
$telo .= "Telefón:          " . ($telefon      ?: '—')       . "\n";
$telo .= "Počet zamestnancov: " . ($zamestnanci ?: '—')      . "\n\n";
$telo .= "Súhlas s GDPR:    ÁNO\n\n";
$telo .= "==========================================\n";
$telo .= "Odoslané: " . date('d.m.Y H:i:s') . "\n";
$telo .= "Zdroj: stavbook.sk\n";

$hlavicky  = "From: info@mizaprojects.sk\r\n";
$hlavicky .= "Reply-To: " . filter_var($_POST['email'], FILTER_SANITIZE_EMAIL) . "\r\n";
$hlavicky .= "X-Mailer: PHP/" . phpversion() . "\r\n";
$hlavicky .= "Content-Type: text/plain; charset=UTF-8\r\n";
$hlavicky .= "MIME-Version: 1.0\r\n";

$odoslane = mail($prijemca, '=?UTF-8?B?' . base64_encode($predmet) . '?=', $telo, $hlavicky);

if ($odoslane) {
    echo json_encode(['ok' => true, 'message' => 'Záujem bol úspešne odoslaný.']);
} else {
    $err = error_get_last();
    http_response_code(500);
    echo json_encode([
        'ok'      => false,
        'message' => 'Chyba pri odosielaní. Kontaktujte nás priamo na info@mizaprojects.sk',
        'debug'   => $err['message'] ?? 'neznáma chyba'
    ]);
}
?>
