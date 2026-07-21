<?php
/**
 * Generate tutorial PDF with live screenshots.
 * CLI: php docs/tutorial/generate.php
 */
declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    exit('CLI only');
}

$root = dirname(__DIR__, 2);
$tutorialDir = __DIR__;
$baseUrl = getenv('TUTORIAL_BASE_URL') ?: 'http://localhost/maarifnu';
$tutorialPass = getenv('TUTORIAL_PETUGAS_PASS') ?: 'tutorial2026';
$tutorialUser = getenv('TUTORIAL_PETUGAS_USER') ?: 'panji';

require $root . '/includes/config.php';
require $root . '/includes/database.php';
require $root . '/includes/distribusi_lkpd_functions.php';

echo "=== Generate Tutorial Distribusi LKPD ===\n";
echo "Base URL: {$baseUrl}\n";

// Ensure petugas can login for screenshots
ensureDistribusiLkpdSchema();
$pdo = getDb();
$stmt = $pdo->prepare('SELECT id FROM distribusi_petugas WHERE username = :u LIMIT 1');
$stmt->execute([':u' => $tutorialUser]);
$row = $stmt->fetch(PDO::FETCH_ASSOC);
if ($row) {
    $hash = password_hash($tutorialPass, PASSWORD_DEFAULT);
    $pdo->prepare('UPDATE distribusi_petugas SET password_hash = :p, aktif = 1 WHERE id = :id')
        ->execute([':p' => $hash, ':id' => (int) $row['id']]);
    echo "Petugas '{$tutorialUser}' password set for capture ({$tutorialPass})\n";
} else {
    $result = createDistribusiPetugas($tutorialUser, $tutorialPass, 'Petugas Demo Tutorial');
    if ($result['ok']) {
        echo "Created demo petugas: {$tutorialUser}\n";
    } else {
        echo "WARN: Could not create petugas — " . ($result['error'] ?? '') . "\n";
    }
}

$nodeBin = 'node';
$npmBin = 'npm';
foreach (['C:\\Program Files\\nodejs\\node.exe'] as $candidate) {
    if (is_file($candidate)) {
        $nodeBin = $candidate;
        $npmBin = dirname($candidate) . '\\npm.cmd';
        break;
    }
}

chdir($tutorialDir);

echo "\n[1/4] npm install...\n";
passthru('"' . $npmBin . '" install 2>&1', $code1);
if ($code1 !== 0) {
    exit("npm install failed\n");
}

echo "\n[2/4] Install Playwright Chromium...\n";
passthru('"' . $nodeBin . '" node_modules/playwright/cli.js install chromium 2>&1', $code2);

echo "\n[3/4] Capture screenshots...\n";
putenv('TUTORIAL_BASE_URL=' . $baseUrl);
putenv('TUTORIAL_PETUGAS_USER=' . $tutorialUser);
putenv('TUTORIAL_PETUGAS_PASS=' . $tutorialPass);
passthru('"' . $nodeBin . '" capture-screenshots.mjs 2>&1', $code3);
if ($code3 !== 0) {
    exit("Screenshot capture failed\n");
}

echo "\n[4/4] Build PDF...\n";
passthru('python build-pdf.py 2>&1', $code4);
if ($code4 !== 0) {
    passthru('py build-pdf.py 2>&1', $code5);
    if ($code5 !== 0) {
        exit("PDF build failed\n");
    }
}

$pdf = $tutorialDir . '/TUTORIAL-DISTRIBUSI-LKPD-MI-MAARIF.pdf';
if (is_file($pdf)) {
    echo "\nSUCCESS: {$pdf}\n";
    echo "Size: " . number_format(filesize($pdf) / 1024, 1) . " KB\n";
} else {
    exit("PDF not found after build\n");
}
