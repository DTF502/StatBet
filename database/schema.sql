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
