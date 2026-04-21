# Manual Test Checklist: Pool de Estudios V2

> **Instrucciones**: Ejecutar estos tests en orden. Marcar con ✅ cuando pase, ❌ cuando falle, ⏸️ si no aplica.
> **Screenshots**: Tomar captura de cada pantalla clave (M1, M2, M5).
> **Reporte**: Enviar a Cascade con formato al final.

---

## Preparación (Setup)

### Paso 0: Crear estudios de prueba
- [ ] Ir a **EIPSI > Estudios Longitudinales > Crear**
- [ ] Crear estudio "MINDFULNESS_A" (T1)
- [ ] Crear estudio "MINDFULNESS_B" (T1)  
- [ ] Crear estudio "MINDFULNESS_C" (T1)
- [ ] Crear estudio "MINDFULNESS_D" (T1)
- [ ] Anotar los IDs numéricos de cada estudio

### Paso 0.5: Crear Pool de prueba
- [ ] Ir a **EIPSI > Pool Hub V2 > Crear Pool**
- [ ] Código: `POOL_TEST_2024`
- [ ] Descripción: "Estudio comparativo de intervenciones para ansiedad social"
- [ ] Mensaje incentivo: "Sorteo de 3 gift cards de $100 entre participantes"
- [ ] Añadir 4 estudios: MINDFULNESS_A, MINDFULNESS_B, MINDFULNESS_C, MINDFULNESS_D
- [ ] Probabilidades: 25% cada uno
- [ ] Guardar

---

## Test M1: Acceso al Pool (Interfaz Minimalista)

**URL**: `/pool/POOL_TEST_2024/`

### Checklist visual:
- [ ] Se ve el **título del pool** (POOL_TEST_2024)
- [ ] Se ve la **descripción** configurada
- [ ] Se ve el **mensaje de incentivo** (gift cards)
- [ ] **NO** se ve lista de estudios disponibles
- [ ] **NO** se ve duración estimada de estudios
- [ ] Campo de **email** visible y funcional
- [ ] **Checkbox** "Entiendo que seré asignado aleatoriamente" visible
- [ ] Botón "🚀 Participar en el Pool" visible

### **📸 SCREENSHOT REQUERIDO**: Pantalla completa del pool

**Estado**: ⬜ PASSED / ⬜ FAILED / ⬜ NOT TESTED

**Notas**: _________________________________

---

## Test M2: Primera Asignación (Flujo Completo)

### Pasos:
1. Email: `test_participant_001@test.com`
2. Marcar checkbox de consentimiento
3. Clic en "Participar"
4. Esperar asignación

### Checklist página "Asignación Exitosa":
- [ ] Aparece título "✅ ¡Asignación Exitosa!"
- [ ] Muestra **nombre del estudio asignado** (A, B, C o D)
- [ ] Muestra mensaje "Asignación aleatoria completada"
- [ ] Botón "📋 Comenzar mi estudio" visible y clickeable
- [ ] Footer muestra "Pool: POOL_TEST_2024"

### Verificación BD:
```sql
-- Ejecutar en phpMyAdmin o similar
SELECT * FROM wp_eipsi_pool_assignments 
WHERE participant_id LIKE '%test_participant_001%' 
AND pool_id = 'POOL_TEST_2024';
```

- [ ] Retorna **exactamente 1 fila**
- [ ] `study_id` corresponde al mostrado en pantalla
- [ ] `assigned_at` tiene fecha/hora reciente
- [ ] `ip_address` registrado

### **📸 SCREENSHOT REQUERIDO**: Página "Asignación Exitosa"

**Estudio asignado**: ⬜ A / ⬜ B / ⬜ C / ⬜ D

**Estado**: ⬜ PASSED / ⬜ FAILED / ⬜ NOT TESTED

---

## Test M3: Re-ingreso (Idempotencia)

### Escenario A: Mismo email, misma sesión
1. Sin cerrar navegador, volver a `/pool/POOL_TEST_2024/`

