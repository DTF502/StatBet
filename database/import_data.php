<?php
// Importador StatBet: carga Matches.csv + all-euro-data-2025-2026.xlsx en MySQL.
// Ejecutar desde la raíz del proyecto:
// /c/xampp/php/php.exe database/import_data.php

ini_set('memory_limit', '1024M');
set_time_limit(0);

$root = dirname(__DIR__);
$csvPath = $root . DIRECTORY_SEPARATOR . 'data' . DIRECTORY_SEPARATOR . 'raw' . DIRECTORY_SEPARATOR . 'Matches.csv';
$xlsxPath = $root . DIRECTORY_SEPARATOR . 'data' . DIRECTORY_SEPARATOR . 'raw' . DIRECTORY_SEPARATOR . 'all-euro-data-2025-2026.xlsx';
$minHistoricalDate = '2022-01-01';

$pdo = new PDO('mysql:host=localhost;dbname=statbet;charset=utf8mb4', 'root', '', [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
]);

$sql = "
INSERT INTO matches (
    source_file, division_code, season, match_date, match_time,
    home_team, away_team,
    ft_home_goals, ft_away_goals, ft_result,
    ht_home_goals, ht_away_goals, ht_result,
    home_shots, away_shots,
    home_shots_target, away_shots_target,
    home_fouls, away_fouls,
    home_corners, away_corners,
    home_yellow, away_yellow,
    home_red, away_red,
    home_elo, away_elo
) VALUES (
    :source_file, :division_code, :season, :match_date, :match_time,
    :home_team, :away_team,
    :ft_home_goals, :ft_away_goals, :ft_result,
    :ht_home_goals, :ht_away_goals, :ht_result,
    :home_shots, :away_shots,
    :home_shots_target, :away_shots_target,
    :home_fouls, :away_fouls,
    :home_corners, :away_corners,
    :home_yellow, :away_yellow,
    :home_red, :away_red,
    :home_elo, :away_elo
)
ON DUPLICATE KEY UPDATE
    season = VALUES(season),
    match_time = VALUES(match_time),
    ft_home_goals = VALUES(ft_home_goals),
    ft_away_goals = VALUES(ft_away_goals),
    ft_result = VALUES(ft_result),
    ht_home_goals = VALUES(ht_home_goals),
    ht_away_goals = VALUES(ht_away_goals),
    ht_result = VALUES(ht_result),
    home_shots = VALUES(home_shots),
    away_shots = VALUES(away_shots),
    home_shots_target = VALUES(home_shots_target),
    away_shots_target = VALUES(away_shots_target),
    home_fouls = VALUES(home_fouls),
    away_fouls = VALUES(away_fouls),
    home_corners = VALUES(home_corners),
    away_corners = VALUES(away_corners),
    home_yellow = VALUES(home_yellow),
    away_yellow = VALUES(away_yellow),
    home_red = VALUES(home_red),
    away_red = VALUES(away_red),
    home_elo = VALUES(home_elo),
    away_elo = VALUES(away_elo),
    source_file = VALUES(source_file)
";

$stmt = $pdo->prepare($sql);

function cleanValue($value) {
    if ($value === null) return null;
    $value = trim((string)$value);
    return $value === '' ? null : $value;
}

function cleanInt($value) {
    $value = cleanValue($value);
    if ($value === null) return null;
    return (int)round((float)$value);
}

function cleanDecimal($value) {
    $value = cleanValue($value);
    if ($value === null) return null;
    return (float)$value;
}

function cleanText($value) {
    $value = cleanValue($value);
    return $value === null ? null : $value;
}

function normalizeDateValue($value) {
    $value = cleanValue($value);
    if ($value === null) return null;

    // Excel serial date, e.g. 45884
    if (is_numeric($value) && (float)$value > 20000) {
        $base = new DateTime('1899-12-30');
        $base->modify('+' . ((int)$value) . ' days');
        return $base->format('Y-m-d');
    }

    $formats = ['Y-m-d', 'd/m/Y', 'd/m/y', 'm/d/Y', 'm/d/y'];
    foreach ($formats as $format) {
        $dt = DateTime::createFromFormat($format, $value);
        if ($dt instanceof DateTime) {
            return $dt->format('Y-m-d');
        }
    }

    $timestamp = strtotime($value);
    return $timestamp ? date('Y-m-d', $timestamp) : null;
}

function normalizeTimeValue($value) {
    $value = cleanValue($value);
    if ($value === null) return null;

    // Excel time fraction, e.g. 0.833333333 = 20:00:00
    if (is_numeric($value) && (float)$value > 0 && (float)$value < 1) {
        $seconds = (int)round((float)$value * 86400);
        $hours = intdiv($seconds, 3600);
        $minutes = intdiv($seconds % 3600, 60);
        return sprintf('%02d:%02d:00', $hours, $minutes);
    }

    if (preg_match('/^\d{1,2}:\d{2}/', $value)) {
        return substr($value, 0, 5) . ':00';
    }

    return null;
}

