<?php
// database/update_matches.php
// Script para actualizar partidos desde football-data.org (plan gratuito).

ini_set('memory_limit', '256M');
set_time_limit(300);

require_once __DIR__ . '/../backend/db.php';

const FD_API_KEY = 'bf55d16dbdac44e2a13983a2f09e49ad';
const FD_BASE_URL = 'https://api.football-data.org/v4/';

const COMPETITIONS = [
    'PL'  => 'E0',
    'ELC' => 'E1',
    'PD'  => 'SP1',
    'SD'  => 'SP2',
    'SA'  => 'I1',
    'BL1' => 'D1',
    'FL1' => 'F1',
    'DED' => 'N1',
    'PPL' => 'P1',
];

const TEAM_NAME_MAP = [
    // España SP1
    'Athletic Club'                     => 'Ath Bilbao',
    'Club Atlético de Madrid'           => 'Ath Madrid',
    'FC Barcelona'                      => 'Barcelona',
    'Real Betis Balompié'               => 'Betis',
    'Cádiz CF'                          => 'Cadiz',
    'RC Celta de Vigo'                  => 'Celta',
    'Elche CF'                          => 'Elche',
    'RCD Espanyol de Barcelona'         => 'Espanol',
    'Getafe CF'                         => 'Getafe',
    'Girona FC'                         => 'Girona',
    'Granada CF'                        => 'Granada',
    'UD Las Palmas'                     => 'Las Palmas',
    'CD Leganés'                        => 'Leganes',
    'Levante UD'                        => 'Levante',
    'RCD Mallorca'                      => 'Mallorca',
    'CA Osasuna'                        => 'Osasuna',
    'Real Oviedo'                       => 'Oviedo',
    'Real Madrid CF'                    => 'Real Madrid',
    'Sevilla FC'                        => 'Sevilla',
    'Real Sociedad de Fútbol'           => 'Sociedad',
    'Valencia CF'                       => 'Valencia',
    'Real Valladolid CF'                => 'Valladolid',
    'Rayo Vallecano de Madrid'          => 'Vallecano',
    'Villarreal CF'                     => 'Villarreal',
    'Deportivo Alavés'                  => 'Alaves',
    'UD Almería'                        => 'Almeria',

    // España SP2
    'SD Eibar'                          => 'Eibar',
    'SD Huesca'                         => 'Huesca',
    'Real Zaragoza'                     => 'Zaragoza',
    'Cultural y Deportiva Leonesa'      => 'Cultural Leonesa',
    'CD Mirandés'                       => 'Mirandes',
    'Sporting de Gijón'                 => 'Sp Gijon',
    'Real Sporting de Gijón'            => 'Sp Gijon',
    'CD Tenerife'                       => 'Tenerife',
    'Villarreal CF B'                   => 'Villarreal B',
    'Real Sociedad B'                   => 'Sociedad B',
    'Burgos CF'                         => 'Burgos',
    'RC Deportivo de La Coruña'         => 'La Coruna',
    'FC Cartagena'                      => 'Cartagena',
    'CD Castellón'                      => 'Castellon',
    'AD Ceuta FC'                       => 'Ceuta',
    'CD Córdoba'                        => 'Cordoba',
    'FC Andorra'                        => 'Andorra',
    'Albacete Balompié'                 => 'Albacete',
    'CD Eldense'                        => 'Eldense',
    'Racing Club de Ferrol'             => 'Ferrol',
    'CD Fuenlabrada'                    => 'Fuenlabrada',
    'SD Ibiza'                          => 'Ibiza',
    'CD Lugo'                           => 'Lugo',
    'Málaga CF'                         => 'Malaga',
    'SD Ponferradina'                   => 'Ponferradina',
    'Racing de Santander'               => 'Santander',
    'Amorebieta'                        => 'Amorebieta',
    'Alcorcón'                          => 'Alcorcon',

    // Inglaterra E0
    'Arsenal FC'                        => 'Arsenal',
    'Aston Villa FC'                    => 'Aston Villa',
    'AFC Bournemouth'                   => 'Bournemouth',
    'Brentford FC'                      => 'Brentford',
    'Brighton & Hove Albion FC'         => 'Brighton',
    'Burnley FC'                        => 'Burnley',
    'Chelsea FC'                        => 'Chelsea',
    'Crystal Palace FC'                 => 'Crystal Palace',
    'Everton FC'                        => 'Everton',
    'Fulham FC'                         => 'Fulham',
    'Ipswich Town FC'                   => 'Ipswich',
    'Leeds United FC'                   => 'Leeds',
    'Leicester City FC'                 => 'Leicester',
    'Liverpool FC'                      => 'Liverpool',
    'Luton Town FC'                     => 'Luton',
    'Manchester City FC'                => 'Man City',
    'Manchester United FC'              => 'Man United',
    'Newcastle United FC'               => 'Newcastle',
    'Norwich City FC'                   => 'Norwich',
    'Nottingham Forest FC'              => 'Nott\'m Forest',
    'Sheffield United FC'               => 'Sheffield United',
    'Southampton FC'                    => 'Southampton',
    'Sunderland AFC'                    => 'Sunderland',
    'Tottenham Hotspur FC'              => 'Tottenham',
    'Watford FC'                        => 'Watford',
    'West Ham United FC'                => 'West Ham',
    'Wolverhampton Wanderers FC'        => 'Wolves',

    // Inglaterra E1
    'Barnsley FC'                       => 'Barnsley',
    'Birmingham City FC'                => 'Birmingham',
    'Blackburn Rovers FC'               => 'Blackburn',
    'Blackpool FC'                      => 'Blackpool',
    'Bristol City FC'                   => 'Bristol City',
    'Cardiff City FC'                   => 'Cardiff',
    'Charlton Athletic FC'              => 'Charlton',
    'Coventry City FC'                  => 'Coventry',
    'Derby County FC'                   => 'Derby',
    'Huddersfield Town AFC'             => 'Huddersfield',
    'Hull City AFC'                     => 'Hull',
    'Middlesbrough FC'                  => 'Middlesbrough',
    'Millwall FC'                       => 'Millwall',
    'Oxford United FC'                  => 'Oxford',
    'Peterborough United FC'            => 'Peterboro',
    'Plymouth Argyle FC'                => 'Plymouth',
    'Portsmouth FC'                     => 'Portsmouth',
    'Preston North End FC'              => 'Preston',
    'Queens Park Rangers FC'            => 'QPR',
    'Reading FC'                        => 'Reading',
    'Rotherham United FC'               => 'Rotherham',
    'Sheffield Wednesday FC'            => 'Sheffield Weds',
    'Stoke City FC'                     => 'Stoke',
    'Swansea City AFC'                  => 'Swansea',
    'West Bromwich Albion FC'           => 'West Brom',
    'Wigan Athletic FC'                 => 'Wigan',
    'Wrexham AFC'                       => 'Wrexham',

    // Italia I1
    'Atalanta BC'                       => 'Atalanta',
    'Bologna FC 1909'                   => 'Bologna',
    'Cagliari Calcio'                   => 'Cagliari',
    'Como 1907'                         => 'Como',
    'US Cremonese'                      => 'Cremonese',
    'ACF Fiorentina'                    => 'Fiorentina',
    'Frosinone Calcio'                  => 'Frosinone',
    'Genoa CFC'                         => 'Genoa',
    'FC Internazionale Milano'          => 'Inter',
    'Juventus FC'                       => 'Juventus',
    'SS Lazio'                          => 'Lazio',
    'US Lecce'                          => 'Lecce',
    'AC Milan'                          => 'Milan',
    'AC Monza'                          => 'Monza',
    'SSC Napoli'                        => 'Napoli',
    'Parma Calcio 1913'                 => 'Parma',
    'AS Roma'                           => 'Roma',
    'US Salernitana 1919'               => 'Salernitana',
    'UC Sampdoria'                      => 'Sampdoria',
    'US Sassuolo Calcio'                => 'Sassuolo',
    'Spezia Calcio'                     => 'Spezia',
    'Torino FC'                         => 'Torino',
    'Udinese Calcio'                    => 'Udinese',
    'Venezia FC'                        => 'Venezia',
    'Hellas Verona FC'                  => 'Verona',
    'Empoli FC'                         => 'Empoli',
    'AC Pisa 1909'                      => 'Pisa',

    // Alemania D1
    'FC Augsburg'                       => 'Augsburg',
    'FC Bayern München'                 => 'Bayern Munich',
    'DSC Arminia Bielefeld'             => 'Bielefeld',
    'VfL Bochum 1848'                   => 'Bochum',
    'SV Darmstadt 98'                   => 'Darmstadt',
    'Borussia Dortmund'                 => 'Dortmund',
    'Eintracht Frankfurt'               => 'Ein Frankfurt',
    '1. FC Köln'                        => 'FC Koln',
    'Sport-Club Freiburg'               => 'Freiburg',
    'SpVgg Greuther Fürth'              => 'Greuther Furth',
    'Hamburger SV'                      => 'Hamburg',
    '1. FC Heidenheim 1846'             => 'Heidenheim',
    'Hertha BSC'                        => 'Hertha',
    'TSG 1899 Hoffenheim'               => 'Hoffenheim',
    'Holstein Kiel'                     => 'Holstein Kiel',
    'Bayer 04 Leverkusen'               => 'Leverkusen',
    'Borussia Mönchengladbach'          => 'M\'gladbach',
    '1. FSV Mainz 05'                   => 'Mainz',
    'RB Leipzig'                        => 'RB Leipzig',
    'FC Schalke 04'                     => 'Schalke 04',
    'FC St. Pauli 1910'                 => 'St Pauli',
    'VfB Stuttgart'                     => 'Stuttgart',
    '1. FC Union Berlin'                => 'Union Berlin',
    'SV Werder Bremen'                  => 'Werder Bremen',
    'VfL Wolfsburg'                     => 'Wolfsburg',

    // Francia F1
    'Ajaccio'                           => 'Ajaccio',
    'Angers SCO'                        => 'Angers',
    'AJ Auxerre'                        => 'Auxerre',
    'FC Girondins de Bordeaux'          => 'Bordeaux',
    'Stade Brestois 29'                 => 'Brest',
    'Clermont Foot 63'                  => 'Clermont',
    'Le Havre AC'                       => 'Le Havre',
    'RC Lens'                           => 'Lens',
    'LOSC Lille'                        => 'Lille',
    'FC Lorient'                        => 'Lorient',
    'Olympique Lyonnais'                => 'Lyon',
    'Olympique de Marseille'            => 'Marseille',
    'FC Metz'                           => 'Metz',
    'AS Monaco FC'                      => 'Monaco',
    'Montpellier HSC'                   => 'Montpellier',
    'FC Nantes'                         => 'Nantes',
    'OGC Nice'                          => 'Nice',
    'Paris FC'                          => 'Paris FC',
    'Paris Saint-Germain FC'            => 'Paris SG',
    'Stade de Reims'                    => 'Reims',
    'Stade Rennais FC 1901'             => 'Rennes',
    'AS Saint-Étienne'                  => 'St Etienne',
    'RC Strasbourg Alsace'              => 'Strasbourg',
    'Toulouse FC'                       => 'Toulouse',
    'ES Troyes AC'                      => 'Troyes',

    // Países Bajos N1
    'AFC Ajax'                          => 'Ajax',
    'Almere City FC'                    => 'Almere City',
    'AZ Alkmaar'                        => 'AZ Alkmaar',
    'SBV Excelsior'                     => 'Excelsior',
    'FC Emmen'                          => 'FC Emmen',
    'Feyenoord Rotterdam'               => 'Feyenoord',
    'Fortuna Sittard'                   => 'For Sittard',
    'Go Ahead Eagles'                   => 'Go Ahead Eagles',
    'FC Groningen'                      => 'Groningen',
    'SC Heerenveen'                     => 'Heerenveen',
    'Heracles Almelo'                   => 'Heracles',
    'NAC Breda'                         => 'NAC Breda',
    'NEC Nijmegen'                      => 'Nijmegen',
    'PSV Eindhoven'                     => 'PSV Eindhoven',
    'Sparta Rotterdam'                  => 'Sparta Rotterdam',
    'Telstar'                           => 'Telstar',
    'FC Twente'                         => 'Twente',
    'FC Utrecht'                        => 'Utrecht',
    'Vitesse'                           => 'Vitesse',
    'SC Volendam'                       => 'Volendam',
    'RKC Waalwijk'                      => 'Waalwijk',
    'Willem II'                         => 'Willem II',
    'PEC Zwolle'                        => 'Zwolle',
    'SV Cambuur'                        => 'Cambuur',

    // Portugal P1
    'SL Benfica'                        => 'Benfica',
    'SC Braga'                          => 'Sp Braga',
    'Sporting CP'                       => 'Sp Lisbon',
    'FC Porto'                          => 'Porto',
    'Vitória SC'                        => 'Vitoria',
    'Casa Pia AC'                       => 'Casa Pia',
    'GD Estoril Praia'                  => 'Estoril',
    'FC Famalicão'                      => 'Famalicao',
    'CD Nacional'                       => 'Nacional',
    'Moreirense FC'                     => 'Moreirense',
    'FC Arouca'                         => 'Arouca',
    'AVS Futebol SAD'                   => 'AVS',
    'CF Belenenses'                     => 'Belenenses',
    'Boavista FC'                       => 'Boavista',
    'GD Chaves'                         => 'Chaves',
    'CD Estrela da Amadora'             => 'Estrela',
    'Gil Vicente FC'                    => 'Gil Vicente',
    'Vitória SC Guimarães'              => 'Guimaraes',
    'CS Marítimo'                       => 'Maritimo',
    'FC Paços de Ferreira'              => 'Pacos Ferreira',
    'Portimonense SC'                   => 'Portimonense',
    'Rio Ave FC'                        => 'Rio Ave',
    'Santa Clara'                       => 'Santa Clara',
    'CD Tondela'                        => 'Tondela',
    'FC Vizela'                         => 'Vizela',
    'CD Alverca'                        => 'Alverca',
];

