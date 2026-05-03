<?php
header('Content-Type: application/json; charset=utf-8');

$DATA_DIR = __DIR__ . '/../data/kaggle';

function respond($data, $code = 200) {
    http_response_code($code);
    echo json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    exit;
}

function get_param($name, $default = '') {
    return isset($_GET[$name]) ? trim((string)$_GET[$name]) : $default;
}

function csv_path($filename) {
    global $DATA_DIR;
    return $DATA_DIR . '/' . $filename;
}

function open_csv($filename) {
    $path = csv_path($filename);
    if (!file_exists($path)) {
        respond([
            'error' => true,
            'message' => "No encuentro $filename. Coloca los CSV en /data/kaggle/"
        ], 500);
    }

    $handle = fopen($path, 'r');
    if (!$handle) {
        respond(['error' => true, 'message' => "No se puede abrir $filename"], 500);
    }

    $headers = fgetcsv($handle);
    return [$handle, $headers];
}

function assoc_row($headers, $row) {
    if (count($row) < count($headers)) {
        $row = array_pad($row, count($headers), '');
    }
    return array_combine($headers, array_slice($row, 0, count($headers)));
}

function normalize_text($value) {
    $value = strtolower(trim((string)$value));
    $value = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $value);
    $value = preg_replace('/[^a-z0-9]+/', ' ', $value);
    $value = preg_replace('/\s+/', ' ', $value);
    return trim($value);
}

function display_club_name($name) {
    $name = trim($name);
    $replacements = [
        ' Football Club' => '',
        ' Fútbol Club' => '',
        ' Club de Fútbol' => '',
        ' Fußball' => '',
        ' S.p.A.' => '',
        ' S.A.D.' => '',
        ' Association Football Club' => '',
    ];
    return trim(str_replace(array_keys($replacements), array_values($replacements), $name));
}

function display_competition_name($name, $id = '') {
    $map = [
        'GB1' => 'Premier League',
        'ES1' => 'LaLiga',
        'IT1' => 'Serie A',
        'CL'  => 'Champions League',
        'EL'  => 'Europa League',
        'UCOL' => 'Conference League',
        'FAC' => 'FA Cup',
        'CGB' => 'EFL Cup',
        'CDR' => 'Copa del Rey',
        'CIT' => 'Coppa Italia',
        'SUC' => 'Supercopa',
        'SCI' => 'Supercoppa Italiana',
        'GBCS' => 'Community Shield'
    ];
    if ($id && isset($map[$id])) return $map[$id];

    $pretty = str_replace('-', ' ', (string)$name);
    $pretty = ucwords($pretty);
    return $pretty ?: $id;
}

function load_competitions_map() {
    static $map = null;
    if ($map !== null) return $map;

    $map = [];
    [$handle, $headers] = open_csv('competitions.csv');
    while (($row = fgetcsv($handle)) !== false) {
        $r = assoc_row($headers, $row);
        $id = $r['competition_id'];
        $map[$id] = [
            'id' => $id,
            'name' => display_competition_name($r['name'], $id),
            'raw_name' => $r['name'],
            'country_name' => $r['country_name'],
            'type' => $r['type'],
            'sub_type' => $r['sub_type']
        ];
    }
    fclose($handle);
    return $map;
}

function find_club_by_id($clubId) {
    if (!$clubId) return null;

    [$handle, $headers] = open_csv('clubs.csv');
    while (($row = fgetcsv($handle)) !== false) {
        $r = assoc_row($headers, $row);
        if ((string)$r['club_id'] === (string)$clubId) {
            fclose($handle);
            return $r;
        }
    }
    fclose($handle);
    return null;
}

function find_club_by_name($query, $competition = '') {
    $needle = normalize_text($query);
    if (!$needle) return null;

    $best = null;
    $bestScore = -1;

    [$handle, $headers] = open_csv('clubs.csv');
    while (($row = fgetcsv($handle)) !== false) {
        $r = assoc_row($headers, $row);
        if ($competition && $r['domestic_competition_id'] !== $competition) continue;

        $clubName = normalize_text($r['name']);
        $clubCode = normalize_text($r['club_code']);
        $display = normalize_text(display_club_name($r['name']));

        $score = 0;
        if ($display === $needle || $clubName === $needle || $clubCode === $needle) $score = 100;
        elseif (strpos($display, $needle) !== false) $score = 80;
        elseif (strpos($clubName, $needle) !== false) $score = 70;
        elseif (strpos($needle, $display) !== false && strlen($display) > 3) $score = 65;
        elseif (strpos($clubCode, $needle) !== false) $score = 50;

        if ($score > $bestScore) {
            $bestScore = $score;
            $best = $r;
        }
    }
    fclose($handle);

    return $bestScore > 0 ? $best : null;
}

