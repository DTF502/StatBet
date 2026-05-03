<?php
// backend/import.php
// ─────────────────────────────────────────────────────────────────────────────
// Importa los CSV del dataset de Transfermarkt (Kaggle) a MySQL.
//
// REQUISITOS PREVIOS:
//   1. La base de datos "statbet" debe existir:
//        CREATE DATABASE statbet CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
//   2. El esquema debe estar aplicado (backend/schema.sql).
//   3. Los CSV deben estar en data/kaggle/ (ver README.md).
//   4. Ajusta las credenciales en backend/config.php si es necesario.
//
// USO:
//   Abre en el navegador: http://localhost/StatBet/backend/import.php
//   La primera importación tarda varios minutos (games.csv > 100 MB).
//   Puedes volver a ejecutarlo para actualizar los datos sin problema;
//   usa INSERT IGNORE para no duplicar filas existentes.
// ─────────────────────────────────────────────────────────────────────────────

set_time_limit(0);
ini_set('memory_limit', '512M');
if (ob_get_level()) ob_end_clean();
ini_set('output_buffering', 'off');

header('Content-Type: text/html; charset=UTF-8');
header('X-Accel-Buffering: no');

require_once __DIR__ . '/config.php';

// ── Helpers de salida ────────────────────────────────────────────────────────
function out(string $msg, string $class = 'info'): void {
    $icon = match ($class) {
        'ok'    => '✅',
        'error' => '❌',
        'warn'  => '⚠️',
        'step'  => '⏳',
        default => 'ℹ️',
    };
    echo "<p class='$class'>$icon $msg</p>\n";
    flush();
}

function section(string $title): void {
    echo "<h2>$title</h2>\n";
    flush();
}

// ── Ruta de los CSV ──────────────────────────────────────────────────────────
$DATA_DIR = dirname(__DIR__) . '/data/kaggle';

function csvPath(string $filename): string {
    global $DATA_DIR;
    return $DATA_DIR . '/' . $filename;
}

function openCsv(string $filename): array {
    $path = csvPath($filename);
    if (!file_exists($path)) {
        out("Archivo no encontrado: <strong>$path</strong>. Descarga los CSV del dataset de Kaggle y colócalos en <code>data/kaggle/</code>.", 'error');
        echo '</body></html>';
        exit;
    }
    $handle = fopen($path, 'r');
    if (!$handle) {
        out("No se puede abrir: $path", 'error');
        echo '</body></html>';
        exit;
    }
    $headers = fgetcsv($handle);
    return [$handle, array_map('trim', $headers)];
}

function colIdx(array $headers, string ...$names): array {
    $map = [];
    foreach ($names as $n) {
        $idx = array_search($n, $headers, true);
        if ($idx === false) {
            out("Columna '<strong>$n</strong>' no encontrada en el CSV. Verifica que usas la versión correcta del dataset.", 'error');
            echo '</body></html>';
            exit;
        }
        $map[$n] = $idx;
    }
    return $map;
}

function nullable(string $value): ?string {
    $v = trim($value);
    return $v === '' ? null : $v;
}

function nullableInt(string $value): ?int {
    $v = trim($value);
    return $v === '' ? null : (int)$v;
}

function nullableFloat(string $value): ?float {
    $v = trim($value);
    return $v === '' ? null : (float)$v;
}

function nullableDate(string $value): ?string {
    $v = trim($value);
    if ($v === '' || $v === '0000-00-00') return null;
    return substr($v, 0, 10); // Acepta YYYY-MM-DD o YYYY-MM-DD HH:MM:SS
}

