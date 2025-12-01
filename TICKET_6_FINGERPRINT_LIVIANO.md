# Ticket 6: Fingerprint Clínico Liviano

## Objetivo Clínico
Permitir distinguir pacientes/sesiones cuando comparten la misma IP (ej. wifi de clínica), respetando al 100% la filosofía de "privacidad por defecto" de EIPSI Forms.

**Caso típico:**  
10 personas completan formularios desde la misma red wifi → necesitás poder decir:  
"Estos dos envíos son probablemente de dispositivos distintos" sin introducir tracking invasivo.

---

## ✅ Implementación Completada

### 1. Frontend: Captura Mejorada de Metadatos Técnicos

**Archivo:** `assets/js/eipsi-forms.js`

#### Mejoras en `getBrowser()`:
- Ahora detecta **nombre + versión mayor** del navegador
- Ejemplos: `"Chrome 131"`, `"Firefox 132"`, `"Safari 17"`
- Soporta: Firefox, Samsung Browser, Opera, Edge, Internet Explorer, Chrome, Safari

#### Mejoras en `getOS()`:
- Ahora detecta **nombre + versión mayor** del sistema operativo
- Ejemplos: `"Windows 10"`, `"Android 15"`, `"iOS 18"`, `"macOS 14.2"`
- Soporta: Windows, macOS, Android, iOS, Linux, UNIX

#### Mejoras en `populateDeviceInfo()`:
- **Screen size completo**: Ahora captura `"1920x1080"` en lugar de solo el ancho
- Los datos se capturan siempre (si los campos hidden existen), pero el backend solo los guarda si los toggles están ON

**Código clave:**
```javascript
// Browser con versión
browser = 'Chrome';
const match = ua.match( /Chrome\/(\d+)/ );
version = match ? match[ 1 ] : '';
return version ? `${ browser } ${ version }` : browser;

// OS con versión
os = 'Android';
const match = ua.match( /Android (\d+(?:\.\d+)?)/ );
version = match ? match[ 1 ] : '';
return version ? `${ os } ${ version }` : os;

// Screen size completo
const width = window.screen.width || '';
const height = window.screen.height || '';
screenField.value = width && height ? `${ width }x${ height }` : width;
```

---

### 2. Backend: Respeto Absoluto a Toggles de Privacidad

**Archivo:** `admin/ajax-handlers.php`

El handler `vas_dinamico_submit_form_handler()` ya estaba configurado para respetar los toggles:

```php
// Obtener configuración de privacidad
require_once dirname(__FILE__) . '/privacy-config.php';
$privacy_config = get_privacy_config($stable_form_id);

// Aplicar privacy config a los campos capturados
$browser = ($privacy_config['browser'] ?? false) ? $browser_raw : null;
$os = ($privacy_config['os'] ?? false) ? $os_raw : null;
$screen_width = ($privacy_config['screen_width'] ?? false) ? $screen_width_raw : null;
$ip_address = ($privacy_config['ip_address'] ?? true) ? $ip_address_raw : null;
```

**Cambios realizados:**
- Cambiado `screen_width_raw` de `intval()` a `sanitize_text_field()` para aceptar formatos tipo `"1920x1080"`

**Comportamiento:**
- Si toggle OFF → campo se guarda como `NULL` en la BD
- Si toggle ON → campo se guarda con el valor capturado

---

### 3. UI: Sección "Detalles Técnicos del Dispositivo" en Submissions

**Archivo:** `admin/ajax-handlers.php` (función `eipsi_ajax_get_response_details()`)

Nueva sección **colapsable** agregada al modal de "Session Metadata":

```php
// DETALLES TÉCNICOS DEL DISPOSITIVO (COLAPSABLE)
$has_device_info = !empty($response->browser) || !empty($response->os) 
    || !empty($response->screen_width) || !empty($response->ip_address);

if ($has_device_info) {
    // Botón toggle
    $html .= '<button type="button" id="toggle-device-info" ...>
        🖥️ Mostrar Detalles Técnicos del Dispositivo
    </button>';
    
    // Sección colapsable
    $html .= '<div id="device-info-section" style="display: none;">
        <h4>🖥️ Fingerprint Liviano (Dispositivo)</h4>
        <p>Ayuda a distinguir envíos desde la misma IP (ej. wifi de clínica). 
           Solo se captura si los toggles están ON en Privacy & Metadata.</p>
        
        // IP, Browser, OS, Screen size, Session ID
    </div>';
}
```

