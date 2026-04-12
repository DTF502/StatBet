# StatBet

StatBet es una plataforma web dinámica orientada a la consulta rápida e intuitiva de estadísticas de fútbol. Está diseñada para aficionados, analistas o usuarios que desean visualizar el rendimiento de equipos deportivos antes de un encuentro, agrupando diferentes métricas como estadísticas de temporada, partidos recientes e información fundamental del club.

## Características Principales
- **Filtros jerárquicos:** Búsqueda y filtrado secuencial por País ➔ Liga ➔ Equipo.
- **Datos Reales:** Integración directa con [API-Football](https://www.api-football.com/) a través de un backend asíncrono para obtener alineaciones, goles, competiciones y fixtures.
- **Dashboard Analítico:** Visualización del estado del equipo agrupado en tarjetas (KPIs), tablas de resultados de enfrentamientos recientes y gráficos interactivos con Chart.js.
- **Micro-Backend Seguro:** Servicio en PHP que firma las peticiones cURL protegiendo la clave (API Key) del cliente.

## Estructura del Proyecto
El proyecto sigue un modelo Cliente-Servidor ligero:
- **Frontend:** HTML5, CSS Nativo (sin librerías pesadas CSS) y Vanilla JavaScript (`app.js`, `stats.js`, `data.js`) enfocado en peticiones `fetch`.
- **Backend:** Scripts de PHP (`backend/api.php` y `backend/config.php`) orientados a su servicio local (ej. XAMPP) operando como proxy hacia el servicio externo `API-Football`.

## Puesta en Marcha (Desarrollo Local)
1. Coloca este repositorio dentro de la carpeta pública de tu servidor PHP (por ejemplo en `C:\xampp\htdocs\StatBet`).
2. Consigue tu API Key de API-Football y reemplaza el fragmento indicativo en el archivo `backend/config.php`.
3. Inicia tu servidor Apache.
4. Entra desde tu navegador web a `http://localhost/StatBet/index.html`.

## Avisos y Limitaciones de la API
Al utilizar la versión gratuita (Free Tier) de **API-Football**, este proyecto presenta una serie de limitaciones a tener en cuenta:
- **Límite de créditos diarios:** La clave de API gratuita solo permite 100 peticiones diarias. Si se excede este número, la aplicación dejará de cargar nuevas estadísticas hasta el día siguiente.
- **Actualización de los datos:** Debido a las restricciones de la API gratuita, los datos obtenidos en la interfaz (como los partidos recientes o clasificaciones) no son actuales ni operan en estricto tiempo real, funcionando más como una prueba de concepto.
