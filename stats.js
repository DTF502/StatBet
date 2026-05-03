const params = new URLSearchParams(window.location.search);

const state = {
  teamId: params.get("team") || "",
  context: "all",
  competition: "all",
  dateRange: "all",
  dateStart: null,
  dateEnd: null,
  charts: {},
};

const sel = {
  teamTitle:     document.getElementById("teamTitle"),
  teamLogo:      document.getElementById("teamLogo"),
  teamLogoPlaceholder: document.getElementById("teamLogoPlaceholder"),
  statsMeta:     document.getElementById("statsMeta"),
  homeAway:      document.getElementById("homeAwaySelect"),
  competition:   document.getElementById("competitionSelect"),
  dateRange:     document.getElementById("dateRangeSelect"),
  dateStart:     document.getElementById("dateStart"),
  dateEnd:       document.getElementById("dateEnd"),
  dateError:     document.getElementById("dateError"),
  dateRangePicker: document.getElementById("dateRangePicker"),
  recentMatches: document.getElementById("recentMatches"),
  stats: {
    matches:         document.getElementById("statMatches"),
    goalsFor:        document.getElementById("statGoalsFor"),
    goalsAgainst:    document.getElementById("statGoalsAgainst"),
    cornersFor:      document.getElementById("statCornersFor"),
    cornersAgainst:  document.getElementById("statCornersAgainst"),
    yellowCards:     document.getElementById("statYellowCards"),
    fouls:           document.getElementById("statFouls"),
    updatedAt:       document.getElementById("statUpdatedAt"),
  },
  avg: {
    goalsFor:       document.getElementById("avgGoalsFor"),
    goalsAgainst:   document.getElementById("avgGoalsAgainst"),
    cornersFor:     document.getElementById("avgCornersFor"),
    cornersAgainst: document.getElementById("avgCornersAgainst"),
    yellowCards:    document.getElementById("avgYellowCards"),
    fouls:          document.getElementById("avgFouls"),
  },
};

const CHART_DEFAULTS = {
  responsive: true,
  maintainAspectRatio: true,
  plugins: { legend: { display: true, position: "bottom", labels: { font: { size: 11, family: "Manrope" }, boxWidth: 12, padding: 12 } } },
  scales: {
    x: { grid: { display: false }, ticks: { font: { size: 10, family: "Manrope" }, maxRotation: 30 } },
    y: { grid: { color: "#f0f2f5" }, ticks: { font: { size: 10, family: "Manrope" }, stepSize: 1 }, beginAtZero: true },
  },
};

function makeBar(ctx, labels, datasets) {
  return new Chart(ctx, {
    type: "bar",
    data: { labels, datasets },
    options: {
      ...CHART_DEFAULTS,
      scales: {
        ...CHART_DEFAULTS.scales,
        x: { ...CHART_DEFAULTS.scales.x, stacked: false },
        y: { ...CHART_DEFAULTS.scales.y, stacked: false },
      },
    },
  });
}

function destroyCharts() {
  Object.values(state.charts).forEach((c) => c && c.destroy());
  state.charts = {};
}

async function init() {
  if (!state.teamId) { window.location.href = "index.html"; return; }

  const team = await window.StatBetData.api.getTeamById(state.teamId);
  if (!team) { window.location.href = "index.html"; return; }

  if (team.icon) {
    sel.teamLogo.src = team.icon;
    sel.teamLogo.alt = team.name;
    sel.teamLogo.classList.remove("hidden");
  } else {
    sel.teamLogoPlaceholder.classList.remove("hidden");
  }

  sel.teamTitle.textContent = team.name;
  await populateCompetitionFilter();
  bindEvents();
  await renderStats();
}

function bindEvents() {
  sel.homeAway.addEventListener("change", () => { state.context = sel.homeAway.value; renderStats(); });
  sel.competition.addEventListener("change", () => { state.competition = sel.competition.value; renderStats(); });
  sel.dateRange.addEventListener("change", onDateRangeChange);
  sel.dateStart.addEventListener("change", onCustomDateChange);
  sel.dateEnd.addEventListener("change", onCustomDateChange);
}

function onDateRangeChange() {
  state.dateRange = sel.dateRange.value;
  clearDateError();

  if (state.dateRange === "custom") {
    sel.dateRangePicker.classList.remove("hidden");
    initializeCustomDates();
  } else {
    sel.dateRangePicker.classList.add("hidden");
    state.dateStart = null;
    state.dateEnd = null;
  }
  renderStats();
}

function initializeCustomDates() {
  const today = new Date();
  sel.dateEnd.valueAsDate = today;

  const startDate = new Date(today);
  startDate.setDate(startDate.getDate() - 30);
  sel.dateStart.valueAsDate = startDate;
}

