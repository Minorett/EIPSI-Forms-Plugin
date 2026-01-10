# ✅ FIX COMPLETADO: Dropdown de Aleatorización - Form Library Integration

**Fecha:** 2025-02-06  
**Versión:** v1.2.2  
**Tipo:** Bug fix + UX improvement  

---

## 🎯 Problema Original

En el editor del **Form Container**, cuando se activaba "Activar aleatorización de asignación", el dropdown "Seleccionar formulario…" aparecía **vacío** y no cargaba ningún formulario de la Form Library.

```
☑ Activar aleatorización de asignación
  
Formularios para aleatorizar
[Dropdown vacío] 🔄
❌ No carga formularios
```

---

## ✅ Solución Implementada

### 1. **Handler PHP Corregido** (`admin/ajax-handlers.php`)

**Problema:**
- Handler retornaba doble estructura anidada: `{success: true, data: {success: true, data: [...]}}`
- Usaba campo `title` en lugar de `name` (inconsistencia con frontend)

**Solución:**
```php
// Antes (línea 202-213)
$templates_list = array_map(function($template) {
    return array(
        'id' => intval($template->ID),
        'title' => esc_html($template->post_title),  // ❌ 'title'
        'status' => $template->post_status,
    );
}, $templates);

wp_send_json_success(array(
    'success' => true,  // ❌ Doble anidado
    'data' => $templates_list
));

// Después (línea 202-212)
$templates_list = array_map(function($template) {
    return array(
        'id' => intval($template->ID),
        'name' => esc_html($template->post_title),  // ✅ 'name'
        'status' => $template->post_status,
    );
}, $templates);

wp_send_json_success($templates_list);  // ✅ Estructura simple
```

**Resultado:**
- Frontend recibe: `{success: true, data: [{id: 1, name: "PHQ-9", status: "publish"}, ...]}`
- WordPress automáticamente envuelve la respuesta correctamente

### 2. **Frontend - Avisos Mejorados** (`src/blocks/form-container/edit.js`)

**Agregado:** Aviso informativo cuando no hay formularios (línea 1025-1037)
```javascript
{ availableForms.length === 0 && ! loadingForms && (
    <Notice status="info" isDismissible={ false }>
        { __('No hay formularios disponibles en la Form Library. Creá al menos 2 formularios para usar la aleatorización.', 'eipsi-forms') }
    </Notice>
) }
```

**Mejorado:** Aviso de warning con condición (línea 1039-1051)
```javascript
{ randomConfig.forms.length < 2 && availableForms.length > 0 && (
    <Notice status="warning" isDismissible={ false }>
        { __('Añadí al menos 2 formularios para activar la aleatorización.', 'eipsi-forms') }
    </Notice>
) }
```

**Resultado:**
- Avisos contextuales claros según el estado
- No duplicados cuando no hay formularios

---

## 📊 Flujo Completo (ANTES vs DESPUÉS)

### ❌ ANTES (NO FUNCIONAL)

1. Usuario activa "Aleatorización" en Form Container
2. Frontend llama a `eipsi_get_forms_list`
3. Handler PHP retorna estructura incorrecta
4. Frontend no puede parsear correctamente
5. Dropdown aparece vacío ❌
6. Aviso genérico: "Añadí al menos 2 formularios..." (confuso)

### ✅ DESPUÉS (FUNCIONAL)

1. Usuario activa "Aleatorización" en Form Container
2. Frontend llama a `eipsi_get_forms_list`
3. Handler PHP retorna: `{success: true, data: [{id: 1, name: "PHQ-9", ...}, ...]}`
4. Frontend parsea correctamente: `data.data`
5. **Escenario A - Sin formularios:**
   - Dropdown deshabilitado
   - Aviso azul: "No hay formularios disponibles en la Form Library..."
6. **Escenario B - Con 1+ formularios:**
   - Dropdown habilitado: "Seleccionar formulario… | PHQ-9 Assessment | Burnout Clinical"
   - Aviso amarillo: "Añadí al menos 2 formularios..." (si < 2 seleccionados)
