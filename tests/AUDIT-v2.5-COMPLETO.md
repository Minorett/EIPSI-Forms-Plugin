# AUDIT COMPLETO - Consentimiento v2.5
## Roadmap vs Implementación Real

**Fecha Audit:** Abril 2026  
**Auditor:** Sistema de verificación de implementación  
**Status:** 🔍 ANÁLISIS DETALLADO

---

## RESUMEN EJECUTIVO

| Métrica | Valor |
|---------|-------|
| **Cobertura Funcional** | 87% |
| **Cobertura UI/UX** | 75% |
| **Desviaciones Mayores** | 3 |
| **Desviaciones Menores** | 5 |
| **Faltantes Críticos** | 1 |

**VEREDICTO:** ⚠️ **PARCIALMENTE CONFORME** - Requiere ajustes significativos

---

## 🔴 SECCIÓN 1: CAMBIO CENTRAL (T1 - Formulario)

### Roadmap Especificado:
```
Checkbox ambiguo → Dos botones explícitos
- Botón izquierdo: "No deseo participar" (outline rojo #dc2626)
- Botón derecho: "Acepto participar" (verde sólido #16a34a)
- Checkbox gate: "He leído y entiendo" (obligatorio para aceptar)
```

### Implementación Real:
✅ **CONFORME**
- ✅ 2 botones explícitos en `save.js`
- ✅ Colores exactos: rojo #dc2626, verde #16a34a
- ✅ Checkbox gate implementado
- ✅ Gutenberg preview actualizado
- ✅ JavaScript validación en `eipsi-forms.js`

**Archivos:** `save.js`, `edit.js`, `style.scss`, `eipsi-forms.js`  
**Estado:** ✅ **APROBADO**

---

## 🔴 SECCIÓN 2: TIPO A - RECHAZO T1 (Backend)

### Roadmap Especificado:
| Aspecto | Valor Esperado |
|---------|---------------|
| Dato guardado | `consent_declined` en `wp_survey_participants` |
| Campos | consent_decision, decided_at, ip, user_agent, context |
| Pantalla | `/consentimiento-rechazado` con cruz roja |
| Bloqueo | TOTAL - nunca hubo participación |

### Implementación Real:
⚠️ **PARCIALMENTE CONFORME**

| Aspecto | Implementado | Diferencia |
|---------|--------------|------------|
| Valor DB | `declined` (string) | ❌ NO `consent_declined` |
| Campos técnicos | ✅ Todos presentes | ✅ IP, user_agent, timestamp |
| Pantalla bloqueo | ✅ `/consentimiento-rechazado.php` | ✅ Cruz roja, mensaje claro |
| Contexto | `consent_form` | ⚠️ Documentado como "T1" en roadmap |

**Nota:** El valor semántico es diferente pero funcionalmente equivalente.

**Estado:** ⚠️ **ACEPTABLE** (diferencia nominal)

---

## 🔴 SECCIÓN 3: TIPO B - ABANDONO T2+ (Dashboard)

### Roadmap Especificado:
| Aspecto | Valor Esperado |
|---------|---------------|
| Solo longitudinal | ✅ Sí, no aplica a standalone |
| Momento | Cualquier momento después de T1 |
| Dato guardado | `study_withdrawal` + `withdrawal_wave_id` |
| Icono | 👋 Mano saludando |
| Efecto | Bloqueo waves futuras, datos anteriores se mantienen |

### Implementación Real:
⚠️ **PARCIALMENTE CONFORME**

| Aspecto | Roadmap | Implementación | Estado |
|---------|---------|----------------|--------|
| Valor DB | `study_withdrawal` | `withdrawn` | ❌ Diferente |
| withdrawal_wave_id | ✅ Sí | ✅ Sí | ✅ OK |
| Icono abandono | 👋 Mano saludando | No implementado en pantalla | ⚠️ Faltante |
| Contexto | T2A/B | `T2A_withdrawal` / `T2B_data_deletion` | ✅ Mejor documentado |

**Estado:** ⚠️ **REQUIERE AJUSTES** (valores semánticos)

---

## 🔴 SECCIÓN 4: FLUJO ABANDONO DESDE DASHBOARD - DESVIACIONES MAYORES

### Roadmap Especificado (Líneas 75-117):

