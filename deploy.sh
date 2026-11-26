#!/usr/bin/env bash
###############################################################################
# Script de despliegue a producción - Helpdesk Laravel
#
# Requisitos en el servidor destino:
#   - PHP 8.3+ con extensiones de Laravel (incluido sqlsrv para SQL Server)
#   - Composer
#   - Node.js y npm (para Vite / Tailwind)
#   - Git
#   - Acceso a la base de datos SQL Server configurada en .env
#
# Uso:
#   chmod +x deploy.sh
#   ./deploy.sh
###############################################################################
set -euo pipefail

# ---------------------------------------------------------------------------
# Configuración (ajusta según tu entorno de producción)
# ---------------------------------------------------------------------------
APP_DIR="/var/www/helpdesk"                 # Ruta de la aplicación en el servidor
BRANCH="main"                               # Rama a desplegar
PHP_BIN="php"                               # Binario de PHP (ej. /usr/bin/php8.3)
COMPOSER_BIN="composer"
NPM_BIN="npm"
ARTISAN="$PHP_BIN artisan"

# Colores para salida
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

log()  { printf '%b\n' "$1"; }
ok()   { printf '%b✔%b %s\n' "$GREEN" "$NC" "$1"; }
warn() { printf '%b⚠%b %s\n' "$YELLOW" "$NC" "$1"; }
err()  { printf '%b✘%b %s\n' "$RED" "$NC" "$1"; }

# ---------------------------------------------------------------------------
# Validaciones previas
# ---------------------------------------------------------------------------
if [ ! -d "$APP_DIR" ]; then
    err "No existe el directorio de la aplicación: $APP_DIR"
    exit 1
fi

cd "$APP_DIR"

if ! command -v "$PHP_BIN" >/dev/null 2>&1; then
    err "No se encontró PHP ($PHP_BIN)."
    exit 1
fi

if ! command -v "$COMPOSER_BIN" >/dev/null 2>&1; then
    err "No se encontró Composer."
    exit 1
fi

if ! command -v "$NPM_BIN" >/dev/null 2>&1; then
    err "No se encontró npm."
    exit 1
fi

log "============================================================"
log "  DESPLIEGUE A PRODUCCIÓN - Helpdesk Laravel"
log "  Directorio: $APP_DIR"
log "  Rama: $BRANCH"
log "============================================================"

# ---------------------------------------------------------------------------
# 1. Modo mantenimiento (evita peticiones durante el despliegue)
# ---------------------------------------------------------------------------
log "\n>> Activando modo mantenimiento..."
$ARTISAN down --message="La aplicación está en mantenimiento. Vuelve pronto." --retry=60 || true
ok "Modo mantenimiento activado."

# ---------------------------------------------------------------------------
# 2. Traer el código más reciente
# ---------------------------------------------------------------------------
log "\n>> Descargando cambios del repositorio ($BRANCH)..."
git fetch origin
git checkout "$BRANCH"
git pull origin "$BRANCH"
ok "Código actualizado."

# ---------------------------------------------------------------------------
# 3. Dependencias de PHP (sin dependencias de desarrollo)
# ---------------------------------------------------------------------------
log "\n>> Instalando dependencias de Composer (producción)..."
$COMPOSER_BIN install --no-dev --optimize-autoloader --no-interaction --prefer-dist
ok "Dependencias de Composer instaladas."

# ---------------------------------------------------------------------------
# 4. Dependencias de frontend y build de assets (Vite + Tailwind)
# ---------------------------------------------------------------------------
log "\n>> Instalando dependencias de npm y construyendo assets..."
$NPM_BIN install --ignore-scripts --no-audit --no-fund
$NPM_BIN run build
ok "Assets construidos para producción."

# ---------------------------------------------------------------------------
# 5. Archivo de entorno (.env)
# ---------------------------------------------------------------------------
if [ ! -f .env ]; then
    warn "No existe .env. Copiando desde .env.example (debes editarlo con los valores de producción)."
    cp .env.example .env
    $PHP_BIN -r "file_exists('.env') || copy('.env.example', '.env');"
    $ARTISAN key:generate --ansi
fi
ok "Archivo .env listo."

# ---------------------------------------------------------------------------
# 6. Migraciones de base de datos
# ---------------------------------------------------------------------------
log "\n>> Ejecutando migraciones de base de datos..."
$ARTISAN migrate --force --no-interaction
ok "Migraciones aplicadas."

# ---------------------------------------------------------------------------
# 7. Caché de Laravel (config, rutas, vistas, eventos)
# ---------------------------------------------------------------------------
log "\n>> Generando caché de producción..."
$ARTISAN config:cache
$ARTISAN route:cache
$ARTISAN view:cache
$ARTISAN event:cache
ok "Caché generado."

# ---------------------------------------------------------------------------
# 8. Permisos de directorios críticos
# ---------------------------------------------------------------------------
log "\n>> Ajustando permisos de storage y bootstrap/cache..."
chmod -R 775 storage bootstrap/cache
chown -R www-data:www-data storage bootstrap/cache 2>/dev/null || true
ok "Permisos ajustados."

# ---------------------------------------------------------------------------
# 9. Desactivar modo mantenimiento
# ---------------------------------------------------------------------------
log "\n>> Desactivando modo mantenimiento..."
$ARTISAN up
ok "Modo mantenimiento desactivado."

# ---------------------------------------------------------------------------
# 10. Recargar servicios (opcional, descomenta según tu servidor)
# ---------------------------------------------------------------------------
# log "\n>> Recargando PHP-FPM..."
# sudo systemctl reload php8.3-fpm || true
# ok "PHP-FPM recargado."

# ---------------------------------------------------------------------------
# Fin
# ---------------------------------------------------------------------------
log "\n============================================================"
log "  ¡Despliegue completado con éxito!"
log "============================================================"
