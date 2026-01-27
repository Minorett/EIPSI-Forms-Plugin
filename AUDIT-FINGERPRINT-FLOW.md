## AUDIT RESULTS - Fingerprint Flow
## Generated: 2025-01-27
## Investigación completa del flujo de fingerprint en EIPSI Forms

---

### 1. POST Parameters en formulario completado

**¿Viene fingerprint en POST del formulario completado?**
- ❌ **NO**

**Parámetro recibido en backend:**
- No existe parámetro `eipsi_user_fingerprint` en `$_POST`
- El handler `eipsi_forms_submit_form_handler()` (línea 795-1158) NO captura fingerprint

**Ubicación en código:**
- **Backend handler:** `/home/engine/project/admin/ajax-handlers.php:795-1158`
- **Frontend submit:** `/home/engine/project/assets/js/eipsi-forms.js:2882-2899`

**Parámetros SÍ enviados en POST:**
```javascript
// Línea 2882-2899 de eipsi-forms.js
formData.append('action', 'eipsi_forms_submit_form');
formData.append('nonce', this.config.nonce);
formData.append('form_end_time', end_timestamp_ms);
formData.append('end_timestamp_ms', end_timestamp_ms);
formData.append('participant_id', participantId);  // ← UUID, NO fingerprint
formData.append('session_id', sessionId);
formData.append('metadata', JSON.stringify(finalMetadata));
```

**Excepción:** El fingerprint se genera SÍ para RCT (Randomized Controlled Trials) pero NO se incluye en el POST de submit del formulario. Se usa solo para asignación aleatoria.

---

### 2. Database Structure - Tabla `wp_vas_form_results`

**Columna `user_fingerprint` existe:**
- ❌ **NO**

**Columnas presentes en `wp_vas_form_results`:**
```sql
-- Ubicación: /home/engine/project/admin/database-schema-manager.php:140-169
id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
form_id varchar(15) DEFAULT NULL,
participant_id varchar(255) DEFAULT NULL,        -- ← UUID, NO fingerprint
session_id varchar(255) DEFAULT NULL,
participant varchar(255) DEFAULT NULL,            -- ← Legacy column
interaction varchar(255) DEFAULT NULL,            -- ← Legacy column
form_name varchar(255) NOT NULL,
created_at datetime NOT NULL,
device varchar(100) DEFAULT NULL,
browser varchar(100) DEFAULT NULL,
os varchar(100) DEFAULT NULL,
screen_width int(11) DEFAULT NULL,
duration int(11) DEFAULT NULL,
duration_seconds decimal(8,3) DEFAULT NULL,
start_timestamp_ms bigint(20) DEFAULT NULL,
end_timestamp_ms bigint(20) DEFAULT NULL,
ip_address varchar(45) DEFAULT NULL,
metadata LONGTEXT DEFAULT NULL,
status enum('pending','submitted','error') DEFAULT 'submitted',
form_responses longtext DEFAULT NULL
```

**Ubicación del schema:**
- `/home/engine/project/admin/database-schema-manager.php:120-211`

---

### 3. Export Generation - Excel/CSV

**Archivos responsables:**
- `/home/engine/project/admin/export.php`
- Función `eipsi_export_to_excel()` (línea 62-351)
- Función `eipsi_export_to_csv()` (línea 353-644)

**Función principal:**
- `eipsi_export_to_excel()` → Genera archivo .xlsx
- `eipsi_export_to_csv()` → Genera archivo .csv

**Columnas actuales en export (headers):**
```php
// Línea 180-221 de export.php (Excel) - similar en CSV
$headers = array(
    'Form ID',
    'Participant ID',          // ← Viene de participant_id (UUID)
    'Form Name',
    'Date',
    'Time',
    'Duration(s)',
    'Start Time (UTC)',
    'End Time (UTC)',
    // Aleatorización (solo si hay datos)
    'Assignment Form',
    'Seed',
    'Type (Random/Manual)',
    // Metadata (según privacy config)
    'IP Address',
    'Device',
    'Browser',
    'OS',
    // Page Timings (si existen)
    'Page {N} - Duration(s)',
    'Page {N} - Timestamp',
    // Field Timings (si existen)
    '{Field} - Time(s)',
    '{Field} - Interactions',
    '{Field} - Focus Count',
    // Total Duration
    'Total Duration(s)',
    // Preguntas dinámicas del formulario...
);
```

