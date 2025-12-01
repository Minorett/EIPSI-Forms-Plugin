# Formularios de Prueba - QA Clínico EIPSI Forms

**Versión**: v1.2.2+  
**Fecha**: Febrero 2025  
**Propósito**: Guía práctica para testing manual de tickets 1–7

---

## 🎯 Objetivo

Estos 3 formularios de prueba cubren TODAS las funcionalidades implementadas en tickets 1–7:

1. **Formulario "Ingreso Ansiedad"** — VAS, Radio, Condicionales AND/OR, Navegación Multipágina
2. **Formulario "Evaluación Semanal"** — Likert, Checkbox, Descripción sin slug, Opciones con semicolon
3. **Formulario "Intake Rápido"** — Finalización personalizada, Privacidad, Fingerprint

---

## 📋 Formulario 1: Ingreso Ansiedad (Multipágina + Condicionales AND/OR)

### Configuración en Gutenberg

**Form Container:**
- Form ID: `ingreso_ansiedad_qa_01`
- Submit Button Label: `Enviar evaluación`
- Preset: `Clinical Blue`
- Allow Backwards Nav: ✅ ON
- Show Progress Bar: ✅ ON
- Use Custom Completion: ❌ OFF (usa global)

---

### Página 1: Estado General

**Bloque: Heading**
```
Página 1: Tu estado hoy
```

**Bloque: VAS Slider**
- Field Name: `ansiedad_actual`
- Question: `¿Cómo calificarías tu nivel de ansiedad en este momento?`
- Min Label: `Nada ansioso/a`
- Max Label: `Extremadamente ansioso/a`
- Required: ✅ ON

**Bloque: Radio**
- Field Name: `ataques_panico`
- Question: `¿Tuviste ataques de pánico esta semana?`
- Options: `Sí; No; No estoy seguro/a` (separados por `;`)
- Required: ✅ ON

**Bloque: Campo Descripción** (SIN slug)
- Label: `Instrucciones`
- Helper Text: `Las siguientes preguntas son parte de una evaluación estándar. Respondé con sinceridad.`
- Placeholder: `Recordá que tus respuestas son confidenciales.`

---

### Página 2: Profundización (condicional AND)

**Lógica de visibilidad de la página completa:**
```
Si ansiedad_actual >= 70 Y ataques_panico = "Sí" → mostrar esta página
```

**Bloque: Heading**
```
Página 2: Contanos más
```

**Bloque: Campo Descripción**
- Label: `Solo si cumplís ambas condiciones`
- Helper Text: `Esta página aparece si tu ansiedad es ≥ 70 Y tuviste ataques de pánico.`

**Bloque: Textarea**
- Field Name: `crisis_descripcion`
- Question: `¿Podrías contarnos brevemente qué está pasando?`
- Placeholder: `Escribí acá con tus propias palabras...`
- Required: ❌ OFF

**Bloque: Radio**
- Field Name: `ayuda_profesional`
- Question: `¿Estás recibiendo ayuda profesional actualmente?`
- Options: `Sí, con psicólogo/a; Sí, con psiquiatra; Ambos; No, todavía no`
- Required: ✅ ON

---

### Página 3: Síntomas Físicos (condicional OR)

**Lógica de visibilidad de la página completa:**
```
Si ansiedad_actual >= 50 O ataques_panico = "Sí" → mostrar esta página
```

**Bloque: Heading**
```
Página 3: Síntomas físicos
```

**Bloque: Checkbox (Multiple)**
- Field Name: `sintomas_fisicos`
- Question: `¿Cuáles de estos síntomas experimentaste esta semana? (podés elegir varios)`
- Options: `Palpitaciones, sudoración; Temblor, mareos; Náuseas, molestias estomacales; Respiración agitada; Ninguno de estos`
- Required: ✅ ON

---

### Testing Manual - Formulario 1

#### Test 1.1: Navegación básica multipágina
- [ ] Abrir formulario
- [ ] Página 1 muestra SOLO botón "Siguiente" (no "Anterior", no "Enviar")
- [ ] Completar campos y avanzar a Página 2
- [ ] Página 2 muestra "Anterior" y "Siguiente" (allowBackwardsNav ON)
- [ ] Avanzar a Página 3 (última)
- [ ] Página 3 muestra "Anterior" y "Enviar" (NO "Siguiente")
- [ ] Barra de progreso muestra "Página X de Y" correctamente

