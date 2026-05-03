/* Servicio de datos StatBet conectado al dataset Kaggle/Transfermarkt mediante PHP.
   No usa MySQL: backend/kaggle_api.php lee los CSV directamente. */
(function () {
  const KAGGLE_ENDPOINT = "backend/kaggle_api.php";
  const statsCache = new Map();
  const teamCache = new Map();

  const countries = [
    { code: "ES", name: "España", icon: "images/españa.svg" },
    { code: "GB", name: "Reino Unido", icon: "images/inglaterra.svg" },
    { code: "IT", name: "Italia", icon: "images/italia.svg" },
  ];

  const leagues = [
    { id: "la_liga", name: "LaLiga", countryCode: "ES", competitionId: "ES1", icon: "images/España/laliga.svg" },
    { id: "premier", name: "Premier League", countryCode: "GB", competitionId: "GB1", icon: "images/Inglaterra/premierleague.svg" },
    { id: "serie_a", name: "Serie A", countryCode: "IT", competitionId: "IT1", icon: "images/Italia/seriea.svg" },
  ];

  // Para que sigan funcionando URLs antiguas como stats.html?team=arsenal.
  const legacyTeams = {
    realmadrid: { name: "Real Madrid", leagueId: "la_liga", countryCode: "ES", icon: "images/España/LaLiga/realmadrid.svg" },
    barcelona: { name: "Barcelona", leagueId: "la_liga", countryCode: "ES", icon: "images/España/LaLiga/barcelona.svg" },
    atleticomadrid: { name: "Atlético Madrid", leagueId: "la_liga", countryCode: "ES", icon: "images/España/LaLiga/atleticomadrid.svg" },
    realbetis: { name: "Real Betis", leagueId: "la_liga", countryCode: "ES", icon: "images/España/LaLiga/realbetis.svg" },
    valencia: { name: "Valencia", leagueId: "la_liga", countryCode: "ES", icon: "images/España/LaLiga/valencia.svg" },
    sevilla: { name: "Sevilla", leagueId: "la_liga", countryCode: "ES", icon: "images/España/LaLiga/sevilla.svg" },
    arsenal: { name: "Arsenal", leagueId: "premier", countryCode: "GB", icon: "images/Inglaterra/PremierLeague/arsenal.svg" },
    chelsea: { name: "Chelsea", leagueId: "premier", countryCode: "GB", icon: "images/Inglaterra/PremierLeague/chelsea.svg" },
    liverpool: { name: "Liverpool", leagueId: "premier", countryCode: "GB", icon: "images/Inglaterra/PremierLeague/liverpool.svg" },
    mancity: { name: "Manchester City", leagueId: "premier", countryCode: "GB", icon: "images/Inglaterra/PremierLeague/mancity.svg" },
    manunited: { name: "Manchester United", leagueId: "premier", countryCode: "GB", icon: "images/Inglaterra/PremierLeague/manunited.svg" },
    juventus: { name: "Juventus", leagueId: "serie_a", countryCode: "IT", icon: "images/Italia/SerieA/juventus.svg" },
    inter: { name: "Inter", leagueId: "serie_a", countryCode: "IT", icon: "images/Italia/SerieA/inter.svg" },
    milan: { name: "Milan", leagueId: "serie_a", countryCode: "IT", icon: "images/Italia/SerieA/milan.svg" },
    napoli: { name: "Napoli", leagueId: "serie_a", countryCode: "IT", icon: "images/Italia/SerieA/napoli.svg" },
    roma: { name: "Roma", leagueId: "serie_a", countryCode: "IT", icon: "images/Italia/SerieA/roma.svg" },
  };

  const api = {
    async getCountries() {
      return countries;
    },

    async getLeaguesByCountry(countryCode) {
      return leagues.filter((league) => league.countryCode === countryCode);
    },

    async getTeamsByLeague(leagueId) {
      const league = getLeague(leagueId);
      if (!league) return [];

      try {
        const json = await getJSON(`${KAGGLE_ENDPOINT}?action=clubs&competition=${encodeURIComponent(league.competitionId)}&league_id=${encodeURIComponent(league.id)}`);
        return (json.results || []).map((team) => ({
          id: team.id,
          club_id: team.club_id,
          name: team.name,
          leagueId: league.id,
          countryCode: league.countryCode,
          icon: findLegacyIcon(team.name, league.id),
        }));
      } catch (error) {
        console.error("Error cargando equipos desde Kaggle:", error);
        return [];
      }
    },

    async searchTeam(query) {
      const normalizedQuery = normalize(query);
      const local = Object.entries(legacyTeams).find(([, team]) => normalize(team.name).includes(normalizedQuery));
      if (local) return { id: local[0], ...local[1] };

      try {
        const json = await getJSON(`${KAGGLE_ENDPOINT}?action=searchClub&q=${encodeURIComponent(query)}`);
        const league = leagues.find((item) => item.competitionId === json.competition_id) || leagues[0];
        return {
          id: json.id,
          club_id: json.club_id,
          name: json.name,
          leagueId: league.id,
          countryCode: league.countryCode,
          icon: findLegacyIcon(json.name, league.id),
        };
      } catch (error) {
        return null;
      }
    },

    async getTeamById(teamId) {
      if (teamCache.has(teamId)) return teamCache.get(teamId);

      if (teamId.startsWith("kg_")) {
        try {
          const clubId = teamId.replace("kg_", "");
          const json = await getJSON(`${KAGGLE_ENDPOINT}?action=clubById&club_id=${encodeURIComponent(clubId)}`);
          const league = leagues.find((item) => item.competitionId === json.competition_id) || leagues[0];
          const team = {
            id: json.id,
            club_id: json.club_id,
            name: json.name,
            leagueId: league.id,
            countryCode: league.countryCode,
            icon: findLegacyIcon(json.name, league.id),
          };
          teamCache.set(teamId, team);
          return team;
        } catch (error) {
          console.error("Error resolviendo equipo:", error);
          return null;
        }
      }

      if (legacyTeams[teamId]) {
        const team = { id: teamId, ...legacyTeams[teamId] };
        teamCache.set(teamId, team);
        return team;
      }

      return null;
    },

    async getLeagueById(leagueId) {
      return getLeague(leagueId) || null;
    },

    async getTeamStats(teamId, context, competition) {
      const cacheKey = `${teamId}|${context || "all"}|${competition || "all"}`;
      if (statsCache.has(cacheKey)) return statsCache.get(cacheKey);

      const team = await this.getTeamById(teamId);
      if (!team) return null;

      const league = getLeague(team.leagueId);
      const params = new URLSearchParams({
        action: "displayTeamStats",
        team_id: team.id,
        team_name: team.name,
        domestic_competition: league?.competitionId || "",
        context: context || "all",
        competition: competition || "all",
        season: "latest",
      });

      try {
        const json = await getJSON(`${KAGGLE_ENDPOINT}?${params.toString()}`);
        const payload = {
          ...json,
          teamName: json.teamName || team.name,
          recentMatches: json.recentMatches || [],
          competitions: json.competitions || [],
          updatedAt: json.updatedAt || new Date().toISOString(),
        };
        statsCache.set(cacheKey, payload);
        return payload;
      } catch (error) {
        console.error("Error cargando estadísticas Kaggle:", error);
        return {
          teamName: team.name,
          sourceStats: emptyStats(),
          recentMatches: [],
          competitions: [],
          updatedAt: new Date().toISOString(),
        };
      }
    },
  };

  window.StatBetData = {
    api,
    helpers: {
      normalize,
      formatDateTime,
      isUnavailable,
    },
  };

  function getLeague(leagueId) {
    return leagues.find((league) => league.id === leagueId);
  }

  async function getJSON(url) {
    const response = await fetch(url);
    const json = await response.json();
    if (!response.ok || json.error) {
      throw new Error(json.message || `Error HTTP ${response.status}`);
    }
    return json;
  }

  function normalize(value) {
    return String(value || "")
      .toLowerCase()
      .normalize("NFD")
      .replace(/[\u0300-\u036f]/g, "")
      .replace(/football club|club de futbol|futbol club|cf|fc/g, "")
      .replace(/[^a-z0-9]+/g, " ")
      .trim();
  }

  function findLegacyIcon(name, leagueId) {
    const wanted = normalize(name);
    const item = Object.values(legacyTeams).find(
      (team) => team.leagueId === leagueId && (normalize(team.name) === wanted || wanted.includes(normalize(team.name)) || normalize(team.name).includes(wanted))
    );
    return item?.icon || "";
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

  function isUnavailable(value) {
    return value === null || value === undefined || Number.isNaN(value);
  }

  function emptyStats() {
    return {
      matches: 0,
      goalsFor: 0,
      goalsAgainst: 0,
      cornersFor: null,
      cornersAgainst: null,
      yellowCards: 0,
      fouls: null,
    };
  }
})();