**¿Es dinámico o hardcodeado?**
- ✅ **DINÁMICO**
- Los headers base son fijos (metadata, timestamps)
- Las preguntas del formulario se agregan dinámicamente
- Los timings (page/field) se agregan solo si existen en datos
- Los datos de aleatorización se agregan solo si hay randomización real

**Participant ID en export:**
```php
// Línea 244 de export.php
$participant_id = !empty($row->participant_id)
    ? $row->participant_id
    : export_generateStableFingerprint($user_data);
```
- **VIENE DE:** Columna `participant_id` de la tabla (UUID)
- **FALLBACK:** `export_generateStableFingerprint()` solo si está vacío
- El fingerprint que genera esta función es DIFERENTE del fingerprint de RCT

---

### 4. Fingerprint Generation - Flujo Completo

**Archivo principal:**
- `/home/engine/project/assets/js/eipsi-fingerprint.js`

**eipsi-fingerprint.js aplica a:**
- ✅ **RCT (Randomized Controlled Trials)**
- ✅ Contenedores con clase `.eipsi-randomization-container`
- ❌ Formularios NO-RCT (no se genera)

**Detección de contenedores RCT:**
```javascript
// Línea 252-256 de eipsi-fingerprint.js
const containers = document.querySelectorAll(
    '.eipsi-randomization-container'
);
```

**Almacenamiento:**
- ✅ **sessionStorage** → clave `eipsi_fingerprint`
- ✅ Dura solo la sesión del navegador
- ✅ Fallback: re-generar cada vez si no hay sessionStorage

**¿Se envía en POST?**
- ❌ **NO** (este es el problema principal)
- El fingerprint se crea en un input hidden dentro del contenedor RCT:
  ```javascript
  // Línea 259-267 de eipsi-fingerprint.js
  let fingerprintInput = container.querySelector(
      'input[name="eipsi_user_fingerprint"]'
  );
  if (!fingerprintInput) {
      fingerprintInput = document.createElement('input');
      fingerprintInput.type = 'hidden';
      fingerprintInput.name = 'eipsi_user_fingerprint';
      container.appendChild(fingerprintInput);
  }
  fingerprintInput.value = fingerprint;
  ```
- **PERO este input está FUERA del formulario principal**
- El formulario principal NO incluye este input al hacer submit

**Código de submit del formulario:**
```javascript
// Línea 2878-2888 de eipsi-forms.js
const formId = this.getFormId(form) || '';
const participantId = getUniversalParticipantId();  // ← UUID, NO fingerprint
const sessionId = getSessionId(formId);

formData.append('action', 'eipsi_forms_submit_form');
formData.append('nonce', this.config.nonce);
formData.append('form_end_time', end_timestamp_ms);
formData.append('end_timestamp_ms', end_timestamp_ms);
formData.append('participant_id', participantId);  // ← UUID aquí
formData.append('session_id', sessionId);
```

**¿Cuál es el participant_id actual?**
```javascript
// Línea 20-33 de eipsi-forms.js
function getUniversalParticipantId() {
    const STORAGE_KEY = 'eipsi_participant_id';

    let pid = localStorage.getItem(STORAGE_KEY);
    if (!pid) {
        // Generar UUID v4 truncado a 12 caracteres
        pid = 'p-' + crypto.randomUUID().replace(/-/g, '').substring(0, 12);
        localStorage.setItem(STORAGE_KEY, pid);
    }

    return pid;  // ← "p-a1b2c3d4e5f6"
}
```

---

### 5. Diagrama de Flujo Actual

