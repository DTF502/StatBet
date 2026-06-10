/* Datos y servicio compartidos entre index.html y stats.html */
(function () {
  const db = createMockDatabase();

  const api = {
    async getCountries() {
      return db.countries;
    },
    async getLeaguesByCountry(countryCode) {
      return db.leagues.filter((league) => league.countryCode === countryCode);
    },
    async getTeamsByLeague(leagueId) {
      return db.teams.filter((team) => team.leagueId === leagueId);
    },
    async searchTeam(query) {
      const normalized = normalize(query);
      return db.teams.find((team) => normalize(team.name).includes(normalized)) || null;
    },
    async getTeamById(teamId) {
      return db.teams.find((team) => team.id === teamId) || null;
    },
    async getLeagueById(leagueId) {
      return db.leagues.find((league) => league.id === leagueId) || null;
    },
    async getTeamStats(teamId, context, competition) {
      return {
        teamName: teamId,
        sourceStats: { matches: 0, goalsFor: 0, goalsAgainst: 0, cornersFor: 0, cornersAgainst: 0, yellowCards: 0, fouls: 0 },
        recentMatches: [],
        updatedAt: new Date().toISOString(),
        competitions: [],
      };
    },
  };

  window.StatBetData = {
    api,
    helpers: { normalize, formatDateTime },
  };

  function normalize(value) {
    return value.toLowerCase().normalize("NFD").replace(/[\u0300-\u036f]/g, "");
  }

  function formatDateTime(isoDate) {
    const date = new Date(isoDate);
    if (Number.isNaN(date.getTime())) return "--:--";
    return date.toLocaleString("es-ES", { day: "2-digit", month: "2-digit", hour: "2-digit", minute: "2-digit" });
  }

  function imagePath(type, country, filename) {
    if (type === "country") return `images/${filename}`;
    if (type === "league") return `images/${country}/${filename}`;
    return "";
  }

  function createMockDatabase() {
    const countries = [
      { code: "ES", name: "España",       icon: imagePath("country", null, "españa.svg") },
      { code: "GB", name: "Inglaterra",  icon: imagePath("country", null, "inglaterra.svg") },
      { code: "IT", name: "Italia",       icon: imagePath("country", null, "italia.svg") },
      { code: "DE", name: "Alemania",     icon: imagePath("country", null, "alemania.svg") },
      { code: "FR", name: "Francia",      icon: imagePath("country", null, "francia.svg") },
      { code: "PT", name: "Portugal",     icon: imagePath("country", null, "portugal.svg") },
      { code: "NL", name: "Países Bajos", icon: imagePath("country", null, "paisesbajos.svg") },
    ];

    const leagues = [
      { id: "la_liga",      name: "LaLiga",          countryCode: "ES", icon: imagePath("league", "España",        "laliga.svg") },
      { id: "laliga2",      name: "LaLiga 2",         countryCode: "ES", icon: imagePath("league", "España",        "laliga2.svg") },
      { id: "premier",      name: "Premier League",   countryCode: "GB", icon: imagePath("league", "Inglaterra",    "premierleague.svg") },
      { id: "championship", name: "Championship",     countryCode: "GB", icon: imagePath("league", "Inglaterra",    "eflchampionship.svg") },
      { id: "serie_a",      name: "Serie A",          countryCode: "IT", icon: imagePath("league", "Italia",        "seriea.svg") },
      { id: "serie_b",      name: "Serie B",          countryCode: "IT", icon: imagePath("league", "Italia",        "serieb.svg") },
      { id: "bundesliga",   name: "Bundesliga",       countryCode: "DE", icon: imagePath("league", "Alemania",      "bundelisga.svg") },
      { id: "bundesliga2",  name: "2. Bundesliga",    countryCode: "DE", icon: imagePath("league", "Alemania",      "bundesliga2.svg") },
      { id: "ligue1",       name: "Ligue 1",          countryCode: "FR", icon: imagePath("league", "Francia",       "ligue1.svg") },
      { id: "ligue2",       name: "Ligue 2",          countryCode: "FR", icon: imagePath("league", "Francia",       "ligue2.svg") },
      { id: "ligaportugal", name: "Liga Portugal",    countryCode: "PT", icon: imagePath("league", "Portugal",      "ligaportugal.svg") },
      { id: "eredivisie",   name: "Eredivisie",       countryCode: "NL", icon: imagePath("league", "Países Bajos",  "eredivisie.svg") },
    ];

    // Los equipos se cargan desde la BD via statbet_api.php
    const teams = [];

    return { countries, leagues, teams };
  }
})();