- [ ] **NO** se ve interfaz del pool de nuevo
- [ ] Redirect automático a estudio asignado
- [ ] URL final: `/estudio/MINDFULNESS_X/`

### Escenario B: Mismo email, nueva sesión (incógnito)
1. Abrir ventana incógnito
2. Ir a `/pool/POOL_TEST_2024/`
3. Ingresar mismo email: `test_participant_001@test.com`

- [ ] Muestra "Ya tenés un estudio asignado"
- [ ] O redirect automático al estudio
- [ ] **NO** crea nueva asignación en BD

### Verificación BD:
```sql
SELECT COUNT(*) as total, study_id 
FROM wp_eipsi_pool_assignments 
WHERE participant_id LIKE '%test_participant_001%'
GROUP BY study_id;
```

- [ ] Retorna **1 fila** (count=1)
- [ ] Mismo `study_id` que en M2

**Estado**: ⬜ PASSED / ⬜ FAILED / ⬜ NOT TESTED

---

## Test M4: Pool Saturado (Límite de Capacidad)

### Setup (hacer solo si se necesita test saturación):
1. Crear pool `POOL_TINY_TEST` con solo 2 slots totales
2. Target: Estudio_A=1, Estudio_B=1

### Pasos:
1. Asignar participante 1: `sat_001@test.com`
2. Asignar participante 2: `sat_002@test.com`
3. Intentar asignar participante 3: `sat_003@test.com`

### Checklist:
- [ ] Participante 1: Asignación exitosa
- [ ] Participante 2: Asignación exitosa
- [ ] Participante 3: Mensaje "Pool temporalmente cerrado" o similar
- [ ] **NO** se crea tercera asignación en BD

### Verificación BD:
```sql
SELECT COUNT(*) FROM wp_eipsi_pool_assignments 
WHERE pool_id = 'POOL_TINY_TEST';
-- Debe retornar: 2
```

**Estado**: ⬜ PASSED / ⬜ FAILED / ⬜ NOT TESTED / ⬜ SKIPPED

---

## Test M5: Experiencia Post-Asignación (Estudio Individual)

### Pasos:
1. Desde página de asignación, clic en "Comenzar mi estudio"
2. Completar al menos 2 páginas del formulario
3. Guardar progreso (Save & Continue)
4. Verificar dashboard del participante

### Checklist:
- [ ] Interfaz del estudio es **idéntica** a estudio individual normal
- [ ] **NO** se menciona "Pool" en ningún lado del estudio
- [ ] Save & Continue funciona normalmente
- [ ] Al volver, recupera progreso correctamente
- [ ] Dashboard muestra "Estudio MINDFULNESS_X" (no "Pool")
- [ ] Progreso porcentaje calculado correctamente

### **📸 SCREENSHOT REQUERIDO**: Dashboard del participante (con estudio asignado)

**Estado**: ⬜ PASSED / ⬜ FAILED / ⬜ NOT TESTED

---

## Test M6: Modo de Redirección (Transition vs Minimal)

### Test M6A: Modo Transición (default)
1. Verificar que el bloque Gutenberg tenga "Modo de acceso: Transición"
2. Acceder al pool

- [ ] Después de "Participar", aparece página intermedia
- [ ] Mensaje "¡Asignación Exitosa!" visible
- [ ] Botón "Comenzar mi estudio" requiere click adicional

### Test M6B: Modo Mínimo (1 click)
1. Cambiar en bloque Gutenberg a "Modo de acceso: Mínimo"
2. Guardar página
3. Acceder al pool con nuevo participante

- [ ] Después de "Participar", **redirect inmediato** al estudio
- [ ] **NO** hay página intermedia
- [ ] Botón integrado: "Participar - Entiendo asignación aleatoria"

**Estado M6A**: ⬜ PASSED / ⬜ FAILED / ⬜ NOT TESTED  
**Estado M6B**: ⬜ PASSED / ⬜ FAILED / ⬜ NOT TESTED

---

## Test M7: Exportación con Contexto de Pool (Fase 5)

