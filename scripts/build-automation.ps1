#
# EIPSI Forms - Script de Automatización de Build (PowerShell)
# Compatibilidad: Arquitectura Modular de Bloques Gutenberg
#
# Este script automatiza el build completo del plugin con clonación del repositorio
# y validación de arquitectura modular para bloques individuales.
#
# Pasos:
#   [1/10] Limpiar y clonar repositorio
#   [2/10] Instalar dependencias
#   [3/10] Verificar estructura del plugin
#   [4/10] Lint: Verificar código JavaScript
#   [5/10] Lint: Verificar duplicados de funciones
#   [6/10] Formatear código estilo WordPress
#   [7/10] Build de producción
#   [8/10] Verificar archivos base del build
#   [9/10] Verificar bloques individuales (modular)
#   [10/10] Resumen final
#
# Uso:
#   powershell -ExecutionPolicy Bypass -File scripts/build-automation.ps1
#
# Requisitos:
#   - Node.js >= 14.x
#   - npm >= 7.x
#   - PowerShell 5.1 o superior
#   - Git (para clonación del repositorio)
#
# Parámetros opcionales:
#   -NoExit    No cerrar la terminal al finalizar
#

param(
    [switch]$NoExit = $false
)

# Configurar para detener en errores
$ErrorActionPreference = "Stop"

# Configurar salida de UTF-8 para PowerShell
$OutputEncoding = [System.Text.Encoding]::UTF8

# ============================================================================
# FUNCIONES HELPER
# ============================================================================

function Write-Header {
    param([string]$Title)
    Write-Host "╔════════════════════════════════════════════════════════════╗" -ForegroundColor Cyan
    Write-Host "║  $Title" -NoNewline
    $padding = 55 - $Title.Length
    if ($padding -gt 0) {
        Write-Host (" " * $padding) -NoNewline
    }
    Write-Host "║" -ForegroundColor Cyan
    Write-Host "╚════════════════════════════════════════════════════════════╝" -ForegroundColor Cyan
    Write-Host ""
}

function Write-Step {
    param(
        [string]$Message,
        [int]$Number,
        [int]$Total = 10
    )
    Write-Host ("[${Number}/${Total}] $Message" -f $Number, $Total) -ForegroundColor Cyan
}

function Write-Success {
    param([string]$Message)
    Write-Host "✓ $Message" -ForegroundColor Green
}

function Write-Error {
    param([string]$Message)
    Write-Host "✗ $Message" -ForegroundColor Red
}

function Write-Warning {
    param([string]$Message)
    Write-Host "⚠ $Message" -ForegroundColor Yellow
}

function Write-Info {
    param([string]$Message)
    Write-Host "  $Message" -ForegroundColor Gray
}

function Exit-Script {
    param([int]$ExitCode = 0)
    
    if (-not $NoExit) {
        Write-Host ""
        Write-Host "Presiona Enter para cerrar..." -ForegroundColor Gray
        $null = Read-Host
    }
    
    exit $ExitCode
}

function Test-CommandExists {
    param([string]$Command)
    $null = Get-Command $Command -ErrorAction SilentlyContinue
    return $?
}

# ============================================================================
# INICIO DEL SCRIPT
# ============================================================================

Clear-Host
Write-Header "EIPSI Forms - Automatización de Build Clínico"

# Verificar requisitos previos
if (-not (Test-CommandExists "git")) {
    Write-Error "git no está instalado o no está en el PATH"
    Write-Info "Por favor instala Git para clonar el repositorio"
    Exit-Script 1
}

if (-not (Test-CommandExists "npm")) {
    Write-Error "npm no está instalado o no está en el PATH"
    Write-Info "Por favor instala Node.js y npm"
    Exit-Script 1
}

# Obtener versiones
$gitVersion = git --version
$npmVersion = npm --version
Write-Info "git version: $gitVersion"
Write-Info "npm version: $npmVersion"
Write-Host ""

# Configuración del repositorio
$repoUrl = "https://github.com/eipsi/eipsi-forms.git"
$workDir = "eipsi-forms-work"
$parentDir = Split-Path -Parent (Get-Location)

# ============================================================================
# [1/10] LIMPIAR Y CLONAR REPOSITORIO
# ============================================================================

Write-Step "Limpiando carpeta anterior y clonando repositorio" -Number 1 -Total 10

# Cambiar al directorio padre
Write-Info "Cambiando a directorio padre: $parentDir"
Set-Location $parentDir

# Limpiar carpeta de trabajo anterior si existe
if (Test-Path $workDir) {
    Write-Info "Eliminando carpeta de trabajo anterior: $workDir"
    Remove-Item -Path $workDir -Recurse -Force -ErrorAction Stop
}