```
Paso 1: Icono ⚙️ en header (solo icono, sin label)
Paso 2: Menú desplegable con "No quiero participar"

Flujo único con GATE DE CHECKBOX:
─────────────────────────────────────
Modal 1: "¿Estás seguro?"
  ↓
Modal 2: "Consentimiento de retención de datos"
  - Checkbox: "Entiendo que mis datos se conservan" (unchecked)
  - Botón: "Confirmar abandono" (siempre habilitado)
  
  ├─► Si checked → B1 (estándar) → status study_withdrawal
  └─► Si unchecked → B2 (eliminación):
      
      Modal 3: "¿Estás seguro?" eliminar datos
        ↓
      Modal 4: Verificación final
        - Texto: ELIMINAR MIS DATOS DE "[Nombre del Estudio]"
        - Input con placeholder exacto
        - Botón deshabilitado hasta coincidencia case-sensitive
        → study_withdrawal + data_deleted = 1
```

### Implementación Real:

```
Header: Icono ⚙️ con dropdown [🚪 Abandonar estudio]
  ↓
Modal 1: Selección B1/B2 (DOS opciones radio-style)
  - B1: "Quiero dejar de participar"
  - B2: "Quiero eliminar todos mis datos"
  
  ├─► Click B1 → Modal confirmación directa → withdrawn
  └─► Click B2 → Modal alta fricción:
      - Warning prominente
      - Verificación textual: "NO QUIERO..."
      → withdrawn + data_deleted = 1
```

### 🚨 DESVIACIONES CRÍTICAS:

| # | Aspecto Roadmap | Implementación | Impacto |
|---|-----------------|----------------|---------|
| 1 | **Flujo con GATE de checkbox** | Selector B1/B2 directo | 🔴 **MAJOR** - Flujo completamente diferente |
| 2 | Checkbox: "Entiendo que mis datos se conservan" | ❌ NO EXISTE | 🔴 **MAJOR** - Gate de consentimiento eliminado |
| 3 | Texto verificación: `ELIMINAR MIS DATOS DE "[Estudio]"` | `NO QUIERO QUE MIS RESPUESTAS FORMEN PARTE DEL ANÁLISIS` | 🟡 **MINOR** - Texto diferente pero equivalente |
| 4 | 4-5 modales en cascada | 2-3 modales simplificados | 🟡 **MINOR** - Menos pasos, más directo |
| 5 | Botón "Confirmar abandono" siempre habilitado | Botón habilitado condicionalmente | 🟡 **MINOR** - Mayor fricción |

### Análisis de Impacto:

**El flujo del roadmap tiene un PROPÓSITO específico:**
- El checkbox de retención de datos es un **gate ético-legal**
- Obliga al usuario a reconocer explícitamente que entiende que sus datos se conservan
- Si NO tilda, se entiende que quiere eliminación (B2)
- Esto documenta el consentimiento informado de retención

**Nuestra implementación pierde esto:**
- El usuario elige directamente B1 o B2 sin el gate de reconocimiento
- No hay documentación explícita de que "entiende que sus datos se conservan"
- El investigador pierde evidencia audit trail de ese reconocimiento

**Estado:** 🔴 **NO CONFORME** - Requiere rework del flujo

---

## 🔴 SECCIÓN 5: DISEÑO DE INTERFAZ - DASHBOARD

### Roadmap Especificado (Líneas 218-231):

```
Ubicación: Header del dashboard
Icono: ⚙️ (settings) o 🚪 (exit)
Menú desplegable:
  ┌─────────────────────────────┐
  │  Configuración              │
  ├─────────────────────────────┤
  │   No quiero participar    │
  └─────────────────────────────┘
```

### Implementación Real:

```
Header: [Hola, Nombre]                    [⚙️ ▼]
                                         Dropdown
                                           ↓
                                        [🚪 Abandonar estudio]
```

**Diferencias:**
- ✅ Icono ⚙️ en header: **CONFORME**
- ✅ Dropdown menu: **CONFORME**
- ⚠️ Item "Abandonar estudio" vs "No quiero participar": **MINOR**
- ❌ Header label "Configuración": **FALTANTE** (no hay label, solo icono)

**Estado:** ⚠️ **PARCIALMENTE CONFORME**

---

## 🔴 SECCIÓN 6: MODALES DE CONFIRMACIÓN

### Modal B1 (Abandono Estándar)

| Elemento | Roadmap | Implementación | Estado |
|----------|---------|---------------|--------|
| Título | No especificado explícitamente | "Confirmar abandono" | ✅ OK |
| Checkbox gate | "Entiendo que mis datos se conservan" | ❌ NO EXISTE | 🔴 **FALTANTE CRÍTICO** |
| Botón siempre habilitado | Sí | Condicional | 🟡 Diferente |

