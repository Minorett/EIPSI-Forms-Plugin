# VAS ALIGNMENT RETHINK – GUÍA DE VERIFICACIÓN POST-DEPLOY

## CHECKLIST DE VERIFICACIÓN EN PRODUCCIÓN

### 1. Verificación en Editor de WordPress

**Pasos:**
1. Abre un formulario (cualquier formulario con VAS)
2. Edita el bloque VAS Slider
3. Busca el panel "Label Alignment" en Inspector

**Verifica:**
- [ ] Ya NO hay slider visual RangeControl
- [ ] Solo hay input numérico "Valor:" con placeholder "0-200"
- [ ] Descripción dice: "0 = compactas | 100 = bien marcadas | >100 = separación extrema"
- [ ] Puedes cambiar el valor manualmente (ej: 0, 50, 100, 150, 200)
- [ ] El preview en el editor actualiza en tiempo real

**✅ Si pasa:** UI editor está correcta

---

### 2. Verificación en Frontend – Alignment = 100 (CRÍTICO)

**Setup:**
- Crea un formulario con un VAS que tenga:
  - Labels: "Nada;Algo;Bastante" (semicolon-separated)
  - Alignment: 100
  - Min: 0, Max: 100

**Pasos:**
1. Abre el formulario en navegador
2. Busca el bloque VAS

**Verifica - VISUALMENTE:**
- [ ] Label "Nada" comienza EXACTAMENTE en el borde izquierdo del slider
  - El "N" de "Nada" debe tocar la punta izquierda (sin espacio)
- [ ] Label "Algo" está CENTRADO perfectamente en el medio
- [ ] Label "Bastante" termina EXACTAMENTE en el borde derecho del slider
  - La "a" final de "Bastante" debe tocar la punta derecha (sin espacio)

**Visual esperado:**
```
Nada←←←[                      ]←←←Bastante
───────────────────SLIDER─────────────────
↑ Exacto                            ↑ Exacto
```

**❌ Si ves esto (INCORRECTO):**
```
[  Nada  ]      [Algo]      [Bastante  ]
                                    ↑ Espacio entre label y borde
```

**✅ Si pasa:** Positioning es correcto en alignment=100

---

### 3. Verificación Responsive – Desktop vs Tablet vs Mobile

**Desktop (1920px):**
```
Abre DevTools → F12 → Desactiva "Device Pixel Ratio"
Verifica: Labels tocan exactamente los extremos (igual a paso 2)
```

**Tablet (768px):**
```
DevTools → Toggle device toolbar → iPad (768×1024)
Verifica: Labels aún se posicionan correctamente
No hay overflow
```

**Mobile (375px):**
```
DevTools → Toggle device toolbar → iPhone (375×667)
Verifica: Labels son legibles
No hay overflow
Positioning es correcto (puede que labels sean más compactos)
```

**✅ Si pasa:** Responsive design funciona

---

### 4. Verificación de Todos los Alignment Values

**Crea un formulario TEST con 7 VAS idénticos, cada uno con diferente alignment:**

| ID Bloque | Alignment | Comportamiento Esperado |
|-----------|-----------|------------------------|
| vas-1 | 0 | Todos los labels se superponen en el centro |
| vas-2 | 25 | Labels algo separados |
| vas-3 | 50 | Labels moderadamente separados |
| vas-4 | 75 | Labels bastante separados |
| vas-5 | **100** | **Labels TOCAN exactamente extremos** ✅ |
| vas-6 | 150 | Labels sobrepasan extremos |
| vas-7 | 200 | Labels sobrepasan MÁS |

**Pasos:**
1. Abre el formulario TEST
2. Observa cada VAS
3. Compara visualmente la progresión

**✅ Si pasa:** Progresión es visible y correcta

---

### 5. Verificación de Dark Mode

**Pasos:**
1. Activa Dark Mode en el formulario (si existe toggle)
2. Observa el VAS

**Verifica:**
- [ ] Labels aún son visibles
- [ ] Colores contrastan suficientemente (WCAG AA mínimo)
- [ ] Positioning NO ha cambiado (labels aún tocan extremos si alignment=100)
- [ ] Background del slider y label containers son visibles

**✅ Si pasa:** Dark Mode compatible

---

### 6. Verificación de Conditional Logic

**Setup:**
- Crea un formulario con:
  - Campo A (Radio): "Mostrar VAS?", opciones "Sí/No"
  - Campo B (VAS): Conditional visible si Campo A = "Sí"

**Pasos:**
1. Abre el formulario
2. Selecciona "No" en Campo A
   - VAS debe desaparecer
3. Selecciona "Sí" en Campo A
   - VAS debe aparecer
   - Alignment debe ser correcto

**✅ Si pasa:** Conditional logic no afecta positioning

---

### 7. Verificación de Form Submit

**Pasos:**
1. Crea un formulario con un VAS (alignment=100)
2. Completa el formulario
3. Mueve el slider a un valor (ej: 50)
4. Envía el formulario

**Verifica en base de datos:**
- [ ] El valor enviado es correcto (50 en este caso)
- [ ] No hay errores en logs
- [ ] Si la BD externa está configurada, el registro aparece allí también

**✅ Si pasa:** Data submission funciona

---

### 8. Verificación de Edición en Editor