#### Test 1.2: Condicional AND (Página 2)
- [ ] Caso A: VAS=80 + RADIO="Sí" → Página 2 DEBE aparecer ✅
- [ ] Caso B: VAS=80 + RADIO="No" → Página 2 NO debe aparecer ❌
- [ ] Caso C: VAS=60 + RADIO="Sí" → Página 2 NO debe aparecer ❌
- [ ] Caso D: VAS=60 + RADIO="No" → Página 2 NO debe aparecer ❌

#### Test 1.3: Condicional OR (Página 3)
- [ ] Caso A: VAS=50 + RADIO="No" → Página 3 DEBE aparecer ✅
- [ ] Caso B: VAS=40 + RADIO="Sí" → Página 3 DEBE aparecer ✅
- [ ] Caso C: VAS=40 + RADIO="No" → Página 3 NO debe aparecer ❌

#### Test 1.4: VAS Slider alignment y valor 100
- [ ] Mover slider VAS a valor 100
- [ ] Verificar visualmente: thumb alineado con label "Extremadamente ansioso/a"
- [ ] Verificar valor guardado en Submissions: debe ser 100 (no 98 ni 99)

#### Test 1.5: Campo Descripción sin slug
- [ ] Verificar en editor: bloque Descripción NO muestra campo "Field Name"
- [ ] Completar formulario y enviarlo
- [ ] Ir a Submissions → ver detalles
- [ ] Verificar: NO aparece ninguna columna/respuesta del campo "Instrucciones"

#### Test 1.6: Opciones con semicolon (comas internas)
- [ ] Verificar opciones de checkbox "Palpitaciones, sudoración" se muestra correcta
- [ ] Seleccionar esa opción y enviar formulario
- [ ] Verificar en Submissions: valor guardado es "Palpitaciones, sudoración" completo

---

## 📋 Formulario 2: Evaluación Semanal (Likert + Checkbox + Descripción)

### Configuración en Gutenberg

**Form Container:**
- Form ID: `evaluacion_semanal_qa_02`
- Submit Button Label: `Enviar evaluación`
- Preset: `Clinical Green`
- Allow Backwards Nav: ❌ OFF (sin botón "Anterior")
- Show Progress Bar: ❌ OFF (sin barra)
- Use Custom Completion: ✅ ON

**Custom Completion Override:**
- Title: `¡Gracias por tu evaluación semanal!`
- Message: `Tus respuestas nos ayudan a hacer seguimiento de tu progreso.`
- Logo URL: *(dejar vacío)*
- Button Label: `Volver al inicio`

---

### Página 1: Estado de Ánimo (única página)

**Bloque: Heading**
```
¿Cómo te sentiste esta semana?
```

**Bloque: Campo Descripción**
- Label: `Instrucciones breves`
- Helper Text: `Respondé según cómo te sentiste la mayor parte del tiempo esta última semana.`

**Bloque: Likert Scale**
- Field Name: `utilidad_sesion`
- Question: `¿Qué tan útil te resultaron las sesiones de esta semana?`
- Min Label: `Nada útil`
- Max Label: `Muy útil`
- Scale Type: `1-5`
- Required: ✅ ON

**Bloque: Likert Scale**
- Field Name: `animo_general`
- Question: `¿Cómo estuvo tu ánimo en general?`
- Min Label: `Muy bajo`
- Max Label: `Muy alto`
- Scale Type: `1-7`
- Required: ✅ ON

**Bloque: Checkbox**
- Field Name: `dificultades_semana`
- Question: `¿Qué dificultades experimentaste? (podés marcar varias)`
- Options: `Dormir mal, insomnio; Falta de energía, cansancio; Dificultad para concentrarme; Pensamientos negativos recurrentes; Ninguna de las anteriores`
- Required: ✅ ON

**Bloque: Textarea** (condicional field visibility dentro de misma página)
- Field Name: `otras_dificultades`
- Question: `Si querés, contanos más sobre esas dificultades:`
- Placeholder: `Opcional...`
- Required: ❌ OFF
- **Conditional Logic**: Mostrar solo si `dificultades_semana` incluye cualquier opción EXCEPTO "Ninguna de las anteriores"

---

### Testing Manual - Formulario 2

#### Test 2.1: Navegación sin "Anterior" ni barra de progreso
- [ ] Abrir formulario
- [ ] Verificar que NO aparece botón "Anterior" en ningún momento
- [ ] Verificar que NO aparece "Página X de Y"
- [ ] Solo debe verse botón "Enviar" al final

#### Test 2.2: Finalización personalizada (override en container)
- [ ] Completar formulario y enviarlo
- [ ] Verificar que mensaje de gracias dice: "¡Gracias por tu evaluación semanal!"
- [ ] Verificar que mensaje secundario dice: "Tus respuestas nos ayudan..."
- [ ] Verificar que botón dice: "Volver al inicio"
- [ ] Click en botón → debe recargar página con formulario limpio