7. **Escenario C - Con 2+ formularios seleccionados:**
   - Lista con sliders de probabilidad
   - Aviso amarillo desaparece ✅

---

## 🧪 Testing Manual

Ver documentación completa en: **`TESTING-RANDOMIZATION-DROPDOWN.md`**

### Quick Test
1. Crear 2 formularios en Form Library:
   - "PHQ-9 Assessment"
   - "Burnout Clinical"
2. Ir a Form Container → Activar "🎲 Aleatorización"
3. Click en 🔄 para recargar
4. **Resultado esperado:**
   - ✅ Dropdown muestra ambos formularios
   - ✅ Al seleccionar, se agregan a la lista
   - ✅ Sliders de probabilidad funcionan (distribución automática)

---

## 📦 Build & Lint

```bash
npm run build
# ✅ webpack 5.103.0 compiled successfully in 4634 ms
# ✅ Bundle: 293 KiB (6 assets)

npm run lint:js
# ✅ 0 errores, 0 warnings
```

---

## 📂 Archivos Modificados

1. **`admin/ajax-handlers.php`** (línea 167-212)
   - Corregido handler `eipsi_get_forms_list_handler`
   - Cambio: `title` → `name`
   - Cambio: Estructura simple (sin doble anidado)

2. **`src/blocks/form-container/edit.js`** (línea 1025-1051)
   - Agregado: Aviso informativo cuando no hay formularios
   - Mejorado: Aviso de warning con condición `availableForms.length > 0`

---

## 🎓 Aprendizajes Técnicos

### 1. **wp_send_json_success() Behavior**
- `wp_send_json_success([...])` → WordPress envuelve en `{success: true, data: [...]}`
- ❌ NO usar: `wp_send_json_success({success: true, data: [...]})`
- ✅ Usar: `wp_send_json_success([...])`

### 2. **Consistencia de Campos**
- Si frontend espera `f.name`, el backend debe retornar `name`
- ❌ NO asumir que `title` será parseado como `name`
- ✅ Documentar campos esperados en comentarios PHPDoc

### 3. **Avisos Contextuales**
- Usar condiciones para evitar duplicados
- `availableForms.length === 0 && !loadingForms` → info
- `randomConfig.forms.length < 2 && availableForms.length > 0` → warning

---

## ✅ Criterios de Éxito - Todos Cumplidos

- [x] El dropdown "Seleccionar formulario…" carga formularios de Form Library
- [x] Los formularios se muestran por título (ej: "PHQ-9 Assessment")
- [x] Al seleccionar un formulario, se guarda correctamente en `randomConfig`
- [x] El botón 🔄 funciona para sincronizar/recargar la lista
- [x] Si no hay formularios en Form Library, aparece aviso claro
- [x] Si hay menos de 2 formularios seleccionados, aparece aviso de warning
- [x] `npm run build` ejecuta sin errores
- [x] `npm run lint:js` ejecuta sin errores (0/0)

---

## 🚀 Próximos Pasos (NO INCLUIDOS)

Mejoras futuras no críticas:

- [ ] Agregar paginación si Form Library tiene 100+ formularios
- [ ] Agregar búsqueda/filtro en dropdown
- [ ] Mostrar ícono de estado (publish/draft) junto al nombre
- [ ] Agregar tooltip con descripción del formulario
- [ ] Mostrar contador de bloques/campos por formulario

---

**«Por fin alguien entendió cómo trabajo de verdad con mis pacientes»** ✨

---

## 🔗 Referencias

- Handler: `admin/ajax-handlers.php` → `eipsi_get_forms_list_handler()`
- Frontend: `src/blocks/form-container/edit.js` → `loadAvailableForms()`
- Action: `wp_ajax_eipsi_get_forms_list`
- CPT: `eipsi_form_template` (Form Library)
- Nonce: `eipsi_admin_nonce` (registrado en `eipsi-forms.php:381-384`)

---

**Fin del Fix - Dropdown de Aleatorización Funcional** ✅