# Clonar el repositorio
Write-Info "Clonando repositorio desde: $repoUrl"
try {
    git clone $repoUrl $workDir
    if ($LASTEXITCODE -ne 0) {
        throw "git clone falló con código de salida $LASTEXITCODE"
    }
    Write-Success "Repositorio clonado exitosamente"
    Write-Host ""
} catch {
    Write-Error "Error al clonar el repositorio: $_"
    Exit-Script 1
}

# Cambiar al directorio del repositorio
Write-Info "Cambiando al directorio del repositorio: $workDir"
Set-Location $workDir

# ============================================================================
# [2/10] INSTALAR DEPENDENCIAS
# ============================================================================

Write-Step "Instalando/actualizando dependencias" -Number 2 -Total 10

try {
    npm install --legacy-peer-deps
    if ($LASTEXITCODE -ne 0) {
        throw "npm install falló con código de salida $LASTEXITCODE"
    }
    Write-Success "Dependencias instaladas correctamente"
    Write-Host ""
} catch {
    Write-Error "Error al instalar dependencias: $_"
    Exit-Script 1
}

# ============================================================================
# [3/10] VERIFICAR ESTRUCTURA DEL PLUGIN
# ============================================================================

Write-Step "Verificando estructura del plugin" -Number 3 -Total 10

$requiredFiles = @(
    "eipsi-forms.php",
    "package.json",
    "webpack.config.js",
    "src/blocks/",
    "admin/",
    "includes/",
    "build/"
)

$structureOk = $true
foreach ($file in $requiredFiles) {
    if (Test-Path $file) {
        Write-Info "Encontrado: $file"
    } else {
        Write-Error "Falta: $file"
        $structureOk = $false
    }
}

if (-not $structureOk) {
    Write-Host ""
    Write-Error "Estructura del plugin incompleta"
    Exit-Script 1
}

Write-Success "Estructura del plugin verificada"
Write-Host ""

# ============================================================================
# [4/10] LINT: VERIFICAR CÓDIGO JAVASCRIPT
# ============================================================================

Write-Step "Ejecutando linting de JavaScript" -Number 4 -Total 10

try {
    # Primero intentar auto-fix
    Write-Info "Intentando auto-fix de problemas de lint..."
    npm run lint:js -- --fix
    
    if ($LASTEXITCODE -eq 0) {
        Write-Success "Linting completado (con auto-fix si fue necesario)"
        Write-Host ""
    } else {
        throw "npm run lint:js falló con código de salida $LASTEXITCODE"
    }
} catch {
    Write-Warning "Error en linting: $_"
    Write-Info "Intenta corregir manualmente los errores y ejecuta de nuevo"
    Write-Host ""
}

# ============================================================================
# [5/10] LINT: VERIFICAR DUPLICADOS DE FUNCIONES
# ============================================================================

Write-Step "Verificando duplicados de funciones" -Number 5 -Total 10

try {
    npm run lint:duplicates
    if ($LASTEXITCODE -ne 0) {
        Write-Warning "Se detectaron posibles duplicados"
        Write-Info "Revisa los warnings y decide si requieren acción"
    } else {
        Write-Success "No se detectaron duplicados críticos"
    }
    Write-Host ""
} catch {
    Write-Warning "Error al verificar duplicados: $_"
    Write-Host ""
}

# ============================================================================
# [6/10] FORMATEAR CÓDIGO
# ============================================================================

Write-Step "Formateando código estilo WordPress" -Number 6 -Total 10

try {
    npm run format
    if ($LASTEXITCODE -ne 0) {
        throw "npm run format falló con código de salida $LASTEXITCODE"
    }
    Write-Success "Código formateado correctamente"
    Write-Host ""
} catch {
    Write-Warning "Error al formatear código: $_"
    Write-Host ""
}

# ============================================================================
# [7/10] BUILD DE PRODUCCIÓN
# ============================================================================

Write-Step "Ejecutando build de producción" -Number 7 -Total 10

# Asegurar que la carpeta build esté limpia
if (Test-Path "build") {
    Write-Info "Limpiando carpeta build anterior..."
    Remove-Item -Path "build" -Recurse -Force -ErrorAction Stop
}

try {
    npm run build
    if ($LASTEXITCODE -ne 0) {
        throw "npm run build falló con código de salida $LASTEXITCODE"
    }
    Write-Success "Build de producción completado"
    Write-Host ""
} catch {
    Write-Error "Error en el build: $_"
    Exit-Script 1
}

# ============================================================================
# [8/10] VERIFICAR ARCHIVOS BASE DEL BUILD
# ============================================================================

Write-Step "Verificando archivos base del build" -Number 8 -Total 10

$baseFiles = @(
    "build/index.js",
    "build/index.css",
    "build/style-index.css",
    "build/blocks"
)

