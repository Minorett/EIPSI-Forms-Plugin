#!/usr/bin/env bash
set -Eeuo pipefail

# ---------------------------------------------
# PSIRA WSL2 Setup Script
# Clona repos, configura entorno y levanta Docker Compose
# ---------------------------------------------

SCRIPT_NAME="$(basename "$0")"
DEFAULT_BASE_DIR="$HOME/eipsi/psira"
LOG_DIR_NAME=".setup-logs"
LOG_FILE=""

# Repos configurables: "nombre|url|branch"
REPOS=(
  "EIPSI-Forms-Plugin|https://github.com/psira/EIPSI-Forms-Plugin.git|main"
  "psira-frontend|https://github.com/psira/psira-frontend.git|main"
  # "psira-backend|https://github.com/psira/psira-backend.git|main"
)

# Flags
FORCE_CLONE=false
FORCE_PULL=false
SKIP_DOCKER=false
NO_BUILD=false
VERBOSE=false
BASE_DIR="$DEFAULT_BASE_DIR"
COMPOSE_DIR=""

# ---------------------------------------------
# Logging
# ---------------------------------------------
log_init() {
  local log_dir="$BASE_DIR/$LOG_DIR_NAME"
  mkdir -p "$log_dir"
  LOG_FILE="$log_dir/setup-$(date +%Y%m%d-%H%M%S).log"
  touch "$LOG_FILE"
}

log() {
  local level="$1"; shift
  local msg="$*"
  local ts
  ts="$(date '+%Y-%m-%d %H:%M:%S')"
  echo "[$ts] [$level] $msg" | tee -a "$LOG_FILE"
}

log_info() { log "INFO" "$*"; }
log_ok() { log "OK" "$*"; }
log_warn() { log "WARN" "$*"; }
log_error() { log "ERROR" "$*"; }
log_debug() { if $VERBOSE; then log "DEBUG" "$*"; fi }

# ---------------------------------------------
# Error handling
# ---------------------------------------------
on_error() {
  local exit_code=$?
  local line_no=$1
  log_error "Error en línea $line_no (exit $exit_code)."
  log_error "Revisá el log: $LOG_FILE"
  exit $exit_code
}
trap 'on_error $LINENO' ERR

# ---------------------------------------------
# Helpers
# ---------------------------------------------
show_help() {
  cat <<EOF
Uso:
  $SCRIPT_NAME [opciones]

Opciones:
  --base-dir PATH      Directorio base (default: $DEFAULT_BASE_DIR)
  --compose-dir PATH   Directorio con docker-compose.yml
  --force-clone        Borra y clona de nuevo los repos
  --force-pull         Fuerza git pull en repos existentes
  --skip-docker        No levanta Docker Compose
  --no-build           No hace build (docker compose up sin --build)
  --verbose            Logs detallados
  --help               Muestra esta ayuda
EOF
}

check_command() {
  local cmd="$1"
  if ! command -v "$cmd" >/dev/null 2>&1; then
    log_error "No encontré '$cmd'. Instalalo y reintentá."
    exit 1
  fi
}

is_wsl2() {
  grep -qi "microsoft" /proc/version 2>/dev/null
}

safe_mkdir() {
  local dir="$1"
  mkdir -p "$dir"
  chmod 755 "$dir" || true
}

# ---------------------------------------------
# Prerequisitos
# ---------------------------------------------
check_prereqs() {
  log_info "Validando prerequisitos..."
  check_command git
  check_command bash

  if is_wsl2; then
    log_ok "WSL detectado."
  else
    log_warn "No detecté WSL. El script igual puede funcionar en Linux nativo."
  fi

  if ! $SKIP_DOCKER; then
    check_command docker
    if ! docker info >/dev/null 2>&1; then
      log_error "Docker no está corriendo. Abrí Docker Desktop y reintentá."
      exit 1
    fi

    if command -v docker-compose >/dev/null 2>&1; then
      log_ok "docker-compose disponible."
    else
      check_command docker
      if ! docker compose version >/dev/null 2>&1; then
        log_error "No encontré docker compose. Instalá docker-compose o Docker Compose v2."
        exit 1
      fi
    fi
  fi
}

# ---------------------------------------------
# Git
# ---------------------------------------------
get_repo_dir() {
  local repo_name="$1"
  echo "$BASE_DIR/$repo_name"
}

clone_repo() {
  local name="$1" url="$2" branch="$3"
  local dest
  dest="$(get_repo_dir "$name")"

  if [ -d "$dest/.git" ]; then
    if $FORCE_CLONE; then
      log_warn "Borrando repo existente: $name"
      rm -rf "$dest"
    else
      log_info "Repo ya existe: $name"
      return 0
    fi
  fi

  log_info "Clonando $name ($branch)..."
  git clone --branch "$branch" --single-branch "$url" "$dest"
  log_ok "Clonado: $name"
}