// ── HTML inicial ─────────────────────────────────────────────────────────────
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<title>StatBet – Importación CSV → MySQL</title>
<style>
  body  { font-family: monospace; background:#0f203f; color:#e2e8f0; padding:24px; max-width:860px; }
  h1    { color:#60a5fa; border-bottom:1px solid #1e3a5f; padding-bottom:10px; }
  h2    { color:#93c5fd; margin-top:28px; }
  p     { margin:3px 0; font-size:13px; line-height:1.6; }
  code  { background:#1e2d48; padding:1px 5px; border-radius:3px; }
  .ok     { color:#4ade80; }
  .error  { color:#f87171; font-weight:bold; }
  .warn   { color:#fbbf24; }
  .step   { color:#94a3b8; }
  .info   { color:#64748b; }
  .done   { background:#0d2137; border:1px solid #3b82f6; padding:18px 20px;
            border-radius:8px; margin-top:28px; line-height:1.9; }
  .done a { color:#60a5fa; }
</style>
</head>
<body>
<h1>StatBet – Importación del dataset Transfermarkt (Kaggle) → MySQL</h1>
<?php

// ── Conexión a MySQL ─────────────────────────────────────────────────────────
section('1. Conectando a MySQL');
try {
    $pdo = getDB();
    out('Conexión establecida.', 'ok');
} catch (Exception $e) {
    out('No se pudo conectar a MySQL: ' . htmlspecialchars($e->getMessage()), 'error');
    out('Comprueba las credenciales en <code>backend/config.php</code> y que MySQL esté activo.', 'warn');
    echo '</body></html>';
    exit;
}

// ═══════════════════════════════════════════════════════════════════
// COMPETITIONS
// ═══════════════════════════════════════════════════════════════════
section('2. Importando competitions.csv');

[$fh, $headers] = openCsv('competitions.csv');
$col = colIdx($headers,
    'competition_id', 'name', 'sub_type', 'type',
    'country_id', 'country_name', 'domestic_league_code',
    'confederation', 'url', 'is_major_national_league'
);

$stmt = $pdo->prepare('
    INSERT IGNORE INTO competitions
        (competition_id, name, sub_type, type, country_id, country_name,
         domestic_league_code, confederation, url, is_major_national_league)
    VALUES (?,?,?,?,?,?,?,?,?,?)
');

$count = 0;
while (($row = fgetcsv($fh)) !== false) {
    if (count($row) < 3) continue;
    $compId = nullable($row[$col['competition_id']]);
    if (!$compId) continue;

    $stmt->execute([
        $compId,
        nullable($row[$col['name']]) ?? '',
        nullable($row[$col['sub_type']]),
        nullable($row[$col['type']]),
        nullableInt($row[$col['country_id']]),
        nullable($row[$col['country_name']]),
        nullable($row[$col['domestic_league_code']]),
        nullable($row[$col['confederation']]),
        nullable($row[$col['url']]),
        (int)trim($row[$col['is_major_national_league']]),
    ]);
    $count++;
}
fclose($fh);
out("Competiciones insertadas/ignoradas: <strong>$count</strong>", 'ok');


// ═══════════════════════════════════════════════════════════════════
// CLUBS
// ═══════════════════════════════════════════════════════════════════
section('3. Importando clubs.csv');

[$fh, $headers] = openCsv('clubs.csv');
$col = colIdx($headers,
    'club_id', 'club_code', 'name', 'domestic_competition_id',
    'total_market_value', 'squad_size', 'average_age',
    'foreigners_number', 'foreigners_percentage', 'national_team_players',
    'stadium_name', 'stadium_seats', 'net_transfer_record',
    'coach_name', 'last_season', 'filename', 'url'
);

$stmt = $pdo->prepare('
    INSERT IGNORE INTO clubs
        (club_id, club_code, name, domestic_competition_id,
         total_market_value, squad_size, average_age,
         foreigners_number, foreigners_percentage, national_team_players,
         stadium_name, stadium_seats, net_transfer_record,
         coach_name, last_season, filename, url)
    VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)
');

$count = 0;
while (($row = fgetcsv($fh)) !== false) {
    $clubId = nullableInt($row[$col['club_id']] ?? '');
    if (!$clubId) continue;

    $stmt->execute([
        $clubId,
        nullable($row[$col['club_code']]),
        nullable($row[$col['name']]) ?? '',
        nullable($row[$col['domestic_competition_id']]),
        nullable($row[$col['total_market_value']]),
        nullableInt($row[$col['squad_size']]),
        nullableFloat($row[$col['average_age']]),
        nullableInt($row[$col['foreigners_number']]),
        nullableFloat($row[$col['foreigners_percentage']]),
        nullableInt($row[$col['national_team_players']]),
        nullable($row[$col['stadium_name']]),
        nullableInt($row[$col['stadium_seats']]),
        nullable($row[$col['net_transfer_record']]),
        nullable($row[$col['coach_name']]),
        nullableInt($row[$col['last_season']]),
        nullable($row[$col['filename']]),
        nullable($row[$col['url']]),
    ]);
    $count++;
}
fclose($fh);
out("Clubes insertados/ignorados: <strong>$count</strong>", 'ok');


// ═══════════════════════════════════════════════════════════════════
// GAMES
// ═══════════════════════════════════════════════════════════════════
section('4. Importando games.csv (archivo grande, puede tardar varios minutos…)');

[$fh, $headers] = openCsv('games.csv');
$col = colIdx($headers,
    'game_id', 'competition_id', 'season', 'round', 'date',
    'home_club_id', 'away_club_id',
    'home_club_goals', 'away_club_goals',
    'home_club_position', 'away_club_position',
    'home_club_manager_name', 'away_club_manager_name',
    'stadium', 'attendance', 'referee', 'url',
    'home_club_name', 'away_club_name'
);

$stmt = $pdo->prepare('
    INSERT IGNORE INTO games
        (game_id, competition_id, season, round, date,
         home_club_id, away_club_id,
         home_club_goals, away_club_goals,
         home_club_position, away_club_position,
         home_club_manager_name, away_club_manager_name,
         stadium, attendance, referee, url,
         home_club_name, away_club_name)
    VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)
');

$pdo->beginTransaction();
$count = 0;
while (($row = fgetcsv($fh)) !== false) {
    $gameId = nullableInt($row[$col['game_id']] ?? '');
    if (!$gameId) continue;

    // Solo importamos partidos ya jugados (con resultado)
    $hGoals = trim($row[$col['home_club_goals']]);
    $aGoals = trim($row[$col['away_club_goals']]);
    if ($hGoals === '' || $aGoals === '') continue;

    $stmt->execute([
        $gameId,
        nullable($row[$col['competition_id']]),
        nullableInt($row[$col['season']]),
        nullable($row[$col['round']]),
        nullableDate($row[$col['date']]),
        nullableInt($row[$col['home_club_id']]),
        nullableInt($row[$col['away_club_id']]),
        (int)$hGoals,
        (int)$aGoals,
        nullableInt($row[$col['home_club_position']]),
        nullableInt($row[$col['away_club_position']]),
        nullable($row[$col['home_club_manager_name']]),
        nullable($row[$col['away_club_manager_name']]),
        nullable($row[$col['stadium']]),
        nullableInt($row[$col['attendance']]),
        nullable($row[$col['referee']]),
        nullable($row[$col['url']]),
        nullable($row[$col['home_club_name']]),
        nullable($row[$col['away_club_name']]),
    ]);
    $count++;

    if ($count % 5000 === 0) {
        $pdo->commit();
        $pdo->beginTransaction();
        out("  ⏳ $count partidos procesados…", 'step');
    }
}
$pdo->commit();
fclose($fh);
out("Partidos insertados/ignorados: <strong>$count</strong>", 'ok');


// ═══════════════════════════════════════════════════════════════════
// CLUB_GAMES
// ═══════════════════════════════════════════════════════════════════
section('5. Importando club_games.csv (archivo grande…)');

[$fh, $headers] = openCsv('club_games.csv');
$col = colIdx($headers,
    'game_id', 'club_id', 'own_goals', 'opponent_goals',
    'own_position', 'own_manager_name',
    'opponent_id', 'opponent_manager_name',
    'hosting', 'is_win'
);

$stmt = $pdo->prepare('
    INSERT IGNORE INTO club_games
        (game_id, club_id, own_goals, opponent_goals,
         own_position, own_manager_name,
         opponent_id, opponent_manager_name,
         hosting, is_win)
    VALUES (?,?,?,?,?,?,?,?,?,?)
');

$pdo->beginTransaction();
$count = 0;
while (($row = fgetcsv($fh)) !== false) {
    $gameId = nullableInt($row[$col['game_id']]  ?? '');
    $clubId = nullableInt($row[$col['club_id']]  ?? '');
    if (!$gameId || !$clubId) continue;

    $stmt->execute([
        $gameId,
        $clubId,
        (int)trim($row[$col['own_goals']]),
        (int)trim($row[$col['opponent_goals']]),
        nullableInt($row[$col['own_position']]),
        nullable($row[$col['own_manager_name']]),
        nullableInt($row[$col['opponent_id']]),
        nullable($row[$col['opponent_manager_name']]),
        nullable($row[$col['hosting']]),
        trim($row[$col['is_win']]) === '' ? null : (int)trim($row[$col['is_win']]),
    ]);
    $count++;

    if ($count % 5000 === 0) {
        $pdo->commit();
        $pdo->beginTransaction();
        out("  ⏳ $count filas procesadas…", 'step');
    }
}
$pdo->commit();
fclose($fh);
out("Filas de club_games insertadas/ignoradas: <strong>$count</strong>", 'ok');


// ═══════════════════════════════════════════════════════════════════
// GAME_EVENTS  (solo tipo 'Cards' para tarjetas)
// ═══════════════════════════════════════════════════════════════════
section('6. Importando game_events.csv (solo eventos de tarjetas…)');

[$fh, $headers] = openCsv('game_events.csv');
$col = colIdx($headers,
    'game_event_id', 'date', 'game_id', 'minute',
    'type', 'club_id', 'player_id', 'description',
    'player_in_id', 'player_assist_id'
);

$stmt = $pdo->prepare('
    INSERT IGNORE INTO game_events
        (game_event_id, date, game_id, minute,
         type, club_id, player_id, description,
         player_in_id, player_assist_id)
    VALUES (?,?,?,?,?,?,?,?,?,?)
');

$pdo->beginTransaction();
$count = 0;
$skipped = 0;
while (($row = fgetcsv($fh)) !== false) {
    // Filtramos solo tarjetas para mantener la tabla manejable
    $type = trim($row[$col['type']] ?? '');
    if ($type !== 'Cards') { $skipped++; continue; }

    $eventId = nullable($row[$col['game_event_id']]);
    $gameId  = nullableInt($row[$col['game_id']]);
    if (!$eventId || !$gameId) continue;

    $stmt->execute([
        $eventId,
        nullableDate($row[$col['date']]),
        $gameId,
        nullableInt($row[$col['minute']]),
        $type,
        nullableInt($row[$col['club_id']]),
        nullableInt($row[$col['player_id']]),
        nullable($row[$col['description']]),
        nullableInt($row[$col['player_in_id']]),
        nullableInt($row[$col['player_assist_id']]),
    ]);
    $count++;

    if ($count % 5000 === 0) {
        $pdo->commit();
        $pdo->beginTransaction();
        out("  ⏳ $count eventos de tarjeta insertados…", 'step');
    }
}
$pdo->commit();
fclose($fh);
out("Eventos de tarjeta insertados/ignorados: <strong>$count</strong> (saltados otros tipos: $skipped)", 'ok');


// ═══════════════════════════════════════════════════════════════════
// VERIFICACIÓN FINAL
// ═══════════════════════════════════════════════════════════════════
section('7. Verificación final');

$tables = ['competitions', 'clubs', 'games', 'club_games', 'game_events'];
foreach ($tables as $t) {
    $n = $pdo->query("SELECT COUNT(*) FROM `$t`")->fetchColumn();
    out("<code>$t</code>: <strong>" . number_format($n) . "</strong> filas", 'ok');
}

// Muestra 3 partidos de LaLiga para confirmar que los datos son correctos
$sample = $pdo->query("
    SELECT date, home_club_name, home_club_goals, away_club_goals, away_club_name
    FROM games
    WHERE competition_id = 'ES1'
    ORDER BY date DESC
    LIMIT 3
")->fetchAll();

if ($sample) {
    out('Muestra LaLiga (últimos partidos importados):', 'info');
    foreach ($sample as $r) {
        out("  {$r['date']} — {$r['home_club_name']} {$r['home_club_goals']}–{$r['away_club_goals']} {$r['away_club_name']}", 'step');
    }
}
?>
<div class="done">
  ✅ <strong>Importación completada.</strong><br>
  La base de datos MySQL está lista. Para que la aplicación la use, actualiza
  <code>backend/config.php</code> con tus credenciales MySQL.<br><br>
  📌 Para actualizar los datos en el futuro, descarga el ZIP de Kaggle de nuevo,
  reemplaza los CSV en <code>data/kaggle/</code> y vuelve a ejecutar este script.<br><br>
  🔗 Dataset: <a href="https://www.kaggle.com/datasets/davidcariboo/player-scores" target="_blank">
  Transfermarkt – Football Data from Transfermarkt</a>
</div>
</body>
</html>
