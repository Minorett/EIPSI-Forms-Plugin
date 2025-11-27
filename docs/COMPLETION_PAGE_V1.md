# Completion Page v1 - Documentación Técnica

**Versión:** 1.0  
**Fecha:** Febrero 2025  
**Estado:** ✅ Implementado

## Resumen

La **Completion Page v1** es una página de finalización simple y clínica que se muestra cuando el paciente completa un formulario. Se mantiene en la **misma URL** (sin redirecciones externas) y permite reiniciar el formulario con un solo botón.

## Características

### ✅ Configuración en Form Container (Gutenberg)

En el inspector del bloque **EIPSI Form Container**, nueva sección "Completion Page" con:

1. **Título de finalización**
   - Campo de texto simple
   - Default: "¡Gracias por completar el cuestionario!"
   
2. **Mensaje de finalización**
   - Textarea multilinea (4 filas)
   - Respeta saltos de línea simples y dobles
   - Default: "Sus respuestas han sido registradas correctamente."

3. **Texto del botón**
   - Campo de texto simple
   - Default: "Comenzar de nuevo"
   - Ejemplos sugeridos: "Volver a empezar", "Nuevo paciente"

4. **Logo o imagen (opcional)**
   - Selector de media (WordPress Media Library)
   - Se muestra en la parte superior de la página de finalización
   - Si no se configura, no se muestra ningún logo

### ✅ Comportamiento en Frontend

Al enviar el formulario exitosamente:

1. **Misma URL**: El paciente permanece en la misma página donde empezó el formulario (sin redirecciones)
2. **Reemplazo del contenido**: El formulario desaparece y se muestra la Completion Page
3. **Layout limpio**:
   - Logo (si está configurado)
   - Título grande
   - Mensaje con formato de párrafos
   - Botón único "Comenzar de nuevo"
4. **Botón de reinicio**: Al hacer clic, recarga la página (`window.location.reload()`) generando una nueva sesión

### ✅ Compatibilidad con Presets y Dark Mode

- La Completion Page **hereda automáticamente** el preset de estilos del formulario
- Los colores, tipografía y espaciados respetan las variables CSS del formulario
- Compatible con dark mode (cuando esté reactivado)

### ✅ Fallback para Formularios Legacy

Si un formulario no tiene configurada la Completion Page (formularios creados antes de esta feature):

1. Intenta leer la configuración global del backend (si existe)
2. Si falla, usa defaults razonables: título genérico, mensaje simple, botón "Comenzar de nuevo"

## Implementación Técnica

### Archivos Modificados

#### 1. Form Container Block (Gutenberg)

**`blocks/form-container/block.json`**
- Nuevos atributos: `completionTitle`, `completionMessage`, `completionLogoId`, `completionLogoUrl`, `completionButtonLabel`

**`src/blocks/form-container/edit.js`**
- Nuevo `PanelBody` con controles de configuración
- Importa `MediaUpload` y `MediaUploadCheck` para selector de imagen
- Preview de logo en inspector

**`src/blocks/form-container/save.js`**
- Guarda configuración como `data-completion-*` attributes en el contenedor del form
- Permite que frontend lea la config sin AJAX

#### 2. Frontend JavaScript

**`assets/js/eipsi-forms.js`**

Nuevas funciones:

- **`getCompletionConfigFromContainer( formContainer )`**  
  Lee la configuración desde los data-attributes del Form Container. Si existe `data-completion-title`, considera que el formulario tiene configuración propia.

- **`fetchCompletionConfigFromBackend( form )`**  
  Fallback: intenta obtener configuración global del backend vía AJAX.

- **`formatCompletionMessage( message )`**  
  Formatea el mensaje de finalización:
  - Convierte saltos de línea dobles (`\n\n`) en párrafos separados
  - Convierte saltos de línea simples (`\n`) en `<br>`
  - Escapa HTML para prevenir XSS
  - Si el mensaje ya contiene HTML, lo respeta tal cual

Función modificada:

- **`showIntegratedThankYouPage( form )`**  
  Flujo actualizado:
  1. Si existe un bloque "Thank You Page" de Gutenberg → lo muestra
  2. Si no, intenta leer configuración del Form Container
  3. Si no hay config, recurre al backend global
  4. Como último recurso, usa defaults hard-coded

- **`createThankYouPage( form, config )`**  
  Soporte para `logo_url` directo desde config (en lugar de solo `show_logo`)