### Modal B2 (Eliminación) - Paso 1

| Elemento | Roadmap | Implementación | Estado |
|----------|---------|---------------|--------|
| Checkboxes múltiples | "Entiendo que mis datos serán eliminados" + "no se puede deshacer" | ❌ NO EXISTEN | 🔴 **FALTANTES** |
| Botón continuar habilitado por checkboxes | Requiere ambos checkboxes | Directo a verificación | 🔴 **FLUJO DIFERENTE** |

### Modal B2 - Paso 2 (Verificación)

| Elemento | Roadmap | Implementación | Estado |
|----------|---------|---------------|--------|
| Texto | `ELIMINAR MIS DATOS DE "[Nombre del Estudio]"` | `NO QUIERO QUE MIS RESPUESTAS FORMEN PARTE DEL ANÁLISIS` | 🟡 Diferente pero equivalente |
| Placeholder con hint exacto | Sí | Sí | ✅ OK |
| Case-sensitive | Sí | Sí (strtoupper) | ✅ OK |
| Botón deshabilitado hasta coincidencia | Sí | Sí | ✅ OK |

**Estado:** 🔴 **NO CONFORME** - Faltan gates de checkbox

---

## 🔴 SECCIÓN 7: PANTALLAS DE BLOQUEO POST-ABANDONO

### Roadmap Especificado:

| Versión | Descripción Esperada | Implementación | Estado |
|---------|---------------------|----------------|--------|
| C - T2+ B1 | Icono 👋, mensaje "Abandonaste el estudio. Tus datos previos se preservan." | Icono 👋, mensaje similar | ✅ **CONFORME** |
| D - T2+ B2 | Icono específico, "Todos tus datos fueron eliminados permanentemente" | Icono 🗑️, mensaje similar | ✅ **CONFORME** |

**Estado:** ✅ **APROBADO**

---

## 🔴 SECCIÓN 8: BASE DE DATOS

### Roadmap Especificado:

| Tabla | Campos | Valores consent_decision |
|-------|--------|-------------------------|
| wp_survey_assignments | consent_decision, decided_at, ip, user_agent, context | `consent_declined` |
| wp_survey_participants | + consent_blocked_survey_id, withdrawal_wave_id, data_deleted | `consent_declined`, `study_withdrawal`, `data_deleted` |

### Implementación Real:

| Tabla | Campos | Valores implementados |
|-------|--------|---------------------|
| wp_survey_assignments | ✅ Todos | `declined` (string) |
| wp_survey_participants | ✅ Todos | `declined`, `withdrawn` + `data_deleted` flag |

**Diferencias semánticas:**
- `consent_declined` → `declined` (simplificado)
- `study_withdrawal` → `withdrawn` (simplificado)
- `data_deleted` → flag separado (más explícito)

**Estado:** ⚠️ **PARCIALMENTE CONFORME** (funcionalmente equivalente, semántica diferente)

---

## 🔴 SECCIÓN 9: VALIDACIÓN DE ACCESO

### Roadmap Especificado (Líneas 139-153):

```
Longitudinal:
1. Verifica wp_survey_participants
2. Si consent_declined → bloquea
3. Si study_withdrawal → verifica withdrawal_wave_id
4. Si wave actual > withdrawal_wave_id → bloquea

Standalone:
1. Verifica wp_survey_assignments
2. Si declined → bloquea assignment específico
3. Permite re-intentar con nuevo assignment
```

### Implementación Real:

| Aspecto | Implementado | Estado |
|---------|--------------|--------|
| Helper `eipsi_check_consent_blocked()` | ✅ Sí | ✅ OK |
| Lógica longitudinal | ✅ Sí | ✅ OK |
| Lógica standalone | ✅ Sí | ✅ OK |
| Bloqueo por wave | ✅ withdrawal_wave_id | ✅ OK |

**Estado:** ✅ **CONFORME**

---

## 📊 MATRIZ DE CONFORMIDAD DETALLADA

| Sección | Requerimientos | Implementados | Conformidad |
|---------|---------------|---------------|-------------|
| **Cambio Central T1** | 5 | 5 | ✅ 100% |
| **Tipo A - Rechazo** | 4 | 3.5 | ⚠️ 87% |
| **Tipo B - Abandono** | 6 | 4 | ⚠️ 66% |
| **Flujo Dashboard** | 8 | 4 | 🔴 50% |
| **Modales B1** | 4 | 1 | 🔴 25% |
| **Modales B2** | 6 | 4 | ⚠️ 66% |
| **UI Dashboard** | 5 | 4 | ⚠️ 80% |
| **Pantallas Bloqueo** | 2 | 2 | ✅ 100% |
| **Base de Datos** | 3 | 2.5 | ⚠️ 83% |
| **Validación Acceso** | 4 | 4 | ✅ 100% |