/* ─────────────────────────────────────────────
   Override BD local StatBet
   Usa MySQL mediante backend/statbet_api.php
───────────────────────────────────────────── */
(function () {
  if (!window.StatBetData || !window.StatBetData.api) return;

  const originalApi = window.StatBetData.api;

  const leagueToDivision = {
    premier:      "E0",
    championship: "E1",
    la_liga:      "SP1",
    laliga2:      "SP2",
    serie_a:      "I1",
    serie_b:      "I2",
    bundesliga:   "D1",
    bundesliga2:  "D2",
    ligue1:       "F1",
    ligue2:       "F2",
    ligaportugal: "P1",
    eredivisie:   "N1",
  };

  const divisionToCountry = {
    E0: "GB", E1: "GB",
    SP1: "ES", SP2: "ES",
    I1: "IT",  I2: "IT",
    D1: "DE",  D2: "DE",
    F1: "FR",  F2: "FR",
    P1: "PT",
    N1: "NL",
  };

  function normalizeId(value) {
    return String(value || "")
      .toLowerCase()
      .normalize("NFD")
      .replace(/[\u0300-\u036f]/g, "")
      .replace(/[^a-z0-9]/g, "");
  }

  async function fetchJson(url) {
    const res = await fetch(url);
    if (!res.ok) throw new Error(`HTTP ${res.status}`);
    return await res.json();
  }

  originalApi.getTeamsByLeague = async function (leagueId) {
    const division = leagueToDivision[leagueId];
    if (!division) return [];

    try {
      const json = await fetchJson(`backend/statbet_api.php?action=teams&division=${encodeURIComponent(division)}`);
      if (!json.ok || !Array.isArray(json.teams)) return [];

      return json.teams.map((item) => ({
        id: normalizeId(item.team_name),
        name: item.team_name,
        leagueId,
        countryCode: divisionToCountry[division] || "",
        icon: item.icon_path || "",
      }));
    } catch (e) {
      console.error("Error cargando equipos desde BD:", e);
      return [];
    }
  };

  originalApi.getTeamById = async function (teamId) {
    try {
      const json = await fetchJson(`backend/statbet_api.php?action=teamById&team=${encodeURIComponent(teamId)}`);
      if (json.ok && json.team) return json.team;
    } catch (e) {}
    return { id: teamId, name: teamId, leagueId: "", countryCode: "", icon: "" };
  };

  originalApi.searchTeam = async function (query) {
    try {
      const json = await fetchJson(`backend/statbet_api.php?action=searchTeam&q=${encodeURIComponent(query)}`);
      if (json.ok && json.team) return json.team;
    } catch (e) {}
    return null;
  };

  originalApi.getTeamStats = async function (teamId, context = "all", competition = "all") {
    try {
      const url = `backend/statbet_api.php?action=teamStats`
        + `&team=${encodeURIComponent(teamId)}`
        + `&context=${encodeURIComponent(context)}`
        + `&competition=${encodeURIComponent(competition)}`;

      const json = await fetchJson(url);
      if (json.ok) return json;
      console.warn("La BD no encontró estadísticas:", json);
    } catch (e) {
      console.error("Error cargando stats desde BD:", e);
    }

    return {
      teamName: teamId,
      sourceStats: { matches: 0, goalsFor: 0, goalsAgainst: 0, cornersFor: 0, cornersAgainst: 0, yellowCards: 0, fouls: 0 },
      recentMatches: [],
      updatedAt: new Date().toISOString(),
      competitions: [],
    };
  };
})();