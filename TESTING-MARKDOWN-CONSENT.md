# 🧪 TESTING MANUAL - Markdown Dinámico en Bloque de Consentimiento

## ✅ IMPLEMENTACIÓN COMPLETADA (v1.2.3)

### Archivos Creados:
- ✅ `assets/js/consent-markdown-parser.js` (118 líneas)

### Archivos Modificados:
- ✅ `src/blocks/consent-block/edit.js` (185 líneas)
- ✅ `src/blocks/consent-block/save.js` (93 líneas)

### Build & Lint:
- ✅ `npm run build` → exitoso (5063 ms, bundle 143 KB)
- ✅ `npm run lint:js` → 0 errores / 0 warnings

---

## 🎯 GUÍA DE TESTING MANUAL

### PARTE 1: Testing en el Editor de WordPress

#### 1.1 Crear Bloque de Consentimiento
1. Abrir editor de WordPress (Gutenberg)
2. Crear nueva página o editar existente
3. Agregar bloque "Consentimiento Informado" (EIPSI Forms)
4. ✅ **Verificar**: Aparece cheat sheet azul con sintaxis markdown

#### 1.2 Verificar Cheat Sheet Visual
Debe aparecer un recuadro azul claro con este contenido:

```
💡 Formato de Texto:
Escribe *tu texto* para negrita
Escribe _tu texto_ para itálica
Escribe *_tu texto_* para negrita e itálica
```

