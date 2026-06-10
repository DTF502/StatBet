<?php
// backend/run_update.php
header('Content-Type: application/json; charset=utf-8');

$scriptPath = dirname(__DIR__) . DIRECTORY_SEPARATOR . 'database' . DIRECTORY_SEPARATOR . 'update_matches.php';

if (!file_exists($scriptPath)) {
    echo json_encode(['ok' => false, 'error' => 'Script no encontrado: ' . $scriptPath]);
    exit;
}

$phpBin = 'C:\\xampp\\php\\php.exe';
$command = escapeshellcmd("\"$phpBin\" \"$scriptPath\"") . ' 2>&1';

$output = [];
$returnCode = 0;
exec($command, $output, $returnCode);

echo json_encode([
    'ok'     => $returnCode === 0,
    'output' => implode("\n", $output),
    'code'   => $returnCode,
], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
?>