function onCustomDateChange() {
  clearDateError();
  const start = sel.dateStart.valueAsDate;
  const end = sel.dateEnd.valueAsDate;

  if (start && end && start > end) {
    showDateError();
    return;
  }

  state.dateStart = start;
  state.dateEnd = end;
  renderStats();
}

function showDateError() { sel.dateError.classList.remove("hidden"); }
function clearDateError() { sel.dateError.classList.add("hidden"); }

async function populateCompetitionFilter() {
  const data = await window.StatBetData.api.getTeamStats(state.teamId, "all", "all");
  const competitions = data?.competitions || [];

  sel.competition.innerHTML = "";
  addOption(sel.competition, "all", "Todas las competiciones", false, true);
  competitions.forEach((comp) => addOption(sel.competition, comp, comp, false, false));

  state.competition = "all";
  sel.competition.value = state.competition;
  initializeCustomDates();
}

async function renderStats() {
  const payload = await window.StatBetData.api.getTeamStats(state.teamId, state.context, state.competition);
  if (!payload) return;

  const allMatches = payload.recentMatches || [];
  const filteredMatches = filterMatchesByDateRange(allMatches);
  const computedStats = computeStatsFromMatches(filteredMatches, payload.sourceStats);
  const last5 = filteredMatches.slice(0, 5);

  renderKpis(computedStats, payload.updatedAt);
  renderRecentMatches(last5);
  renderMeta(payload.teamName, payload.season);
  renderCharts(last5, computedStats);
}

function filterMatchesByDateRange(matches) {
  if (state.dateRange === "all") return matches;

  const now = new Date();
  let rangeStart = new Date(now);

  if (state.dateRange === "week") {
    rangeStart.setDate(rangeStart.getDate() - 7);
  } else if (state.dateRange === "month") {
    rangeStart.setMonth(rangeStart.getMonth() - 1);
  } else if (state.dateRange === "custom") {
    if (!state.dateStart || !state.dateEnd) return [];
    rangeStart = state.dateStart;
    now.setTime(state.dateEnd.getTime());
  }

  rangeStart.setHours(0, 0, 0, 0);
  now.setHours(23, 59, 59, 999);

  return matches.filter((m) => {
    const matchDate = parseDate(m.date);
    return matchDate >= rangeStart && matchDate <= now;
  });
}

function parseDate(dateString) {
  if (!dateString) return new Date(0);
  if (dateString.includes("-")) return new Date(dateString);
  const [day, month, year] = dateString.split("/").map(Number);
  return new Date(year, month - 1, day);
}

function computeStatsFromMatches(matches, seasonStats) {
  if (!matches.length) {
    return { matches: 0, goalsFor: 0, goalsAgainst: 0, cornersFor: null, cornersAgainst: null, yellowCards: 0, fouls: null };
  }

  const cornersAvailable = matches.some((m) => m.cornersFor !== null && m.cornersFor !== undefined);
  const foulsAvailable = matches.some((m) => m.fouls !== null && m.fouls !== undefined);

  return {
    matches: matches.length,
    goalsFor: sum(matches, "goalsFor"),
    goalsAgainst: sum(matches, "goalsAgainst"),
    cornersFor: cornersAvailable ? sum(matches, "cornersFor") : null,
    cornersAgainst: cornersAvailable ? sum(matches, "cornersAgainst") : null,
    yellowCards: sum(matches, "yellowCards"),
    fouls: foulsAvailable ? sum(matches, "fouls") : null,
  };
}

function sum(matches, key) {
  return matches.reduce((total, item) => total + (Number(item[key]) || 0), 0);
}

function renderKpis(stats, updatedAt) {
  const m = stats.matches || 1;
  sel.stats.matches.textContent        = stats.matches;
  sel.stats.goalsFor.textContent       = displayValue(stats.goalsFor);
  sel.stats.goalsAgainst.textContent   = displayValue(stats.goalsAgainst);
  sel.stats.cornersFor.textContent     = displayValue(stats.cornersFor);
  sel.stats.cornersAgainst.textContent = displayValue(stats.cornersAgainst);
  sel.stats.yellowCards.textContent    = displayValue(stats.yellowCards);
  sel.stats.fouls.textContent          = displayValue(stats.fouls);
  sel.stats.updatedAt.textContent      = window.StatBetData.helpers.formatDateTime(updatedAt);

  sel.avg.goalsFor.textContent       = avg(stats.goalsFor, m) + " / partido";
  sel.avg.goalsAgainst.textContent   = avg(stats.goalsAgainst, m) + " / partido";
  sel.avg.cornersFor.textContent     = avg(stats.cornersFor, m) + " / partido";
  sel.avg.cornersAgainst.textContent = avg(stats.cornersAgainst, m) + " / partido";
  sel.avg.yellowCards.textContent    = avg(stats.yellowCards, m) + " / partido";
  sel.avg.fouls.textContent          = avg(stats.fouls, m) + " / partido";
}

