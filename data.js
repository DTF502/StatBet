/* Datos y servicio compartidos entre index.html y stats.html */
(function () {
  const db = createMockDatabase();

  const api = {
    getCountries() {
      return db.countries;
    },
    getLeaguesByCountry(countryCode) {
      return db.leagues.filter((league) => league.countryCode === countryCode);
    },
    getTeamsByLeague(leagueId) {
      return db.teams.filter((team) => team.leagueId === leagueId);
    },
    searchTeam(query) {
      const normalized = normalize(query);
      return db.teams.find((team) => normalize(team.name).includes(normalized)) || null;
    },
    getTeamById(teamId) {
      return db.teams.find((team) => team.id === teamId) || null;
    },
    getLeagueById(leagueId) {
      return db.leagues.find((league) => league.id === leagueId) || null;
    },
    getTeamStats(teamId, context, competition) {
      const team = db.teamDetails[teamId];
      const teamInfo = db.teams.find((item) => item.id === teamId) || null;
      if (!team && !teamInfo) return null;

      if (!team) {
        return {
          teamName: teamInfo.name,
          sourceStats: {
            matches: 0,
            goalsFor: 0,
            goalsAgainst: 0,
            cornersFor: 0,
            cornersAgainst: 0,
            yellowCards: 0,
            fouls: 0,
          },
          recentMatches: [],
          updatedAt: new Date().toISOString(),
          competitions: [],
        };
      }

      const competitionStats =
        competition !== "all" && team.stats.byCompetition[competition]
          ? team.stats.byCompetition[competition]
          : null;

      const sourceStats = competitionStats || team.stats[context] || team.stats.all;

      const recentMatches = team.recentMatches
        .filter((match) => competition === "all" || match.competition === competition)
        .filter((match) => context === "all" || match.homeAway === context)
        .slice(0, 5);

      return {
        teamName: team.name,
        sourceStats,
        recentMatches,
        updatedAt: team.updatedAt,
        competitions: team.competitions,
      };
    },
  };

  window.StatBetData = {
    api,
    helpers: {
      normalize,
      formatDateTime,
    },
  };

  function normalize(value) {
    return value
      .toLowerCase()
      .normalize("NFD")
      .replace(/[\u0300-\u036f]/g, "");
  }

  function formatDateTime(isoDate) {
    const date = new Date(isoDate);
    if (Number.isNaN(date.getTime())) return "--:--";
    return date.toLocaleString("es-ES", {
      day: "2-digit",
      month: "2-digit",
      hour: "2-digit",
      minute: "2-digit",
    });
  }

  function imagePath(type, country, league, filename) {
    // Genera la ruta correcta según la estructura jerárquica
    if (type === "country") {
      return `images/${filename}`;
    } else if (type === "league") {
      return `images/${country}/${filename}`;
    } else if (type === "team") {
      return `images/${country}/${league}/${filename}`;
    }
    return "";
  }

  function createMockDatabase() {
    const countries = [
      { code: "ES", name: "España", icon: imagePath("country", null, null, "españa.svg") },
      { code: "GB", name: "Reino Unido", icon: imagePath("country", null, null, "inglaterra.svg") },
      { code: "IT", name: "Italia", icon: imagePath("country", null, null, "italia.svg") },
    ];

    const leagues = [
      { id: "la_liga",      name: "LaLiga",     countryCode: "ES",     icon: imagePath("league", "España", null, "laliga.svg") },
      { id: "laliga2",      name: "LaLiga 2",   countryCode: "ES",     icon: imagePath("league", "España", null, "laliga2.svg") },
      { id: "premier",      name: "Premier League", countryCode: "GB", icon: imagePath("league", "Inglaterra", null, "premierleague.svg") },
      { id: "championship", name: "Championship",   countryCode: "GB", icon: imagePath("league", "Inglaterra", null, "eflchampionship.svg") },
      { id: "serie_a", name: "Serie A", countryCode: "IT", icon: imagePath("league", "Italia", null, "seriea.svg") },
      { id: "serie_b", name: "Serie B", countryCode: "IT", icon: imagePath("league", "Italia", null, "serieb.svg") },
    ];

    const teams = [
      { id: "realmadrid", name: "Real Madrid", leagueId: "la_liga", countryCode: "ES", icon: imagePath("team", "España", "LaLiga", "realmadrid.svg") },
      { id: "barcelona", name: "FC Barcelona", leagueId: "la_liga", countryCode: "ES", icon: imagePath("team", "España", "LaLiga", "barcelona.svg") },
      { id: "atleticomadrid", name: "Atletico Madrid", leagueId: "la_liga", countryCode: "ES", icon: imagePath("team", "España", "LaLiga", "atleticomadrid.svg") },
      { id: "realbetis", name: "Real Betis", leagueId: "la_liga", countryCode: "ES", icon: imagePath("team", "España", "LaLiga", "realbetis.svg") },
      { id: "villarreal", name: "Villarreal", leagueId: "la_liga", countryCode: "ES", icon: imagePath("team", "España", "LaLiga", "villarreal.svg") },
      { id: "athleticclub", name: "Athletic Bilbao", leagueId: "la_liga", countryCode: "ES", icon: imagePath("team", "España", "LaLiga", "athleticclub.svg") },
      { id: "realsociedad", name: "Real Sociedad", leagueId: "la_liga", countryCode: "ES", icon: imagePath("team", "España", "LaLiga", "realsociedad.svg") },
      { id: "valencia", name: "Valencia", leagueId: "la_liga", countryCode: "ES", icon: imagePath("team", "España", "LaLiga", "valencia.svg") },
      { id: "sevilla", name: "Sevilla", leagueId: "la_liga", countryCode: "ES", icon: imagePath("team", "España", "LaLiga", "sevilla.svg") },
      { id: "girona", name: "Girona", leagueId: "la_liga", countryCode: "ES", icon: imagePath("team", "España", "LaLiga", "girona.svg") },
      { id: "getafe", name: "Getafe", leagueId: "la_liga", countryCode: "ES", icon: imagePath("team", "España", "LaLiga", "getafe.svg") },
      { id: "rayovallecano", name: "Rayo Vallecano", leagueId: "la_liga", countryCode: "ES", icon: imagePath("team", "España", "LaLiga", "rayovallecano.svg") },
      { id: "espanyol", name: "RCD Espanyol", leagueId: "la_liga", countryCode: "ES", icon: imagePath("team", "España", "LaLiga", "espanyol.svg") },
      { id: "osasuna", name: "CA Osasuna", leagueId: "la_liga", countryCode: "ES", icon: imagePath("team", "España", "LaLiga", "osasuna.svg") },
      { id: "mallorca", name: "RCD Mallorca", leagueId: "la_liga", countryCode: "ES", icon: imagePath("team", "España", "LaLiga", "mallorca.svg") },
      { id: "celta", name: "RC Celta", leagueId: "la_liga", countryCode: "ES", icon: imagePath("team", "España", "LaLiga", "celta.svg") },
      { id: "levante", name: "Levante", leagueId: "la_liga", countryCode: "ES", icon: imagePath("team", "España", "LaLiga", "levante.svg") },
      { id: "elche", name: "Elche", leagueId: "la_liga", countryCode: "ES", icon: imagePath("team", "España", "LaLiga", "elche.svg") },
      { id: "deportivo", name: "RC Deportivo", leagueId: "la_liga", countryCode: "ES", icon: imagePath("team", "España", "LaLiga", "deportivo.svg") },
      { id: "oviedo", name: "Real Oviedo", leagueId: "la_liga", countryCode: "ES", icon: imagePath("team", "España", "LaLiga", "oviedo.svg") },
      { id: "albacete", name: "Albacete", leagueId: "laliga2", countryCode: "ES", icon: imagePath("team", "España", "LaLiga2", "albacete.svg") },
      { id: "almeria", name: "Almeria", leagueId: "laliga2", countryCode: "ES", icon: imagePath("team", "España", "LaLiga2", "almeria.svg") },
      { id: "andorra", name: "Andorra", leagueId: "laliga2", countryCode: "ES", icon: imagePath("team", "España", "LaLiga2", "andorra.svg") },
      { id: "burgos", name: "Burgos", leagueId: "laliga2", countryCode: "ES", icon: imagePath("team", "España", "LaLiga2", "burgos.svg") },
      { id: "cadiz", name: "Cadiz", leagueId: "laliga2", countryCode: "ES", icon: imagePath("team", "España", "LaLiga2", "cadiz.svg") },
      { id: "castellon", name: "Castellon", leagueId: "laliga2", countryCode: "ES", icon: imagePath("team", "España", "LaLiga2", "castellon.svg") },
      { id: "ceuta", name: "Ceuta", leagueId: "laliga2", countryCode: "ES", icon: imagePath("team", "España", "LaLiga2", "ceuta.svg") },
      { id: "cordoba", name: "Cordoba", leagueId: "laliga2", countryCode: "ES", icon: imagePath("team", "España", "LaLiga2", "cordoba.svg") },
      { id: "culturalleonesa", name: "Cultural Leonesa", leagueId: "laliga2", countryCode: "ES", icon: imagePath("team", "España", "LaLiga2", "culturalleonesa.svg") },
      { id: "eibar", name: "Eibar", leagueId: "laliga2", countryCode: "ES", icon: imagePath("team", "España", "LaLiga2", "eibar.svg") },
      { id: "granada", name: "Granada", leagueId: "laliga2", countryCode: "ES", icon: imagePath("team", "España", "LaLiga2", "granada.svg") },
      { id: "huesca", name: "Huesca", leagueId: "laliga2", countryCode: "ES", icon: imagePath("team", "España", "LaLiga2", "huesca.svg") },
      { id: "lacoruna", name: "La Coruna", leagueId: "laliga2", countryCode: "ES", icon: imagePath("team", "España", "LaLiga2", "lacoruña.svg") },
      { id: "laspalmas", name: "Las Palmas", leagueId: "laliga2", countryCode: "ES", icon: imagePath("team", "España", "LaLiga2", "laspalmas.svg") },
      { id: "leganes", name: "Leganes", leagueId: "laliga2", countryCode: "ES", icon: imagePath("team", "España", "LaLiga2", "leganes.svg") },
      { id: "malaga", name: "Malaga", leagueId: "laliga2", countryCode: "ES", icon: imagePath("team", "España", "LaLiga2", "malaga.svg") },
      { id: "mirandes", name: "Mirandes", leagueId: "laliga2", countryCode: "ES", icon: imagePath("team", "España", "LaLiga2", "mirandes.svg") },
      { id: "racing", name: "Racing", leagueId: "laliga2", countryCode: "ES", icon: imagePath("team", "España", "LaLiga2", "racing.svg") },
      { id: "sportinggijon", name: "Sporting Gijon", leagueId: "laliga2", countryCode: "ES", icon: imagePath("team", "España", "LaLiga2", "sportinggijon.svg") },
      { id: "valladolid", name: "Valladolid", leagueId: "laliga2", countryCode: "ES", icon: imagePath("team", "España", "LaLiga2", "valladolid.svg") },
      { id: "zaragoza", name: "Zaragoza", leagueId: "laliga2", countryCode: "ES", icon: imagePath("team", "España", "LaLiga2", "zaragoza.svg") },
      { id: "arsenal", name: "Arsenal", leagueId: "premier", countryCode: "GB", icon: imagePath("team", "Inglaterra", "PremierLeague", "arsenal.svg") },
      { id: "astonvilla", name: "Aston Villa", leagueId: "premier", countryCode: "GB", icon: imagePath("team", "Inglaterra", "PremierLeague", "astonvilla.svg") },
      { id: "bournemouth", name: "Bournemouth", leagueId: "premier", countryCode: "GB", icon: imagePath("team", "Inglaterra", "PremierLeague", "bournemouth.svg") },
      { id: "brentford", name: "Brentford", leagueId: "premier", countryCode: "GB", icon: imagePath("team", "Inglaterra", "PremierLeague", "brentford.svg") },
      { id: "brighton", name: "Brighton", leagueId: "premier", countryCode: "GB", icon: imagePath("team", "Inglaterra", "PremierLeague", "brighton.svg") },
      { id: "burnley", name: "Burnley", leagueId: "premier", countryCode: "GB", icon: imagePath("team", "Inglaterra", "PremierLeague", "burnley.svg") },
      { id: "chelsea", name: "Chelsea", leagueId: "premier", countryCode: "GB", icon: imagePath("team", "Inglaterra", "PremierLeague", "chelsea.svg") },
      { id: "crystalpalace", name: "Crystal Palace", leagueId: "premier", countryCode: "GB", icon: imagePath("team", "Inglaterra", "PremierLeague", "crystalpalace.svg") },
      { id: "everton", name: "Everton", leagueId: "premier", countryCode: "GB", icon: imagePath("team", "Inglaterra", "PremierLeague", "everton.svg") },
      { id: "fulham", name: "Fulham", leagueId: "premier", countryCode: "GB", icon: imagePath("team", "Inglaterra", "PremierLeague", "fulham.svg") },
      { id: "leedsunited", name: "Leeds United", leagueId: "premier", countryCode: "GB", icon: imagePath("team", "Inglaterra", "PremierLeague", "leedsunited.svg") },
      { id: "liverpool", name: "Liverpool", leagueId: "premier", countryCode: "GB", icon: imagePath("team", "Inglaterra", "PremierLeague", "liverpool.svg") },
      { id: "manchestercity", name: "Manchester City", leagueId: "premier", countryCode: "GB", icon: imagePath("team", "Inglaterra", "PremierLeague", "manchestercity.svg") },
      { id: "manchesterunited", name: "Manchester United", leagueId: "premier", countryCode: "GB", icon: imagePath("team", "Inglaterra", "PremierLeague", "manchesterunited.svg") },
      { id: "newcastle", name: "Newcastle", leagueId: "premier", countryCode: "GB", icon: imagePath("team", "Inglaterra", "PremierLeague", "newcastle.svg") },
      { id: "nottinghamforest", name: "Nottingham Forest", leagueId: "premier", countryCode: "GB", icon: imagePath("team", "Inglaterra", "PremierLeague", "nottinghamforest.svg") },
      { id: "sunderland", name: "Sunderland", leagueId: "premier", countryCode: "GB", icon: imagePath("team", "Inglaterra", "PremierLeague", "sunderland.svg") },
      { id: "tottenham", name: "Tottenham", leagueId: "premier", countryCode: "GB", icon: imagePath("team", "Inglaterra", "PremierLeague", "tottenham.svg") },
      { id: "westham", name: "West Ham", leagueId: "premier", countryCode: "GB", icon: imagePath("team", "Inglaterra", "PremierLeague", "westham.svg") },
      { id: "wolves", name: "Wolves", leagueId: "premier", countryCode: "GB", icon: imagePath("team", "Inglaterra", "PremierLeague", "wolves.svg") },
      { id: "atalanta", name: "Atalanta", leagueId: "serie_a", countryCode: "IT", icon: imagePath("team", "Italia", "SerieA", "atalanta.svg") },
      { id: "bologna", name: "Bologna", leagueId: "serie_a", countryCode: "IT", icon: imagePath("team", "Italia", "SerieA", "bologna.svg") },
      { id: "cagliari", name: "Cagliari", leagueId: "serie_a", countryCode: "IT", icon: imagePath("team", "Italia", "SerieA", "cagliari.svg") },
      { id: "como", name: "Como", leagueId: "serie_a", countryCode: "IT", icon: imagePath("team", "Italia", "SerieA", "como.svg") },
      { id: "cremonese", name: "Cremonese", leagueId: "serie_a", countryCode: "IT", icon: imagePath("team", "Italia", "SerieA", "cremonese.svg") },
      { id: "fiorentina", name: "Fiorentina", leagueId: "serie_a", countryCode: "IT", icon: imagePath("team", "Italia", "SerieA", "fiorentina.svg") },
      { id: "genoa", name: "Genoa", leagueId: "serie_a", countryCode: "IT", icon: imagePath("team", "Italia", "SerieA", "genoa.svg") },
      { id: "inter", name: "Inter", leagueId: "serie_a", countryCode: "IT", icon: imagePath("team", "Italia", "SerieA", "inter.svg") },
      { id: "juventus", name: "Juventus", leagueId: "serie_a", countryCode: "IT", icon: imagePath("team", "Italia", "SerieA", "juventus.svg") },
      { id: "lazio", name: "Lazio", leagueId: "serie_a", countryCode: "IT", icon: imagePath("team", "Italia", "SerieA", "lazio.svg") },
      { id: "lecce", name: "Lecce", leagueId: "serie_a", countryCode: "IT", icon: imagePath("team", "Italia", "SerieA", "lecce.svg") },
      { id: "milan", name: "AC Milan", leagueId: "serie_a", countryCode: "IT", icon: imagePath("team", "Italia", "SerieA", "milan.svg") },
      { id: "napoli", name: "Napoli", leagueId: "serie_a", countryCode: "IT", icon: imagePath("team", "Italia", "SerieA", "napoli.svg") },
      { id: "parma", name: "Parma", leagueId: "serie_a", countryCode: "IT", icon: imagePath("team", "Italia", "SerieA", "parma.svg") },
      { id: "pisa", name: "Pisa", leagueId: "serie_a", countryCode: "IT", icon: imagePath("team", "Italia", "SerieA", "pisa.svg") },
      { id: "roma", name: "Roma", leagueId: "serie_a", countryCode: "IT", icon: imagePath("team", "Italia", "SerieA", "roma.svg") },
      { id: "sassuolo", name: "Sassuolo", leagueId: "serie_a", countryCode: "IT", icon: imagePath("team", "Italia", "SerieA", "sassuolo.svg") },
      { id: "torino", name: "Torino", leagueId: "serie_a", countryCode: "IT", icon: imagePath("team", "Italia", "SerieA", "torino.svg") },
      { id: "udinese", name: "Udinese", leagueId: "serie_a", countryCode: "IT", icon: imagePath("team", "Italia", "SerieA", "udinese.svg") },
      { id: "verona", name: "Verona", leagueId: "serie_a", countryCode: "IT", icon: imagePath("team", "Italia", "SerieA", "verona.svg") },
      { id: "avellino", name: "Avellino", leagueId: "serie_b", countryCode: "IT", icon: imagePath("team", "Italia", "SerieB", "avellino.svg") },
      { id: "bari", name: "Bari", leagueId: "serie_b", countryCode: "IT", icon: imagePath("team", "Italia", "SerieB", "bari.svg") },
      { id: "carrarese", name: "Carrarese", leagueId: "serie_b", countryCode: "IT", icon: imagePath("team", "Italia", "SerieB", "carrarese.svg") },
      { id: "catanzaro", name: "Catanzaro", leagueId: "serie_b", countryCode: "IT", icon: imagePath("team", "Italia", "SerieB", "catanzaro.svg") },
      { id: "cesena", name: "Cesena", leagueId: "serie_b", countryCode: "IT", icon: imagePath("team", "Italia", "SerieB", "cesena.svg") },
      { id: "empoli", name: "Empoli", leagueId: "serie_b", countryCode: "IT", icon: imagePath("team", "Italia", "SerieB", "empoli.svg") },
      { id: "frosinone", name: "Frosinone", leagueId: "serie_b", countryCode: "IT", icon: imagePath("team", "Italia", "SerieB", "frosinone.svg") },
      { id: "juvestabia", name: "Juve Stabia", leagueId: "serie_b", countryCode: "IT", icon: imagePath("team", "Italia", "SerieB", "juvestabia.svg") },
      { id: "mantova", name: "Mantova", leagueId: "serie_b", countryCode: "IT", icon: imagePath("team", "Italia", "SerieB", "mantova.svg") },
      { id: "modena", name: "Modena", leagueId: "serie_b", countryCode: "IT", icon: imagePath("team", "Italia", "SerieB", "modena.svg") },
      { id: "monza", name: "Monza", leagueId: "serie_b", countryCode: "IT", icon: imagePath("team", "Italia", "SerieB", "monza.svg") },
      { id: "padova", name: "Padova", leagueId: "serie_b", countryCode: "IT", icon: imagePath("team", "Italia", "SerieB", "padova.svg") },
      { id: "palermo", name: "Palermo", leagueId: "serie_b", countryCode: "IT", icon: imagePath("team", "Italia", "SerieB", "palermo.svg") },
      { id: "pescara", name: "Pescara", leagueId: "serie_b", countryCode: "IT", icon: imagePath("team", "Italia", "SerieB", "pescara.svg") },
      { id: "reggiana", name: "Reggiana", leagueId: "serie_b", countryCode: "IT", icon: imagePath("team", "Italia", "SerieB", "reggiana.svg") },
      { id: "sampdoria", name: "Sampdoria", leagueId: "serie_b", countryCode: "IT", icon: imagePath("team", "Italia", "SerieB", "sampdoria.svg") },
      { id: "spezia", name: "Spezia", leagueId: "serie_b", countryCode: "IT", icon: imagePath("team", "Italia", "SerieB", "spezia.svg") },
      { id: "suditrol", name: "Sud Tirol", leagueId: "serie_b", countryCode: "IT", icon: imagePath("team", "Italia", "SerieB", "suditrol.svg") },
      { id: "venezia", name: "Venezia", leagueId: "serie_b", countryCode: "IT", icon: imagePath("team", "Italia", "SerieB", "venezia.svg") },
      { id: "virtusentella", name: "Virtus Entella", leagueId: "serie_b", countryCode: "IT", icon: imagePath("team", "Italia", "SerieB", "virtusentella.svg") },
      { id: "birmingham", name: "Birmingham", leagueId: "championship", countryCode: "GB", icon: imagePath("team", "Inglaterra", "EFLChampionship", "birmingham.svg") },
      { id: "blackburn", name: "Blackburn", leagueId: "championship", countryCode: "GB", icon: imagePath("team", "Inglaterra", "EFLChampionship", "blackburn.svg") },
      { id: "bristolcity", name: "Bristol City", leagueId: "championship", countryCode: "GB", icon: imagePath("team", "Inglaterra", "EFLChampionship", "bristolcity.svg") },
      { id: "charlton", name: "Charlton", leagueId: "championship", countryCode: "GB", icon: imagePath("team", "Inglaterra", "EFLChampionship", "charlton.svg") },
      { id: "coventrycity", name: "Coventry City", leagueId: "championship", countryCode: "GB", icon: imagePath("team", "Inglaterra", "EFLChampionship", "coventrycity.svg") },
      { id: "derbycounty", name: "Derby County", leagueId: "championship", countryCode: "GB", icon: imagePath("team", "Inglaterra", "EFLChampionship", "derbycounty.svg") },
      { id: "hullcity", name: "Hull City", leagueId: "championship", countryCode: "GB", icon: imagePath("team", "Inglaterra", "EFLChampionship", "hullcity.svg") },
      { id: "ipswich", name: "Ipswich", leagueId: "championship", countryCode: "GB", icon: imagePath("team", "Inglaterra", "EFLChampionship", "ipswich.svg") },
      { id: "leicester", name: "Leicester", leagueId: "championship", countryCode: "GB", icon: imagePath("team", "Inglaterra", "EFLChampionship", "leicester.svg") },
      { id: "middlesbrough", name: "Middlesbrough", leagueId: "championship", countryCode: "GB", icon: imagePath("team", "Inglaterra", "EFLChampionship", "middlesbrough.svg") },
      { id: "millwall", name: "Millwall", leagueId: "championship", countryCode: "GB", icon: imagePath("team", "Inglaterra", "EFLChampionship", "millwall.svg") },
      { id: "norwichcity", name: "Norwich City", leagueId: "championship", countryCode: "GB", icon: imagePath("team", "Inglaterra", "EFLChampionship", "norwichcity.svg") },
      { id: "oxfordunited", name: "Oxford United", leagueId: "championship", countryCode: "GB", icon: imagePath("team", "Inglaterra", "EFLChampionship", "oxfordunited.svg") },
      { id: "portsmouth", name: "Portsmouth", leagueId: "championship", countryCode: "GB", icon: imagePath("team", "Inglaterra", "EFLChampionship", "portsmouth.svg") },
      { id: "prestonnorthend", name: "Preston North End", leagueId: "championship", countryCode: "GB", icon: imagePath("team", "Inglaterra", "EFLChampionship", "prestonnorthend.svg") },
      { id: "queensparkrangers", name: "Queens Park Rangers", leagueId: "championship", countryCode: "GB", icon: imagePath("team", "Inglaterra", "EFLChampionship", "queensparkrangers.svg") },
      { id: "sheffieldunited", name: "Sheffield United", leagueId: "championship", countryCode: "GB", icon: imagePath("team", "Inglaterra", "EFLChampionship", "sheffieldunited.svg") },
      { id: "sheffieldwednesday", name: "Sheffield Wednesday", leagueId: "championship", countryCode: "GB", icon: imagePath("team", "Inglaterra", "EFLChampionship", "sheffieldwednesday.svg") },
      { id: "southampton", name: "Southampton", leagueId: "championship", countryCode: "GB", icon: imagePath("team", "Inglaterra", "EFLChampionship", "southampton.svg") },
      { id: "stokecity", name: "Stoke City", leagueId: "championship", countryCode: "GB", icon: imagePath("team", "Inglaterra", "EFLChampionship", "stokecity.svg") },
      { id: "swanseacity", name: "Swansea City", leagueId: "championship", countryCode: "GB", icon: imagePath("team", "Inglaterra", "EFLChampionship", "swanseacity.svg") },
      { id: "watford", name: "Watford", leagueId: "championship", countryCode: "GB", icon: imagePath("team", "Inglaterra", "EFLChampionship", "watford.svg") },
      { id: "westbromwichalbion", name: "West Bromwich Albion", leagueId: "championship", countryCode: "GB", icon: imagePath("team", "Inglaterra", "EFLChampionship", "westbromwichalbion.svg") },
      { id: "wrexham", name: "Wrexham", leagueId: "championship", countryCode: "GB", icon: imagePath("team", "Inglaterra", "EFLChampionship", "wrexham.svg") },
    ];

    const teamDetails = {
      realmadrid: makeTeamDetail("Real Madrid", 68, 23, 198, 112, 56, 320, [
        ["09/03/2026", "LaLiga", "FC Barcelona", "home", "3-1", 3, 1, 8, 5, 2],
        ["02/03/2026", "Copa del Rey", "Atletico", "away", "2-2", 2, 2, 7, 4, 1],
        ["27/02/2026", "Champions", "PSG", "home", "1-0", 1, 0, 10, 3, 1],
        ["20/02/2026", "LaLiga", "Valencia", "away", "0-0", 0, 0, 5, 2, 3],
        ["16/02/2026", "LaLiga", "Sevilla", "home", "4-1", 4, 1, 12, 6, 2],
      ]),
      barcelona: makeTeamDetail("FC Barcelona", 60, 26, 182, 107, 60, 303, [
        ["09/03/2026", "LaLiga", "Real Madrid", "away", "1-3", 1, 3, 5, 8, 2],
        ["03/03/2026", "LaLiga", "Villarreal", "home", "2-0", 2, 0, 9, 2, 2],
        ["27/02/2026", "Champions", "Milan", "home", "1-1", 1, 1, 7, 4, 1],
        ["20/02/2026", "Copa del Rey", "Valencia", "home", "3-1", 3, 1, 11, 3, 2],
        ["15/02/2026", "LaLiga", "Athletic", "away", "0-0", 0, 0, 6, 5, 3],
      ]),
      atleticomadrid: makeTeamDetail("Atletico Madrid", 54, 24, 175, 109, 64, 338, [
        ["10/03/2026", "LaLiga", "Betis", "home", "2-0", 2, 0, 8, 2, 3],
        ["04/03/2026", "LaLiga", "Real Sociedad", "away", "1-1", 1, 1, 5, 6, 2],
        ["27/02/2026", "Champions", "Bayern", "home", "0-1", 0, 1, 6, 8, 4],
        ["22/02/2026", "Copa del Rey", "Sevilla", "away", "2-1", 2, 1, 7, 4, 1],
        ["17/02/2026", "LaLiga", "Getafe", "home", "1-0", 1, 0, 9, 3, 2],
      ]),
      realbetis: makeTeamDetail("Real Betis", 52, 28, 165, 118, 58, 315, [
        ["11/03/2026", "LaLiga", "Valencia", "away", "1-1", 1, 1, 6, 7, 2],
        ["05/03/2026", "LaLiga", "Girona", "home", "2-1", 2, 1, 8, 4, 1],
        ["28/02/2026", "Copa del Rey", "Oviedo", "home", "3-0", 3, 0, 9, 2, 1],
        ["23/02/2026", "LaLiga", "Sevilla", "away", "0-2", 0, 2, 4, 8, 3],
        ["18/02/2026", "LaLiga", "Elche", "home", "2-1", 2, 1, 7, 3, 2],
      ]),
      villarreal: makeTeamDetail("Villarreal", 49, 31, 158, 125, 52, 298, [
        ["10/03/2026", "LaLiga", "Barcelona", "away", "0-2", 0, 2, 5, 9, 2],
        ["04/03/2026", "LaLiga", "Athletic", "home", "1-1", 1, 1, 6, 6, 1],
        ["27/02/2026", "Europa League", "Roma", "away", "1-0", 1, 0, 4, 5, 2],
        ["22/02/2026", "LaLiga", "Real Sociedad", "home", "2-0", 2, 0, 7, 2, 1],
        ["17/02/2026", "LaLiga", "Valencia", "away", "0-1", 0, 1, 3, 6, 3],
      ]),
      athleticclub: makeTeamDetail("Athletic Bilbao", 47, 32, 152, 130, 61, 325, [
        ["11/03/2026", "LaLiga", "Barcelona", "home", "0-0", 0, 0, 5, 5, 2],
        ["06/03/2026", "LaLiga", "Villarreal", "away", "1-1", 1, 1, 6, 6, 1],
        ["01/03/2026", "Copa del Rey", "Levante", "home", "2-1", 2, 1, 8, 3, 2],
        ["24/02/2026", "LaLiga", "Getafe", "away", "1-0", 1, 0, 4, 4, 1],
        ["19/02/2026", "LaLiga", "Real Sociedad", "home", "2-1", 2, 1, 9, 2, 3],
      ]),
      realsociedad: makeTeamDetail("Real Sociedad", 50, 29, 163, 121, 55, 310, [
        ["12/03/2026", "LaLiga", "Atletico", "home", "1-1", 1, 1, 6, 5, 2],
        ["06/03/2026", "LaLiga", "Villarreal", "away", "0-2", 0, 2, 4, 7, 1],
        ["02/03/2026", "LaLiga", "Girona", "home", "2-0", 2, 0, 8, 3, 2],
        ["25/02/2026", "Copa del Rey", "Mallorca", "away", "1-0", 1, 0, 5, 2, 1],
        ["20/02/2026", "LaLiga", "Celta", "home", "3-1", 3, 1, 10, 4, 2],
      ]),
      valencia: makeTeamDetail("Valencia", 45, 33, 148, 135, 54, 308, [
        ["11/03/2026", "LaLiga", "Betis", "home", "1-1", 1, 1, 7, 6, 2],
        ["07/03/2026", "LaLiga", "Villarreal", "home", "1-0", 1, 0, 6, 3, 1],
        ["02/03/2026", "Copa del Rey", "Oviedo", "away", "2-1", 2, 1, 7, 4, 2],
        ["25/02/2026", "LaLiga", "Real Madrid", "home", "0-0", 0, 0, 4, 5, 1],
        ["20/02/2026", "LaLiga", "Levante", "away", "1-1", 1, 1, 5, 5, 3],
      ]),
      sevilla: makeTeamDetail("Sevilla", 46, 31, 152, 128, 57, 312, [
        ["12/03/2026", "LaLiga", "Real Madrid", "away", "1-4", 1, 4, 4, 12, 2],
        ["06/03/2026", "LaLiga", "Betis", "home", "2-0", 2, 0, 8, 3, 1],
        ["01/03/2026", "Europa League", "Roma", "home", "1-1", 1, 1, 6, 5, 2],
        ["24/02/2026", "LaLiga", "Girona", "away", "0-1", 0, 1, 3, 6, 2],
        ["19/02/2026", "LaLiga", "Getafe", "home", "2-1", 2, 1, 7, 4, 1],
      ]),
      girona: makeTeamDetail("Girona", 48, 30, 160, 124, 50, 305, [
        ["11/03/2026", "LaLiga", "Betis", "away", "1-2", 1, 2, 5, 8, 1],
        ["05/03/2026", "LaLiga", "Real Sociedad", "away", "0-2", 0, 2, 3, 8, 2],
        ["01/03/2026", "Copa del Rey", "Levante", "home", "2-0", 2, 0, 8, 2, 1],
        ["24/02/2026", "LaLiga", "Sevilla", "home", "1-0", 1, 0, 6, 3, 2],
        ["19/02/2026", "LaLiga", "Celta", "away", "2-1", 2, 1, 7, 4, 1],
      ]),
      getafe: makeTeamDetail("Getafe", 41, 38, 135, 145, 68, 345, [
        ["12/03/2026", "LaLiga", "Atletico", "away", "0-1", 0, 1, 3, 9, 2],
        ["07/03/2026", "LaLiga", "Athletic", "home", "0-1", 0, 1, 4, 4, 3],
        ["02/03/2026", "LaLiga", "Elche", "away", "1-1", 1, 1, 5, 5, 2],
        ["25/02/2026", "Copa del Rey", "Oviedo", "home", "2-0", 2, 0, 6, 2, 1],
        ["20/02/2026", "LaLiga", "Sevilla", "away", "1-2", 1, 2, 4, 7, 3],
      ]),
      rayovallecano: makeTeamDetail("Rayo Vallecano", 43, 36, 142, 138, 63, 320, [
        ["11/03/2026", "LaLiga", "Celta", "away", "1-2", 1, 2, 5, 7, 2],
        ["06/03/2026", "LaLiga", "Levante", "home", "2-1", 2, 1, 7, 4, 1],
        ["01/03/2026", "Copa del Rey", "Mallorca", "away", "0-0", 0, 0, 4, 4, 2],
        ["24/02/2026", "LaLiga", "Elche", "home", "1-0", 1, 0, 6, 2, 1],
        ["19/02/2026", "LaLiga", "Deportivo", "away", "2-1", 2, 1, 7, 5, 2],
      ]),
      espanyol: makeTeamDetail("RCD Espanyol", 39, 40, 128, 150, 65, 335, [
        ["12/03/2026", "LaLiga", "Girona", "away", "0-1", 0, 1, 3, 6, 2],
        ["06/03/2026", "LaLiga", "Oviedo", "home", "2-1", 2, 1, 7, 3, 1],
        ["01/03/2026", "Copa del Rey", "Levante", "away", "1-2", 1, 2, 4, 6, 3],
        ["25/02/2026", "LaLiga", "Elche", "home", "1-0", 1, 0, 6, 2, 1],
        ["20/02/2026", "LaLiga", "Mallorca", "away", "0-1", 0, 1, 3, 5, 2],
      ]),
      osasuna: makeTeamDetail("CA Osasuna", 42, 37, 140, 142, 59, 318, [
        ["11/03/2026", "LaLiga", "Levante", "away", "2-1", 2, 1, 7, 4, 1],
        ["07/03/2026", "LaLiga", "Oviedo", "home", "1-0", 1, 0, 5, 2, 2],
        ["02/03/2026", "Copa del Rey", "Mallorca", "away", "0-1", 0, 1, 4, 5, 1],
        ["25/02/2026", "LaLiga", "Celta", "home", "2-1", 2, 1, 8, 3, 2],
        ["20/02/2026", "LaLiga", "Deportivo", "away", "1-0", 1, 0, 6, 2, 1],
      ]),
      mallorca: makeTeamDetail("RCD Mallorca", 40, 39, 135, 148, 61, 330, [
        ["11/03/2026", "LaLiga", "Elche", "home", "1-1", 1, 1, 6, 6, 2],
        ["06/03/2026", "LaLiga", "Deportivo", "away", "2-0", 2, 0, 7, 2, 1],
        ["01/03/2026", "Copa del Rey", "Rayo Vallecano", "home", "0-0", 0, 0, 5, 5, 2],
        ["25/02/2026", "LaLiga", "Levante", "away", "0-1", 0, 1, 3, 6, 1],
        ["20/02/2026", "LaLiga", "Oviedo", "home", "2-1", 2, 1, 7, 3, 2],
      ]),
      celta: makeTeamDetail("RC Celta", 44, 35, 148, 135, 56, 310, [
        ["12/03/2026", "LaLiga", "Rayo Vallecano", "home", "2-1", 2, 1, 8, 4, 1],
        ["07/03/2026", "LaLiga", "Osasuna", "away", "1-2", 1, 2, 4, 8, 2],
        ["02/03/2026", "Copa del Rey", "Elche", "home", "3-1", 3, 1, 9, 2, 1],
        ["25/02/2026", "LaLiga", "Girona", "home", "1-2", 1, 2, 6, 7, 2],
        ["20/02/2026", "LaLiga", "Real Sociedad", "away", "1-3", 1, 3, 5, 10, 2],
      ]),
      levante: makeTeamDetail("Levante", 38, 41, 132, 152, 62, 328, [
        ["12/03/2026", "LaLiga", "Osasuna", "home", "1-2", 1, 2, 5, 7, 2],
        ["06/03/2026", "LaLiga", "Rayo Vallecano", "away", "1-2", 1, 2, 4, 7, 2],
        ["01/03/2026", "Copa del Rey", "Espanyol", "home", "2-1", 2, 1, 7, 3, 1],
        ["25/02/2026", "LaLiga", "Mallorca", "home", "1-0", 1, 0, 6, 3, 2],
        ["20/02/2026", "LaLiga", "Valencia", "home", "1-1", 1, 1, 5, 5, 1],
      ]),
      elche: makeTeamDetail("Elche", 36, 43, 125, 158, 66, 340, [
        ["12/03/2026", "LaLiga", "Mallorca", "away", "1-1", 1, 1, 4, 6, 2],
        ["07/03/2026", "LaLiga", "Getafe", "home", "1-1", 1, 1, 6, 5, 1],
        ["02/03/2026", "Copa del Rey", "Celta", "away", "1-3", 1, 3, 3, 9, 2],
        ["25/02/2026", "LaLiga", "Espanyol", "away", "0-1", 0, 1, 2, 6, 2],
        ["20/02/2026", "LaLiga", "Betis", "away", "1-2", 1, 2, 4, 7, 2],
      ]),
      deportivo: makeTeamDetail("RC Deportivo", 37, 42, 130, 155, 64, 335, [
        ["11/03/2026", "LaLiga", "Mallorca", "home", "0-2", 0, 2, 3, 7, 2],
        ["06/03/2026", "LaLiga", "Oviedo", "away", "1-0", 1, 0, 5, 2, 1],
        ["01/03/2026", "Copa del Rey", "Celta", "away", "1-1", 1, 1, 4, 5, 2],
        ["24/02/2026", "LaLiga", "Osasuna", "home", "1-1", 1, 1, 6, 6, 2],
        ["19/02/2026", "LaLiga", "Rayo Vallecano", "home", "1-2", 1, 2, 5, 7, 1],
      ]),
      oviedo: makeTeamDetail("Real Oviedo", 35, 44, 120, 162, 69, 345, [
        ["12/03/2026", "LaLiga", "Deportivo", "home", "0-1", 0, 1, 3, 5, 2],
        ["07/03/2026", "LaLiga", "Espanyol", "away", "1-2", 1, 2, 4, 7, 2],
        ["02/03/2026", "Copa del Rey", "Valencia", "home", "1-2", 1, 2, 5, 7, 2],
        ["25/02/2026", "LaLiga", "Getafe", "away", "0-2", 0, 2, 2, 6, 2],
        ["20/02/2026", "LaLiga", "Mallorca", "away", "1-2", 1, 2, 4, 7, 3],
      ]),
    };

    return { countries, leagues, teams, teamDetails };
  }

  function makeTeamDetail(name, goalsFor, goalsAgainst, cornersFor, cornersAgainst, yellowCards, fouls, matches) {
    const all = {
      matches: 28,
      goalsFor,
      goalsAgainst,
      cornersFor,
      cornersAgainst,
      yellowCards,
      fouls,
    };

    const home = {
      matches: 14,
      goalsFor: Math.round(all.goalsFor * 0.58),
      goalsAgainst: Math.round(all.goalsAgainst * 0.46),
      cornersFor: Math.round(all.cornersFor * 0.56),
      cornersAgainst: Math.round(all.cornersAgainst * 0.47),
      yellowCards: Math.round(all.yellowCards * 0.45),
      fouls: Math.round(all.fouls * 0.48),
    };

    const away = {
      matches: 14,
      goalsFor: all.goalsFor - home.goalsFor,
      goalsAgainst: all.goalsAgainst - home.goalsAgainst,
      cornersFor: all.cornersFor - home.cornersFor,
      cornersAgainst: all.cornersAgainst - home.cornersAgainst,
      yellowCards: all.yellowCards - home.yellowCards,
      fouls: all.fouls - home.fouls,
    };

    const competitions = [...new Set(matches.map((match) => match[1]))];
    const byCompetition = {};

    competitions.forEach((competitionName, index) => {
      const factor = 0.6 - index * 0.08;
      byCompetition[competitionName] = {
        matches: Math.max(4, Math.round(all.matches * factor)),
        goalsFor: Math.max(4, Math.round(all.goalsFor * factor)),
        goalsAgainst: Math.max(2, Math.round(all.goalsAgainst * factor)),
        cornersFor: Math.max(12, Math.round(all.cornersFor * factor)),
        cornersAgainst: Math.max(10, Math.round(all.cornersAgainst * factor)),
        yellowCards: Math.max(7, Math.round(all.yellowCards * factor)),
        fouls: Math.max(40, Math.round(all.fouls * factor)),
      };
    });

    return {
      name,
      competitions,
      stats: {
        all,
        home,
        away,
        byCompetition,
      },
      recentMatches: matches.map((m) => ({
        date: m[0],
        competition: m[1],
        opponent: m[2],
        homeAway: m[3],
        score: m[4],
        goalsFor: m[5],
        goalsAgainst: m[6],
        cornersFor: m[7],
        cornersAgainst: m[8],
        yellowCards: m[9],
      })),
      updatedAt: new Date().toISOString(),
    };
  }
})();
