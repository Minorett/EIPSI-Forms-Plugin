#!/usr/bin/env bash
#
# EIPSI Forms - Script de verificación de build clínico
#
# Este script verifica que el plugin se pueda construir correctamente
# y que todos los artefactos críticos estén presentes.
#
# Uso:
#   ./scripts/verify-build.sh
#
# Requisitos:
#   - Node.js >= 14.x
#   - npm >= 7.x
#

set -euo pipefail

# Colores para mensajes
GREEN='\033[0;32m'
BLUE='\033[0;34m'
RED='\033[0;31m'
NC='\033[0m' # No Color

echo -e "${BLUE}╔════════════════════════════════════════════════════════════╗${NC}"
echo -e "${BLUE}║  EIPSI Forms - Verificación de Build Clínico             ║${NC}"
echo -e "${BLUE}╚════════════════════════════════════════════════════════════╝${NC}"
echo ""

# [1/3] Instalar dependencias
echo -e "${BLUE}[1/3] Instalando dependencias...${NC}"
npm install --legacy-peer-deps
echo -e "${GREEN}✓ Dependencias instaladas correctamente${NC}"
echo ""

# [2/3] Ejecutar build
echo -e "${BLUE}[2/3] Ejecutando build de producción...${NC}"
npm run build
BUILD_EXIT_CODE=$?

if [ $BUILD_EXIT_CODE -ne 0 ]; then
    echo -e "${RED}✗ Error: El build falló con código de salida ${BUILD_EXIT_CODE}${NC}"
    exit 1
fi

echo -e "${GREEN}✓ Build ejecutado correctamente${NC}"
echo ""

# [3/3] Verificar artefactos críticos
echo -e "${BLUE}[3/3] Verificando artefactos críticos del plugin...${NC}"
echo ""

ARTIFACTS=(
    "build/index.js"
    "build/index.css"
    "build/style-index.css"
)

MISSING_FILES=()

for artifact in "${ARTIFACTS[@]}"; do
    if [ ! -f "$artifact" ]; then
        MISSING_FILES+=("$artifact")
        echo -e "${RED}✗ FALTA: ${artifact}${NC}"
    else
        SIZE=$(du -h "$artifact" | cut -f1)
        echo -e "${GREEN}✓ ${artifact} (${SIZE})${NC}"
    fi
done

echo ""

if [ ${#MISSING_FILES[@]} -gt 0 ]; then
    echo -e "${RED}╔════════════════════════════════════════════════════════════╗${NC}"
    echo -e "${RED}║  ERROR: Faltan ${#MISSING_FILES[@]} archivo(s) crítico(s)                      ║${NC}"
    echo -e "${RED}╚════════════════════════════════════════════════════════════╝${NC}"
    exit 1
fi

# Verificar tamaños mínimos (deben ser > 0 bytes)
for artifact in "${ARTIFACTS[@]}"; do
    SIZE_BYTES=$(stat -c%s "$artifact" 2>/dev/null || stat -f%z "$artifact" 2>/dev/null || echo "0")
    if [ "$SIZE_BYTES" -eq 0 ]; then
        echo -e "${RED}✗ ERROR: ${artifact} existe pero tiene tamaño 0 bytes${NC}"
        exit 1
    fi
done

# Todo OK
echo -e "${GREEN}╔════════════════════════════════════════════════════════════╗${NC}"
echo -e "${GREEN}║  ✓ BUILD CLÍNICO VERIFICADO CORRECTAMENTE                 ║${NC}"
echo -e "${GREEN}╚════════════════════════════════════════════════════════════╝${NC}"
echo ""
echo -e "${BLUE}El plugin EIPSI Forms está listo para uso clínico.${NC}"
echo ""

exit 0
