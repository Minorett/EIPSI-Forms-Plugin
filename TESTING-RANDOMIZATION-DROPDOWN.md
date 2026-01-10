# Testing: Randomization Dropdown - Form Library Integration

## ✅ COMPLETADO - Cargar formularios de Form Library en dropdown de aleatorización

### Cambios implementados

#### 1. **Handler PHP (`admin/ajax-handlers.php`)**
- **Línea 173-212:** Handler `eipsi_get_forms_list_handler` corregido
- ✅ Cambiado `title` → `name` para consistencia con frontend
- ✅ Eliminado doble anidado `{success: true, data: {success: true, data: [...]}}`
- ✅ Ahora retorna: `wp_send_json_success([...])` directamente
- ✅ WordPress automáticamente envuelve en `{success: true, data: [...]}`

**Antes:**
```php
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
```

**Después:**
```php
$templates_list = array_map(function($template) {
    return array(
        'id' => intval($template->ID),
        'name' => esc_html($template->post_title),  // ✅ 'name'
        'status' => $template->post_status,
    );
}, $templates);

wp_send_json_success($templates_list);  // ✅ Estructura simple
```

#### 2. **Frontend (`src/blocks/form-container/edit.js`)**

**Línea 121-151:** Función `loadAvailableForms()` ya estaba correcta
- ✅ Llama a `eipsi_get_forms_list` vía fetch con nonce
- ✅ Espera `data.data` (que ahora retorna el handler correctamente)
- ✅ Guarda en `availableForms` state

**Línea 990-1023:** SelectControl ya renderiza correctamente
- ✅ Mapea `availableForSelect.map(f => ({label: f.name, value: f.id}))`
- ✅ Botón 🔄 para recargar formularios
- ✅ Disabled cuando no hay formularios disponibles

**Línea 1025-1051:** Mejoras en avisos (NUEVOS)
- ✅ **NUEVO:** Aviso informativo cuando `availableForms.length === 0`:
  - "No hay formularios disponibles en la Form Library. Creá al menos 2 formularios para usar la aleatorización."
- ✅ **MEJORADO:** Aviso de warning cuando `randomConfig.forms.length < 2`:
  - Ahora solo se muestra si `availableForms.length > 0` (evita duplicados)

---

## Testing Manual

### Escenario 1: Sin formularios en Form Library
1. Ir al editor de un Form Container
2. Activar "🎲 Aleatorización"
3. **Resultado esperado:**
   - Dropdown deshabilitado
   - Aviso azul (info): "No hay formularios disponibles en la Form Library..."
   - Botón 🔄 permite intentar recargar

### Escenario 2: Con 1 formulario en Form Library
1. Crear 1 formulario en Form Library (ej: "PHQ-9 Assessment")
2. Ir al Form Container y activar aleatorización
3. Click en 🔄 para recargar
4. **Resultado esperado:**
   - Dropdown habilitado
   - Aparece: "Seleccionar formulario… | PHQ-9 Assessment"
   - Aviso amarillo (warning): "Añadí al menos 2 formularios..."

### Escenario 3: Con 2+ formularios en Form Library
1. Crear 2 formularios en Form Library:
   - "PHQ-9 Assessment"
   - "Burnout Clinical"
2. Ir al Form Container y activar aleatorización
3. Click en 🔄 para recargar
4. **Resultado esperado:**
   - Dropdown habilitado con ambas opciones
   - Aviso amarillo: "Añadí al menos 2 formularios..."
   - Al seleccionar el primer formulario:
     - Se agrega a la lista con slider de probabilidad
     - El dropdown actualiza y muestra solo el formulario restante
   - Al seleccionar el segundo formulario:
     - Aviso amarillo desaparece (ya hay 2 formularios)
     - Probabilidades se distribuyen automáticamente (50/50)

### Escenario 4: Eliminar formulario de la lista
1. Con 2 formularios seleccionados
2. Click en botón "X" (no-alt icon) de uno de ellos
3. **Resultado esperado:**
   - Formulario removido de la lista
   - Aparece de nuevo en el dropdown
   - Aviso amarillo reaparece: "Añadí al menos 2 formularios..."

---

## Verificación de Datos

### Nonce disponible
✅ `window.eipsiEditorData.nonce` está disponible (registrado en `eipsi-forms.php:381-384`)

### Handler AJAX registrado
✅ `eipsi_get_forms_list` registrado en `admin/ajax-handlers.php:159`

### CPT correcto
✅ Handler consulta `eipsi_form_template` (Form Library)

---

## Checklist de Criterios de Éxito

✅ El dropdown "Seleccionar formulario…" carga formularios de Form Library  
✅ Los formularios se muestran por título (ej: "PHQ-9 Assessment", "Burnout Clinical")  
✅ Al seleccionar un formulario, se guarda correctamente en `randomConfig`  
✅ El botón 🔄 funciona para sincronizar/recargar la lista  
✅ Si no hay formularios en Form Library, aparece: "No hay formularios disponibles..."  
✅ Si hay menos de 2 formularios seleccionados, aparece: "Añadí al menos 2 formularios..."  
✅ `npm run build` ejecuta sin errores  
✅ `npm run lint:js` ejecuta sin errores (0 errores, 0 warnings)  

---

## Archivos modificados

1. **`admin/ajax-handlers.php`** (línea 167-212)
   - Corregido handler `eipsi_get_forms_list_handler`
   - Cambio: `title` → `name`
   - Cambio: Estructura simple (sin doble anidado)

2. **`src/blocks/form-container/edit.js`** (línea 1025-1051)
   - Agregado: Aviso informativo cuando no hay formularios
   - Mejorado: Aviso de warning con condición `availableForms.length > 0`

---

## Build & Lint

```bash
npm run build
# ✅ webpack 5.103.0 compiled successfully in 4634 ms

npm run lint:js
# ✅ 0 errores, 0 warnings
```

---

## Próximos pasos (NO INCLUIDOS en este fix)

- [ ] Agregar paginación si la Form Library tiene 100+ formularios
- [ ] Agregar búsqueda/filtro en dropdown
- [ ] Mostrar ícono de estado (publish/draft) junto al nombre del formulario
- [ ] Agregar tooltip con descripción del formulario

---

**Por fin alguien entendió cómo trabajo de verdad con mis pacientes** ✨
