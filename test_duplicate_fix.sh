#!/bin/bash
# Test: Verificar que no hay declaraciones duplicadas de eipsi_create_manual_overrides_table

echo "🔍 Verificando fix de función duplicada..."
echo ""

# Test 1: Verificar que solo hay 1 declaración
echo "Test 1: Verificar declaración única"
COUNT=$(grep -r "^function eipsi_create_manual_overrides_table" admin/*.php | wc -l)
if [ "$COUNT" -eq 1 ]; then
    echo "✅ PASS - Solo 1 declaración encontrada"
else
    echo "❌ FAIL - Encontradas $COUNT declaraciones (esperado: 1)"
    exit 1
fi
echo ""

# Test 2: Verificar que manual-overrides-table.php se carga antes que randomization-db-setup.php
echo "Test 2: Verificar orden de carga"
LINE_MANUAL=$(grep -n "admin/manual-overrides-table.php" eipsi-forms.php | head -1 | cut -d: -f1)
LINE_RANDOM=$(grep -n "admin/randomization-db-setup.php" eipsi-forms.php | head -1 | cut -d: -f1)

if [ "$LINE_MANUAL" -lt "$LINE_RANDOM" ]; then
    echo "✅ PASS - manual-overrides-table.php (línea $LINE_MANUAL) se carga antes que randomization-db-setup.php (línea $LINE_RANDOM)"
else
    echo "❌ FAIL - Orden incorrecto"
    exit 1
fi
echo ""

# Test 3: Verificar que la función está definida en el archivo correcto
echo "Test 3: Verificar ubicación de la función"
if grep -q "^function eipsi_create_manual_overrides_table" admin/manual-overrides-table.php; then
    echo "✅ PASS - Función definida en admin/manual-overrides-table.php"
else
    echo "❌ FAIL - Función NO encontrada en admin/manual-overrides-table.php"
    exit 1
fi
echo ""

# Test 4: Verificar que randomization-db-setup.php NO tiene la función completa
echo "Test 4: Verificar que randomization-db-setup.php no tiene implementación duplicada"
if ! grep -A 5 "function eipsi_create_manual_overrides_table" admin/randomization-db-setup.php | grep -q "global \$wpdb"; then
    echo "✅ PASS - No hay implementación duplicada en randomization-db-setup.php"
else
    echo "❌ FAIL - Se encontró implementación duplicada"
    exit 1
fi
echo ""

echo "🎉 Todos los tests pasaron exitosamente!"
echo ""
echo "Resumen:"
echo "- ✅ Solo 1 declaración de eipsi_create_manual_overrides_table()"
echo "- ✅ Orden de carga correcto"
echo "- ✅ Función en el archivo correcto"
echo "- ✅ Sin duplicación de implementación"