function seasonFromDate($date) {
    if (!$date) return null;
    $dt = new DateTime($date);
    $year = (int)$dt->format('Y');
    $month = (int)$dt->format('n');
    $start = $month >= 7 ? $year : $year - 1;
    $endShort = substr((string)($start + 1), -2);
    return $start . '/' . $endShort;
}

function executeMatchInsert(PDOStatement $stmt, array $row) {
    $date = normalizeDateValue($row['date'] ?? null);
    $home = cleanText($row['home_team'] ?? null);
    $away = cleanText($row['away_team'] ?? null);
    $division = cleanText($row['division_code'] ?? null);

    // Solo importamos partidos jugados con marcador.
    $homeGoals = cleanInt($row['ft_home_goals'] ?? null);
    $awayGoals = cleanInt($row['ft_away_goals'] ?? null);

    if (!$date || !$home || !$away || !$division || $homeGoals === null || $awayGoals === null) {
        return false;
    }

    $stmt->execute([
        ':source_file' => $row['source_file'],
        ':division_code' => $division,
        ':season' => seasonFromDate($date),
        ':match_date' => $date,
        ':match_time' => normalizeTimeValue($row['time'] ?? null),
        ':home_team' => $home,
        ':away_team' => $away,
        ':ft_home_goals' => $homeGoals,
        ':ft_away_goals' => $awayGoals,
        ':ft_result' => cleanText($row['ft_result'] ?? null),
        ':ht_home_goals' => cleanInt($row['ht_home_goals'] ?? null),
        ':ht_away_goals' => cleanInt($row['ht_away_goals'] ?? null),
        ':ht_result' => cleanText($row['ht_result'] ?? null),
        ':home_shots' => cleanInt($row['home_shots'] ?? null),
        ':away_shots' => cleanInt($row['away_shots'] ?? null),
        ':home_shots_target' => cleanInt($row['home_shots_target'] ?? null),
        ':away_shots_target' => cleanInt($row['away_shots_target'] ?? null),
        ':home_fouls' => cleanInt($row['home_fouls'] ?? null),
        ':away_fouls' => cleanInt($row['away_fouls'] ?? null),
        ':home_corners' => cleanInt($row['home_corners'] ?? null),
        ':away_corners' => cleanInt($row['away_corners'] ?? null),
        ':home_yellow' => cleanInt($row['home_yellow'] ?? null),
        ':away_yellow' => cleanInt($row['away_yellow'] ?? null),
        ':home_red' => cleanInt($row['home_red'] ?? null),
        ':away_red' => cleanInt($row['away_red'] ?? null),
        ':home_elo' => cleanDecimal($row['home_elo'] ?? null),
        ':away_elo' => cleanDecimal($row['away_elo'] ?? null),
    ]);

    return true;
}

function importCsvMatches(PDO $pdo, PDOStatement $stmt, string $csvPath, string $minDate): array {
    if (!file_exists($csvPath)) {
        echo "No existe Matches.csv en: $csvPath\n";
        return [0, 0];
    }

    $handle = fopen($csvPath, 'r');
    $headers = fgetcsv($handle);
    $map = array_flip($headers);

    $imported = 0;
    $skipped = 0;

    $pdo->beginTransaction();

    while (($cols = fgetcsv($handle)) !== false) {
        $date = normalizeDateValue($cols[$map['MatchDate']] ?? null);
        if (!$date || $date < $minDate) {
            $skipped++;
            continue;
        }

        $row = [
            'source_file' => 'Matches.csv',
            'division_code' => $cols[$map['Division']] ?? null,
            'date' => $date,
            'time' => $cols[$map['MatchTime']] ?? null,
            'home_team' => $cols[$map['HomeTeam']] ?? null,
            'away_team' => $cols[$map['AwayTeam']] ?? null,
            'ft_home_goals' => $cols[$map['FTHome']] ?? null,
            'ft_away_goals' => $cols[$map['FTAway']] ?? null,
            'ft_result' => $cols[$map['FTResult']] ?? null,
            'ht_home_goals' => $cols[$map['HTHome']] ?? null,
            'ht_away_goals' => $cols[$map['HTAway']] ?? null,
            'ht_result' => $cols[$map['HTResult']] ?? null,
            'home_shots' => $cols[$map['HomeShots']] ?? null,
            'away_shots' => $cols[$map['AwayShots']] ?? null,
            'home_shots_target' => $cols[$map['HomeTarget']] ?? null,
            'away_shots_target' => $cols[$map['AwayTarget']] ?? null,
            'home_fouls' => $cols[$map['HomeFouls']] ?? null,
            'away_fouls' => $cols[$map['AwayFouls']] ?? null,
            'home_corners' => $cols[$map['HomeCorners']] ?? null,
            'away_corners' => $cols[$map['AwayCorners']] ?? null,
            'home_yellow' => $cols[$map['HomeYellow']] ?? null,
            'away_yellow' => $cols[$map['AwayYellow']] ?? null,
            'home_red' => $cols[$map['HomeRed']] ?? null,
            'away_red' => $cols[$map['AwayRed']] ?? null,
            'home_elo' => $cols[$map['HomeElo']] ?? null,
            'away_elo' => $cols[$map['AwayElo']] ?? null,
        ];

        if (executeMatchInsert($stmt, $row)) {
            $imported++;
        } else {
            $skipped++;
        }

        if ($imported > 0 && $imported % 5000 === 0) {
            $pdo->commit();
            echo "Matches.csv importados: $imported\n";
            $pdo->beginTransaction();
        }
    }

    $pdo->commit();
    fclose($handle);

    $pdo->prepare('INSERT INTO import_logs (source_file, imported_rows, skipped_rows) VALUES (?, ?, ?)')
        ->execute(['Matches.csv', $imported, $skipped]);

    return [$imported, $skipped];
}

