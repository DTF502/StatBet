# StatBet

StatBet es una plataforma web orientada a la consulta rápida de estadísticas de fútbol. Permite a aficionados, analistas y apostadores visualizar el rendimiento de equipos de las principales ligas europeas, con métricas como goles, tarjetas amarillas y resultados recientes.

---

## Características principales

- **Filtros jerárquicos:** Selección secuencial por País → Liga → Equipo.
- **Datos reales:** Integración con el dataset de [Transfermarkt (Kaggle)](https://www.kaggle.com/datasets/davidcariboo/player-scores), que incluye partidos, clubes y competiciones de las principales ligas europeas.
- **Dashboard analítico:** KPIs de temporada, tabla de últimos partidos y gráficos interactivos con Chart.js.
- **Filtros avanzados:** Filtrado por período de fechas (semanal, mensual o personalizado), condición (local/visitante) y competición específica.
- **Búsqueda por nombre:** Localizador rápido de equipos desde la barra de búsqueda superior.

---

## Estructura del proyecto

```
StatBet/
├── index.html              # Página principal (selector de país, liga y equipo)
├── stats.html              # Página de estadísticas del equipo seleccionado
├── styles.css              # Estilos globales (sin frameworks CSS externos)
├── app.js                  # Lógica de la página principal (selectores jerárquicos)
├── stats.js                # Lógica de la página de estadísticas (KPIs, gráficos, tabla)
├── data.js                 # Capa de datos: comunica el frontend con el backend PHP
├── images/                 # Iconos de países, ligas y equipos (SVG)
├── backend/
│   ├── config.php          # Configuración de la base de datos MySQL
│   ├── kaggle_api.php      # Backend principal: sirve datos (CSV o MySQL)
│   ├── import.php          # Script de importación única: carga los CSV en MySQL
│   └── api.php             # (Legado) Proxy hacia API-Football, ya no se usa
└── data/
    └── kaggle/             # CSVs del dataset de Transfermarkt (ver sección de datos)
```

---

## Fuente de datos

Los datos provienen del dataset público **Football Data from Transfermarkt** disponible en Kaggle:

[https://www.kaggle.com/datasets/davidcariboo/player-scores](https://www.kaggle.com/datasets/davidcariboo/player-scores)

### Archivos utilizados

| Archivo | Contenido | Usado para |
|---|---|---|
| `competitions.csv` | Ligas y copas por país | Filtro de competiciones |
| `clubs.csv` | Clubes con su liga doméstica | Listado de equipos por liga |
| `games.csv` | Partidos con resultado y fecha | Estadísticas de temporada y últimos partidos |
| `club_games.csv` | Stats por equipo y partido | Goles a favor/contra, local/visitante |
| `game_events.csv` | Eventos de partido (goles, tarjetas…) | Tarjetas amarillas y rojas por partido |

> **Limitación conocida:** El dataset de Transfermarkt **no incluye datos de córners ni faltas**. Estas métricas se muestran como `N/D` en la interfaz.

---

## Ligas disponibles

| País | Liga | ID competición |
|---|---|---|
| España | LaLiga | `ES1` |
| Reino Unido | Premier League | `GB1` |
| Italia | Serie A | `IT1` |

Para añadir más ligas, basta con incluir su `competition_id` del dataset en el array `leagues` de `data.js`.

---

## Puesta en marcha (desarrollo local)

### Requisitos
- XAMPP (o cualquier servidor con Apache + PHP 8+ + MySQL)
- Los archivos CSV del dataset de Kaggle

### Opción A — Lectura directa de CSV (sin MySQL)

La forma más rápida de arrancar. No requiere ninguna configuración de base de datos.

1. Descarga el dataset de Kaggle y descomprime el ZIP.
2. Copia los CSV a la carpeta `data/kaggle/` del proyecto.
3. Coloca el proyecto en `htdocs/StatBet/`.
4. Inicia Apache en XAMPP.
5. Abre `http://localhost/StatBet/index.html`.

Para verificar que los CSV son detectados correctamente:
```
http://localhost/StatBet/backend/kaggle_api.php?action=health
```

### Opción B — Con base de datos MySQL (recomendado)

Mejora notablemente el rendimiento eliminando la lectura de ficheros grandes en cada petición.

1. Sigue los pasos 1–4 de la Opción A.
2. Crea la base de datos en MySQL (phpMyAdmin o consola):
   ```sql
   CREATE DATABASE statbet CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
   ```
3. Aplica el esquema:
   - Abre phpMyAdmin → selecciona la BD `statbet` → pestaña **SQL** → pega el contenido de `backend/schema.sql` y ejecuta.
4. Importa los datos (tarda varios minutos, hazlo solo una vez):
   ```
   http://localhost/StatBet/backend/import.php
   ```
5. Listo. La aplicación ahora consulta MySQL en lugar de los CSV.

---

## Métricas disponibles

| Métrica | Disponible | Fuente |
|---|---|---|
| Partidos jugados | ✅ | `games.csv` |
| Goles a favor | ✅ | `games.csv` |
| Goles en contra | ✅ | `games.csv` |
| Tarjetas amarillas | ✅ | `game_events.csv` |
| Tarjetas rojas | ✅ | `game_events.csv` |
| Resultado (V/E/D) | ✅ | `games.csv` |
| Local / Visitante | ✅ | `games.csv` |
| Corners | ❌ | No disponible en Transfermarkt |
| Faltas | ❌ | No disponible en Transfermarkt |

---

## Estrategia de ramificación (GitFlow)

| Rama | Propósito |
|---|---|
| `main` | Código estable de producción. Cada entrega oficial genera un Tag de versión. |
| `develop` | Rama de integración. Las features terminadas se fusionan aquí antes de pasar a `main`. |
| `feature/*` | Una rama por funcionalidad (p.ej. `feature/mysql-migration`). Se abre desde `develop` y se cierra con Merge Request. |
| `hotfix/*` | Correcciones urgentes sobre `main`. |
| `release/*` | Preparación del despliegue final (solo correcciones menores y documentación). |

---

## Equipo de desarrollo

| Miembro | Responsabilidad principal |
|---|---|
| Gaizka | Coordinación general y lógica de filtrado de datos |
| Sebas | Diseño de interfaz y experiencia de usuario |
| Diego | Integración con la fuente de datos (Kaggle/API) |
| Lasha | Base de datos y despliegue |

Dedicación estimada: 150 h/persona · 4 personas = **600 h totales**.

---

## Avisos y limitaciones

- Los datos del dataset de Transfermarkt se actualizan **semanalmente** en Kaggle. Para refrescar los datos locales, vuelve a descargar el ZIP y re-ejecuta `import.php`.
- La lectura directa de CSV (Opción A) puede ser lenta en la primera petición de estadísticas, ya que `games.csv` supera los 100 MB. Con MySQL (Opción B) las respuestas son inmediatas.
- La aplicación **no permite realizar apuestas** ni está conectada con ninguna casa de apuestas. Su finalidad es exclusivamente informativa.

