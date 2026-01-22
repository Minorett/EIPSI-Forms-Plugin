#
# EIPSI Forms - Script de verificación de build clínico (PowerShell)
#
# Este script verifica que el plugin se pueda construir correctamente
# y que todos los artefactos críticos estén presentes.
#
# Uso:
#   .\scripts\verify-build.ps1
#
# Requisitos:
#   - Node.js >= 14.x
#   - npm >= 7.x
#   - PowerShell 5.1 o superior
#

# Configurar para detener en errores
$ErrorActionPreference = "Stop"

# Función para escribir mensajes de colores
function Write-ColorOutput {
    param(
        [string]$Message,
        [string]$Color = "White"
    )
    Write-Host $Message -ForegroundColor $Color
}

Write-ColorOutput "╔════════════════════════════════════════════════════════════╗" "Cyan"
Write-ColorOutput "║  EIPSI Forms - Verificación de Build Clínico             ║" "Cyan"
Write-ColorOutput "╚════════════════════════════════════════════════════════════╝" "Cyan"
Write-Host ""

# [1/3] Instalar dependencias
Write-ColorOutput "[1/3] Instalando dependencias..." "Cyan"
try {
    npm install --legacy-peer-deps
    if ($LASTEXITCODE -ne 0) {
        throw "npm install falló con código de salida $LASTEXITCODE"
    }
    Write-ColorOutput "✓ Dependencias instaladas correctamente" "Green"
    Write-Host ""
} catch {
    Write-ColorOutput "✗ Error al instalar dependencias: $_" "Red"
    exit 1
}

# [2/3] Ejecutar build
Write-ColorOutput "[2/3] Ejecutando build de producción..." "Cyan"
try {
    npm run build
    if ($LASTEXITCODE -ne 0) {
        throw "npm run build falló con código de salida $LASTEXITCODE"
    }
    Write-ColorOutput "✓ Build ejecutado correctamente" "Green"
    Write-Host ""
} catch {
    Write-ColorOutput "✗ Error en el build: $_" "Red"
    exit 1
}

# [3/3] Verificar artefactos críticos
Write-ColorOutput "[3/3] Verificando artefactos críticos del plugin..." "Cyan"
Write-Host ""

$artifacts = @(
    "build\index.js"
)

$missingFiles = @()
$zeroSizeFiles = @()

foreach ($artifact in $artifacts) {
    if (-not (Test-Path $artifact)) {
        $missingFiles += $artifact
        Write-ColorOutput "✗ FALTA: $artifact" "Red"
    } else {
        $file = Get-Item $artifact
        $sizeKB = [math]::Round($file.Length / 1KB, 1)
        if ($file.Length -eq 0) {
            $zeroSizeFiles += $artifact
            Write-ColorOutput "✗ VACÍO: $artifact (0 bytes)" "Red"
        } else {
            Write-ColorOutput "✓ $artifact ($sizeKB KB)" "Green"
        }
    }
}

Write-Host ""

# Verificar errores
if ($missingFiles.Count -gt 0) {
    Write-ColorOutput "╔════════════════════════════════════════════════════════════╗" "Red"
    Write-ColorOutput "║  ERROR: Faltan $($missingFiles.Count) archivo(s) crítico(s)                      ║" "Red"
    Write-ColorOutput "╚════════════════════════════════════════════════════════════╝" "Red"
    exit 1
}

if ($zeroSizeFiles.Count -gt 0) {
    Write-ColorOutput "╔════════════════════════════════════════════════════════════╗" "Red"
    Write-ColorOutput "║  ERROR: $($zeroSizeFiles.Count) archivo(s) con tamaño 0 bytes                   ║" "Red"
    Write-ColorOutput "╚════════════════════════════════════════════════════════════╝" "Red"
    exit 1
}

# Todo OK
Write-ColorOutput "╔════════════════════════════════════════════════════════════╗" "Green"
Write-ColorOutput "║  ✓ BUILD CLÍNICO VERIFICADO CORRECTAMENTE                 ║" "Green"
Write-ColorOutput "╚════════════════════════════════════════════════════════════╝" "Green"
Write-Host ""
Write-ColorOutput "El plugin EIPSI Forms está listo para uso clínico." "Cyan"
Write-Host ""

exit 0