$baseOk = $true
foreach ($file in $baseFiles) {
    if (Test-Path $file) {
        if ((Get-Item $file).Length -eq 0) {
            Write-Error "$file existe pero está VACÍO"
            $baseOk = $false
        } else {
            $size = [math]::Round((Get-Item $file).Length / 1024, 2)
            Write-Success "Encontrado: $file ($size KB)"
        }
    } else {
        Write-Error "Falta: $file"
        $baseOk = $false
    }
}

Write-Host ""

# ============================================================================
# [9/10] VERIFICAR BLOQUES INDIVIDUALES (MODULAR)
# ============================================================================

Write-Step "Verificando bloques individuales (arquitectura modular)" -Number 9 -Total 10

$buildBlocksPath = "build/blocks"
$blocksCompiled = @()

if (Test-Path $buildBlocksPath) {
    $blockDirs = Get-ChildItem -Path $buildBlocksPath -Directory -ErrorAction SilentlyContinue
    
    if ($blockDirs.Count -gt 0) {
        Write-Info "Se encontraron $($blockDirs.Count) bloques compilados:"
        Write-Host ""
        
        foreach ($blockDir in $blockDirs) {
            $blockName = $blockDir.Name
            $blockPath = $blockDir.FullName
            
            $indexJs = Join-Path $blockPath "index.js"
            $indexCss = Join-Path $blockPath "index.css"
            $styleCss = Join-Path $blockPath "style-index.css"
            
            $blockOk = $true
            if (!(Test-Path $indexJs) -or (Get-Item $indexJs).Length -eq 0) { $blockOk = $false }
            if (!(Test-Path $indexCss) -or (Get-Item $indexCss).Length -eq 0) { $blockOk = $false }
            if (!(Test-Path $styleCss) -or (Get-Item $styleCss).Length -eq 0) { $blockOk = $false }
            
            if ($blockOk) {
                $jsSize = [math]::Round((Get-Item $indexJs).Length / 1024, 2)
                $cssSize = [math]::Round((Get-Item $indexCss).Length / 1024, 2)
                $styleSize = [math]::Round((Get-Item $styleCss).Length / 1024, 2)
                Write-Success "$blockName : index.js ($jsSize KB) + index.css ($cssSize KB) + style-index.css ($styleSize KB)"
                $blocksCompiled += $blockName
            } else {
                Write-Error "$blockName tiene archivos faltantes o vacíos"
            }
        }
        Write-Host ""
    } else {
        Write-Warning "No se encontraron bloques compilados en $buildBlocksPath"
    }
} else {
    Write-Warning "Carpeta $buildBlocksPath no existe"
}

$allBuildOk = $baseOk -and ($blocksCompiled.Count -gt 0)

Write-Host ""

# ============================================================================
# [10/10] RESUMEN FINAL
# ============================================================================

Write-Step "Resumen final de verificación" -Number 10 -Total 10

if ($allBuildOk -and $blocksCompiled.Count -gt 0) {
    Write-Header "✓ BUILD CLÍNICO COMPLETADO EXITOSAMENTE"
    
    Write-Host "El plugin EIPSI Forms está listo para uso clínico." -ForegroundColor Cyan
    Write-Host ""
    
    Write-Success "Archivos base generados:"
    Write-Info "  • build/index.js"
    Write-Info "  • build/index.css"
    Write-Info "  • build/style-index.css"
    Write-Host ""
    
    Write-Host "Bloques compilados exitosamente:" -ForegroundColor Green
    foreach ($block in $blocksCompiled) {
        Write-Host "  • $block" -ForegroundColor Gray
    }
    
    Write-Host "`nTotal de bloques: $($blocksCompiled.Count)" -ForegroundColor Green
    Write-Host ""
    
    Write-Success "Todos los artefactos de build están presentes y validados."
    Write-Host ""
    
    Exit-Script 0
} else {
    Write-Header "✗ ERROR EN EL BUILD"
    
    Write-Error "El build no cumple con los requisitos de EIPSI Forms"
    Write-Host ""
    
    Write-Warning "Sugerencias de corrección:"
    Write-Info "1. Ejecuta: npm run build"
    Write-Info "2. Verifica errores de lint: npm run lint:js"
    Write-Info "3. Revisa los archivos base en build/ (index.js, index.css, style-index.css)"
    Write-Info "4. Revisa la carpeta build/blocks/ para bloques incompletos"
    Write-Info "5. Verifica que todos los bloques tengan los 3 archivos requeridos:"
    Write-Info "   - index.js"
    Write-Info "   - index.css"
    Write-Info "   - style-index.css"
    Write-Host ""
    
    Write-Warning "Si el problema persiste, revisa la salida del build anterior."
    Write-Host ""
    
    Exit-Script 1
}