```
┌─────────────────────────────────────────────────────────────────┐
│                     EIPSI FORMS - ACTUAL                          │
└─────────────────────────────────────────────────────────────────┘

[Usuario abre formulario]
           │
           ├───► eipsi-fingerprint.js se carga (SOLO si es RCT)
           │
           ├───► Genera fingerprint (canvas + webgl + screen...)
           │       Resultado: "fp_0a1b2c3d4e5f6..." (32 caracteres)
           │
           ├───► Guarda en sessionStorage: 'eipsi_fingerprint'
           │
           └───► Crea input hidden con name="eipsi_user_fingerprint"
                   DENTRO del contenedor .eipsi-randomization-container
                   (PEEEERO está FUERA del formulario principal ❌)

[Usuario llena formulario]
           │
           ├───► eipsi-forms.js genera IDs universales
           │
           ├───► getUniversalParticipantId()
           │       Resultado: "p-a1b2c3d4e5f6" (UUID v4 truncado)
           │
           └───► getSessionId()
                   Resultado: "sess-1738000000000-x7z9k2"

[Usuario hace submit]
           │
           ├───► POST a eipsi_forms_submit_form
           │
           └───► Parámetros enviados:
                   ✓ action
                   ✓ nonce
                   ✓ form_end_time
                   ✓ end_timestamp_ms
                   ✓ participant_id = "p-a1b2c3d4e5f6"  ← UUID
                   ✓ session_id
                   ✓ metadata
                   ✗ eipsi_user_fingerprint  ← NO SE ENVÍA ❌

[Backend procesa]
           │
           ├───► ajax-handlers.php:795-1158
           │
           ├───► NO recibe eipsi_user_fingerprint
           │
           ├───► INSERT en wp_vas_form_results:
                   ✓ form_id
                   ✓ participant_id = "p-a1b2c3d4e5f6"  ← UUID guardado
                   ✓ session_id
                   ✓ form_responses
                   ✓ metadata
                   ✗ user_fingerprint  ← NO EXISTE COLUMNA ❌
                   ...

[Export]
           │
           └───► export.php genera Excel/CSV
                   ✓ Participant ID = "p-a1b2c3d4e5f6"  ← UUID de tabla
                   ✗ Fingerprint de dispositivo  ← NO APARECE ❌
```

---

### 6. SISTEMAS DIFERENTES - Confusión Actual

El código TIENE DOS sistemas de identificación que están CONFUNDIDOS:

**SISTEMA 1: Fingerprint de Dispositivo (RCT)**
- Archivo: `eipsi-fingerprint.js`
- Propósito: Identificar el dispositivo para aleatorización RCT
- Método: Canvas + WebGL + screen + timezone...
- Resultado: `"fp_0a1b2c3d4e5f6..."` (32 caracteres hex)
- Storage: sessionStorage (temporal)
- **Estado:** Generado pero NO usado ❌

**SISTEMA 2: Participant ID Universal (todos los formularios)**
- Archivo: `eipsi-forms.js`
- Propósito: Identificar al participante en TODOS los formularios
- Método: UUID v4 truncado
- Resultado: `"p-a1b2c3d4e5f6"` (14 caracteres)
- Storage: localStorage (persistente)
- **Estado:** SÍ usado, SÍ enviado, SÍ guardado, SÍ exportado ✅

**SISTEMA 3: Fingerprint de Backend (fallback)**
- Archivo: `export.php:42-56`
- Propósito: Fallback para export si no hay participant_id
- Método: Hash de email + nombre
- Resultado: `"FP-0a1b2c3d"` (10 caracteres)
- Storage: Ninguno (calculado on-the-fly)
- **Estado:** Solo en export, solo como fallback ⚠️

---

### 7. Recomendación - Qué hacer para incluir fingerprint en exports

**OPCIÓN A: Usar el fingerprint de dispositivo (recomendado para RCT)**

Si el objetivo es incluir el fingerprint de dispositivo (canvas + webgl + screen) en los exports:

1. **Agregar columna a la tabla:**
   ```sql
   ALTER TABLE wp_vas_form_results
   ADD COLUMN device_fingerprint VARCHAR(32) DEFAULT NULL
   AFTER session_id;
   ```

2. **Capturar fingerprint en POST:**
   - Modificar `/home/engine/project/assets/js/eipsi-forms.js` línea 2887
   ```javascript
   // Capturar fingerprint si existe (para formularios RCT)
   let deviceFingerprint = '';
   const fpInput = form.querySelector('input[name="eipsi_user_fingerprint"]');
   if (fpInput) {
       deviceFingerprint = fpInput.value;
   }

   formData.append('participant_id', participantId);
   formData.append('session_id', sessionId);
   formData.append('device_fingerprint', deviceFingerprint);  // ← AGREGAR
   ```