update_repo() {
  local name="$1" url="$2" branch="$3"
  local dest
  dest="$(get_repo_dir "$name")"

  if [ ! -d "$dest/.git" ]; then
    clone_repo "$name" "$url" "$branch"
    return
  fi

  pushd "$dest" >/dev/null
  git remote set-url origin "$url" || true
  git fetch origin "$branch" --prune

  local local_hash remote_hash
  local_hash="$(git rev-parse HEAD)"
  remote_hash="$(git rev-parse "origin/$branch")"

  if [ "$local_hash" = "$remote_hash" ]; then
    log_ok "$name está al día."
  elif $FORCE_PULL; then
    log_warn "Forzando pull en $name"
    git reset --hard "origin/$branch"
  else
    log_info "Actualizando $name"
    git pull --ff-only origin "$branch"
  fi

  popd >/dev/null
}

clone_repos() {
  log_info "Preparando repos en: $BASE_DIR"
  safe_mkdir "$BASE_DIR"

  for repo in "${REPOS[@]}"; do
    IFS='|' read -r name url branch <<< "$repo"
    update_repo "$name" "$url" "$branch"
  done
}

# ---------------------------------------------
# Env
# ---------------------------------------------
setup_env_files() {
  log_info "Configurando archivos .env (si aplica)..."

  for repo in "${REPOS[@]}"; do
    IFS='|' read -r name _ _ <<< "$repo"
    local dir
    dir="$(get_repo_dir "$name")"

    if [ ! -d "$dir" ]; then
      continue
    fi

    local env_example=""
    if [ -f "$dir/.env.example" ]; then
      env_example="$dir/.env.example"
    elif [ -f "$dir/.env.local.example" ]; then
      env_example="$dir/.env.local.example"
    fi

    if [ -n "$env_example" ]; then
      local env_target="$dir/.env"
      if [ ! -f "$env_target" ]; then
        cp "$env_example" "$env_target"
        log_ok "Creado .env en $name"
      else
        log_debug "Ya existe .env en $name"
      fi
    fi
  done
}

# ---------------------------------------------
# Docker Compose
# ---------------------------------------------
compose_cmd() {
  if command -v docker-compose >/dev/null 2>&1; then
    echo "docker-compose"
  else
    echo "docker compose"
  fi
}

find_compose_dir() {
  if [ -n "$COMPOSE_DIR" ]; then
    echo "$COMPOSE_DIR"
    return
  fi

  if [ -f "$BASE_DIR/docker-compose.yml" ]; then
    echo "$BASE_DIR"
    return
  fi

  for repo in "${REPOS[@]}"; do
    IFS='|' read -r name _ _ <<< "$repo"
    local dir
    dir="$(get_repo_dir "$name")"
    if [ -f "$dir/docker-compose.yml" ]; then
      echo "$dir"
      return
    fi
  done

  echo ""
}

start_docker() {
  if $SKIP_DOCKER; then
    log_warn "Se omitió Docker por --skip-docker."
    return
  fi

  local compose_dir
  compose_dir="$(find_compose_dir)"
  if [ -z "$compose_dir" ]; then
    log_error "No encontré docker-compose.yml. Usá --compose-dir PATH."
    exit 1
  fi

  log_info "Usando docker-compose.yml en: $compose_dir"
  local cmd
  cmd="$(compose_cmd)"

  pushd "$compose_dir" >/dev/null

  if $NO_BUILD; then
    log_info "Levantando servicios sin build..."
    $cmd up -d
  else
    log_info "Levantando servicios con build..."
    $cmd up -d --build
  fi

  log_ok "Docker Compose iniciado."
  $cmd ps

  popd >/dev/null
}

# ---------------------------------------------
# Argumentos
# ---------------------------------------------
parse_args() {
  while [ $# -gt 0 ]; do
    case "$1" in
      --base-dir)
        BASE_DIR="$2"; shift 2 ;;
      --compose-dir)
        COMPOSE_DIR="$2"; shift 2 ;;
      --force-clone)
        FORCE_CLONE=true; shift ;;
      --force-pull)
        FORCE_PULL=true; shift ;;
      --skip-docker)
        SKIP_DOCKER=true; shift ;;
      --no-build)
        NO_BUILD=true; shift ;;
      --verbose)
        VERBOSE=true; shift ;;
      --help)
        show_help; exit 0 ;;
      *)
        log_error "Opción inválida: $1"; show_help; exit 1 ;;
    esac
  done
}

# ---------------------------------------------
# Main
# ---------------------------------------------
main() {
  parse_args "$@"
  log_init

  log_info "Iniciando setup PSIRA"
  log_info "Base dir: $BASE_DIR"
  $SKIP_DOCKER && log_warn "Docker deshabilitado."
  $FORCE_CLONE && log_warn "Force clone activado."
  $FORCE_PULL && log_warn "Force pull activado."

  check_prereqs
  clone_repos
  setup_env_files
  start_docker

  log_ok "Setup completado."
}

main "$@"