function columnLettersToIndex($letters) {
    $index = 0;
    $letters = strtoupper($letters);
    for ($i = 0; $i < strlen($letters); $i++) {
        $index = $index * 26 + (ord($letters[$i]) - 64);
    }
    return $index - 1;
}

function xpathAll(SimpleXMLElement $xml, string $path): array {
    $result = $xml->xpath($path);
    return is_array($result) ? $result : [];
}

function loadSharedStrings(ZipArchive $zip): array {
    $idx = $zip->locateName('xl/sharedStrings.xml');
    if ($idx === false) return [];

    $xmlContent = $zip->getFromIndex($idx);
    if ($xmlContent === false) return [];

    $xml = simplexml_load_string($xmlContent);
    if (!$xml) return [];

    $strings = [];
    foreach (xpathAll($xml, '//*[local-name()="si"]') as $si) {
        $parts = [];
        foreach (xpathAll($si, './/*[local-name()="t"]') as $t) {
            $parts[] = (string)$t;
        }
        $strings[] = implode('', $parts);
    }
    return $strings;
}

function readXlsxSheetRows(ZipArchive $zip, string $sheetPath, array $sharedStrings): array {
    $xmlContent = $zip->getFromName($sheetPath);
    if ($xmlContent === false) return [];

    $xml = simplexml_load_string($xmlContent);
    if (!$xml) return [];

    $rows = [];
    foreach (xpathAll($xml, '//*[local-name()="sheetData"]/*[local-name()="row"]') as $rowXml) {
        $row = [];
        foreach (xpathAll($rowXml, './*[local-name()="c"]') as $cell) {
            $ref = (string)$cell['r'];
            if (!preg_match('/^[A-Z]+/', $ref, $matches)) {
                continue;
            }

            $colIndex = columnLettersToIndex($matches[0]);
            $type = (string)$cell['t'];
            $valueNodes = xpathAll($cell, './*[local-name()="v"]');
            $value = isset($valueNodes[0]) ? (string)$valueNodes[0] : '';

            if ($type === 's' && $value !== '') {
                $value = $sharedStrings[(int)$value] ?? '';
            } elseif ($type === 'inlineStr') {
                $texts = [];
                foreach (xpathAll($cell, './/*[local-name()="t"]') as $t) {
                    $texts[] = (string)$t;
                }
                $value = implode('', $texts);
            }

            $row[$colIndex] = $value;
        }

        if ($row) {
            $max = max(array_keys($row));
            $fullRow = [];
            for ($i = 0; $i <= $max; $i++) {
                $fullRow[$i] = $row[$i] ?? '';
            }
            $rows[] = $fullRow;
        }
    }

    return $rows;
}

function getWorkbookSheets(ZipArchive $zip): array {
    $workbookXml = $zip->getFromName('xl/workbook.xml');
    $relsXml = $zip->getFromName('xl/_rels/workbook.xml.rels');
    if ($workbookXml === false || $relsXml === false) return [];

    $workbook = simplexml_load_string($workbookXml);
    $rels = simplexml_load_string($relsXml);
    if (!$workbook || !$rels) return [];

    $relMap = [];
    foreach (xpathAll($rels, '/*[local-name()="Relationships"]/*[local-name()="Relationship"]') as $rel) {
        $id = (string)$rel['Id'];
        $target = (string)$rel['Target'];
        if ($id && $target) {
            $relMap[$id] = strpos($target, 'xl/') === 0 ? $target : 'xl/' . ltrim($target, '/');
        }
    }

    $sheets = [];
    foreach (xpathAll($workbook, '//*[local-name()="sheets"]/*[local-name()="sheet"]') as $sheet) {
        $attrs = $sheet->attributes('http://schemas.openxmlformats.org/officeDocument/2006/relationships');
        $rid = (string)($attrs['id'] ?? '');
        $name = (string)$sheet['name'];
        if ($rid && isset($relMap[$rid])) {
            $sheets[] = ['name' => $name, 'path' => $relMap[$rid]];
        }
    }
    return $sheets;
}

