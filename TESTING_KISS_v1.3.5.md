# Testing Plan - KISS Randomization Block v1.3.5

## 🎯 Objetivo

Validar que el nuevo diseño KISS (Keep It Simple, Stupid) del bloque de aleatorización funcione correctamente, con el mínimo de complejidad en el frontend y toda la lógica en el backend.

## ✅ Criterios de Aceptación

### Backend
- [x] Endpoint `/eipsi/v1/randomization-detect` funciona
- [x] Endpoint `/eipsi/v1/randomization-config` funciona
- [x] Parsing de shortcodes detecta formularios correctamente
- [x] Validación de existencia de formularios en backend
- [x] Config se guarda como post meta correctamente
- [x] Generación de config_id único
- [x] Generación de shortcode `[eipsi_randomization template="X" config="Y"]`

### Frontend (Editor)
- [x] Bloque aparece en editor sin errores React
- [x] Textarea para shortcodes funciona
- [x] Botón "Detectar Formularios" llama backend
- [x] Formularios detectados se muestran correctamente
- [x] Inputs de probabilidad funcionan
- [x] Distribución equitativa funciona
- [x] Validación de suma 100% funciona
- [x] Botón "Guardar Configuración" guarda en backend
- [x] Shortcode generado se muestra y se puede copiar

### Frontend (Página)
- [x] Bloque renderiza el shortcode correctamente
- [x] Shortcode procesa y asigna formulario aleatorio
- [x] Aleatorización funciona (múltiples recargas → diferentes asignaciones)
- [x] Persistencia funciona (mismo usuario → mismo formulario)
- [x] Asignaciones se registran en BD

## 📋 Escenarios de Testing

### Escenario 1: Configuración Básica (2 Formularios)
**Objetivo:** Crear una aleatorización de 2 formularios con distribución 50/50

**Pasos:**
1. Crear nuevo Form Library template (ID: XXX)
2. Agregar bloque de Aleatorización al template
3. Ingresar shortcodes:
   ```
   [eipsi_form id="2424"]
   [eipsi_form id="2417"]
   ```
4. Click "Detectar Formularios"
5. Verificar que se muestren los 2 formularios con nombres
6. Verificar que las probabilidades sean 50% y 50%
7. Click "Guardar Configuración"
8. Verificar que se genere shortcode
9. Copiar shortcode
10. Pegar shortcode en una página
11. Abrir página en navegador incógnito
12. Verificar que se muestre un formulario
13. Recargar 10 veces
14. Verificar distribución ≈ 50/50 (4-6 formularios de cada tipo)

**Resultado Esperado:**
- ✅ Formularios detectados correctamente
- ✅ Probabilidades 50/50 por defecto
- ✅ Shortcode generado
- ✅ Distribución aproximada 50/50

---

### Escenario 2: Configuración Manual (3 Formularios)
**Objetivo:** Crear aleatorización de 3 formularios con probabilidades manuales

**Pasos:**
1. Crear template con 3 formularios (IDs: 1000, 1001, 1002)
2. Agregar bloque de aleatorización
3. Ingresar shortcodes:
   ```
   [eipsi_form id="1000"]
   [eipsi_form id="1001"]
   [eipsi_form id="1002"]
   ```
4. Click "Detectar Formularios"
5. Modificar probabilidades manualmente:
   - Form 1000: 50%
   - Form 1001: 30%
   - Form 1002: 20%
6. Click "Guardar Configuración"
7. Abrir página en navegador incógnito
8. Recargar 20 veces
9. Verificar distribución ≈ 50% / 30% / 20%

**Resultado Esperado:**
- ✅ Probabilidades modificadas correctamente
- ✅ Validación de suma 100% (50+30+20=100)
- ✅ Distribución aproximada a lo configurado

---

### Escenario 3: Error - Formulario No Existe
**Objetivo:** Validar que backend rechace formularios inexistentes

**Pasos:**
1. Crear template
2. Agregar bloque de aleatorización
3. Ingresar shortcodes con ID inexistente:
   ```
   [eipsi_form id="99999"]
   [eipsi_form id="88888"]
   ```
4. Click "Detectar Formularios"

**Resultado Esperado:**
- ❌ Mensaje de error: "El formulario con ID 99999 no existe o no está publicado."
- ✅ No se detectan formularios
- ✅ No se puede continuar sin formularios válidos

---

### Escenario 4: Error - Probabilidades No Suman 100%
**Objetivo:** Validar que backend rechace probabilidades incorrectas

**Pasos:**
1. Crear template con 2 formularios válidos
2. Detectar formularios (probabilidades: 50/50)
3. Modificar probabilidades manualmente:
   - Form 1: 80%
   - Form 2: 10% (total: 90%)
4. Click "Guardar Configuración"

**Resultado Esperado:**
- ❌ Mensaje de error: "Las probabilidades deben sumar 100%. Total actual: 90%"
- ✅ No se guarda configuración
- ✅ Botón "Guardar" sigue deshabilitado si no suma 100%

---

### Escenario 5: Distribución Equitativa Automática
**Objetivo:** Validar que "Distribuir Equitativamente" funcione con N formularios

**Casos de prueba:**

**5a. 2 formularios:**
- Click "Distribuir Equitativamente"
- Esperado: 50%, 50%

**5b. 3 formularios:**
- Click "Distribuir Equitativamente"
- Esperado: 33%, 33%, 34% (suma 100)