function displayValue(value) {
  return value === null || value === undefined || Number.isNaN(value) ? "N/D" : value;
}

function avg(value, matches) {
  if (value === null || value === undefined || Number.isNaN(value)) return "N/D";
  return matches > 0 ? (value / matches).toFixed(1) : "0.0";
}

function renderRecentMatches(matches) {
  sel.recentMatches.innerHTML = "";

  if (!matches.length) {
    const row = document.createElement("tr");
    row.className = "empty-row";
    row.innerHTML = '<td colspan="9">No hay partidos para los filtros seleccionados.</td>';
    sel.recentMatches.appendChild(row);
    return;
  }

  matches.forEach((match) => {
    const result = getResult(match.goalsFor, match.goalsAgainst);
    const row = document.createElement("tr");
    row.innerHTML = `
      <td>${match.date}</td>
      <td>${match.competition}</td>
      <td><strong>${match.opponent}</strong></td>
      <td><span class="context-pill context-pill--${match.homeAway}">${match.homeAway === "home" ? "Local" : "Visitante"}</span></td>
      <td><span class="result-badge result-badge--${result}">${result}</span></td>
      <td class="score-cell">${match.score}</td>
      <td>${match.goalsFor}–${match.goalsAgainst}</td>
      <td>${displayPair(match.cornersFor, match.cornersAgainst)}</td>
      <td>${displayValue(match.yellowCards)}</td>
    `;
    sel.recentMatches.appendChild(row);
  });
}

function displayPair(a, b) {
  if (a === null || a === undefined || b === null || b === undefined) return "N/D";
  return `${a}–${b}`;
}

function getResult(goalsFor, goalsAgainst) {
  if (goalsFor > goalsAgainst) return "W";
  if (goalsFor < goalsAgainst) return "L";
  return "D";
}

function renderMeta(teamName, season) {
  const ctxLabel = state.context === "all" ? "General" : state.context === "home" ? "Local" : "Visitante";
  const compLabel = state.competition === "all" ? "Todas las competiciones" : state.competition;
  const seasonLabel = season ? `Temporada ${season}/${String(Number(season) + 1).slice(-2)}` : "Última temporada";
  sel.statsMeta.textContent = `${ctxLabel} | ${compLabel} | ${seasonLabel}`;
}

function renderCharts(matches, seasonStats) {
  destroyCharts();

  const labels = matches.length
    ? matches.map((m) => m.opponent.length > 10 ? m.opponent.slice(0, 10) + "…" : m.opponent)
    : [];

  state.charts.goals = makeBar(
    document.getElementById("chartGoals"),
    labels,
    [
      { label: "A favor",    data: matches.map((m) => m.goalsFor),      backgroundColor: "rgba(15,32,63,0.75)", borderRadius: 4 },
      { label: "En contra",  data: matches.map((m) => m.goalsAgainst),  backgroundColor: "rgba(220,38,38,0.55)", borderRadius: 4 },
    ]
  );

  state.charts.corners = makeBar(
    document.getElementById("chartCorners"),
    labels,
    [
      { label: "A favor",   data: matches.map((m) => m.cornersFor ?? 0),     backgroundColor: "rgba(15,32,63,0.75)", borderRadius: 4 },
      { label: "En contra", data: matches.map((m) => m.cornersAgainst ?? 0), backgroundColor: "rgba(220,38,38,0.55)", borderRadius: 4 },
    ]
  );

  state.charts.cards = makeBar(
    document.getElementById("chartCards"),
    labels,
    [
      { label: "Amarillas", data: matches.map((m) => m.yellowCards), backgroundColor: "rgba(217,119,6,0.75)", borderRadius: 4 },
    ]
  );

  const m = seasonStats.matches || 1;
  state.charts.season = new Chart(document.getElementById("chartSeason"), {
    type: "bar",
    data: {
      labels: ["Goles F.", "Goles C.", "Amarillas"],
      datasets: [{
        label: "Promedio / partido",
        data: [
          parseFloat(avg(seasonStats.goalsFor, m)) || 0,
          parseFloat(avg(seasonStats.goalsAgainst, m)) || 0,
          parseFloat(avg(seasonStats.yellowCards, m)) || 0,
        ],
        backgroundColor: [
          "rgba(15,32,63,0.75)",
          "rgba(220,38,38,0.55)",
          "rgba(217,119,6,0.7)",
        ],
        borderRadius: 4,
      }],
    },
    options: {
      ...CHART_DEFAULTS,
      plugins: { ...CHART_DEFAULTS.plugins, legend: { display: false } },
    },
  });
}

function addOption(select, value, label, disabled = false, selected = false) {
  const option = document.createElement("option");
  option.value = value;
  option.textContent = label;
  option.disabled = disabled;
  option.selected = selected;
  select.appendChild(option);
}

init();