**Pasos:**
1. Abre un VAS en editor
2. Cambia el alignment de 100 a 50
3. Observa el preview
4. Cambio debe ser inmediato (tiempo real)
5. Cambia de nuevo a 100
6. Preview debe volver a mostrar labels tocando extremos

**✅ Si pasa:** Editor update es reactivo

---

### 9. Verificación de Labels Muy Largos

**Setup:**
- Crea un VAS con labels muy largos:
  - "Extremadamente poco bajo control"
  - "Algo bajo control"
  - "Extremadamente muy bajo control"

**Pasos:**
1. Alignment = 100
2. Abre en diferentes tamaños (desktop, tablet, mobile)

**Verifica:**
- [ ] Labels son completamente visibles
- [ ] No hay truncado (text-overflow: ellipsis)
- [ ] Labels aún tocan los extremos
- [ ] No rompen el layout

**✅ Si pasa:** Long labels handled correctly

---

### 10. Verificación de Casos Edge

**Test 1: Alignment = 0**
- [ ] Todos los labels en el centro, superpuestos
- [ ] Visibles pero superpuestos
- [ ] No rompen layout

**Test 2: Alignment = 200**
- [ ] Labels sobrepasan significativamente los extremos
- [ ] No hay errores visuales
- [ ] HTML se renderiza correctamente

**Test 3: Alignment = 75 (valor intermedio)**
- [ ] Labels están separados pero no en extremos
- [ ] Progresión visual entre 50 y 100 es suave

**✅ Si pasa:** Edge cases handled

---

## TROUBLESHOOTING

### Problema: Labels no tocan los extremos (alignment=100)

**Causa probable:** CSS no se cargó correctamente

**Solución:**
1. Purga caché del navegador (Ctrl+Shift+Delete)
2. Purga caché de WordPress (si aplica)
3. Verifica que `assets/css/eipsi-forms.css` contiene las nuevas reglas CSS
4. Verifica que `npm run build` se ejecutó sin errores

**Comando:**
```bash
npm run build
# Verifica líneas 1166-1214 en eipsi-forms.css
```

---

### Problema: Input numérico del alignment no aparece en editor

**Causa probable:** JavaScript no se cargó

**Solución:**
1. Verifica que `src/blocks/vas-slider/edit.js` se compiló
2. Purga caché del editor WordPress
3. Recarga la página del editor
4. Verifica la consola (F12) por errores JavaScript

---

### Problema: Labels se superponen incorrectamente en alignment=0

**Causa probable:** Z-index issue

**Solución:**
```css
/* Agregar a .vas-multi-label si es necesario: */
z-index: auto;  /* default, pero explícito */
```

---

### Problema: Alignment funciona en editor pero no en frontend

**Causa probable:** Frontend no está usando el nuevo código

**Solución:**
1. Verifica que `src/blocks/vas-slider/save.js` fue actualizado
2. Ejecuta `npm run build` de nuevo
3. Purga caché de frontend
4. Verifica que el HTML generado contiene clases `--first` y `--last`

---

## PREGUNTAS FRECUENTES

### P: ¿Por qué cambiar el alignment max de 100 a 200?

**R:** Los valores > 100 permiten separación extrema de labels, útil para investigaciones específicas donde se necesita máxima claridad visual aunque los labels sobreposen los extremos del slider.

### P: ¿Por qué quitar el RangeControl visual?

**R:** Porque es más simple y preciso usar un input numérico directo. El RangeControl visual ocupaba espacio innecesario y limitaba a 0-100, ahora el usuario puede escribir cualquier valor 0-200.

### P: ¿Es backward compatible?

**R:** Sí. Los formularios antiguos siguen funcionando. Si tenían alignment antiguo, se migra automáticamente el valor.

### P: ¿Afecta a los datos guardados?

**R:** No. El alignment es solo una propiedad visual del diseñador (block attribute). Los valores del paciente (0-100 en el slider) son completamente independientes.

---

## REPORTE POST-DEPLOY

**Después de verificar TODO, reporta:**

```
✅ VAS Alignment Rethink - POST-DEPLOY VERIFICATION

1. Editor UI: ✅ PASS (input numérico, sin RangeControl)
2. Frontend - Alignment 100: ✅ PASS (labels tocan exactamente)
3. Responsive (Desktop/Tablet/Mobile): ✅ PASS
4. Alignment progression (0-200): ✅ PASS
5. Dark Mode: ✅ PASS
6. Conditional Logic: ✅ PASS
7. Form Submit: ✅ PASS
8. Editor Reactivity: ✅ PASS
9. Long Labels: ✅ PASS
10. Edge Cases: ✅ PASS

STATUS: VERIFIED IN PRODUCTION ✅

Psicólogo que abre VAS 2025 piensa:
"Por fin alguien entendió cómo trabajo de verdad con mis pacientes."
```

---

## CONTACTO / ESCALATION

Si alguna verificación **FALLA**:

1. Describe el problema exacto
2. Incluye screenshot/video
3. Incluye tamaño de pantalla
4. Incluye valor de alignment testeado
5. Incluye navegador y versión
6. Ejecuta:
   ```bash
   npm run build
   npm run lint:js
   ```
   E incluye el output

---

**Fecha de verificación: [Completar]**  
**Testeado por: [Completar]**  
**Environment: [Completar - dev/staging/production]**  
**Status: [Completar - PASS/FAIL]**
