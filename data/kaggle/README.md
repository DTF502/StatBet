# Dataset Kaggle

Esta carpeta debe contener los CSV del dataset de Kaggle:

https://www.kaggle.com/datasets/davidcariboo/player-scores/data

Descargar el ZIP completo, descomprimirlo y colocar aquí directamente los archivos CSV:

- appearances.csv
- club_games.csv
- clubs.csv
- competitions.csv
- countries.csv
- game_events.csv
- game_lineups.csv
- games.csv
- national_teams.csv
- player_valuations.csv
- players.csv
- transfers.csv

La ruta final debe quedar así:

data/kaggle/clubs.csv
data/kaggle/games.csv
data/kaggle/club_games.csv
...

Para comprobar que PHP detecta los CSV:

http://localhost/StatBet-main/backend/kaggle_api.php?action=health