#### Test 2.3: Likert Scale 1-5 y 1-7
- [ ] Verificar que primer Likert muestra 5 opciones (1-5)
- [ ] Verificar que segundo Likert muestra 7 opciones (1-7)
- [ ] Seleccionar valor 3 en ambos
- [ ] Enviar y verificar en Submissions que valores son correctos (3 y 3)

#### Test 2.4: Opciones con comas y semicolon
- [ ] Verificar que opciones de checkbox se ven correctas:
  - "Dormir mal, insomnio" (con coma interna)
  - "Falta de energía, cansancio" (con coma interna)
- [ ] Seleccionar ambas y enviar
- [ ] Verificar en Submissions que ambas opciones se guardaron completas

#### Test 2.5: Condicional field visibility (dentro de misma página)
- [ ] Al cargar formulario, campo "otras_dificultades" debe estar oculto
- [ ] Marcar checkbox "Dormir mal, insomnio"
- [ ] Campo "otras_dificultades" DEBE aparecer inmediatamente ✅
- [ ] Desmarcar todas las opciones
- [ ] Campo "otras_dificultades" debe ocultarse ❌
- [ ] Marcar "Ninguna de las anteriores"
- [ ] Campo "otras_dificultades" debe permanecer oculto ❌

---

## 📋 Formulario 3: Intake Rápido (Privacidad + Fingerprint + Finalización Global)

### Configuración en Gutenberg

**Form Container:**
- Form ID: `intake_rapido_qa_03`
- Submit Button Label: `Enviar`
- Preset: `Clinical Purple`
- Allow Backwards Nav: ✅ ON
- Show Progress Bar: ✅ ON
- Use Custom Completion: ❌ OFF (usa configuración global)

**Configuración Global de Finalización** (Admin → Results & Experience → Finalización):
- Title: `¡Gracias por completar el formulario!`
- Message: `Tu información ha sido registrada correctamente.`
- Show Logo: ❌ OFF
- Button Text: `Comenzar de nuevo`
- Button Action: `Reload page`

**Configuración de Privacidad** (Admin → Results & Experience → Privacy & Metadata):
- Seleccionar formulario: `intake_rapido_qa_03`
- Device Type: ✅ ON
- IP Address: ✅ ON
- Browser: ✅ ON
- OS: ✅ ON
- Screen Width: ✅ ON

---

### Página 1: Datos Básicos

**Bloque: Heading**
```
Información inicial
```

**Bloque: Text Field**
- Field Name: `edad`
- Question: `¿Cuántos años tenés?`
- Placeholder: `Ej: 28`
- Required: ✅ ON

**Bloque: Radio**
- Field Name: `genero`
- Question: `Género:`
- Options: `Femenino; Masculino; No binario; Prefiero no decir`
- Required: ✅ ON

**Bloque: Select**
- Field Name: `motivo_consulta`
- Question: `¿Cuál es el motivo principal de tu consulta?`
- Options: `Ansiedad; Depresión; Estrés; Problemas de pareja; Otro`
- Required: ✅ ON

---

### Página 2: Expectativas

**Bloque: Heading**
```
Tus expectativas
```

**Bloque: Textarea**
- Field Name: `expectativas`
- Question: `¿Qué esperás lograr con estas sesiones?`
- Placeholder: `Compartí con confianza...`
- Required: ❌ OFF

**Bloque: Radio**
- Field Name: `experiencia_terapia_previa`
- Question: `¿Hiciste terapia antes?`
- Options: `Sí; No`
- Required: ✅ ON

---

### Testing Manual - Formulario 3

#### Test 3.1: Finalización con configuración global
- [ ] Completar formulario y enviarlo
- [ ] Verificar que mensaje de gracias usa el texto de configuración global
- [ ] Verificar que título es: "¡Gracias por completar el formulario!"
- [ ] Verificar que mensaje es: "Tu información ha sido registrada correctamente."
- [ ] Verificar que botón dice: "Comenzar de nuevo"
- [ ] Click en botón → debe recargar página con formulario limpio

#### Test 3.2: Fingerprint liviano (metadatos técnicos)
- [ ] Completar formulario desde Chrome en Windows
- [ ] Ir a Submissions → seleccionar el envío reciente → ver detalles
- [ ] Click en "🖥️ Mostrar Detalles Técnicos del Dispositivo"
- [ ] Verificar datos capturados:
  - **Device Type**: desktop, mobile o tablet
  - **Browser**: ej. "Chrome 131" (con versión)
  - **OS**: ej. "Windows 10" (con versión)
  - **Screen Size**: ej. "1920x1080" (ancho x alto)
  - **IP Address**: dirección IP real
  - **Session ID**: código tipo "sess-1234567890-abc123"