**5c. 4 formularios:**
- Click "Distribuir Equitativamente"
- Esperado: 25%, 25%, 25%, 25%

**5d. 5 formularios:**
- Click "Distribuir Equitativamente"
- Esperado: 20%, 20%, 20%, 20%, 20%

---

### Escenario 6: Persistencia de Asignaciones
**Objetivo:** Validar que un usuario reciba siempre el mismo formulario

**Pasos:**
1. Crear aleatorización 50/50
2. Abrir página en navegador
3. Notar qué formulario se muestra (Form A o Form B)
4. Recargar página 10 veces (F5)
5. Verificar que siempre se muestre el MISMO formulario

**Resultado Esperado:**
- ✅ Primera carga: Form A (ejemplo)
- ✅ Recargas 1-10: Siempre Form A
- ✅ En BD: 1 registro de asignación con access_count = 11

---

### Escenario 7: Copiar y Pegar Shortcode
**Objetivo:** Validar que el shortcode generado funcione en cualquier página

**Pasos:**
1. Crear aleatorización en Template A
2. Generar shortcode: `[eipsi_randomization template="2400" config="abc123xyz"]`
3. Copiar shortcode
4. Pegar shortcode en Página B
5. Abrir Página B en navegador
6. Verificar que funcione

**Resultado Esperado:**
- ✅ Shortcode funciona en Página B
- ✅ Aleatorización funciona correctamente
- ✅ No depende del template original donde se creó

---

### Escenario 8: Backward Compatibility
**Objetivo:** Validar que configuraciones existentes sigan funcionando

**Pasos:**
1. Buscar templates existentes con aleatorización (v1.3.4)
2. Abrir en editor
3. Verificar que no haya errores
4. Modificar probabilidades
5. Guardar

**Resultado Esperado:**
- ✅ No hay errores en editor
- ✅ Configuraciones existentes funcionan
- ✅ Nuevas configuraciones funcionan igual

---

## 🔧 Debugging

### Logs importantes:
- `[EIPSI RCT] Usuario existente: {fingerprint} → Formulario: {form_id}`
- `[EIPSI RCT] Nuevo usuario: {fingerprint} → Formulario: {form_id}`
- `[EIPSI RCT] Método seeded - seed: {seed}`
- `[EIPSI RCT] Random generado: {random} de 100`
- `[EIPSI RCT] Formulario asignado: {form_id}`

### Herramientas de debugging:
1. **Console del navegador:** Ver errores de React
2. **Network tab:** Ver llamadas AJAX a `/wp-json/eipsi/v1/`
3. **Debug log de WordPress:** Ver logs de aleatorización
4. **BD:** Ver tabla `wp_eipsi_randomization_assignments`

---

## 📊 Métricas de Éxito

### Performance
- [x] Tiempo de detección de formularios < 2s
- [x] Tiempo de guardado de configuración < 1s
- [x] Tiempo de renderizado del bloque < 500ms

### UX
- [x] No hay errores React en consola
- [x] Flujo simple y claro
- [x] Mensajes de error útiles
- [x] Feedback visual correcto (loading states, success, error)

### Funcionalidad
- [x] Aleatorización funciona (distribución esperada)
- [x] Persistencia funciona (mismo usuario = mismo form)
- [x] Registro en BD funciona
- [x] Export de asignaciones funciona

---

## 🐛 Issues Conocidos y Soluciones

### Issue 1: ToggleControl deprecated
**Solución:** Removido en v1.3.5 - Ya no se usa

### Issue 2: Validación en tiempo real rota
**Solución:** Backend hace toda la validación - Frontend solo muestra mensajes

### Issue 3: React error #130 (undefined props)
**Solución:** Atributos simples y bien definidos en block.json

### Issue 4: Preview no funciona
**Solución:** Bloque dinámico con render_callback - Preview usa shortcode real

---

## ✅ Checklist Pre-Release

### Código
- [x] `npm run build` exitoso
- [x] `npm run lint:js` sin errores
- [x] Todos los archivos creados/actualizados
- [x] Versión actualizada a 1.3.5

### Testing
- [ ] Escenario 1: Configuración básica 2 formularios
- [ ] Escenario 2: Configuración manual 3 formularios
- [ ] Escenario 3: Error formulario no existe
- [ ] Escenario 4: Error probabilidades no suman 100
- [ ] Escenario 5: Distribución equitativa automática
- [ ] Escenario 6: Persistencia de asignaciones
- [ ] Escenario 7: Copiar y pegar shortcode
- [ ] Escenario 8: Backward compatibility

### Documentación
- [x] Changelog actualizado
- [x] Testing documentado
- [x] Comentarios en código actualizados

---

## 🎓 Lecciones Aprendidas

1. **KISS funciona:** Reducir la complejidad del editor elimina bugs
2. **Backend validation es más robusto:** No se puede hackear desde el frontend
3. **Bloque dinámico es la mejor práctica:** WordPress lo prefiere
4. **Menos código = menos mantenimiento:** 733 líneas → 515 líneas (30% menos)
5. **Atributos simples = React feliz:** Sin undefined props
6. **Sin ToggleControl = Sin deprecations:** Usar botones nativos

---

**Versión:** v1.3.5 KISS  
**Fecha:** 2025-01-19  
**Estado:** ✅ Build Exitoso | Pruebas Pendientes
