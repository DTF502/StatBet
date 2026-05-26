# StatBet

StatBet es una plataforma web dinámica orientada a la consulta rápida e intuitiva de estadísticas de fútbol. Está diseñada para aficionados, analistas o usuarios que desean visualizar el rendimiento de equipos deportivos antes de un encuentro, agrupando diferentes métricas como estadísticas de temporada, partidos recientes e información fundamental del club.

Tras las últimas actualizaciones, la aplicación ha pasado de usar mock data/API externa directa a integrar un sistema completo de base de datos local con actualizaciones periódicas.

## Características Principales

- **Filtros jerárquicos y Búsqueda:** Búsqueda rápida por nombre de equipo y filtrado secuencial por País ➔ Liga ➔ Equipo.
- **Base de Datos Local (MySQL):** Carga inicial de datos históricos y de temporada para ofrecer estadísticas completas sin consumir cuotas de APIs externas.
- **Importador de Datos Integrado:** Scripts preparados para ingerir datasets grandes en formato CSV y hojas de cálculo XLSX (datos históricos desde 2022 y de la temporada 2025-2026).
- **Actualización en Tiempo Real:** Integración con la API de [football-data.org](https://www.football-data.org/) para obtener resultados recientes (últimos 7 días) mediante un trigger web interactivo.
- **Dashboard Analítico Detallado:**
  - Tarjetas de KPIs interactivos (goles a favor/contra, córners, amarillas, faltas y promedios por partido).
  - Paginación dinámica ("Ver más partidos") para explorar el historial de enfrentamientos.
  - Visualización gráfica avanzada con Chart.js (gráficos individuales para goles, córners, tarjetas amarillas y resumen de promedios de temporada).

## Estructura del Proyecto

El proyecto sigue un modelo Cliente-Servidor ligero con persistencia local:

- **Frontend:**
  - `index.html` y `stats.html`: Vistas principales y del dashboard de estadísticas.
  - `styles.css`: Estilos visuales optimizados (CSS nativo y diseño responsivo).
  - `app.js` y `stats.js`: Controladores que manejan la interacción del usuario, filtrados (contexto local/visitante, competición, fechas) y renderizado de gráficas.
  - `data.js`: Diccionario de datos de ligas/países y capa de comunicación (fetch) que intercepta y redirige peticiones hacia la API local de PHP.
  - `update.js`: Lógica del frontend para activar y notificar el progreso de la actualización de partidos.

- **Backend / API local:**
  - `backend/db.php`: Conector PDO a la base de datos local MySQL (`statbet`).
  - `backend/statbet_api.php`: API local que sirve las consultas de equipos y calcula las métricas/estadísticas acumuladas de cada club de forma dinámica.
  - `backend/run_update.php`: Endpoint para ejecutar de forma asíncrona la actualización de partidos recientes.

- **Base de Datos y Datos Semilla:**
  - `database/schema.sql`: Definición de tablas (`matches`, `competitions`, `import_logs`) e inserción inicial de códigos de competición europeos.
  - `database/import_data.php`: Script de importación masiva para procesar los ficheros locales.
  - `database/update_matches.php`: Script que descarga partidos finalizados desde `football-data.org`, mapea los nombres de los equipos y actualiza la base de datos local.
  - `data/raw/Matches.csv`: Dataset histórico de partidos.
  - `data/raw/all-euro-data-2025-2026.xlsx`: Dataset detallado de ligas europeas para la temporada actual.

## Puesta en Marcha (Desarrollo Local)

### Requisitos previos
- Servidor local con soporte de PHP y MySQL (por ejemplo, **XAMPP**).
- PHP con la extensión `ZipArchive` habilitada (requerido para procesar el archivo XLSX).

### Pasos para la instalación
1. Coloca este repositorio dentro de la carpeta pública de tu servidor (ej. `C:\xampp\htdocs\StatBet`).
2. **Crear la base de datos**: Ejecuta el script SQL en tu servidor MySQL.
   ```bash
   /c/xampp/mysql/bin/mysql.exe -u root < database/schema.sql
   ```
3. **Importar los datos semilla**: Ejecuta el importador local desde la consola para poblar la base de datos inicial:
   ```bash
   /c/xampp/php/php.exe database/import_data.php
   ```
4. **Configurar la API Key de actualización**:
   - Abre `database/update_matches.php`.
   - Si deseas cambiar o usar tu propia API Key de `football-data.org`, edita la constante `FD_API_KEY`.
5. Inicia el servidor Apache y MySQL en tu panel de XAMPP.
6. Accede desde tu navegador a `http://localhost/StatBet/index.html`.

## Avisos y Limitaciones de la API de Actualización

Para mantener la base de datos actualizada con los últimos resultados de la jornada sin depender constantemente de conexiones externas lentas:
- **Uso bajo demanda:** Los partidos nuevos se descargan únicamente cuando el usuario hace clic en el botón **"Actualizar datos"** en la interfaz.
- **Filtro de ventana temporal:** La API gratuita de `football-data.org` limita la consulta del histórico, por lo que el actualizador solo solicita partidos disputados en los **últimos 7 días**.
- **Control de límites (Rate Limiting):** El script local tiene lógica de reintento automático (`sleep` de 60 segundos) en caso de recibir un error HTTP 429 (límite de peticiones de la API gratuita excedido). Los logs de cada ejecución se pueden auditar en `database/update_log.txt`.

## Estrategia de Ramificación (GitFlow)

Este proyecto adopta una estrategia de ramificación basada en **GitFlow** para mantener el código estructurado y seguro:

1. **`main` (Master):** Contiene el código de producción estable y funcional. Cada entrega oficial se marca en esta rama con un Tag de versión.
2. **`develop`:** Funciona como rama base o principal de integración. Todas las nuevas funciones probadas se combinan aquí antes de pasar a producción.
3. **`feature/*`:** Ramas individuales y temporales de trabajo (por ejemplo `feature/db-integration`). Cuando una nueva característica está completada y validada, se realiza un *Merge Request* hacia la rama `develop`.
4. **`hotfix/*`:** Ramas de mantenimiento urgente creadas directamente desde `main` destinadas a subsanar de forma rápida algún problema crítico.
5. **`release/*`:** Ramas transitorias preparativas de un nuevo despliegue donde exclusivamente se corrigen detalles de última hora o documentación antes de fusionar en `main`.