3. **Guardar en base de datos:**
   - Modificar `/home/engine/project/admin/ajax-handlers.php` línea 1012-1031
   ```php
   $data = array(
       'form_id' => $stable_form_id,
       'participant_id' => $participant_id,
       'session_id' => $session_id,
       'device_fingerprint' => isset($_POST['device_fingerprint']) ? sanitize_text_field($_POST['device_fingerprint']) : null,  // ← AGREGAR
       'form_name' => $form_name,
       // ...
   );
   ```

4. **Agregar columna al schema manager:**
   - Modificar `/home/engine/project/admin/database-schema-manager.php` línea 182-194
   ```php
   $required_columns = array(
       'form_id' => "...",
       'participant_id' => "...",
       'session_id' => "...",
       'device_fingerprint' => "ALTER TABLE `{$table_name}` ADD COLUMN device_fingerprint varchar(32) DEFAULT NULL AFTER session_id",  // ← AGREGAR
       // ...
   );
   ```

5. **Agregar columna a exports:**
   - Modificar `/home/engine/project/admin/export.php` línea 180
   ```php
   $headers = array(
       'Form ID',
       'Participant ID',
       'Device Fingerprint',  // ← AGREGAR
       'Form Name',
       // ...
   );
   ```
   - Modificar línea 266-274 (para cada fila)
   ```php
   $row_data = array(
       $form_id,
       $participant_id,
       $row->device_fingerprint ?? '',  // ← AGREGAR
       $row->form_name,
       // ...
   );
   ```

---

**OPCIÓN B: Usar el participant_id actual (ya funciona, solo renombrar)**

Si el `participant_id` actual (UUID) es suficiente:

1. **No requiere cambios** ✅
2. El UUID `"p-a1b2c3d4e5f6"` ya se:
   - ✓ Genera en frontend
   - ✓ Envía en POST
   - ✓ Guarda en BD
   - ✓ Exporta en Excel/CSV
3. Solo sería cuestión de documentar que `participant_id` = identificador único del dispositivo/usuario

---

**OPCIÓN C: Generar fingerprint en backend (alternativa)**

Si queremos generar fingerprint en backend usando los mismos datos del dispositivo:

1. **Capturar device info en POST:**
   ```javascript
   // Agregar metadata de dispositivo en submit
   formData.append('metadata', JSON.stringify({
       ...finalMetadata,
       device_info: {
           screen_width: window.screen.width,
           screen_height: window.screen.height,
           user_agent: navigator.userAgent,
           // ...
       }
   }));
   ```

2. **Generar fingerprint en backend:**
   - Extraer device_info de `metadata` en backend
   - Crear función `generate_device_fingerprint()` similar a la del frontend
   - Guardar en columna `device_fingerprint`

---

### 8. RESUMEN EJECUTIVO

**Estado actual:**
- ❌ Fingerprint de dispositivo existe pero NO se usa en exports
- ✅ Participant ID (UUID) SÍ funciona y está en exports
- ⚠️ Hay confusión entre 3 sistemas de identificación diferentes

**Para incluir fingerprint en exports, necesitas decidir:**

1. **¿Qué quieres en exports?**
   - Fingerprint de dispositivo (canvas + webgl) → OPCIÓN A
   - Participant ID actual (UUID) → Ya funciona, OPCIÓN B
   - Fingerprint generado en backend → OPCIÓN C

2. **¿Para qué formularios?**
   - Todos los formularios → Usar participant_id (ya funciona)
   - Solo RCT (Randomized Controlled Trials) → Usar device_fingerprint (OPCIÓN A)

3. **¿Qué prioridad tiene esto?**
   - Alta → Implementar OPCIÓN A ASAP
   - Media → Documentar y clarificar OPCIÓN B
   - Baja → Considerar OPCIÓN C para futuro

---

## PRÓXIMOS PASOS

**Mínimo viable (1-2 horas):**
1. Decidir qué identificador usar para exports
2. Documentar en README.md qué significa cada columna
3. Aclarar en admin dashboard qué es "Participant ID"

**Implementación completa (3-4 horas):**
1. Implementar OPCIÓN A si es fingerprint de dispositivo
2. Agregar migración de BD
3. Testing en staging con formularios reales

**Documentación (30 minutos):**
1. Actualizar docs con diagrama de flujo actual
2. Explicar diferencia entre fingerprint RCT y participant_id universal
3. Agregar ejemplos de exports

---

## FIN DEL AUDIT