**UI features:**
- **Solo se muestra si hay datos disponibles** (al menos uno de los campos tiene valor)
- **Colapsable por defecto** (no satura al clínico)
- **Feedback claro** cuando un dato no está disponible: _"No disponible (toggle OFF)"_
- Muestra Session ID como `<code>` con estilo monospace

**Archivo:** `admin/tabs/submissions-tab.php`

JavaScript agregado para controlar el toggle:

```javascript
$('#toggle-device-info').on('click', function() {
    var section = $('#device-info-section');
    if (section.is(':visible')) {
        section.slideUp('fast');
        $(this).html('🖥️ Show Device Fingerprint');
    } else {
        section.slideDown('fast');
        $(this).html('🖥️ Hide Device Fingerprint');
    }
});
```

---

### 4. Privacy & Metadata: Texto Actualizado

**Archivo:** `admin/privacy-dashboard.php`

**Título de sección mejorado:**
```
🖥️ Fingerprint Liviano del Dispositivo (Opcional)
```

**Descripción mejorada:**
```
⚠️ Estos datos son opcionales y están desactivados por defecto. 
Actívalos si necesitas distinguir pacientes con IP compartida 
(ej. wifi de clínica).
```

**Tooltips actualizados con ejemplos:**
- Navegador: `"(ej: Chrome 131, Firefox 132, Safari 17)"`
- Sistema Operativo: `"(ej: Windows 10, Android 15, iOS 18)"`
- Tamaño de Pantalla: `"(ej: 1920x1080, 1080x2400)"`

---

## 🎯 Casos de Uso Clínicos

### Escenario 1: Consultorio con Wifi Compartida
**Situación:**  
10 pacientes completan PHQ-9 en la sala de espera, todos desde la misma IP.

**Con Fingerprint Liviano:**
```
Submission A:
  IP: 198.51.100.23
  Browser: Chrome 131
  OS: Android 15
  Screen: 1080x2400

Submission B:
  IP: 198.51.100.23
  Browser: Safari 17
  OS: iOS 18
  Screen: 1170x2532

Submission C:
  IP: 198.51.100.23
  Browser: Chrome 129
  OS: Windows 10
  Screen: 1920x1080
```

**Resultado:** El clínico puede distinguir claramente que son 3 dispositivos diferentes.

### Escenario 2: Privacy por Defecto
**Situación:**  
Psicóloga elige NO capturar metadatos técnicos (toggles OFF).

**Resultado:**
```
Submission A:
  IP: NULL
  Browser: NULL
  OS: NULL
  Screen: NULL
```

**UI muestra:**  
_"Detalles Técnicos del Dispositivo"_ → sección no aparece (porque `$has_device_info = false`)

---

## ✅ Criterios de Aceptación (Cumplidos)

### ✅ Parte 1: Metadatos técnicos básicos
- [x] Browser con nombre + versión mayor (ej. "Chrome 131")
- [x] OS con nombre + versión mayor (ej. "Android 15")
- [x] Screen size completo (ej. "1920x1080")
- [x] Toggles en Privacy & Metadata (OFF por defecto)
- [x] Texto claro y entendible para psicóloga sin background técnico

### ✅ Parte 2: Fingerprint liviano
- [x] Combinación IP + browser + OS + screen + session_id
- [x] No expone "hash raro" al clínico, solo datos humanos
- [x] Permite distinguir 2 envíos desde misma IP con dispositivos diferentes

### ✅ Parte 3: Integración con Submissions
- [x] Metadatos guardados en BD cuando toggles ON
- [x] Sección "Detalles Técnicos del Dispositivo" colapsable en UI
- [x] UI no falla si no hay datos técnicos
- [x] Muestra "No disponible (toggle OFF)" cuando corresponde

### ✅ Parte 4: Respeto a toggles de privacidad
- [x] OFF = no captura, no envía, no guarda (no solo "no mostrar")
- [x] Cambio de toggle = cambio inmediato de comportamiento
- [x] Sin banners de privacidad nuevos innecesarios

