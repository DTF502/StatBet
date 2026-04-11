<?php
// backend/api.php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");
header("Content-Type: application/json; charset=UTF-8");

require_once 'config.php';

// Si es una petición OPTIONS (Preflight), terminamos aquí
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

$action = isset($_GET['action']) ? $_GET['action'] : '';

function makeApiRequest($endpoint) {
    $url = API_FOOTBALL_BASE_URL . $endpoint;
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        "x-apisports-key: " . API_FOOTBALL_KEY
    ]);
    
    // Ignorar verificación SSL local, útil en XAMPP para no dar error de certificado
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    
    $result = curl_exec($ch);
    
    if(curl_errno($ch)){
        return json_encode(['error' => curl_error($ch)]);
    }
    
    // Comprobar código HTTP
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    if ($httpCode >= 400) {
        return json_encode(['error' => 'Error en la petición a la API. HTTP Code: ' . $httpCode]);
    }
    
    return $result;
}

if ($action === 'getTeams') {
    $leagueId = isset($_GET['league']) ? $_GET['league'] : 140; // 140 = La Liga
    $season = isset($_GET['season']) ? $_GET['season'] : 2023;
    
    $response = makeApiRequest("teams?league={$leagueId}&season={$season}");
    echo $response;
    
} elseif ($action === 'getTeamStats') {
    $leagueId = isset($_GET['league']) ? $_GET['league'] : 140;
    $season = isset($_GET['season']) ? $_GET['season'] : 2023;
    $teamId = isset($_GET['team']) ? $_GET['team'] : '';
    
    if (!$teamId) {
        echo json_encode(['error' => 'Se requiere el ID del equipo']);
        exit;
    }
    
    $response = makeApiRequest("teams/statistics?league={$leagueId}&season={$season}&team={$teamId}");
    echo $response;
    
} elseif ($action === 'getTeamFixtures') {
    $leagueId = isset($_GET['league']) ? $_GET['league'] : 140;
    $season = isset($_GET['season']) ? $_GET['season'] : 2023;
    $teamId = isset($_GET['team']) ? $_GET['team'] : '';
    
    if (!$teamId) {
        echo json_encode(['error' => 'Se requiere el ID del equipo']);
        exit;
    }
    
    // En el plan Free no se puede usar el parámetro 'last', así que pedimos todos
    $response = makeApiRequest("fixtures?league={$leagueId}&season={$season}&team={$teamId}");
    echo $response;
    
} else {
    echo json_encode(['error' => 'Acción no válida']);
}
?>
