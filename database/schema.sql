CREATE DATABASE IF NOT EXISTS statbet
CHARACTER SET utf8mb4
COLLATE utf8mb4_unicode_ci;

USE statbet;

CREATE TABLE IF NOT EXISTS competitions (
    division_code VARCHAR(10) PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    country VARCHAR(100) NULL,
    tier INT NULL
);

CREATE TABLE IF NOT EXISTS matches (
    id INT AUTO_INCREMENT PRIMARY KEY,

    source_file VARCHAR(100) NOT NULL,
    division_code VARCHAR(10) NOT NULL,
    season VARCHAR(20) NULL,

    match_date DATE NOT NULL,
    match_time TIME NULL,

    home_team VARCHAR(150) NOT NULL,
    away_team VARCHAR(150) NOT NULL,

    ft_home_goals INT NULL,
    ft_away_goals INT NULL,
    ft_result CHAR(1) NULL,

    ht_home_goals INT NULL,
    ht_away_goals INT NULL,
    ht_result CHAR(1) NULL,

    home_shots INT NULL,
    away_shots INT NULL,

    home_shots_target INT NULL,
    away_shots_target INT NULL,

    home_fouls INT NULL,
    away_fouls INT NULL,

    home_corners INT NULL,
    away_corners INT NULL,

    home_yellow INT NULL,
    away_yellow INT NULL,

    home_red INT NULL,
    away_red INT NULL,

    home_elo DECIMAL(8,2) NULL,
    away_elo DECIMAL(8,2) NULL,

    imported_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    UNIQUE KEY unique_match (
        division_code,
        match_date,
        home_team,
        away_team
    ),

    INDEX idx_home_team (home_team),
    INDEX idx_away_team (away_team),
    INDEX idx_match_date (match_date),
    INDEX idx_division (division_code),
    INDEX idx_season (season)
);

CREATE TABLE IF NOT EXISTS import_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    source_file VARCHAR(100) NOT NULL,
    imported_rows INT NOT NULL DEFAULT 0,
    skipped_rows INT NOT NULL DEFAULT 0,
    imported_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

INSERT INTO competitions (division_code, name, country, tier) VALUES
('E0', 'Premier League', 'England', 1),
('E1', 'Championship', 'England', 2),
('E2', 'League One', 'England', 3),
('E3', 'League Two', 'England', 4),
('EC', 'National League', 'England', 5),

('SP1', 'LaLiga', 'Spain', 1),
('SP2', 'LaLiga 2', 'Spain', 2),

('I1', 'Serie A', 'Italy', 1),
('I2', 'Serie B', 'Italy', 2),

('D1', 'Bundesliga', 'Germany', 1),
('D2', '2. Bundesliga', 'Germany', 2),

('F1', 'Ligue 1', 'France', 1),
('F2', 'Ligue 2', 'France', 2),

('N1', 'Eredivisie', 'Netherlands', 1),
('P1', 'Liga Portugal', 'Portugal', 1),
('B1', 'Belgian Pro League', 'Belgium', 1),
('T1', 'Süper Lig', 'Turkey', 1),
('G1', 'Greek Super League', 'Greece', 1),

('SC0', 'Scottish Premiership', 'Scotland', 1),
('SC1', 'Scottish Championship', 'Scotland', 2),
('SC2', 'Scottish League One', 'Scotland', 3),
('SC3', 'Scottish League Two', 'Scotland', 4)
ON DUPLICATE KEY UPDATE
    name = VALUES(name),
    country = VALUES(country),
    tier = VALUES(tier);


    CREATE TABLE IF NOT EXISTS teams (
    id INT AUTO_INCREMENT PRIMARY KEY,
    team_name VARCHAR(150) NOT NULL,
    icon_path VARCHAR(300) NULL,
    UNIQUE KEY unique_team_name (team_name)
);

-- insert_teams.sql
-- Insertar equipos con sus iconos en la tabla teams
-- Ejecutar después de schema.sql

