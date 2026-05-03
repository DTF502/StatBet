-- ============================================================
--  StatBet – Esquema de base de datos
--  Dataset fuente: Transfermarkt (Kaggle)
--  https://www.kaggle.com/datasets/davidcariboo/player-scores
-- ============================================================
-- Uso:
--   1. Crea la BD:  CREATE DATABASE statbet CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
--   2. Selecciónala: USE statbet;
--   3. Ejecuta este archivo completo.
-- ============================================================

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;


-- ------------------------------------------------------------
-- competitions
--   Fuente: competitions.csv
--   Usada para: listar ligas por país, mapear competition_id → nombre
-- ------------------------------------------------------------
DROP TABLE IF EXISTS competitions;
CREATE TABLE competitions (
    competition_id          VARCHAR(20)   NOT NULL,
    name                    VARCHAR(200)  NOT NULL,
    sub_type                VARCHAR(50)   DEFAULT NULL,   -- 'first_division', 'domestic_cup'…
    type                    VARCHAR(50)   DEFAULT NULL,   -- 'domestic_league', 'domestic_cup'…
    country_id              INT           DEFAULT NULL,
    country_name            VARCHAR(100)  DEFAULT NULL,
    domestic_league_code    VARCHAR(20)   DEFAULT NULL,
    confederation           VARCHAR(20)   DEFAULT NULL,
    url                     VARCHAR(255)  DEFAULT NULL,
    is_major_national_league TINYINT(1)   NOT NULL DEFAULT 0,
    PRIMARY KEY (competition_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- ------------------------------------------------------------
-- clubs
--   Fuente: clubs.csv
--   Usada para: listar equipos por liga, resolver nombres de clubes
-- ------------------------------------------------------------
DROP TABLE IF EXISTS clubs;
CREATE TABLE clubs (
    club_id                 INT           NOT NULL,
    club_code               VARCHAR(50)   DEFAULT NULL,
    name                    VARCHAR(200)  NOT NULL,
    domestic_competition_id VARCHAR(20)   DEFAULT NULL,
    total_market_value      VARCHAR(50)   DEFAULT NULL,  -- p.ej. "€850.00m"
    squad_size              SMALLINT      DEFAULT NULL,
    average_age             DECIMAL(4,1)  DEFAULT NULL,
    foreigners_number       SMALLINT      DEFAULT NULL,
    foreigners_percentage   DECIMAL(5,2)  DEFAULT NULL,
    national_team_players   SMALLINT      DEFAULT NULL,
    stadium_name            VARCHAR(200)  DEFAULT NULL,
    stadium_seats           INT           DEFAULT NULL,
    net_transfer_record     VARCHAR(50)   DEFAULT NULL,
    coach_name              VARCHAR(200)  DEFAULT NULL,
    last_season             SMALLINT      DEFAULT NULL,
    filename                VARCHAR(255)  DEFAULT NULL,
    url                     VARCHAR(255)  DEFAULT NULL,
    PRIMARY KEY (club_id),
    INDEX idx_competition (domestic_competition_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- ------------------------------------------------------------
-- games
--   Fuente: games.csv
--   Usada para: estadísticas de temporada, últimos partidos
--   Columnas clave: game_id, competition_id, season, date,
--     home/away club ids y goles, nombres de clubes
-- ------------------------------------------------------------
DROP TABLE IF EXISTS games;
CREATE TABLE games (
    game_id                 INT           NOT NULL,
    competition_id          VARCHAR(20)   DEFAULT NULL,
    season                  SMALLINT      DEFAULT NULL,
    round                   VARCHAR(50)   DEFAULT NULL,
    date                    DATE          DEFAULT NULL,
    home_club_id            INT           DEFAULT NULL,
    away_club_id            INT           DEFAULT NULL,
    home_club_goals         TINYINT UNSIGNED DEFAULT NULL,
    away_club_goals         TINYINT UNSIGNED DEFAULT NULL,
    home_club_position      TINYINT UNSIGNED DEFAULT NULL,
    away_club_position      TINYINT UNSIGNED DEFAULT NULL,
    home_club_manager_name  VARCHAR(200)  DEFAULT NULL,
    away_club_manager_name  VARCHAR(200)  DEFAULT NULL,
    stadium                 VARCHAR(200)  DEFAULT NULL,
    attendance              INT           DEFAULT NULL,
    referee                 VARCHAR(200)  DEFAULT NULL,
    url                     VARCHAR(255)  DEFAULT NULL,
    home_club_name          VARCHAR(200)  DEFAULT NULL,   -- desnormalizado para consultas rápidas
    away_club_name          VARCHAR(200)  DEFAULT NULL,
    PRIMARY KEY (game_id),
    INDEX idx_competition_season (competition_id, season),
    INDEX idx_home_club          (home_club_id),
    INDEX idx_away_club          (away_club_id),
    INDEX idx_date               (date),
    INDEX idx_season             (season)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- ------------------------------------------------------------
-- club_games
--   Fuente: club_games.csv
--   Usada para: goles a favor/contra, local/visitante por partido
-- ------------------------------------------------------------
DROP TABLE IF EXISTS club_games;
CREATE TABLE club_games (
    game_id                 INT           NOT NULL,
    club_id                 INT           NOT NULL,
    own_goals               TINYINT UNSIGNED DEFAULT 0,
    opponent_goals          TINYINT UNSIGNED DEFAULT 0,
    own_position            TINYINT UNSIGNED DEFAULT NULL,
    own_manager_name        VARCHAR(200)  DEFAULT NULL,
    opponent_id             INT           DEFAULT NULL,
    opponent_manager_name   VARCHAR(200)  DEFAULT NULL,
    hosting                 VARCHAR(5)    DEFAULT NULL,  -- 'Home' o 'Away'
    is_win                  TINYINT(1)    DEFAULT NULL,
    PRIMARY KEY (game_id, club_id),
    INDEX idx_club_id        (club_id),
    INDEX idx_club_hosting   (club_id, hosting)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- ------------------------------------------------------------
-- game_events
--   Fuente: game_events.csv
--   Usada para: tarjetas amarillas y rojas por partido/club
--   Tipos relevantes: 'Cards' (description: 'Yellow Card', 'Red Card'…)
-- ------------------------------------------------------------
DROP TABLE IF EXISTS game_events;
CREATE TABLE game_events (
    game_event_id           VARCHAR(50)   NOT NULL,
    date                    DATE          DEFAULT NULL,
    game_id                 INT           DEFAULT NULL,
    minute                  TINYINT UNSIGNED DEFAULT NULL,
    type                    VARCHAR(50)   DEFAULT NULL,   -- 'Goals', 'Cards', 'Substitutions'…
    club_id                 INT           DEFAULT NULL,
    player_id               INT           DEFAULT NULL,
    description             VARCHAR(200)  DEFAULT NULL,   -- 'Yellow Card', 'Red Card', 'Goal'…
    player_in_id            INT           DEFAULT NULL,   -- para sustituciones
    player_assist_id        INT           DEFAULT NULL,
    PRIMARY KEY (game_event_id),
    INDEX idx_game_id        (game_id),
    INDEX idx_club_id        (club_id),
    INDEX idx_type           (type),
    INDEX idx_game_club      (game_id, club_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- ============================================================
-- Vista auxiliar: tarjetas por partido y club
--   Evita escanear game_events en cada petición de estadísticas.
--   Se recalcula automáticamente; no requiere mantenimiento.
-- ============================================================
DROP VIEW IF EXISTS v_match_cards;
CREATE VIEW v_match_cards AS
SELECT
    game_id,
    club_id,
    SUM(CASE WHEN LOWER(description) LIKE '%yellow%' THEN 1 ELSE 0 END) AS yellow_cards,
    SUM(CASE WHEN LOWER(description) LIKE '%red%'
             AND LOWER(description) NOT LIKE '%yellow%' THEN 1 ELSE 0 END) AS red_cards
FROM game_events
WHERE type = 'Cards'
GROUP BY game_id, club_id;


-- ============================================================
-- Vista principal de partidos
--   Combina games + club_games + v_match_cards en una sola fila
--   por (partido, club). Es lo que kaggle_api.php consulta para
--   devolver estadísticas y últimos partidos.
-- ============================================================
DROP VIEW IF EXISTS v_club_match_stats;
CREATE VIEW v_club_match_stats AS
SELECT
    g.game_id,
    g.competition_id,
    g.season,
    g.date,
    cg.club_id,
    cg.hosting,                                     -- 'Home' | 'Away'
    cg.own_goals        AS goals_for,
    cg.opponent_goals   AS goals_against,
    cg.opponent_id,
    cg.is_win,
    CASE
        WHEN cg.hosting = 'Home' THEN g.away_club_name
        ELSE g.home_club_name
    END                 AS opponent_name,
    CASE
        WHEN cg.hosting = 'Home' THEN g.home_club_goals
        ELSE g.away_club_goals
    END                 AS home_goals,
    CASE
        WHEN cg.hosting = 'Home' THEN g.away_club_goals
        ELSE g.home_club_goals
    END                 AS away_goals,
    g.home_club_name,
    g.away_club_name,
    COALESCE(mc.yellow_cards, 0) AS yellow_cards,
    COALESCE(mc.red_cards,    0) AS red_cards
FROM games g
JOIN  club_games    cg ON cg.game_id = g.game_id
LEFT JOIN v_match_cards mc ON mc.game_id = g.game_id AND mc.club_id = cg.club_id;


SET FOREIGN_KEY_CHECKS = 1;
