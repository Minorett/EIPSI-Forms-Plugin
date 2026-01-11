# ✅ CHECKLIST RÁPIDO - Markdown en Consentimiento Informado

## 🚀 VALIDACIÓN RÁPIDA (5 minutos)

### ✅ PASO 1: Verificar Build
```bash
cd /home/engine/project
npm run build
npm run lint:js
```

**Resultado esperado:**
- [ ] Build exitoso (no errores)
- [ ] Lint exitoso (0/0)
- [ ] Bundle < 250 KB

---

### ✅ PASO 2: Abrir Editor de WordPress

1. **Ir a:** WordPress Admin → Páginas → Editar cualquier página
2. **Agregar bloque:** Buscar "Consentimiento Informado"
3. **Verificar visualmente:**
   - [ ] Bloque carga correctamente
   - [ ] Aparece cheat sheet azul con sintaxis
   - [ ] No hay errores en console (F12)

---

### ✅ PASO 3: Test de Sintaxis Básica (2 minutos)

#### Test 1: Negrita
**En el campo "Contenido", escribir:**
```
He leído *completamente* este documento.
```

**Verificar en preview:**
- [ ] "completamente" aparece en **negrita**

---

#### Test 2: Itálica
**Escribir:**
```
Mi participación es _voluntaria_.
```

**Verificar en preview:**
- [ ] "voluntaria" aparece en *itálica*

---

#### Test 3: Negrita + Itálica
**Escribir:**
```
*_IMPORTANTE:_* Lee con atención.
```

**Verificar en preview:**
- [ ] "IMPORTANTE:" aparece en ***negrita + itálica***

---

### ✅ PASO 4: Test de Validación (1 minuto)

#### Test 1: Error detectado
**Escribir:**
```
*sin cerrar
```

**Verificar:**
- [ ] Aparece warning amarillo: "⚠️ Asteriscos desparejados: 1 total"

---

#### Test 2: Error corregido
**Agregar asterisco de cierre:**
```
*cerrado correctamente*
```

**Verificar:**
- [ ] Warning desaparece automáticamente
- [ ] Texto aparece en negrita en preview

---

### ✅ PASO 5: Test en Frontend (2 minutos)

1. **Publicar página** (clic en "Actualizar" o "Publicar")
2. **Abrir en navegador** (Vista previa → Ver en sitio)

**Verificar:**
- [ ] Formato se aplica correctamente (negrita, itálica)
- [ ] Cheat sheet NO aparece (solo en editor)
- [ ] Warning NO aparece (solo en editor)
- [ ] Texto es seleccionable (prueba copiar-pegar)
- [ ] No hay errores en console (F12)

---

## 🎯 CHECKLIST COMPLETO (Opcional - 15 minutos)

### Sintaxis Avanzada
- [ ] Múltiples formatos en una línea: `*bold* y _italic_ juntos`
- [ ] Espacios dentro: `* texto con espacios *`
- [ ] Saltos de línea preservados (Enter entre párrafos)
- [ ] Listas de viñetas con formato: `• _Confidencialidad_ - texto`

### Casos Edge
- [ ] Texto sin formato funciona normal
- [ ] Caracteres especiales escapados: `*<script>alert('XSS')</script>*` → no se ejecuta
- [ ] Doble asterisco ignorado: `**texto**` → se muestra como está

### Regresión
- [ ] Checkbox de aceptación funciona
- [ ] Campo obligatorio valida correctamente
- [ ] Marca de tiempo se registra en metadata
- [ ] Exports incluyen consentimiento aceptado
- [ ] Otros bloques no afectados

---

## 🚨 PROBLEMAS A REPORTAR

Si encuentras alguno de estos, reportar inmediatamente:

1. **Build/Lint falla**
   - [ ] npm run build → errores
   - [ ] npm run lint:js → errores

2. **Preview no funciona**
   - [ ] Preview no se actualiza al escribir
   - [ ] Formato no se aplica en preview

3. **Validación no funciona**
   - [ ] Warning no aparece con errores
   - [ ] Warning no desaparece al corregir

4. **Frontend no funciona**
   - [ ] Formato no se aplica en sitio público
   - [ ] Cheat sheet aparece en frontend (no debería)
   - [ ] Errores en console del navegador

5. **Seguridad**
   - [ ] Tags `<script>` se ejecutan (CRÍTICO)
   - [ ] HTML no escapado

---

## ✅ RESULTADO ESPERADO FINAL

### En el Editor:
```
┌─────────────────────────────────────────────┐
│ 💡 Formato de Texto:                        │
│ Escribe *tu texto* para negrita            │
│ Escribe _tu texto_ para itálica            │
│ Escribe *_tu texto_* para negrita e itálica│
└─────────────────────────────────────────────┘

[PREVIEW CON FORMATO APLICADO]
```

### En el Frontend:
```
[TEXTO CON FORMATO - SIN CHEAT SHEET]
☐ He leído y acepto los términos y condiciones *
```

---

## 🎉 SI TODOS LOS TESTS PASAN

**Resultado:** ✅ IMPLEMENTACIÓN EXITOSA

**Mensaje para investigadores:**
> **«Por fin alguien entendió cómo trabajo de verdad con mis pacientes»**

**Beneficios logrados:**
- ✅ Formato profesional sin HTML
- ✅ Preview en tiempo real
- ✅ Validación automática
- ✅ Seguridad garantizada
- ✅ Zero friction

---

## 📋 CHECKLIST DE ARCHIVOS CREADOS/MODIFICADOS

### Archivos Nuevos
- [x] `/home/engine/project/assets/js/consent-markdown-parser.js`
- [x] `/home/engine/project/TESTING-MARKDOWN-CONSENT.md`
- [x] `/home/engine/project/EJEMPLO-CONSENTIMIENTO-MARKDOWN.md`
- [x] `/home/engine/project/RESUMEN-IMPLEMENTACION-MARKDOWN.md`
- [x] `/home/engine/project/CHECKLIST-MARKDOWN-CONSENT.md`

### Archivos Modificados
- [x] `/home/engine/project/src/blocks/consent-block/edit.js`
- [x] `/home/engine/project/src/blocks/consent-block/save.js`

### Build Artifacts
- [x] `/home/engine/project/build/index.js` (143 KB)
- [x] `/home/engine/project/build/index.asset.php`

---

## 🚀 COMANDO RÁPIDO DE VERIFICACIÓN

```bash
# Desde la raíz del proyecto
npm run build && npm run lint:js && echo "✅ BUILD & LINT PASS"
```

**Si no hay errores, estás listo para testing manual en WordPress.**

---

**Versión:** 1.2.3
**Feature:** Markdown Dinámico en Consentimiento Informado
**Status:** ✅ READY FOR TESTING
**Tiempo estimado de testing:** 5-15 minutos
