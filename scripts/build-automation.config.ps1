# ============================================================================
# EIPSI Forms - Configuración de Autenticación
# ============================================================================
# 
# INSTRUCCIONES:
# 1. Copia este archivo como 'build-automation.local.ps1' (no se sube a git)
# 2. Configura tu método de autenticación preferido
# 3. Ejecuta el script principal
#
# ============================================================================

# ----------------------------------------------------------------------------
# MÉTODO 1: Personal Access Token (más fácil para CI/CD)
# ----------------------------------------------------------------------------
# 1. Crea un token en GitHub: Settings -> Developer settings -> Personal access tokens
# 2. Selecciona scope 'repo' para acceso completo al repositorio
# 3. Copia el token aquí (mantén este archivo seguro y fuera de git)

# $env:GITHUB_TOKEN = "ghp_tu_token_aqui"
# $script:repoUrl = "https://$env:GITHUB_TOKEN@github.com/Minorett/EIPSI-Forms-Plugin.git"

# ----------------------------------------------------------------------------
# MÉTODO 2: SSH (más seguro para uso personal)
# ----------------------------------------------------------------------------
# 1. Genera una clave SSH: ssh-keygen -t ed25519 -C "tu-email@ejemplo.com"
# 2. Agrega la clave pública a GitHub: Settings -> SSH and GPG keys
# 3. Descomenta la línea de abajo:

# $script:repoUrl = "git@github.com:Minorett/EIPSI-Forms-Plugin.git"

# ----------------------------------------------------------------------------
# MÉTODO 3: URL del repositorio personalizado
# ----------------------------------------------------------------------------
# Si tienes un fork o mirror del repositorio, cambia la URL aquí:

# $script:repoUrl = "https://github.com/tu-usuario/EIPSI-Forms-Plugin.git"

# ----------------------------------------------------------------------------
# CONFIGURACIÓN OPCIONAL
# ----------------------------------------------------------------------------

# Directorio de trabajo personalizado (por defecto: eipsi-forms-work)
# $script:workDir = "mi-carpeta-personalizada"

# Ruta base personalizada (por defecto: directorio padre del script)
# $script:parentDir = "C:\Users\TuUsuario\Proyectos"

# ----------------------------------------------------------------------------
# NOTAS DE SEGURIDAD
# ----------------------------------------------------------------------------
# ⚠️ NUNCA hagas commit de este archivo con tokens reales
# ⚠️ Agrega '*.local.ps1' a tu .gitignore
# ⚠️ Considera usar variables de entorno del sistema para tokens
# ⚠️ Rota tus tokens regularmente en GitHub
#
# Para usar variables de entorno del sistema:
#   [Environment]::SetEnvironmentVariable("GITHUB_TOKEN", "tu_token", "User")
#
# ============================================================================

Write-Host "Configuración de autenticación cargada" -ForegroundColor Green