INSERT INTO teams (team_name, icon_path) VALUES
-- España LaLiga SP1
('Alaves', 'images/España/LaLiga/alaves.svg'),
('Ath Bilbao', 'images/España/LaLiga/athleticclub.svg'),
('Ath Madrid', 'images/España/LaLiga/atleticomadrid.svg'),
('Barcelona', 'images/España/LaLiga/barcelona.svg'),
('Betis', 'images/España/LaLiga/realbetis.svg'),
('Celta', 'images/España/LaLiga/celta.svg'),
('Deportivo Alaves', 'images/España/LaLiga/alaves.svg'),
('Elche', 'images/España/LaLiga/elche.svg'),
('Espanol', 'images/España/LaLiga/espanyol.svg'),
('Getafe', 'images/España/LaLiga/getafe.svg'),
('Girona', 'images/España/LaLiga/girona.svg'),
('Levante', 'images/España/LaLiga/levante.svg'),
('Mallorca', 'images/España/LaLiga/mallorca.svg'),
('Osasuna', 'images/España/LaLiga/osasuna.svg'),
('Oviedo', 'images/España/LaLiga/oviedo.svg'),
('Real Betis', 'images/España/LaLiga/realbetis.svg'),
('Real Madrid', 'images/España/LaLiga/realmadrid.svg'),
('Sevilla', 'images/España/LaLiga/sevilla.svg'),
('Sociedad', 'images/España/LaLiga/realsociedad.svg'),
('Valencia', 'images/España/LaLiga/valencia.svg'),
('Vallecano', 'images/España/LaLiga/rayovallecano.svg'),
('Villarreal', 'images/España/LaLiga/villarreal.svg'),
-- España LaLiga2 SP2
('Albacete', 'images/España/LaLiga2/albacete.svg'),
('Almeria', 'images/España/LaLiga2/almeria.svg'),
('Andorra', 'images/España/LaLiga2/andorra.svg'),
('Burgos', 'images/España/LaLiga2/burgos.svg'),
('Cadiz', 'images/España/LaLiga2/cadiz.svg'),
('Castellon', 'images/España/LaLiga2/castellon.svg'),
('Ceuta', 'images/España/LaLiga2/ceuta.svg'),
('Cordoba', 'images/España/LaLiga2/cordoba.svg'),
('Cultural Leonesa', 'images/España/LaLiga2/culturalleonesa.svg'),
('Eibar', 'images/España/LaLiga2/eibar.svg'),
('Granada', 'images/España/LaLiga2/granada.svg'),
('Huesca', 'images/España/LaLiga2/huesca.svg'),
('La Coruna', 'images/España/LaLiga2/lacoruña.svg'),
('Las Palmas', 'images/España/LaLiga2/laspalmas.svg'),
('Leganes', 'images/España/LaLiga2/leganes.svg'),
('Malaga', 'images/España/LaLiga2/malaga.svg'),
('Mirandes', 'images/España/LaLiga2/mirandes.svg'),
('Racing', 'images/España/LaLiga2/racing.svg'),
('Sp Gijon', 'images/España/LaLiga2/sportinggijon.svg'),
('Valladolid', 'images/España/LaLiga2/valladolid.svg'),
('Zaragoza', 'images/España/LaLiga2/zaragoza.svg'),
-- Alemania Bundesliga D1
('Augsburg', 'images/Alemania/Bundesliga/augsburg.svg'),
('Bayern Munich', 'images/Alemania/Bundesliga/bayern.svg'),
('Dortmund', 'images/Alemania/Bundesliga/borussia.svg'),
('Ein Frankfurt', 'images/Alemania/Bundesliga/frankfurt.svg'),
('FC Koln', 'images/Alemania/Bundesliga/cologne.svg'),
('Freiburg', 'images/Alemania/Bundesliga/freiburg.svg'),
('Heidenheim', 'images/Alemania/Bundesliga/heidenheim.svg'),
('Hoffenheim', 'images/Alemania/Bundesliga/hoffenheim.svg'),
('Leverkusen', 'images/Alemania/Bundesliga/leverkusen.svg'),
('Mainz', 'images/Alemania/Bundesliga/mainz.svg'),
('M''gladbach', 'images/Alemania/Bundesliga/monchen.svg'),
('MGladbach', 'images/Alemania/Bundesliga/monchen.svg'),
('RB Leipzig', 'images/Alemania/Bundesliga/leipzig.svg'),
('St Pauli', 'images/Alemania/Bundesliga/pauli.svg'),
('Stuttgart', 'images/Alemania/Bundesliga/stuttgart.svg'),
('Union Berlin', 'images/Alemania/Bundesliga/union.svg'),
('Werder Bremen', 'images/Alemania/Bundesliga/werder.svg'),
('Wolfsburg', 'images/Alemania/Bundesliga/wolfsburg.svg'),
('Hamburg', 'images/Alemania/Bundesliga/hamburguer.svg'),
-- Alemania Bundesliga2 D2
('Bielefeld', 'images/Alemania/Bundesliga2/arminia.svg'),
('Bochum', 'images/Alemania/Bundesliga2/bochum.svg'),
('Braunschweig', 'images/Alemania/Bundesliga2/braunschweig.svg'),
('Darmstadt', 'images/Alemania/Bundesliga2/darmstadt.svg'),
('Dynamo Dresden', 'images/Alemania/Bundesliga2/dynamodresden.svg'),
('Elversberg', 'images/Alemania/Bundesliga2/elversberg.svg'),
('Fortuna Dusseldorf', 'images/Alemania/Bundesliga2/fortuna.svg'),
('Greuther Furth', 'images/Alemania/Bundesliga2/spvgg.svg'),
('Hannover 96', 'images/Alemania/Bundesliga2/hannover.svg'),
('Hertha', 'images/Alemania/Bundesliga2/hertha.svg'),
('Holstein Kiel', 'images/Alemania/Bundesliga2/holstein.svg'),
('Kaiserslautern', 'images/Alemania/Bundesliga2/kaisers.svg'),
('Karlsruher SC', 'images/Alemania/Bundesliga2/karlsruher.svg'),
('Magdeburg', 'images/Alemania/Bundesliga2/magdeburg.svg'),
('Munster', 'images/Alemania/Bundesliga2/munster.svg'),
('Nurnberg', 'images/Alemania/Bundesliga2/nurnberg.svg'),
('Paderborn', 'images/Alemania/Bundesliga2/paderborn.svg'),
('Schalke 04', 'images/Alemania/Bundesliga2/schalke.svg'),
-- Francia Ligue1 F1
('Angers', 'images/Francia/Ligue1/angers.svg'),
('Auxerre', 'images/Francia/Ligue1/auxerre.svg'),
('Brest', 'images/Francia/Ligue1/brest.svg'),
('Le Havre', 'images/Francia/Ligue1/lehavre.svg'),
('Lens', 'images/Francia/Ligue1/lens.svg'),
('Lille', 'images/Francia/Ligue1/lile.svg'),
('Lorient', 'images/Francia/Ligue1/lorient.svg'),
('Lyon', 'images/Francia/Ligue1/lyon.svg'),
('Marseille', 'images/Francia/Ligue1/mraseille.svg'),
('Metz', 'images/Francia/Ligue1/metz.svg'),
('Monaco', 'images/Francia/Ligue1/monaco.svg'),
('Nantes', 'images/Francia/Ligue1/nantes.svg'),
('Nice', 'images/Francia/Ligue1/nice.svg'),
('Paris FC', 'images/Francia/Ligue1/paris.svg'),
('Paris SG', 'images/Francia/Ligue1/psg.svg'),
('Rennes', 'images/Francia/Ligue1/rennes.svg'),
('Strasbourg', 'images/Francia/Ligue1/strasbourg.svg'),
('Toulouse', 'images/Francia/Ligue1/toulouse.svg'),
-- Francia Ligue2 F2
('Amiens', 'images/Francia/Ligue2/amiens.svg'),
('Annecy', 'images/Francia/Ligue2/annecy.svg'),
('Bastia', 'images/Francia/Ligue2/bastia.svg'),
('Boulogne', 'images/Francia/Ligue2/boulogne.svg'),
('Clermont', 'images/Francia/Ligue2/clermont.svg'),
('Dunkerque', 'images/Francia/Ligue2/dunkerque.svg'),
('Grenoble', 'images/Francia/Ligue2/grenoble.svg'),
('Guingamp', 'images/Francia/Ligue2/guingamp.svg'),
('Lavallois', 'images/Francia/Ligue2/lavallois.svg'),
('Le Mans', 'images/Francia/Ligue2/lemans.svg'),
('Montpellier', 'images/Francia/Ligue2/montpellier.svg'),
('Nancy', 'images/Francia/Ligue2/nancy.svg'),
('Pau', 'images/Francia/Ligue2/pau.svg'),
('Red Star', 'images/Francia/Ligue2/redstar.svg'),
('Reims', 'images/Francia/Ligue2/reims.svg'),
('Rodez', 'images/Francia/Ligue2/rodez.svg'),
('St Etienne', 'images/Francia/Ligue2/saint.svg'),
('Troyes', 'images/Francia/Ligue2/troyes.svg'),
-- Inglaterra Premier E0
('Arsenal', 'images/Inglaterra/PremierLeague/arsenal.svg'),
('Aston Villa', 'images/Inglaterra/PremierLeague/astonvilla.svg'),
('Bournemouth', 'images/Inglaterra/PremierLeague/bournemouth.svg'),
('Brentford', 'images/Inglaterra/PremierLeague/brentford.svg'),
('Brighton', 'images/Inglaterra/PremierLeague/brighton.svg'),
('Burnley', 'images/Inglaterra/PremierLeague/burnley.svg'),
('Chelsea', 'images/Inglaterra/PremierLeague/chelsea.svg'),
('Crystal Palace', 'images/Inglaterra/PremierLeague/crystalpalace.svg'),
('Everton', 'images/Inglaterra/PremierLeague/everton.svg'),
('Fulham', 'images/Inglaterra/PremierLeague/fulham.svg'),
('Leeds', 'images/Inglaterra/PremierLeague/leedsunited.svg'),
('Liverpool', 'images/Inglaterra/PremierLeague/liverpool.svg'),
('Man City', 'images/Inglaterra/PremierLeague/manchestercity.svg'),
('Man United', 'images/Inglaterra/PremierLeague/manchesterunited.svg'),
('Newcastle', 'images/Inglaterra/PremierLeague/newcastle.svg'),
('Nott''m Forest', 'images/Inglaterra/PremierLeague/nottinghamforest.svg'),
('Nottm Forest', 'images/Inglaterra/PremierLeague/nottinghamforest.svg'),
('Sunderland', 'images/Inglaterra/PremierLeague/sunderland.svg'),
('Tottenham', 'images/Inglaterra/PremierLeague/tottenham.svg'),
('West Ham', 'images/Inglaterra/PremierLeague/westham.svg'),
('Wolves', 'images/Inglaterra/PremierLeague/wolves.svg'),
-- Inglaterra Championship E1
('Birmingham', 'images/Inglaterra/EFLChampionship/birmingham.svg'),
('Blackburn', 'images/Inglaterra/EFLChampionship/blackburn.svg'),
('Bristol City', 'images/Inglaterra/EFLChampionship/bristolcity.svg'),
('Charlton', 'images/Inglaterra/EFLChampionship/charlton.svg'),
('Coventry', 'images/Inglaterra/EFLChampionship/coventrycity.svg'),
('Derby', 'images/Inglaterra/EFLChampionship/derbycounty.svg'),
('Hull', 'images/Inglaterra/EFLChampionship/hullcity.svg'),
('Ipswich', 'images/Inglaterra/EFLChampionship/ipswich.svg'),
('Leicester', 'images/Inglaterra/EFLChampionship/leicester.svg'),
('Middlesbrough', 'images/Inglaterra/EFLChampionship/middlesbrough.svg'),
('Millwall', 'images/Inglaterra/EFLChampionship/millwall.svg'),
('Norwich', 'images/Inglaterra/EFLChampionship/norwichcity.svg'),
('Oxford', 'images/Inglaterra/EFLChampionship/oxfordunited.svg'),
('Portsmouth', 'images/Inglaterra/EFLChampionship/portsmouth.svg'),
('Preston', 'images/Inglaterra/EFLChampionship/prestonnorthend.svg'),
('QPR', 'images/Inglaterra/EFLChampionship/queensparkrangers.svg'),
('Sheffield United', 'images/Inglaterra/EFLChampionship/sheffieldunited.svg'),
('Sheffield Weds', 'images/Inglaterra/EFLChampionship/sheffieldwednesday.svg'),
('Southampton', 'images/Inglaterra/EFLChampionship/southampton.svg'),
('Stoke', 'images/Inglaterra/EFLChampionship/stokecity.svg'),
('Swansea', 'images/Inglaterra/EFLChampionship/swanseacity.svg'),
('Watford', 'images/Inglaterra/EFLChampionship/watford.svg'),
('West Brom', 'images/Inglaterra/EFLChampionship/westbromwichalbion.svg'),
('Wrexham', 'images/Inglaterra/EFLChampionship/wrexham.svg'),
-- Italia Serie A I1
('Atalanta', 'images/Italia/SerieA/atalanta.svg'),
('Bologna', 'images/Italia/SerieA/bologna.svg'),
('Cagliari', 'images/Italia/SerieA/cagliari.svg'),
('Como', 'images/Italia/SerieA/como.svg'),
('Cremonese', 'images/Italia/SerieA/cremonese.svg'),
('Fiorentina', 'images/Italia/SerieA/fiorentina.svg'),
('Genoa', 'images/Italia/SerieA/genoa.svg'),
('Inter', 'images/Italia/SerieA/inter.svg'),
('Juventus', 'images/Italia/SerieA/juventus.svg'),
('Lazio', 'images/Italia/SerieA/lazio.svg'),
('Lecce', 'images/Italia/SerieA/lecce.svg'),
('Milan', 'images/Italia/SerieA/milan.svg'),
('Napoli', 'images/Italia/SerieA/napoli.svg'),
('Parma', 'images/Italia/SerieA/parma.svg'),
('Pisa', 'images/Italia/SerieA/pisa.svg'),
('Roma', 'images/Italia/SerieA/roma.svg'),
('Sassuolo', 'images/Italia/SerieA/sassuolo.svg'),
('Torino', 'images/Italia/SerieA/torino.svg'),
('Udinese', 'images/Italia/SerieA/udinese.svg'),
('Verona', 'images/Italia/SerieA/verona.svg'),
-- Italia Serie B I2
('Avellino', 'images/Italia/SerieB/avellino.svg'),
('Bari', 'images/Italia/SerieB/bari.svg'),
('Carrarese', 'images/Italia/SerieB/carrarese.svg'),
('Catanzaro', 'images/Italia/SerieB/catanzaro.svg'),
('Cesena', 'images/Italia/SerieB/cesena.svg'),
('Empoli', 'images/Italia/SerieB/empoli.svg'),
('Frosinone', 'images/Italia/SerieB/frosinone.svg'),
('Juve Stabia', 'images/Italia/SerieB/juvestabia.svg'),
('Mantova', 'images/Italia/SerieB/mantova.svg'),
('Modena', 'images/Italia/SerieB/modena.svg'),
('Monza', 'images/Italia/SerieB/monza.svg'),
('Padova', 'images/Italia/SerieB/padova.svg'),
('Palermo', 'images/Italia/SerieB/palermo.svg'),
('Pescara', 'images/Italia/SerieB/pescara.svg'),
('Reggiana', 'images/Italia/SerieB/reggiana.svg'),
('Sampdoria', 'images/Italia/SerieB/sampdoria.svg'),
('Spezia', 'images/Italia/SerieB/spezia.svg'),
('Sud Tirol', 'images/Italia/SerieB/suditrol.svg'),
('Venezia', 'images/Italia/SerieB/venezia.svg'),
('Virtus Entella', 'images/Italia/SerieB/virtusentella.svg')
ON DUPLICATE KEY UPDATE icon_path = VALUES(icon_path);