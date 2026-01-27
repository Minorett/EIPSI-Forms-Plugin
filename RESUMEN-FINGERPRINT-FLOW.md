# RESUMEN EJECUTIVO - Flujo de Fingerprint EIPSI Forms

## HALLAZGO PRINCIPAL ❌

**El fingerprint de dispositivo existe pero NO se envía en POST ni se exporta.**

---

## RESPUESTAS A LAS 3 PREGUNTAS CLAVE

### 1. ¿Viene fingerprint en POST del formulario completado?
**NO** ❌

- El archivo `eipsi-fingerprint.js` genera un fingerprint de dispositivo (canvas + webgl + screen)
- Este fingerprint se guarda en `sessionStorage`
- Se crea un input hidden `name="eipsi_user_fingerprint"` PERO está FUERA del formulario principal
- Cuando el usuario hace submit, este fingerprint NO se envía al backend

**Código de submit actual (`eipsi-forms.js:2882-2889`):**
```javascript
formData.append('action', 'eipsi_forms_submit_form');
formData.append('nonce', this.config.nonce);
formData.append('form_end_time', end_timestamp_ms);
formData.append('end_timestamp_ms', end_timestamp_ms);
formData.append('participant_id', participantId);  // ← UUID, NO fingerprint
formData.append('session_id', sessionId);
```

**Falta:** `formData.append('eipsi_user_fingerprint', fingerprint);`

---

### 2. ¿Existe columna `user_fingerprint` en tabla `wp_vas_form_results`?
**NO** ❌

**Columnas actuales:**
```sql
id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
form_id varchar(15) DEFAULT NULL,
participant_id varchar(255) DEFAULT NULL,  -- ← UUID, NO fingerprint
session_id varchar(255) DEFAULT NULL,
-- ... otras columnas ...
```

**Ubicación:** `/home/engine/project/admin/database-schema-manager.php:140-169`

---

### 3. ¿Dónde se generan los exports Excel/CSV y cómo se construyen?

**Archivos responsables:**
- `/home/engine/project/admin/export.php`
- Funciones: `eipsi_export_to_excel()` y `eipsi_export_to_csv()`

**Columnas en export (línea 180-221):**
```php
$headers = array(
    'Form ID',
    'Participant ID',      // ← Viene de participant_id (UUID)
    'Form Name',
    'Date',
    'Time',
    'Duration(s)',
    'Start Time (UTC)',
    'End Time (UTC)',
    // ... metadata según privacy config ...
    // ... preguntas dinámicas del formulario ...
);
```

**¿Es dinámico?** SÍ ✅
- Headers base son fijos
- Preguntas del formulario se agregan dinámicamente
- Timings (page/field) se agregan solo si existen

---

## DIAGRAMA SIMPLIFICADO DEL FLUJO ACTUAL

```
FRONTEND
├─ eipsi-fingerprint.js
│  ├─ Genera fingerprint: "fp_0a1b2c3d4e5f6..." (canvas + webgl)
│  └─ Guarda en sessionStorage
│     └─ Crea input hidden PERO está fuera del formulario ❌
│
└─ eipsi-forms.js
   └─ Genera participant_id: "p-a1b2c3d4e5f6" (UUID v4)
      └─ Envia en POST ✅

BACKEND
└─ ajax-handlers.php:795-1158
   ├─ Recibe participant_id (UUID) ✅
   └─ NO recibe fingerprint ❌
      └─ Guarda en tabla wp_vas_form_results
         ├─ participant_id = "p-a1b2c3d4e5f6" ✅
         └─ device_fingerprint = NO EXISTE COLUMNA ❌

EXPORT
└─ export.php
   └─ Genera Excel/CSV
      ├─ Participant ID = "p-a1b2c3d4e5f6" ✅
      └─ Device Fingerprint = NO APARECE ❌
```

---

## RECOMENDACIÓN EJECUTIVA

**Tienes 3 sistemas de identificación diferentes que están confundidos:**

### SISTEMA 1: Fingerprint de Dispositivo (RCT)
- **Propósito:** Identificar dispositivo para aleatorización RCT
- **Método:** Canvas + WebGL + screen + timezone
- **Resultado:** `"fp_0a1b2c3d4e5f6..."` (32 caracteres hex)
- **Estado:** Generado pero NO usado ❌

### SISTEMA 2: Participant ID Universal (todos los formularios)
- **Propósito:** Identificar participante en TODOS los formularios
- **Método:** UUID v4 truncado
- **Resultado:** `"p-a1b2c3d4e5f6"` (14 caracteres)
- **Estado:** ✅ SÍ usado, enviado, guardado, exportado