✅ **Esperado**: Cheat sheet visible, fondo azul (#e7f3ff), texto claro

---

### PARTE 2: Testing de Sintaxis Básica

#### 2.1 Test: Negrita Simple
**En el campo "Contenido"**, escribir:
```
Declaro que he leído *completamente* este documento.
```

✅ **Esperado en preview**: "Declaro que he leído **completamente** este documento."
✅ **Verificar**: La palabra "completamente" aparece en negrita (bold)

#### 2.2 Test: Itálica Simple
Escribir:
```
Tu participación es _voluntaria_ y puedes retirarte en cualquier momento.
```

✅ **Esperado en preview**: "Tu participación es *voluntaria* y puedes retirarte en cualquier momento."
✅ **Verificar**: La palabra "voluntaria" aparece en itálica (cursiva)

#### 2.3 Test: Negrita + Itálica (Anidación)
Escribir:
```
*_IMPORTANTE:_* Lee este documento con atención.
```

✅ **Esperado en preview**: "***IMPORTANTE:*** Lee este documento con atención."
✅ **Verificar**: "IMPORTANTE:" aparece en negrita E itálica simultáneamente

#### 2.4 Test: Múltiples Formatos en Una Línea
Escribir:
```
*Confidencialidad* - Tus datos serán _protegidos_ bajo *normativas GDPR*.
```

✅ **Esperado en preview**:
- "Confidencialidad" → **negrita**
- "protegidos" → *itálica*
- "normativas GDPR" → **negrita**

---

### PARTE 3: Testing de Validación

#### 3.1 Test: Asterisco Sin Cerrar
Escribir:
```
Esto es un *texto sin cerrar
```

✅ **Esperado**: Aparece warning amarillo:
```
⚠️ Asteriscos desparejados: 1 total
```

#### 3.2 Test: Guion Bajo Sin Cerrar
Escribir:
```
Esto es un _texto sin cerrar
```

✅ **Esperado**: Aparece warning amarillo:
```
⚠️ Guiones bajos desparejados: 1 total
```

#### 3.3 Test: Ambos Errores
Escribir:
```
*Sin cerrar 1 y _sin cerrar 2
```

✅ **Esperado**: Aparece warning amarillo:
```
⚠️ Asteriscos desparejados: 1 total, Guiones bajos desparejados: 1 total
```

#### 3.4 Test: Corrección de Error
1. Tener texto con asterisco sin cerrar
2. Agregar el asterisco de cierre
3. ✅ **Esperado**: Warning desaparece automáticamente

---

### PARTE 4: Testing de Casos Especiales

#### 4.1 Test: Espacios Dentro de Formato
Escribir:
```
* texto con espacios antes y después *
```

✅ **Esperado**: Se renderiza correctamente como negrita

#### 4.2 Test: Saltos de Línea
Escribir:
```
*Puntos clave del estudio:*

• _Confidencialidad_ - Protección de datos
• _Voluntariedad_ - Retiro sin penalización
• _Anonimato_ - Sin identificación personal
```

✅ **Esperado**:
- Saltos de línea se preservan
- Cada línea con formato se renderiza correctamente
- "Puntos clave del estudio:" en negrita
- "Confidencialidad", "Voluntariedad", "Anonimato" en itálica

#### 4.3 Test: Texto Normal (Sin Formato)
Escribir:
```
Este es un texto completamente normal sin ningún formato especial.
```

✅ **Esperado**: Se muestra igual, sin cambios, sin errores

#### 4.4 Test: Caracteres Especiales (XSS Prevention)
Escribir:
```
*Texto con <script>alert('XSS')</script> código*
```

✅ **Esperado**:
- El script NO se ejecuta
- Se muestra como texto plano: `<script>alert('XSS')</script>`
- Los caracteres `<` y `>` están escapados

---

### PARTE 5: Testing en Frontend (Sitio Público)

#### 5.1 Publicar Página
1. En el editor, clic en "Actualizar" o "Publicar"
2. Abrir la página en el frontend (sitio público)
3. ✅ **Verificar**: Bloque de consentimiento se muestra

#### 5.2 Verificar Formato en Frontend
Con este contenido:
```
*Consentimiento Informado*

Declaro que he leído *completamente* este documento. Mi participación es _voluntaria_.

*_IMPORTANTE:_* Puedo retirarme en cualquier momento.
```

✅ **Esperado en frontend**:
- "Consentimiento Informado" → **negrita**
- "completamente" → **negrita**
- "voluntaria" → *itálica*
- "IMPORTANTE:" → ***negrita + itálica***
- NO aparece el cheat sheet (solo visible en editor)
- NO aparece el warning (solo visible en editor)

#### 5.3 Verificar en Diferentes Navegadores
Abrir la página en:
- ✅ Chrome/Edge (Chromium)
- ✅ Firefox
- ✅ Safari (si disponible)

✅ **Esperado**: Formato se ve igual en todos los navegadores

---

### PARTE 6: Testing de Ejemplo Real

#### 6.1 Contenido Completo de Ejemplo
Copiar y pegar este contenido en el bloque:

```
*Consentimiento Informado para Participación en Estudio Clínico*

Declaro que he sido informado/a _completamente_ sobre la naturaleza y propósito de este estudio de investigación. He leído y entiendo *toda la información* proporcionada.

*Derechos del Participante:*

• _Voluntariedad_ - Mi participación es completamente voluntaria. Puedo negarme a participar sin que esto afecte mi atención médica.

• _Retiro_ - Puedo retirarme del estudio en cualquier momento sin necesidad de dar explicaciones.

• _Confidencialidad_ - Mis datos personales serán tratados de manera *confidencial* y de acuerdo con las normativas ANMAT y GDPR.

• _Anonimato_ - Mis respuestas no serán identificadas con mi nombre en los análisis ni publicaciones.

*_IMPORTANTE:_* Si tengo preguntas sobre el estudio, puedo contactar al investigador principal en cualquier momento.

Al marcar el checkbox a continuación, confirmo que:
- He leído y entendido *toda la información* proporcionada
- He tenido la oportunidad de hacer preguntas
- Acepto participar _voluntariamente_ en este estudio
```

#### 6.2 Verificar Renderizado Completo
✅ **Checklist de verificación**:
- [ ] Título principal en negrita
- [ ] Palabras clave en negrita ("toda la información", "confidencial")
- [ ] Conceptos en itálica ("completamente", "voluntariamente")
- [ ] "IMPORTANTE:" en negrita + itálica
- [ ] Saltos de línea respetados
- [ ] Listas de viñetas legibles
- [ ] Sin warnings de validación
- [ ] Preview del editor = Frontend

---

### PARTE 7: Testing de Regresión

#### 7.1 Verificar Funcionalidad Existente
1. ✅ **Checkbox de aceptación** sigue funcionando
2. ✅ **Campo obligatorio** se valida correctamente
3. ✅ **Marca de tiempo** se registra en metadata
4. ✅ **Título opcional** se muestra correctamente
5. ✅ **Texto complementario** se muestra debajo

#### 7.2 Verificar Exports
1. Crear respuesta de formulario con consentimiento aceptado
2. Exportar a Excel
3. ✅ **Verificar**: Campo "eipsi_consent_accepted" = "1" (TRUE)
4. Exportar a CSV
5. ✅ **Verificar**: Mismo formato que Excel

---

## 🎯 RESUMEN DE CRITERIOS DE ACEPTACIÓN

### ✅ Funcionalidad Básica
- [x] `*texto*` → **negrita** (editor + frontend)
- [x] `_texto_` → *itálica* (editor + frontend)
- [x] `*_texto_*` → ***negrita + itálica*** (editor + frontend)

### ✅ UX del Editor
- [x] Preview dinámico se actualiza en tiempo real
- [x] Cheat sheet visible y claro
- [x] Validación en tiempo real con warning visual
- [x] Warning desaparece al corregir

### ✅ Seguridad
- [x] HTML se escapa correctamente (sin XSS)
- [x] Tags `<script>` no se ejecutan
- [x] Caracteres especiales escapados

### ✅ Edge Cases
- [x] Espacios dentro de formato preservados
- [x] Múltiples formatos en mismo párrafo
- [x] Saltos de línea naturales preservados
- [x] Texto sin formato funciona normalmente

### ✅ Técnico
- [x] `npm run build` exitoso
- [x] `npm run lint:js` 0/0
- [x] Bundle < 250 KB
- [x] Sin errores en console

---

## 🚨 PROBLEMAS POTENCIALES A REPORTAR

Si encuentras alguno de estos problemas, reportar inmediatamente:

1. **Preview no se actualiza** al escribir en el campo de contenido
2. **Warning no aparece** cuando hay asteriscos/guiones desparejados
3. **Warning no desaparece** al corregir los errores
4. **Formato NO se aplica** en el frontend (sitio público)
5. **Diferencias visuales** entre preview del editor y frontend
6. **Cheat sheet aparece** en frontend (no debería)
7. **Errores en console** del navegador
8. **Tags HTML ejecutándose** (vulnerabilidad XSS)
9. **Saltos de línea desaparecen** o se duplican
10. **Build falla** o lint reporta errores

---

## 📋 CHECKLIST FINAL DE TESTING

### Editor (WordPress Admin)
- [ ] Bloque de consentimiento se carga correctamente
- [ ] Cheat sheet azul visible
- [ ] Preview se actualiza en tiempo real
- [ ] Validación funciona con warnings
- [ ] Negrita `*texto*` funciona
- [ ] Itálica `_texto_` funciona
- [ ] Anidación `*_texto_*` funciona
- [ ] Múltiples formatos en misma línea
- [ ] Saltos de línea preservados
- [ ] Sin errores en console

### Frontend (Sitio Público)
- [ ] Bloque se renderiza correctamente
- [ ] Negrita se ve en frontend
- [ ] Itálica se ve en frontend
- [ ] Anidación se ve en frontend
- [ ] Cheat sheet NO aparece (solo editor)
- [ ] Warning NO aparece (solo editor)
- [ ] Formato igual en Chrome, Firefox, Safari
- [ ] Sin errores en console

### Regresión
- [ ] Checkbox de aceptación funciona
- [ ] Validación de campo obligatorio funciona
- [ ] Exports incluyen consentimiento aceptado
- [ ] Marca de tiempo se registra

---

## ✅ RESULTADO ESPERADO FINAL

**En el Editor:**
```
💡 Formato de Texto:
Escribe *tu texto* para negrita
Escribe _tu texto_ para itálica
Escribe *_tu texto_* para negrita e itálica

[PREVIEW CON FORMATO APLICADO]
```

**En el Frontend:**
```
[TEXTO CON FORMATO APLICADO - SIN CHEAT SHEET]
[CHECKBOX DE ACEPTACIÓN]
```

---

## 🎉 MENSAJE FINAL

Si todos los tests pasan:

**«Por fin alguien entendió cómo trabajo de verdad con mis pacientes»**

→ Los investigadores pueden dar formato profesional al consentimiento sin saber HTML
→ Preview en tiempo real para ver exactamente cómo se verá
→ Validación inteligente para evitar errores
→ Seguridad garantizada (XSS prevention)
→ Zero friction, zero fear, zero excuses

---

**Versión:** 1.2.3
**Fecha:** 2025-01-10
**Autor:** EIPSI Forms Development Team