#### Test 3.3: Privacidad - Toggles OFF
- [ ] Ir a Privacy & Metadata tab
- [ ] Desactivar toggles: Browser, OS, Screen Width
- [ ] Guardar configuración
- [ ] Completar formulario de nuevo
- [ ] Ir a Submissions → ver detalles
- [ ] Verificar que la sección "Detalles Técnicos del Dispositivo":
  - NO aparece (si todos los toggles están OFF)
  - O muestra "No disponible (toggle OFF)" para cada campo desactivado

#### Test 3.4: Diferentes dispositivos, misma IP
- [ ] Activar todos los toggles de privacidad
- [ ] Completar formulario desde:
  1. Desktop Chrome (Windows/Mac)
  2. Mobile Chrome (Android)
  3. Safari (iPhone/iPad)
- [ ] Los 3 desde la misma red wifi (misma IP)
- [ ] Ir a Submissions
- [ ] Verificar que las 3 submissions tienen:
  - Misma IP ✅
  - Browser diferente ✅
  - OS diferente ✅
  - Screen size diferente ✅
- [ ] **Conclusión clínica**: Podés distinguir 3 pacientes diferentes aunque tengan misma IP

---

## 🧪 Checklist General de QA (todos los formularios)

### Compatibilidad móvil
- [ ] Probar en Android (Chrome)
- [ ] Probar en iOS (Safari)
- [ ] Touch targets de radio/checkbox son fáciles de tocar (≥ 44x44 px)
- [ ] Cambio de orientación (portrait ↔ landscape) no rompe layout

### Dark Mode
- [ ] Toggle dark mode funciona en los 3 formularios
- [ ] Contraste WCAG AA se mantiene en modo oscuro
- [ ] Campos de texto son legibles (no texto gris sobre gris)
- [ ] Preferencia persiste al recargar página

### Submissions & Export
- [ ] Los 3 formularios aparecen en Submissions tab
- [ ] Filtrar por formulario funciona correctamente
- [ ] Exportar a Excel incluye todas las respuestas
- [ ] Exportar a CSV con encoding UTF-8 (tildes y ñ correctas)
- [ ] Metadatos técnicos aparecen en columnas separadas

### Build & Lint (verificación técnica)
- [ ] `npm run build` → sin errores
- [ ] `npm run lint:js` → 0 errors, 0 warnings
- [ ] Bundle size ≤ 250 KB

---

## 🎯 Criterios de Éxito del QA

✅ **QA aprobado si**:
- Todos los tests de los 3 formularios pasan
- No hay errores JavaScript visibles en consola del navegador
- No hay pérdida de datos en ningún caso
- La experiencia en mobile es fluida (sin zoom involuntario ni layout roto)

🔴 **QA bloqueado si**:
- Algún condicional AND/OR no funciona correctamente
- Campo Descripción aparece en Submissions (tiene slug)
- Finalización rompe o redirige a URL externa
- Fingerprint captura datos cuando toggle está OFF
- Opciones con comas internas se cortan o guardan mal

---

## 📝 Registro de Bugs Encontrados

Durante el QA, documentar cualquier bug usando este formato:

```markdown
### Bug #XX: [Título corto]

**Severidad**: ALTA / MEDIA / BAJA  
**Ticket relacionado**: X  
**Formulario afectado**: Ingreso Ansiedad / Evaluación Semanal / Intake Rápido  
**Descripción**: [Qué pasó exactamente]  
**Pasos para reproducir**:
1. [Paso 1]
2. [Paso 2]
3. [Paso 3]

**Comportamiento esperado**: [Qué debería pasar]  
**Comportamiento observado**: [Qué pasó realmente]  
**Screenshot/Video**: [Link si aplica]  
**Navegador/OS**: [ej. Chrome 131 / Windows 10]  
```

---

**Última actualización**: Febrero 2025  
**Versión del plugin**: v1.2.2  
**Autor**: AI Agent (EIPSI Forms Dev Team)  

---

**Regla de oro**:  
«¿Esto hace que un psicólogo clínico hispanohablante diga mañana:  
"Por fin alguien entendió cómo trabajo de verdad con mis pacientes"?»

Si después de probar estos 3 formularios la respuesta es **SÍ**, el QA está aprobado. 🎯