function club_to_api($club, $leagueId = '') {
    $leagueByCompetition = [
        'ES1' => 'la_liga',
        'GB1' => 'premier',
        'IT1' => 'serie_a'
    ];
    $countryByCompetition = [
        'ES1' => 'ES',
        'GB1' => 'GB',
        'IT1' => 'IT'
    ];

    $comp = $club['domestic_competition_id'];
    return [
        'id' => 'kg_' . $club['club_id'],
        'club_id' => $club['club_id'],
        'name' => display_club_name($club['name']),
        'full_name' => $club['name'],
        'leagueId' => $leagueId ?: ($leagueByCompetition[$comp] ?? $comp),
        'competition_id' => $comp,
        'countryCode' => $countryByCompetition[$comp] ?? '',
        'icon' => ''
    ];
}

$action = get_param('action', 'health');

if ($action === 'health') {
    $files = [
        'competitions.csv', 'clubs.csv', 'games.csv', 'club_games.csv',
        'players.csv', 'appearances.csv', 'game_events.csv', 'game_lineups.csv',
        'player_valuations.csv', 'transfers.csv', 'countries.csv', 'national_teams.csv'
    ];

    $result = [];
    foreach ($files as $file) {
        $path = csv_path($file);
        $result[] = [
            'file' => $file,
            'exists' => file_exists($path),
            'size_mb' => file_exists($path) ? round(filesize($path) / 1024 / 1024, 2) : null
        ];
    }

    respond([
        'ok' => true,
        'message' => 'Endpoint Kaggle funcionando.',
        'data_dir' => $GLOBALS['DATA_DIR'],
        'files' => $result
    ]);
}

if ($action === 'clubs') {
    $competition = get_param('competition', 'GB1');
    $leagueId = get_param('league_id', '');

    [$handle, $headers] = open_csv('clubs.csv');
    $rows = [];

    while (($row = fgetcsv($handle)) !== false) {
        $r = assoc_row($headers, $row);
        if ($competition && $r['domestic_competition_id'] !== $competition) continue;
        $rows[] = club_to_api($r, $leagueId);
    }
    fclose($handle);

    usort($rows, fn($a, $b) => strcasecmp($a['name'], $b['name']));
    respond(['competition' => $competition, 'count' => count($rows), 'results' => $rows]);
}

if ($action === 'clubById') {
    $clubId = get_param('club_id');
    $club = find_club_by_id($clubId);
    if (!$club) respond(['error' => true, 'message' => 'Club no encontrado'], 404);
    respond(club_to_api($club));
}

if ($action === 'searchClub') {
    $query = get_param('q');
    $competition = get_param('competition', '');
    $club = find_club_by_name($query, $competition);
    if (!$club) respond(['error' => true, 'message' => 'Club no encontrado'], 404);
    respond(club_to_api($club));
}