function valByHeader(array $row, array $map, string $key) {
    return isset($map[$key]) ? ($row[$map[$key]] ?? null) : null;
}

function importXlsxMatches(PDO $pdo, PDOStatement $stmt, string $xlsxPath): array {
    if (!file_exists($xlsxPath)) {
        echo "No existe all-euro-data-2025-2026.xlsx en: $xlsxPath\n";
        return [0, 0];
    }

    if (!class_exists('ZipArchive')) {
        throw new RuntimeException('La extensión ZipArchive de PHP no está activa. Hay que activarla para leer XLSX.');
    }

    $zip = new ZipArchive();
    if ($zip->open($xlsxPath) !== true) {
        throw new RuntimeException('No se pudo abrir el XLSX.');
    }

    $sharedStrings = loadSharedStrings($zip);
    $sheets = getWorkbookSheets($zip);

    $imported = 0;
    $skipped = 0;

    $pdo->beginTransaction();

    foreach ($sheets as $sheet) {
        echo "Importando hoja {$sheet['name']}...\n";
        $rows = readXlsxSheetRows($zip, $sheet['path'], $sharedStrings);
        if (count($rows) < 2) continue;

        $headers = array_map('trim', $rows[0]);
        $map = array_flip($headers);

        for ($i = 1; $i < count($rows); $i++) {
            $r = $rows[$i];

            $row = [
                'source_file' => 'all-euro-data-2025-2026.xlsx',
                'division_code' => valByHeader($r, $map, 'Div') ?: $sheet['name'],
                'date' => valByHeader($r, $map, 'Date'),
                'time' => valByHeader($r, $map, 'Time'),
                'home_team' => valByHeader($r, $map, 'HomeTeam'),
                'away_team' => valByHeader($r, $map, 'AwayTeam'),
                'ft_home_goals' => valByHeader($r, $map, 'FTHG'),
                'ft_away_goals' => valByHeader($r, $map, 'FTAG'),
                'ft_result' => valByHeader($r, $map, 'FTR'),
                'ht_home_goals' => valByHeader($r, $map, 'HTHG'),
                'ht_away_goals' => valByHeader($r, $map, 'HTAG'),
                'ht_result' => valByHeader($r, $map, 'HTR'),
                'home_shots' => valByHeader($r, $map, 'HS'),
                'away_shots' => valByHeader($r, $map, 'AS'),
                'home_shots_target' => valByHeader($r, $map, 'HST'),
                'away_shots_target' => valByHeader($r, $map, 'AST'),
                'home_fouls' => valByHeader($r, $map, 'HF'),
                'away_fouls' => valByHeader($r, $map, 'AF'),
                'home_corners' => valByHeader($r, $map, 'HC'),
                'away_corners' => valByHeader($r, $map, 'AC'),
                'home_yellow' => valByHeader($r, $map, 'HY'),
                'away_yellow' => valByHeader($r, $map, 'AY'),
                'home_red' => valByHeader($r, $map, 'HR'),
                'away_red' => valByHeader($r, $map, 'AR'),
                'home_elo' => null,
                'away_elo' => null,
            ];

            if (executeMatchInsert($stmt, $row)) {
                $imported++;
            } else {
                $skipped++;
            }

            if ($imported > 0 && $imported % 5000 === 0) {
                $pdo->commit();
                echo "XLSX importados: $imported\n";
                $pdo->beginTransaction();
            }
        }
    }

    $pdo->commit();
    $zip->close();

    $pdo->prepare('INSERT INTO import_logs (source_file, imported_rows, skipped_rows) VALUES (?, ?, ?)')
        ->execute(['all-euro-data-2025-2026.xlsx', $imported, $skipped]);

    return [$imported, $skipped];
}

echo "=== Importador StatBet ===\n";
echo "BD: statbet\n\n";

[$csvImported, $csvSkipped] = importCsvMatches($pdo, $stmt, $csvPath, $minHistoricalDate);
echo "Matches.csv -> importados: $csvImported | saltados: $csvSkipped\n\n";

[$xlsxImported, $xlsxSkipped] = importXlsxMatches($pdo, $stmt, $xlsxPath);
echo "all-euro-data-2025-2026.xlsx -> importados: $xlsxImported | saltados: $xlsxSkipped\n\n";

$total = $pdo->query('SELECT COUNT(*) AS total FROM matches')->fetch()['total'];
echo "TOTAL EN BD matches: $total\n";
echo "Importación terminada.\n";