function normalizeTeamName(string $name): string {
    return TEAM_NAME_MAP[$name] ?? $name;
}

function fdRequest(string $endpoint): ?array {
    $url = FD_BASE_URL . $endpoint;
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['X-Auth-Token: ' . FD_API_KEY]);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);
    $result = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);
    curl_close($ch);
    if ($error) { log_msg("cURL error: $error"); return null; }
    if ($httpCode === 429) {
        log_msg("Limite alcanzado, esperando 60s...");
        sleep(60);
        return fdRequest($endpoint);
    }
    if ($httpCode !== 200) { log_msg("HTTP $httpCode para $endpoint"); return null; }
    return json_decode($result, true);
}

function log_msg(string $msg): void {
    $line = '[' . date('Y-m-d H:i:s') . '] ' . $msg . PHP_EOL;
    echo $line;
    file_put_contents(__DIR__ . '/update_log.txt', $line, FILE_APPEND);
}

function seasonFromDate(string $date): string {
    $dt = new DateTime($date);
    $year = (int)$dt->format('Y');
    $month = (int)$dt->format('n');
    $start = $month >= 7 ? $year : $year - 1;
    return $start . '/' . substr((string)($start + 1), -2);
}

function ftResult(int $home, int $away): string {
    if ($home > $away) return 'H';
    if ($home < $away) return 'A';
    return 'D';
}