### Pasos:
1. Ir a **EIPSI > Resultados** del estudio asignado (ej: MINDFULNESS_A)
2. Exportar a CSV
3. Abrir CSV en Excel

### Checklist:
- [ ] Columna "Pool Code" presente en headers
- [ ] Columna "Pool Assigned At" presente
- [ ] Participante del test tiene `pool_code` = `POOL_TEST_2024`
- [ ] Fecha de asignación coincide con M2
- [ ] Participantes NO del pool tienen "-" (guion)

**Estado**: ⬜ PASSED / ⬜ FAILED / ⬜ NOT TESTED

---

## Test M8: Admin Dashboard (Fase 3)

### Pasos:
1. Ir a **EIPSI > Pool Hub V2**
2. Seleccionar `POOL_TEST_2024`

### Checklist:
- [ ] Muestra total de participantes asignados
- [ ] Muestra distribución por estudio (gráfico o tabla)
- [ ] Porcentajes calculados correctamente
- [ ] Botón "Exportar asignaciones a CSV" funciona
- [ ] Botón "Pausar pool" funciona
- [ ] Botón "Cerrar pool" funciona (con confirmación)

**Estado**: ⬜ PASSED / ⬜ FAILED / ⬜ NOT TESTED

---

## Test M9: Aleatorización Estadística (Opcional - Requiere 40+ participantes)

Si tenés acceso a generar muchos participantes de test:

### Pasos:
1. Asignar 40 participantes al pool
2. Verificar distribución

### Checklist:
- [ ] Cada estudio tiene ~10 participantes (±3)
- [ ] Distribución aproximadamente equiprobable (25% ± 7.5%)

**Distribución observada**:
- Estudio A: ___ participantes (___%)
- Estudio B: ___ participantes (___%)
- Estudio C: ___ participantes (___%)
- Estudio D: ___ participantes (___%)

**Estado**: ⬜ PASSED / ⬜ FAILED / ⬜ SKIPPED

---

## 📝 FORMATO DE REPORTE FINAL

Copiar y pegar en mensaje a Cascade:

```
=== POOL SYSTEM V2 - TEST RESULTS ===
Fecha: YYYY-MM-DD
Tester: [Tu nombre]
Entorno: [Local/Staging/Producción]
Plugin Version: 2.5.4

--- RESUMEN ---
M1 (Interfaz Pool):        [✅/❌/⏸️]
M2 (Primera Asignación):   [✅/❌/⏸️] - Estudio asignado: [A/B/C/D]
M3 (Idempotencia):         [✅/❌/⏸️]
M4 (Pool Saturado):        [✅/❌/⏸️]
M5 (Post-Asignación):      [✅/❌/⏸️]
M6 (Modos Redirect):       [✅/❌/⏸️]
M7 (Export CSV):           [✅/❌/⏸️]
M8 (Admin Dashboard):      [✅/❌/⏸️]
M9 (Distribución Stats):   [✅/❌/⏸️]

--- ISSUES ENCONTRADOS ---
[Describir cualquier bug, error visual, o comportamiento inesperado]

1. 
2. 
3. 

--- RECOMENDACIÓN ---
[ ] LISTO para producción
[ ] NECESITA FIXES antes de producción
[ ] NO LISTO - requiere más trabajo

--- SCREENSHOTS ---
[Adjuntar imágenes: M1, M2, M5]
```

---

## 🐛 Issues Conocidos / Troubleshooting

### Issue 1: "404 en /pool/POOL_CODE/"
**Solución**: Ir a Ajustes > Enlaces permanentes > Guardar cambios (flush rewrite rules)

### Issue 2: "Participante ya asignado no redirige"
**Solución**: Verificar que cookie `eipsi_participant_id` esté seteada

### Issue 3: "Export CSV no tiene columnas de pool"
**Solución**: Verificar que el participante esté en `wp_eipsi_pool_assignments`

### Issue 4: "Aleatorización siempre asigna mismo estudio"
**Solución**: Normal con <5 participantes (varianza alta). Probar con 20+.

---

**Fin del Checklist** - ¡Gracias por testear! 🎉