### SISTEMA 3: Fingerprint de Backend (fallback)
- **Propósito:** Fallback para export si no hay participant_id
- **Método:** Hash de email + nombre
- **Resultado:** `"FP-0a1b2c3d"` (10 caracteres)
- **Estado:** Solo en export, solo como fallback

---

## QUÉ HACER (OPCIONES)

### OPCIÓN A: Incluir fingerprint de dispositivo en exports (recomendado para RCT)

Si quieres el fingerprint de dispositivo (canvas + webgl) en los exports:

**1. Agregar columna a BD (2 min):**
```sql
ALTER TABLE wp_vas_form_results
ADD COLUMN device_fingerprint VARCHAR(32) DEFAULT NULL
AFTER session_id;
```

**2. Capturar en POST (5 min):**
```javascript
// eipsi-forms.js línea 2887
const fpInput = form.querySelector('input[name="eipsi_user_fingerprint"]');
if (fpInput) {
    formData.append('device_fingerprint', fpInput.value);
}
```

**3. Guardar en BD (2 min):**
```php
// ajax-handlers.php línea 1012
'device_fingerprint' => isset($_POST['device_fingerprint'])
    ? sanitize_text_field($_POST['device_fingerprint'])
    : null,
```

**4. Agregar a schema manager (2 min):**
```php
// database-schema-manager.php línea 182
'device_fingerprint' => "ALTER TABLE `{$table_name}`
    ADD COLUMN device_fingerprint varchar(32) DEFAULT NULL
    AFTER session_id",
```

**5. Agregar a exports (5 min):**
```php
// export.php línea 180
$headers = array('Form ID', 'Participant ID', 'Device Fingerprint', ...);

// export.php línea 266
$row_data = array($form_id, $participant_id, $row->device_fingerprint ?? '', ...);
```

**Tiempo total:** ~20-30 minutos

---

### OPCIÓN B: Usar participant_id actual (ya funciona)

El `participant_id` actual (UUID `"p-a1b2c3d4e5f6"`) YA está funcionando:

- ✅ Genera en frontend
- ✅ Envía en POST
- ✅ Guarda en BD
- ✅ Exporta en Excel/CSV

**Solo necesitas:** Documentar que `participant_id` = identificador único del dispositivo/usuario

**Tiempo total:** 0 minutos (ya funciona)

---

### OPCIÓN C: Generar fingerprint en backend (alternativa para futuro)

Si quieres generar fingerprint en backend usando device info:

1. Capturar device info en POST (screen, user_agent, etc.)
2. Generar fingerprint en backend con función similar a frontend
3. Guardar en columna `device_fingerprint`

**Tiempo total:** ~2-3 horas

---

## DECISIÓN NECESARIA

**Para poder implementar algo, necesitas decidir:**

1. **¿Qué identificador quieres en exports?**
   - [ ] Fingerprint de dispositivo (canvas + webgl) → OPCIÓN A
   - [ ] Participant ID actual (UUID) → Ya funciona, OPCIÓN B
   - [ ] Fingerprint generado en backend → OPCIÓN C

2. **¿Para qué formularios?**
   - [ ] Todos los formularios → Usar participant_id (ya funciona)
   - [ ] Solo RCT (Randomized Controlled Trials) → Usar device_fingerprint (OPCIÓN A)

3. **¿Prioridad?**
   - [ ] Alta → Implementar OPCIÓN A ASAP (30 minutos)
   - [ ] Media → Documentar OPCIÓN B (0 minutos, ya funciona)
   - [ ] Baja → Considerar OPCIÓN C para futuro (2-3 horas)

---

## ARCHIVOS CLAVE PARA MODIFICAR

Si decides implementar OPCIÓN A (fingerprint de dispositivo):

1. `/home/engine/project/admin/ajax-handlers.php` → Capturar fingerprint en POST
2. `/home/engine/project/admin/database-schema-manager.php` → Agregar columna
3. `/home/engine/project/admin/export.php` → Agregar a exports
4. `/home/engine/project/assets/js/eipsi-forms.js` → Enviar fingerprint en POST

---

## DOCUMENTACIÓN COMPLETA

Para todos los detalles técnicos, ubicación exacta de código, y diagramas completos, ver:

**`/home/engine/project/AUDIT-FINGERPRINT-FLOW.md`** (497 líneas)

---

## CONCLUSIÓN

**El sistema de fingerprint existe pero está desconectado del flujo principal de envío y exportación.**

**Ruta más rápida:** Usar el `participant_id` actual (UUID) que ya funciona perfectamente y está en los exports.

**Ruta completa:** Implementar OPCIÓN A para incluir fingerprint de dispositivo (canvas + webgl) en exports.

---

**¿Qué quieres hacer?** Elige una opción y te doy el código exacto para implementarla. 🚀