log_msg("=== Inicio actualizacion StatBet (football-data.org) ===");

$pdo = getStatBetPDO();

$sql = "INSERT INTO matches (
    source_file, division_code, season, match_date, match_time,
    home_team, away_team,
    ft_home_goals, ft_away_goals, ft_result,
    ht_home_goals, ht_away_goals, ht_result,
    home_shots, away_shots, home_shots_target, away_shots_target,
    home_fouls, away_fouls, home_corners, away_corners,
    home_yellow, away_yellow, home_red, away_red, home_elo, away_elo
) VALUES (
    :source_file, :division_code, :season, :match_date, :match_time,
    :home_team, :away_team,
    :ft_home_goals, :ft_away_goals, :ft_result,
    :ht_home_goals, :ht_away_goals, :ht_result,
    :home_shots, :away_shots, :home_shots_target, :away_shots_target,
    :home_fouls, :away_fouls, :home_corners, :away_corners,
    :home_yellow, :away_yellow, :home_red, :away_red, :home_elo, :away_elo
)
ON DUPLICATE KEY UPDATE
    ft_home_goals = VALUES(ft_home_goals),
    ft_away_goals = VALUES(ft_away_goals),
    ft_result = VALUES(ft_result),
    ht_home_goals = VALUES(ht_home_goals),
    ht_away_goals = VALUES(ht_away_goals),
    ht_result = VALUES(ht_result),
    home_yellow = VALUES(home_yellow),
    away_yellow = VALUES(away_yellow),
    home_red = VALUES(home_red),
    away_red = VALUES(away_red),
    source_file = VALUES(source_file)";

