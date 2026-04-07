const state = {
  countries: [],
  leagues: [],
  teams: [],
  selected: {
    country: "",
    league: "",
    team: "",
  },
};

const selectors = {
  country: document.getElementById("countrySelect"),
  league: document.getElementById("leagueSelect"),
  team: document.getElementById("teamSelect"),
  teamSearch: document.getElementById("teamSearch"),
  teamSearchBtn: document.getElementById("teamSearchBtn"),
  viewStatsBtn: document.getElementById("viewStatsBtn"),
};

const controls = {
  country: null,
  league: null,
  team: null,
};

function init() {
  setupCustomSelects();
  bindEvents();
  loadCountries();
}

function bindEvents() {
  selectors.viewStatsBtn.addEventListener("click", goToStatsPage);
  selectors.teamSearchBtn.addEventListener("click", onTeamSearch);
  selectors.teamSearch.addEventListener("keydown", (event) => {
    if (event.key === "Enter") {
      event.preventDefault();
      onTeamSearch();
    }
  });
}

function setupCustomSelects() {
  controls.country = createImageSelect(selectors.country, "-- Selecciona un pais --", onCountryChange);
  controls.league = createImageSelect(selectors.league, "-- --", onLeagueChange);
  controls.team = createImageSelect(selectors.team, "-- --", onTeamChange);

  const allControls = [controls.country, controls.league, controls.team];

  // Función para cerrar todos los dropdowns
  const closeAllSelects = (exceptControl = null) => {
    allControls.forEach((control) => {
      if (control !== exceptControl) {
        control.root.classList.remove("is-open");
      }
    });
  };

  // Pasar la función closeAllSelects a cada control para que pueda cerrar los demás
  allControls.forEach((control) => {
    control.closeOthers = closeAllSelects;
  });

  document.addEventListener("click", (event) => {
    const roots = allControls.map((c) => c.root);
    const clickedInside = roots.some((root) => root.contains(event.target));
    if (!clickedInside) {
      closeAllSelects();
    }
  });
}

function loadCountries() {
  state.countries = window.StatBetData.api.getCountries();
  fillSelect(controls.country, state.countries, "code", "name", "icon", "-- Selecciona un pais --");
  disableSelect(controls.league);
  disableSelect(controls.team);
  selectors.viewStatsBtn.disabled = true;
}

function onCountryChange() {
  state.selected.country = controls.country.selectedValue;
  state.selected.league = "";
  state.selected.team = "";

  if (!state.selected.country) {
    disableSelect(controls.league);
    disableSelect(controls.team);
    selectors.viewStatsBtn.disabled = true;
    return;
  }

  state.leagues = window.StatBetData.api.getLeaguesByCountry(state.selected.country);
  fillSelect(controls.league, state.leagues, "id", "name", "icon", "-- Selecciona una liga --");
  disableSelect(controls.team);
  selectors.viewStatsBtn.disabled = true;
}

function onLeagueChange() {
  state.selected.league = controls.league.selectedValue;
  state.selected.team = "";

  if (!state.selected.league) {
    disableSelect(controls.team);
    selectors.viewStatsBtn.disabled = true;
    return;
  }

  state.teams = window.StatBetData.api.getTeamsByLeague(state.selected.league);
  fillSelect(controls.team, state.teams, "id", "name", "icon", "-- Selecciona un equipo --");
  selectors.viewStatsBtn.disabled = true;
}

function onTeamChange() {
  state.selected.team = controls.team.selectedValue;
  selectors.viewStatsBtn.disabled = !state.selected.team;
}

function onTeamSearch() {
  const query = selectors.teamSearch.value.trim();
  if (!query) return;

  const match = window.StatBetData.api.searchTeam(query);
  if (!match) {
    alert("No existe ningun equipo que coincida con esa busqueda.");
    return;
  }

  setSelectValue(controls.country, match.countryCode);
  onCountryChange();

  setSelectValue(controls.league, match.leagueId);
  onLeagueChange();

  setSelectValue(controls.team, match.id);
  state.selected.team = match.id;
  selectors.viewStatsBtn.disabled = false;
}

function goToStatsPage() {
  if (!state.selected.team) return;
  window.location.href = `stats.html?team=${encodeURIComponent(state.selected.team)}`;
}

function fillSelect(control, items, valueKey, labelKey, iconKey, placeholder) {
  control.options = [{ value: "", label: placeholder, icon: "", disabled: true }].concat(
    items.map((item) => ({
      value: item[valueKey],
      label: item[labelKey],
      icon: item[iconKey] || "",
      disabled: false,
    }))
  );
  control.disabled = false;
  control.selectedValue = "";
  renderImageSelect(control);
}

function disableSelect(control) {
  control.options = [{ value: "", label: "-- --", icon: "", disabled: true }];
  control.disabled = true;
  control.selectedValue = "";
  renderImageSelect(control);
}

function setSelectValue(control, value) {
  const exists = control.options.some((option) => option.value === value);
  control.selectedValue = exists ? value : "";
  renderImageSelect(control);
}

function createImageSelect(root, placeholder, onChange) {
  const trigger = document.createElement("button");
  trigger.type = "button";
  trigger.className = "image-select__trigger";

  const valueEl = document.createElement("span");
  valueEl.className = "image-select__value";
  valueEl.textContent = placeholder;
  trigger.appendChild(valueEl);

  const arrow = document.createElement("span");
  arrow.className = "image-select__arrow";
  arrow.textContent = "▼";
  trigger.appendChild(arrow);

  const menu = document.createElement("ul");
  menu.className = "image-select__menu";

  root.innerHTML = "";
  root.appendChild(trigger);
  root.appendChild(menu);

  const control = {
    root,
    trigger,
    valueEl,
    menu,
    options: [],
    disabled: false,
    selectedValue: "",
    onChange,
    closeOthers: null, // Se asigna en setupCustomSelects
  };

  trigger.addEventListener("click", () => {
    if (control.disabled) return;
    // Cerrar todos los demás dropdowns
    if (control.closeOthers) {
      control.closeOthers(control);
    }
    // Abrir/cerrar el actual
    root.classList.toggle("is-open");
  });

  return control;
}

function renderImageSelect(control) {
  const selectedOption =
    control.options.find((option) => option.value === control.selectedValue) || control.options[0];
  control.valueEl.innerHTML = renderOptionInner(selectedOption, true);
  control.trigger.disabled = control.disabled;
  control.root.classList.toggle("is-disabled", control.disabled);
  control.root.classList.remove("is-open");

  control.menu.innerHTML = "";
  control.options.forEach((option) => {
    const item = document.createElement("li");
    item.className = "image-select__option";
    if (option.disabled) item.classList.add("is-disabled");
    if (option.value === control.selectedValue) item.classList.add("is-selected");
    item.innerHTML = renderOptionInner(option, false);

    item.addEventListener("click", () => {
      if (option.disabled || control.disabled) return;
      control.selectedValue = option.value;
      renderImageSelect(control);
      control.onChange();
    });

    control.menu.appendChild(item);
  });
}

function renderOptionInner(option, placeholderStyle) {
  const hasIcon = Boolean(option.icon);
  const iconHtml = hasIcon
    ? `<img src="${option.icon}" alt="" class="image-select__icon" />`
    : '<span class="image-select__icon image-select__icon--empty"></span>';

  const textClass = placeholderStyle && option.disabled ? "image-select__text is-placeholder" : "image-select__text";
  return `${iconHtml}<span class="${textClass}">${option.label}</span>`;
}

init();