if ($action === 'displayTeamStats') {
    $teamId = get_param('team_id', '');
    $clubId = str_starts_with($teamId, 'kg_') ? substr($teamId, 3) : get_param('club_id', '');
    $teamName = get_param('team_name', '');
    $domesticCompetition = get_param('domestic_competition', '');
    $context = get_param('context', 'all');
    $competitionFilter = get_param('competition', 'all');
    $seasonParam = get_param('season', 'latest');

    $club = $clubId ? find_club_by_id($clubId) : find_club_by_name($teamName ?: $teamId, $domesticCompetition);
    if (!$club) {
        respond(['error' => true, 'message' => 'No he podido resolver el equipo en clubs.csv'], 404);
    }
    $clubId = (string)$club['club_id'];

    $competitionsMap = load_competitions_map();
    $matchesRaw = [];
    $latestSeason = null;

    [$handle, $headers] = open_csv('games.csv');
    while (($row = fgetcsv($handle)) !== false) {
        $r = assoc_row($headers, $row);
        $isHome = (string)$r['home_club_id'] === $clubId;
        $isAway = (string)$r['away_club_id'] === $clubId;
        if (!$isHome && !$isAway) continue;
        if ($r['home_club_goals'] === '' || $r['away_club_goals'] === '') continue;

        if ($latestSeason === null || (int)$r['season'] > (int)$latestSeason) {
            $latestSeason = $r['season'];
        }
        $matchesRaw[] = [$r, $isHome, $isAway];
    }
    fclose($handle);

    $season = ($seasonParam === 'latest' || $seasonParam === '') ? $latestSeason : $seasonParam;
    $matches = [];
    $competitionNames = [];
    $matchIds = [];

    foreach ($matchesRaw as [$r, $isHome, $isAway]) {
        if ($season && (string)$r['season'] !== (string)$season) continue;
        if ($context === 'home' && !$isHome) continue;
        if ($context === 'away' && !$isAway) continue;

        $compId = $r['competition_id'];
        $compName = isset($competitionsMap[$compId]) ? $competitionsMap[$compId]['name'] : display_competition_name('', $compId);
        $competitionNames[$compName] = true;

        if ($competitionFilter && $competitionFilter !== 'all') {
            $normFilter = normalize_text($competitionFilter);
            if ($normFilter !== normalize_text($compName) && $normFilter !== normalize_text($compId)) continue;
        }

        $gf = (int)($isHome ? $r['home_club_goals'] : $r['away_club_goals']);
        $ga = (int)($isHome ? $r['away_club_goals'] : $r['home_club_goals']);
        $opponent = $isHome ? $r['away_club_name'] : $r['home_club_name'];
        $dateObj = DateTime::createFromFormat('Y-m-d', $r['date']);
        $dateDisplay = $dateObj ? $dateObj->format('d/m/Y') : $r['date'];

        $match = [
            'game_id' => $r['game_id'],
            'date' => $dateDisplay,
            'rawDate' => $r['date'],
            'competition' => $compName,
            'competition_id' => $compId,
            'opponent' => display_club_name($opponent),
            'homeAway' => $isHome ? 'home' : 'away',
            'score' => $r['home_club_goals'] . '-' . $r['away_club_goals'],
            'goalsFor' => $gf,
            'goalsAgainst' => $ga,
            'cornersFor' => null,
            'cornersAgainst' => null,
            'yellowCards' => null,
            'redCards' => null,
            'fouls' => null
        ];
        $matches[] = $match;
        $matchIds[$r['game_id']] = count($matches) - 1;
    }

    $includeCards = get_param('include_cards', '0') === '1';
    if ($includeCards && count($matchIds) > 0) {
        [$handle, $headers] = open_csv('game_events.csv');
        while (($row = fgetcsv($handle)) !== false) {
            $r = assoc_row($headers, $row);
            if (!isset($matchIds[$r['game_id']])) continue;
            if ((string)$r['club_id'] !== $clubId) continue;
            if ($r['type'] !== 'Cards') continue;

            $idx = $matchIds[$r['game_id']];
            if ($matches[$idx]['yellowCards'] === null) $matches[$idx]['yellowCards'] = 0;
            if ($matches[$idx]['redCards'] === null) $matches[$idx]['redCards'] = 0;
            $desc = strtolower($r['description']);
            if (strpos($desc, 'yellow') !== false) $matches[$idx]['yellowCards']++;
            if (strpos($desc, 'red') !== false && strpos($desc, 'yellow') === false) $matches[$idx]['redCards']++;
        }
        fclose($handle);
    }

    usort($matches, fn($a, $b) => strcmp($b['rawDate'], $a['rawDate']));

    $stats = [
        'matches' => count($matches),
        'goalsFor' => array_sum(array_column($matches, 'goalsFor')),
        'goalsAgainst' => array_sum(array_column($matches, 'goalsAgainst')),
        'cornersFor' => null,
        'cornersAgainst' => null,
        'yellowCards' => $includeCards ? array_sum(array_column($matches, 'yellowCards')) : null,
        'redCards' => $includeCards ? array_sum(array_column($matches, 'redCards')) : null,
        'fouls' => null
    ];

    $competitions = array_keys($competitionNames);
    sort($competitions, SORT_NATURAL | SORT_FLAG_CASE);

    respond([
        'teamName' => display_club_name($club['name']),
        'club_id' => $clubId,
        'season' => $season,
        'sourceStats' => $stats,
        'recentMatches' => $matches,
        'updatedAt' => date('c'),
        'competitions' => $competitions,
        'unavailableMetrics' => ['corners', 'fouls'],
        'note' => 'Transfermarkt/Kaggle no incluye córners ni faltas totales por partido.'
    ]);
}

respond(['error' => true, 'message' => 'Acción no reconocida: ' . $action], 400);
