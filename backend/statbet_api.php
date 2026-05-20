<?php
require_once __DIR__ . '/db.php';

header('Content-Type: application/json; charset=utf-8');

try {
    $pdo = getStatBetPDO();
    $action = $_GET['action'] ?? 'health';

    if ($action === 'health') {
        $total = (int)$pdo->query("SELECT COUNT(*) FROM matches")->fetchColumn();

        echo json_encode([
            'ok' => true,
            'message' => 'API local StatBet funcionando',
            'total_matches' => $total
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        exit;
    }

    if ($action === 'teams') {
        $division = $_GET['division'] ?? 'all';

        if ($division !== 'all') {
            $stmt = $pdo->prepare("
                SELECT DISTINCT team_name
                FROM (
                    SELECT home_team AS team_name FROM matches WHERE division_code = ?
                    UNION
                    SELECT away_team AS team_name FROM matches WHERE division_code = ?
                ) t
                ORDER BY team_name
            ");
            $stmt->execute([$division, $division]);
        } else {
            $stmt = $pdo->query("
                SELECT DISTINCT team_name
                FROM (
                    SELECT home_team AS team_name FROM matches
                    UNION
                    SELECT away_team AS team_name FROM matches
                ) t
                ORDER BY team_name
            ");
        }

        echo json_encode([
            'ok' => true,
            'teams' => $stmt->fetchAll()
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        exit;
    }

    if ($action === 'teamStats') {
        $teamParam = trim($_GET['team'] ?? '');
        $context = $_GET['context'] ?? 'all';
        $competition = $_GET['competition'] ?? 'all';

        if ($teamParam === '') {
            throw new RuntimeException('Falta el parámetro team');
        }

        $teamName = resolveTeamName($pdo, $teamParam);

        if (!$teamName) {
            echo json_encode([
                'ok' => false,
                'error' => 'No se ha encontrado el equipo',
                'team_requested' => $teamParam
            ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
            exit;
        }

        $competitions = getTeamCompetitions($pdo, $teamName);

        $where = [];
        $params = [];

        if ($context === 'home') {
            $where[] = "m.home_team = ?";
            $params[] = $teamName;
        } elseif ($context === 'away') {
            $where[] = "m.away_team = ?";
            $params[] = $teamName;
        } else {
            $where[] = "(m.home_team = ? OR m.away_team = ?)";
            $params[] = $teamName;
            $params[] = $teamName;
        }

        if ($competition !== 'all') {
            $where[] = "m.division_code = ?";
            $params[] = $competition;
        }

        $sql = "
            SELECT 
                m.*,
                COALESCE(c.name, m.division_code) AS competition_name
            FROM matches m
            LEFT JOIN competitions c ON c.division_code = m.division_code
            WHERE " . implode(" AND ", $where) . "
            ORDER BY m.match_date DESC, m.id DESC
        ";

        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $rows = $stmt->fetchAll();

        $stats = [
            'matches' => 0,
            'goalsFor' => 0,
            'goalsAgainst' => 0,
            'cornersFor' => 0,
            'cornersAgainst' => 0,
            'yellowCards' => 0,
            'fouls' => 0,
        ];

        $recentMatches = [];

        foreach ($rows as $row) {
            $isHome = $row['home_team'] === $teamName;

            $goalsFor = $isHome ? $row['ft_home_goals'] : $row['ft_away_goals'];
            $goalsAgainst = $isHome ? $row['ft_away_goals'] : $row['ft_home_goals'];

            if ($goalsFor === null || $goalsAgainst === null) {
                continue;
            }

            $cornersFor = $isHome ? $row['home_corners'] : $row['away_corners'];
            $cornersAgainst = $isHome ? $row['away_corners'] : $row['home_corners'];

            $yellowCards = $isHome ? $row['home_yellow'] : $row['away_yellow'];
            $fouls = $isHome ? $row['home_fouls'] : $row['away_fouls'];

            $stats['matches']++;
            $stats['goalsFor'] += (int)$goalsFor;
            $stats['goalsAgainst'] += (int)$goalsAgainst;
            $stats['cornersFor'] += (int)$cornersFor;
            $stats['cornersAgainst'] += (int)$cornersAgainst;
            $stats['yellowCards'] += (int)$yellowCards;
            $stats['fouls'] += (int)$fouls;

            $recentMatches[] = [
                'date' => formatDateSpanish($row['match_date']),
                'competition' => $row['division_code'],
                'competitionName' => $row['competition_name'],
                'opponent' => $isHome ? $row['away_team'] : $row['home_team'],
                'homeAway' => $isHome ? 'home' : 'away',
                'score' => $row['ft_home_goals'] . '-' . $row['ft_away_goals'],
                'goalsFor' => (int)$goalsFor,
                'goalsAgainst' => (int)$goalsAgainst,
                'cornersFor' => (int)$cornersFor,
                'cornersAgainst' => (int)$cornersAgainst,
                'yellowCards' => (int)$yellowCards,
                'fouls' => (int)$fouls,
            ];
        }

        echo json_encode([
            'ok' => true,
            'teamName' => $teamName,
            'sourceStats' => $stats,
            'recentMatches' => $recentMatches,
            'updatedAt' => date('c'),
            'competitions' => $competitions
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        exit;
    }

    throw new RuntimeException('Acción no reconocida: ' . $action);

} catch (Throwable $e) {
    http_response_code(500);

    echo json_encode([
        'ok' => false,
        'error' => $e->getMessage()
    ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
}

function resolveTeamName(PDO $pdo, string $teamParam): ?string {
    $requested = normalizeTeam($teamParam);

    $stmt = $pdo->query("
        SELECT DISTINCT team_name
        FROM (
            SELECT home_team AS team_name FROM matches
            UNION
            SELECT away_team AS team_name FROM matches
        ) t
        ORDER BY team_name
    ");

    $teams = $stmt->fetchAll();

    foreach ($teams as $team) {
        $name = $team['team_name'];
        $normalized = normalizeTeam($name);

        if ($normalized === $requested) {
            return $name;
        }
    }

    foreach ($teams as $team) {
        $name = $team['team_name'];
        $normalized = normalizeTeam($name);

        if (str_contains($normalized, $requested) || str_contains($requested, $normalized)) {
            return $name;
        }
    }

    return null;
}

function getTeamCompetitions(PDO $pdo, string $teamName): array {
    $stmt = $pdo->prepare("
        SELECT DISTINCT division_code
        FROM matches
        WHERE home_team = ? OR away_team = ?
        ORDER BY division_code
    ");

    $stmt->execute([$teamName, $teamName]);

    return array_map(
        fn($row) => $row['division_code'],
        $stmt->fetchAll()
    );
}

function normalizeTeam(string $value): string {
    $value = trim($value);
    $value = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $value);
    $value = strtolower($value);
    $value = preg_replace('/[^a-z0-9]/', '', $value);
    return $value;
}

function formatDateSpanish(string $date): string {
    $timestamp = strtotime($date);

    if (!$timestamp) {
        return $date;
    }

    return date('d/m/Y', $timestamp);
}