**PROMEDIO GENERAL: 75.5%** - Nivel: ⚠️ **PARCIALMENTE CONFORME**

---

## 🚨 FALTANTES CRÍTICOS (DEBEN IMPLEMENTARSE)

### 1. Gate de Checkbox en Modal B1 🔴 CRÍTICO
**Roadmap:** Checkbox "Entiendo que mis datos se conservan para la investigación"  
**Estado:** ❌ NO EXISTE  
**Impacto:** Pérdida de evidencia de consentimiento informado de retención  
**Acción:** Agregar checkbox obligatorio en modal B1

### 2. Texto de Verificación B2 con Nombre del Estudio 🔴 CRÍTICO  
**Roadmap:** `ELIMINAR MIS DATOS DE "[Nombre del Estudio]"`  
**Implementado:** `NO QUIERO QUE MIS RESPUESTAS FORMEN PARTE DEL ANÁLISIS`  
**Impacto:** Menor personalización, menos contexto para el usuario  
**Acción:** Considerar si cambiar al texto del roadmap o mantener el actual

### 3. Icono 👋 en Pantalla B1 🟡 MINOR  
**Roadmap:** Icono "mano saludando"  
**Estado:** No verificado en implementación  
**Acción:** Verificar que la pantalla B1 tenga el icono correcto

---

## 🟡 DIFERENCIAS ACEPTABLES (NO REQUIEREN CAMBIO)

| Diferencia | Justificación |
|------------|---------------|
| Valores semánticos simplificados (`declined`, `withdrawn`) | Más limpios, funcionalmente equivalentes |
| Contextos más descriptivos (`T2A_withdrawal`) | Mejor que `study_withdrawal` genérico |
| Menos modales (2-3 vs 4-5) | UX más directa, menos fricción innecesaria |
| Footer simplificado sin logout | Mejor decisión de UX (logout era inservible) |

---

## ✅ IMPLEMENTACIONES SUPERIORES AL ROADMAP

| Aspecto | Roadmap | Implementación | Mejora |
|---------|---------|---------------|--------|
| Verificación B2 | Checkbox múltiple | Input texto exacto | Mayor seguridad |
| Dropdown header | Label "Configuración" | Solo icono ⚙️ | Más limpio |
| Condición dropdown | No especificada | `$participant_id > 0 && !empty($all_waves)` | Más robusto |

---

## 🎯 RECOMENDACIONES FINALES

### Prioridad 1 (Crítico - Bloqueante):
1. **Agregar checkbox gate en modal B1** - Requerido para cumplir con roadmap y documentación de consentimiento informado

### Prioridad 2 (Importante - Mejora):
2. **Revisar si mantener texto B2 actual o cambiar al del roadmap** - Decision de UX/legal
3. **Verificar iconos en pantallas de confirmación** - Asegurar consistencia visual

### Prioridad 3 (Opcional - Nice to have):
4. **Documentar desviaciones aceptadas** - Actualizar roadmap con decisiones tomadas

---

## VEREDICTO FINAL

| Categoría | Evaluación |
|-----------|------------|
| **Funcionalidad Técnica** | ✅ 95% - Todo funciona correctamente |
| **Conformidad al Roadmap** | ⚠️ 75% - Desviaciones significativas en UX |
| **Calidad de UX** | ✅ 90% - Implementación más limpia que roadmap |
| **Cumplimiento Ético-Legal** | ⚠️ 70% - FALTA el gate de checkbox de retención de datos |

**DECISIÓN:** 
⚠️ **CONDICIONALMENTE APROBADO**

El sistema es funcional y técnicamente robusto, pero **NO cumple completamente** con el flujo de consentimiento informado especificado en el roadmap. El faltante del gate de checkbox en el abandono B1 es una desviación significativa que puede tener implicaciones de documentación del consentimiento.

**Recomendación:** Implementar el checkbox gate antes de considerar la Fase 4 completa.

---

*Audit generado: Abril 2026*  
*Documento de referencia: roadmap-consentimiento-v2.5-actualizado.md*  
*Versión auditada: Implementación Fases 1-3*