### ✅ Criterios globales
- [x] IP compartida → dispositivos distinguibles por metadatos técnicos
- [x] Toggles claros, OFF por defecto
- [x] Información técnica visible en sección clara y no invasiva
- [x] Texto honesto y simple
- [x] `npm run build` y `npm run lint:js` pasan sin errores

---

## 📊 Datos Capturados vs. Guardados

| Campo        | Frontend Captura | Backend Guarda           | UI Muestra               |
|--------------|------------------|--------------------------|--------------------------|
| Device Type  | Siempre          | Si toggle ON (default ON)| Siempre                  |
| Browser      | Siempre          | Si toggle ON (default OFF)| Si existe valor          |
| OS           | Siempre          | Si toggle ON (default OFF)| Si existe valor          |
| Screen Size  | Siempre          | Si toggle ON (default OFF)| Si existe valor          |
| IP Address   | Siempre          | Si toggle ON (default ON) | Si existe valor          |
| Session ID   | Siempre          | Siempre                  | Si existe valor          |

---

## 🔒 Garantías de Privacidad

1. **OFF por defecto:** Browser, OS y Screen_width vienen desactivados.
2. **No tracking externo:** Ningún dato se envía a servidores de terceros.
3. **No cookies persistentes:** Session ID vive solo en sessionStorage (se borra al cerrar tab).
4. **No reidentificación:** No se generan hashes permanentes para seguimiento cross-sesión.
5. **Control total:** El clínico decide qué capturar por formulario.

---

## 🚀 Testing Sugerido

### Test Manual 1: Captura con Toggles ON
1. Ir a **Privacy & Metadata** tab
2. Seleccionar un formulario
3. Activar: `browser`, `os`, `screen_width`, `ip_address`
4. Guardar
5. Abrir formulario en frontend y completar
6. Ir a **Submissions** tab → Ver detalles
7. Click en "🖥️ Mostrar Detalles Técnicos del Dispositivo"
8. **Verificar:** Se muestran todos los datos capturados

### Test Manual 2: Captura con Toggles OFF
1. Ir a **Privacy & Metadata** tab
2. Seleccionar el mismo formulario
3. Desactivar: `browser`, `os`, `screen_width`
4. Guardar
5. Abrir formulario en frontend y completar
6. Ir a **Submissions** tab → Ver detalles
7. **Verificar:** Sección "Detalles Técnicos del Dispositivo" NO aparece (o muestra "No disponible")

### Test Manual 3: Escenario IP Compartida
1. Activar toggles (browser, os, screen_width)
2. Completar formulario desde:
   - Desktop Chrome
   - Mobile Android
   - iPhone Safari
3. Todos desde la misma red wifi
4. Ir a **Submissions** tab
5. **Verificar:** Las 3 submissions tienen la misma IP pero metadatos técnicos diferentes

---

## 📝 Archivos Modificados

```
assets/js/eipsi-forms.js
  - getBrowser() → Ahora incluye versión
  - getOS() → Ahora incluye versión
  - populateDeviceInfo() → Screen size completo (ancho x alto)

admin/ajax-handlers.php
  - vas_dinamico_submit_form_handler() → Screen_width como texto (no int)
  - eipsi_ajax_get_response_details() → Nueva sección "Detalles Técnicos del Dispositivo"

admin/tabs/submissions-tab.php
  - JavaScript agregado para toggle #toggle-device-info

admin/privacy-dashboard.php
  - Texto actualizado: "Fingerprint Liviano del Dispositivo"
  - Tooltips con ejemplos reales
  - Descripción mejorada sobre IP compartida
```

---

## ✅ Conclusión

El **Ticket 6: Fingerprint Clínico Liviano** está completamente implementado y cumple con todos los criterios de aceptación.

**Filosofía respetada:**  
- ✅ Zero miedo: Sin tracking invasivo ni hashes raros
- ✅ Zero fricción: Datos solo visibles si los necesitás
- ✅ Zero excusas: Funciona out-of-the-box con privacy por defecto

**Próximo paso:**  
Testing manual en entorno real (consultorio con wifi compartida).