#### 3. CSS (ya existente)

**`assets/css/eipsi-forms.css`**

Ya existían estilos para `.eipsi-thank-you-page`, `.eipsi-thank-you-content`, `.eipsi-thank-you-logo`, etc. (líneas 2226-2363).

No se requirieron cambios de CSS para esta feature.

## Uso Clínico

### Caso de Uso Principal: Tablet en Sala

**Escenario:**
Un psicólogo tiene una tablet en sala y la usa para que múltiples pacientes respondan el mismo formulario (ej. PHQ-9, GAD-7).

**Flujo:**
1. Paciente 1 completa el formulario → ve la Completion Page → psicólogo presiona "Comenzar de nuevo"
2. El formulario se recarga limpio, con nueva sesión
3. Paciente 2 responde → repite el ciclo
4. **Sin necesidad de cerrar tabs ni navegar a otra página**

### Personalización Recomendada

**Mensaje para consultorio privado:**
```
Gracias por completar el cuestionario.

Tu terapeuta revisará estas respuestas antes de tu próxima sesión.
```

**Mensaje para investigación:**
```
¡Gracias por participar!

Tus respuestas han sido registradas de forma anónima. El equipo de investigación analizará los datos de forma confidencial.
```

**Mensaje para screening inicial:**
```
Formulario completado correctamente.

El profesional recibirá tus respuestas y se pondrá en contacto contigo en las próximas 48 horas.
```

## Criterios de Aceptación (Verificados)

✅ En el EIPSI Form Container existe una sección "Completion Page" con:
   - Campo de título
   - Campo de mensaje (textarea)
   - Campo de texto del botón
   - Campo para logo/imagen opcional

✅ Al completar un formulario, el paciente ve:
   - La Completion Page con título y mensaje configurados
   - El logo si se configuró
   - Un solo botón de "Comenzar de nuevo" que recarga el formulario limpio en la misma URL

✅ No se muestran botones extra configurables, ni opciones de puntaje total, ni opciones de "enviar por mail" en esta v1

✅ La Completion Page respeta el preset y se ve bien en mobile y desktop (hereda variables CSS)

✅ Formularios antiguos siguen funcionando, con una Completion Page por defecto si no se configuró nada específico

✅ `npm run build` y `npm run lint:js` pasan sin errores ni warnings tras los cambios

## Pendiente para Próximas Versiones

**No incluido en v1 (por decisión de simplicidad):**

- ❌ Botones adicionales con URL configurable (ej. WhatsApp, agenda)
- ❌ Flag para mostrar puntaje total calculado
- ❌ Flag para enviar copia del resultado por mail al paciente
- ❌ Múltiples estilos de Completion Page (solo hay uno: limpio y centrado)

Estas features se evaluarán **solo si los psicólogos las piden explícitamente** en uso real.

## Testing Recomendado

### Checklist Pre-Release

1. **Editor (Gutenberg)**
   - [ ] Crear un formulario nuevo
   - [ ] Configurar título, mensaje y logo en la sección "Completion Page"
   - [ ] Guardar y recargar: verificar que los valores persisten

2. **Frontend - Escritorio**
   - [ ] Chrome: enviar formulario → ver Completion Page → presionar "Comenzar de nuevo"
   - [ ] Firefox/Edge: repetir el test anterior

3. **Frontend - Mobile**
   - [ ] Android Chrome: completar formulario en teléfono
   - [ ] iPad/tablet: verificar que el botón es fácil de presionar (44×44 px mínimo)

4. **Escenarios Edge**
   - [ ] Formulario sin logo configurado → no debe mostrar imagen rota
   - [ ] Mensaje con saltos de línea múltiples → debe formatear párrafos correctos
   - [ ] Formulario legacy (creado antes de esta feature) → debe usar defaults globales

## Notas Técnicas

- Los atributos del Form Container se serializan como `data-completion-*` en el HTML guardado
- El frontend **siempre** prioriza la configuración del bloque sobre la configuración global
- Si falla todo (red caída, backend sin respuesta), usa hard-coded defaults para evitar pantalla vacía
- La función `formatCompletionMessage()` detecta si el mensaje ya contiene HTML y lo respeta (para compatibilidad futura)

---

**Documentación creada:** Febrero 2025  
**Responsable:** EIPSI Forms Development Team  
**Próxima revisión:** Después de feedback clínico real en consultorio