$stmt = $pdo->prepare($sql);

$from = date('Y-m-d', strtotime('-7 days'));
$to   = date('Y-m-d');
$totalInserted = 0;
$requestsUsed  = 0;

foreach (COMPETITIONS as $compCode => $divisionCode) {
    log_msg("Procesando $compCode -> $divisionCode...");
    $endpoint = "competitions/{$compCode}/matches?dateFrom={$from}&dateTo={$to}&status=FINISHED";
    $data = fdRequest($endpoint);
    $requestsUsed++;

    if (!$data || empty($data['matches'])) {
        log_msg("  Sin partidos para $compCode");
        sleep(7);
        continue;
    }

    $inserted = 0; $skipped = 0;

    foreach ($data['matches'] as $match) {
        $homeGoals = $match['score']['fullTime']['home'] ?? null;
        $awayGoals = $match['score']['fullTime']['away'] ?? null;
        if ($homeGoals === null || $awayGoals === null) { $skipped++; continue; }

        $htHome    = $match['score']['halfTime']['home'] ?? null;
        $htAway    = $match['score']['halfTime']['away'] ?? null;
        $dateRaw   = $match['utcDate'] ?? '';
        $matchDate = $dateRaw ? date('Y-m-d', strtotime($dateRaw)) : null;
        $matchTime = $dateRaw ? date('H:i:s', strtotime($dateRaw)) : null;
        if (!$matchDate) { $skipped++; continue; }

        $homeName = normalizeTeamName($match['homeTeam']['name']);
        $awayName = normalizeTeamName($match['awayTeam']['name']);

        $homeYellow = 0; $awayYellow = 0; $homeRed = 0; $awayRed = 0;
        if (!empty($match['bookings'])) {
            foreach ($match['bookings'] as $booking) {
                $team = $booking['team']['id'] ?? 0;
                $homeId = $match['homeTeam']['id'] ?? -1;
                $card = strtoupper($booking['card'] ?? '');
                $isHome = $team === $homeId;
                if ($card === 'YELLOW') { $isHome ? $homeYellow++ : $awayYellow++; }
                elseif (in_array($card, ['RED', 'YELLOW_RED'])) { $isHome ? $homeRed++ : $awayRed++; }
            }
        }

        try {
            $stmt->execute([
                ':source_file'       => 'football_data_org',
                ':division_code'     => $divisionCode,
                ':season'            => seasonFromDate($matchDate),
                ':match_date'        => $matchDate,
                ':match_time'        => $matchTime,
                ':home_team'         => $homeName,
                ':away_team'         => $awayName,
                ':ft_home_goals'     => (int)$homeGoals,
                ':ft_away_goals'     => (int)$awayGoals,
                ':ft_result'         => ftResult((int)$homeGoals, (int)$awayGoals),
                ':ht_home_goals'     => $htHome !== null ? (int)$htHome : null,
                ':ht_away_goals'     => $htAway !== null ? (int)$htAway : null,
                ':ht_result'         => ($htHome !== null && $htAway !== null) ? ftResult((int)$htHome, (int)$htAway) : null,
                ':home_shots'        => null, ':away_shots'        => null,
                ':home_shots_target' => null, ':away_shots_target' => null,
                ':home_fouls'        => null, ':away_fouls'        => null,
                ':home_corners'      => null, ':away_corners'      => null,
                ':home_yellow'       => $homeYellow ?: null,
                ':away_yellow'       => $awayYellow ?: null,
                ':home_red'          => $homeRed ?: null,
                ':away_red'          => $awayRed ?: null,
                ':home_elo'          => null, ':away_elo' => null,
            ]);
            $inserted++;
        } catch (PDOException $e) {
            if ($e->getCode() == 23000) { $skipped++; }
            else { log_msg("Error BD: " . $e->getMessage()); }
        }
    }

    $totalInserted += $inserted;
    log_msg("  $compCode -> insertados: $inserted | ignorados: $skipped");
    sleep(7);
}

log_msg("=== Fin | Total: $totalInserted | Peticiones: $requestsUsed ===");
?>