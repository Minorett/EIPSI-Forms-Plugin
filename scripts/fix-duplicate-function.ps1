# Script para eliminar el include duplicado de pool-assignment-api.php
# Ejecutar después de clonar/build

$eipsiFormsPath = "C:\Users\Mathi\Downloads\EIPSI-Forms\eipsi-forms.php"

Write-Host "🔧 Aplicando fix para función duplicada..." -ForegroundColor Cyan

if (Test-Path $eipsiFormsPath) {
    $content = Get-Content $eipsiFormsPath -Raw
    
    # Buscar y eliminar la línea que incluye pool-assignment-api.php
    $pattern = "require_once EIPSI_FORMS_PLUGIN_DIR \. 'admin/pool-assignment-api\.php';"
    
    if ($content -match $pattern) {
        $newContent = $content -replace [regex]::Escape($pattern + "`r`n"), ""
        $newContent = $newContent -replace [regex]::Escape($pattern + "`n"), ""
        
        Set-Content $eipsiFormsPath $newContent -NoNewline
        Write-Host "✅ Eliminado include duplicado de pool-assignment-api.php" -ForegroundColor Green
        
        # También eliminar el archivo si existe
        $apiFile = "C:\Users\Mathi\Downloads\EIPSI-Forms\admin\pool-assignment-api.php"
        if (Test-Path $apiFile) {
            Remove-Item $apiFile -Force
            Write-Host "✅ Eliminado archivo pool-assignment-api.php" -ForegroundColor Green
        }
    } else {
        Write-Host "⚠️ No se encontró la línea a eliminar (quizás ya fue eliminada)" -ForegroundColor Yellow
    }
} else {
    Write-Host "❌ No se encontró eipsi-forms.php" -ForegroundColor Red
}

Write-Host "🎉 Fix aplicado!" -ForegroundColor Green
