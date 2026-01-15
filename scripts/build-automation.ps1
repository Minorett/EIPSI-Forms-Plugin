#
# EIPSI Forms - Script de Automatización de Build (PowerShell)
# Compatibilidad: Arquitectura Modular de Bloques Gutenberg
#
# Este script automatiza el build completo del plugin con validación
# de arquitectura modular para bloques individuales.
#
# Uso:
#   powershell -ExecutionPolicy Bypass -File scripts/build-automation.ps1
#
# Requisitos:
#   - Node.js >= 14.x
#   - npm >= 7.x
#   - PowerShell 5.1 o superior
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
        [int]$Total = 9
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

function Format-FileSize {
    param([long]$Bytes)
    if ($Bytes -eq 0) { return "0 KB" }
    $KB = [math]::Round($Bytes / 1KB, 1)
    return "$KB KB"
}

function Exit-Script {
    param([int]$ExitCode = 0)
    
    if ($NoExit) {
        Write-Host ""
        Write-Host "Presiona Enter para continuar..." -ForegroundColor Gray
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
if (-not (Test-CommandExists "npm")) {
    Write-Error "npm no está instalado o no está en el PATH"
    Write-Info "Por favor instala Node.js y npm"
    Exit-Script 1
}

# Obtener versión de npm
$npmVersion = npm --version
Write-Info "npm version: $npmVersion"
Write-Host ""

# ============================================================================
# [1/9] VERIFICAR ESTRUCTURA DEL PLUGIN
# ============================================================================

Write-Step "Verificando estructura del plugin" -Number 1

$requiredPaths = @(
    "package.json",
    "src/blocks",
    "src/index.js"
)

$structureOk = $true
foreach ($path in $requiredPaths) {
    if (Test-Path $path) {
        Write-Info "Encontrado: $path"
    } else {
        Write-Error "Falta: $path"
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
# [2/9] ACTUALIZAR DEPENDENCIAS
# ============================================================================

Write-Step "Instalando/actualizando dependencias" -Number 2

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
# [3/9] LINT: VERIFICAR CÓDIGO JAVASCRIPT
# ============================================================================

Write-Step "Ejecutando linting de JavaScript" -Number 3

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
# [4/9] LINT: VERIFICAR DUPLICADOS DE FUNCIONES
# ============================================================================

Write-Step "Verificando duplicados de funciones" -Number 4

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
# [5/9] FORMATEAR CÓDIGO
# ============================================================================

Write-Step "Formateando código estilo WordPress" -Number 5

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
# [6/9] BUILD DE PRODUCCIÓN
# ============================================================================

Write-Step "Ejecutando build de producción" -Number 6

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
# [7/9] VERIFICAR BUILD: ARCHIVOS BASE
# ============================================================================

Write-Step "Verificando archivos base del build" -Number 7

$baseFiles = @(
    "build/index.js",
    "build/index.css",
    "build/style-index.css",
    "build/blocks"
)

$baseOk = $true
foreach ($file in $baseFiles) {
    if (Test-Path $file) {
        $size = Format-FileSize ((Get-Item $file).Length)
        Write-Success "Encontrado: $file ($size)"
    } else {
        Write-Error "Falta: $file"
        $baseOk = $false
    }
}

if (-not $baseOk) {
    Write-Host ""
    Write-Error "Archivos base del build incompletos"
    Exit-Script 1
}

Write-Host ""

# ============================================================================
# [8/9] VERIFICAR BUILD: BLOQUES INDIVIDUALES (MODULAR)
# ============================================================================

Write-Step "Verificando bloques individuales (arquitectura modular)" -Number 8

# Obtener lista de bloques compilados
$blocksDir = "build/blocks"
if (-not (Test-Path $blocksDir)) {
    Write-Error "No se encontró la carpeta de bloques compilados: $blocksDir"
    Exit-Script 1
}

$blockDirs = Get-ChildItem -Path $blocksDir -Directory

if ($blockDirs.Count -eq 0) {
    Write-Error "No se encontraron bloques compilados en $blocksDir"
    Exit-Script 1
}

Write-Info "Se encontraron $($blockDirs.Count) bloques compilados:"
Write-Host ""

$requiredBlockFiles = @("index.js", "index.css", "style-index.css")
$successfulBlocks = @()
$failedBlocks = @()

foreach ($blockDir in $blockDirs) {
    $blockName = $blockDir.Name
    $blockPath = $blockDir.FullName
    
    Write-Host "  Bloque '$blockName':" -ForegroundColor Gray -NoNewline
    
    $blockOk = $true
    $fileSizes = @()
    
    foreach ($requiredFile in $requiredBlockFiles) {
        $filePath = Join-Path -Path $blockPath -ChildPath $requiredFile
        
        if (Test-Path $filePath) {
            $file = Get-Item $filePath
            if ($file.Length -eq 0) {
                $blockOk = $false
            } else {
                $size = Format-FileSize $file.Length
                $fileSizes += $size
            }
        } else {
            $blockOk = $false
        }
    }
    
    if ($blockOk) {
        Write-Success "OK ($($fileSizes -join ', '))"
        $successfulBlocks += $blockName
    } else {
        Write-Error "ERROR (faltan archivos o vacíos)"
        $failedBlocks += $blockName
    }
}

Write-Host ""

# ============================================================================
# [9/9] RESUMEN FINAL Y VERIFICACIÓN
# ============================================================================

Write-Step "Resumen final de verificación" -Number 9

Write-Info "Bloques compilados exitosamente: $($successfulBlocks.Count)"
Write-Info "Bloques con errores: $($failedBlocks.Count)"
Write-Host ""

if ($successfulBlocks.Count -gt 0) {
    Write-Success "Bloques compilados correctamente:"
    foreach ($block in $successfulBlocks) {
        Write-Info "  - $block"
    }
    Write-Host ""
}

if ($failedBlocks.Count -gt 0) {
    Write-Error "Bloques con errores detectados:"
    foreach ($block in $failedBlocks) {
        Write-Info "  - $block"
    }
    Write-Host ""
}

# Determinar resultado final
$buildSuccess = ($successfulBlocks.Count -gt 0) -and ($failedBlocks.Count -eq 0)

if ($buildSuccess) {
    Write-Header "✓ BUILD CLÍNICO COMPLETADO EXITOSAMENTE"
    
    Write-Host "El plugin EIPSI Forms está listo para uso clínico." -ForegroundColor Cyan
    Write-Host ""
    
    $totalBlocks = $successfulBlocks.Count
    $blockList = $successfulBlocks -join ", "
    Write-Info "Total de bloques validados: $totalBlocks"
    Write-Info "Bloques: $blockList"
    Write-Host ""
    
    Write-Success "Todos los artefactos de build están presentes y validados."
    Write-Host ""
    
    Exit-Script 0
} else {
    Write-Header "✗ ERROR EN EL BUILD"
    
    Write-Error "El build no cumple con los requisitos de EIPSI Forms"
    Write-Host ""
    
    if ($failedBlocks.Count -gt 0) {
        Write-Warning "Sugerencias de corrección:"
        Write-Info "1. Ejecuta: npm run build"
        Write-Info "2. Verifica errores de lint: npm run lint:js"
        Write-Info "3. Revisa la carpeta build/blocks/ para bloques incompletos"
        Write-Info "4. Verifica que todos los bloques tengan los 3 archivos requeridos:"
        Write-Info "   - index.js"
        Write-Info "   - index.css"
        Write-Info "   - style-index.css"
        Write-Host ""
    }
    
    Write-Warning "Si el problema persiste, revisa la salida del build anterior."
    Write-Host ""
    
    Exit-Script 1
}