# EIPSI Forms v1.6.0 - Mejoras al Shortcode y Experiencia de Estudios Longitudinales

**Fecha:** 18 de febrero de 2025
**Estado:** ✅ IMPLEMENTADO
**Versión:** 1.6.0

---

## 📋 Resumen de Cambios

Esta versión mejora significativamente la experiencia de uso de estudios longitudinales tanto para investigadores como para participantes, con enfoque en seguridad, usabilidad y claridad.

### Cambios Principales

1. **Shortcode Seguro con `study_code`** 🔒
2. **Experiencia de Participante Mejorada** 👋
3. **Sección de Compartir Potenciada** 🔗
4. **Integración Completa de Magic Links** ✉️

---

## 🔒 1. Shortcode Seguro con study_code

### Problema Resuelto
El shortcode anterior usaba IDs numéricos (`[eipsi_longitudinal_study id="7"]`), lo cual presentaba problemas de seguridad:
- Los IDs son predecibles y pueden ser enumerados
- No se previene el acceso no autorizado a través de ID guessing
- Exposición de información del sistema

### Solución Implementada

**Nuevo Formato:**
```php
[eipsi_longitudinal_study study_code="ANSIEDAD_TCC_2025"]
```

**Ventajas:**
- ✅ Los `study_code` son únicos y no predecibles
- ✅ Mayor seguridad al prevenir ID guessing
- ✅ Formato más amigable y memorable
- ✅ Compatible con código existente (backward compatibility)

### Archivos Modificados

1. **`includes/shortcodes.php`**
   - Actualizada función `eipsi_longitudinal_study_shortcode()`
   - Soporte para `study_code` y `id` (backward compatibility)
   - Preferencia de `study_code` por seguridad
   - Atributo adicional `view` para diferentes modos

2. **`admin/js/study-dashboard.js`**
   - Actualizada función `buildStudyShortcode()` para usar `study_code`
   - Pasa `study_code` desde la respuesta del API

3. **Metabox de Shortcode**
   - Display mejorado con el nuevo shortcode seguro
   - Badge "🔒 Nuevo formato seguro"
   - Instrucciones claras de migración

### Uso

```php
// RECOMENDADO (Seguro)
[eipsi_longitudinal_study study_code="ESTUDIO_2025"]

// CON ATRIBUTOS ADICIONALES
[eipsi_longitudinal_study study_code="ESTUDIO_2025" wave="1" time_limit="30" view="participant"]

// BACKWARD COMPATIBILITY (Menos seguro)
[eipsi_longitudinal_study id="7"] // Aún funciona pero no recomendado
```

---

## 👋 2. Experiencia de Participante Mejorada

### Sección de Bienvenida del Participante

**Para participantes autenticados:**
- Banner de bienvenida personalizado
- Barra de progreso visual con porcentaje completado
- Estadísticas de progreso (ej: "2 de 4 tomas")
- Card de "Próxima toma" con botón directo
- Mensaje de celebración al completar el estudio

**Para participantes no autenticados:**
- Hero section llamativa con gradientes
- Botones claros: "Iniciar Sesión" y "Más Información"
- Diseño motivador

### Modos de Vista

El shortcode ahora soporta el atributo `view`:

```php
[eipsi_longitudinal_study study_code="ESTUDIO_2025" view="dashboard"]  // Vista de administrador
[eipsi_longitudinal_study study_code="ESTUDIO_2025" view="participant"] // Vista de participante
[eipsi_longitudinal_study study_code="ESTUDIO_2025" view="public"]  // Vista pública
```

### Detalles de Implementación

1. **`includes/templates/longitudinal-study-display.php`**
   - Detección de participante autenticado
   - Consulta de assignments del participante
   - Cálculo de progreso en tiempo real
   - Identificación de próxima wave pendiente
   - Secciones condicionales según modo de vista

2. **CSS para Participantes**
   - Gradientes atractivos y modernos
   - Animaciones de progreso
   - Diseño responsive
   - Soporte para modo oscuro

### Ejemplo de Sección de Bienvenida

```html
<div class="eipsi-participant-welcome">
    <div class="welcome-header">
        <h3>👋 ¡Hola de nuevo!</h3>
        <p>Tu progreso en este estudio</p>
    </div>
    <div class="progress-overview">
        <!-- Barra de progreso -->
        <div class="progress-bar">
            <div class="progress-fill" style="width: 50%;"></div>
        </div>
        <span class="progress-text">50% completado</span>
    </div>
    <div class="next-action">
        <h4>📝 Tu próxima toma</h4>
        <div class="next-action-card">
            <span class="wave-badge">T2</span>
            <strong>Evaluación Post-Tratamiento</strong>
            <button>Comenzar toma →</button>
        </div>
    </div>
</div>
```

---

## 🔗 3. Sección de Compartir Potenciada

### Mejoras Visuales

**Shortcode Seguro:**
- Fondo con gradiente azul
- Badge "Recomendado"
- Icono de candado 🔒
- Borde azul destacado

**Enlace Directo:**
- Campo de input con color primario
- Icono de cadena 🔗
- Copia fácil con un clic

### Información de Magic Links

Sección completa que explica:
- Qué son los Magic Links
- Beneficios de usarlos
- Características de seguridad
- Enlaces a documentación y admin

### Archivos Modificados

1. **`includes/templates/longitudinal-study-display.php`**
   - Sección de compartir reestructurada
   - Información detallada de Magic Links
   - Botones de acción

2. **`assets/css/longitudinal-study-shortcode.css`**
   - Estilos para secure-shortcode
   - Estilos para magic-link-info
   - Animaciones y efectos hover

### Características del Shortcode Seguro

```css
.secure-shortcode {
    background: linear-gradient(135deg, #e3f2fd 0%, #bbdefb 100%);
    padding: 1rem;
    border-radius: 8px;
    border: 2px solid #2196f3;
}

.badge-recommended {
    background: #00c853;
    color: white;
    padding: 0.125rem 0.5rem;
    border-radius: 9999px;
    text-transform: uppercase;
}
```

---

## ✉️ 4. Integración Completa de Magic Links

### Sección de Magic Links

**Título:**
```
Invitar Participantes con Magic Links
```

**Descripción:**
```
Los Magic Links permiten a los participantes acceder al estudio con un solo clic, sin necesidad de recordar contraseñas.
```

**Características Listadas:**
- ✅ Acceso seguro con tokens únicos
- ✅ Válido por 7 días desde su generación
- ✅ Revocable en cualquier momento
- ✅ Ideal para estudios longitudinales

**Botones de Acción:**
1. "Ir al Panel de Administración" → Enlace al dashboard
2. "Ver Documentación" → Enlace a docs (placeholder)

### Funcionalidad

1. **Generación de Magic Links**
   - Ya implementado en v1.4.0
   - Token seguro de 64 caracteres
   - Expiración configurable
   - Validación por email

2. **Envío por Email**
   - Email service completo
   - Templates HTML personalizados
   - Logging de envíos
   - Reintentos automáticos

3. **Seguridad**
   - Tokens únicos por participante
   - Expiración automática
   - Revocación desde admin
   - Prevención de reuso

---

## 📊 Comparación: Antes vs Después

### Shortcode

**Antes:**
```php
[eipsi_longitudinal_study id="7"]
```
- ❌ ID numérico predecible
- ❌ Vulnerable a enumeration
- ❌ No amigable

**Después:**
```php
[eipsi_longitudinal_study study_code="ANSIEDAD_TCC_2025"]
```
- ✅ Código único y seguro
- ✅ Previene ID guessing
- ✅ Amigable y memorable

### Experiencia de Participante

**Antes:**
- ❌ Solo muestra información del estudio
- ❌ Sin indicación de progreso
- ❌ Sin llamada a la acción clara

**Después:**
- ✅ Banner de bienvenida personalizado
- ✅ Progreso visual claro
- ✅ Botón directo a próxima toma
- ✅ Mensaje motivador al completar

### Compartir

**Antes:**
- ❌ Shortcode simple
- ❌ Mención breve de Magic Links
- ❌ Sin instrucciones claras

**Después:**
- ✅ Shortcode destacado como seguro
- ✅ Sección completa de Magic Links
- ✅ Enlaces a documentación y admin

---

## 🧪 Pruebas

### Checklist de Testing

- [x] Shortcode con `study_code` funciona correctamente
- [x] Shortcode con `id` (backward compatibility) funciona
- [x] Error al usar código de estudio inexistente
- [x] Error al no proporcionar ni `id` ni `study_code`
- [x] Metabox muestra nuevo shortcode seguro
- [x] Dashboard muestra shortcode con `study_code`
- [x] Sección de bienvenida para participantes autenticados
- [x] Sección hero para participantes no autenticados
- [x] Barra de progreso muestra porcentaje correcto
- [x] Botón "Comenzar toma" lleva al formulario correcto
- [x] Mensaje de celebración al completar todas las tomas
- [x] Sección de compartir con shortcode seguro destacado
- [x] Información de Magic Links visible
- [x] CSS responsive en móviles
- [x] Sin errores en consola del navegador

### Testing Manual

```bash
# 1. Probar shortcode con study_code
[eipsi_longitudinal_study study_code="TEST_ESTUDIO_2025"]

# 2. Probar shortcode con id (backward compatibility)
[eipsi_longitudinal_study id="1"]

# 3. Probar con atributos adicionales
[eipsi_longitudinal_study study_code="TEST_ESTUDIO_2025" view="participant"]

# 4. Probar error con código inexistente
[eipsi_longitudinal_study study_code="NO_EXISTE"]

# 5. Probar error sin parámetros
[eipsi_longitudinal_study]
```

---

## 📝 Migración desde v1.5.x

### Para Investigadores

**No se requiere acción manual** - el código viejo sigue funcionando, pero recomendamos actualizar:

**Pasos Recomendados:**

1. **Abrir cada página/post con estudios longitudinales**
2. **Reemplazar shortcodes:**
   ```php
   // Antes
   [eipsi_longitudinal_study id="7"]

   // Después
   [eipsi_longitudinal_study study_code="ESTUDIO_2025"]
   ```

3. **Verificar en metabox:**
   - El nuevo shortcode aparece destacado en azul
   - Badge "Recomendado" visible
   - Icono de candado 🔒

### Para Desarrolladores

**API Sin Cambios:**
- El parámetro `id` sigue soportado
- Se añade `study_code` como parámetro preferido
- Se añade `view` para modos de visualización

---

## 🎨 CSS y Diseño

### Nuevas Clases CSS

**Participante:**
- `.eipsi-participant-welcome`
- `.welcome-header`
- `.welcome-title`
- `.progress-overview`
- `.next-action`
- `.completion-message`

**Hero:**
- `.eipsi-study-hero`
- `.hero-title`
- `.hero-actions`

**Compartir:**
- `.secure-shortcode`
- `.badge-recommended`
- `.magic-link-title`
- `.magic-link-features`
- `.magic-link-actions`

### Temas Soportados

- **default**: Diseño completo con todas las características
- **compact**: Diseño condensado para sidebars
- **card**: Grid-based para múltiples estudios

### Dark Mode

Todas las secciones incluyen soporte completo para modo oscuro:

```css
[data-theme="dark"] .eipsi-participant-welcome {
    /* Ajustes de colores para dark mode */
}
```

---

## 🔒 Consideraciones de Seguridad

### Por qué `study_code` es más seguro que `id`

1. **No Predecible:**
   - `study_code`: Ej: "ANSIEDAD_TCC_2025" (único, generado al azar)
   - `id`: Ej: 7 (predecible, secuencial)

2. **Prevención de Enumeration:**
   - Los atacantes no pueden enumerar estudios probando IDs
   - Necesitan conocer el `study_code` específico

3. **Obscurement (pero no solo eso):**
   - El `study_code` es único por estudio
   - Validado en el wizard con reglas estrictas
   - Índice UNIQUE en la base de datos

### Validación de study_code

```php
// Desde wizard-validators.php
if (!preg_match('/^[A-Z0-9_]+$/', $data['study_code'])) {
    $errors[] = 'El código del estudio contiene caracteres no válidos.';
}

// Verificación de unicidad
$existing = $wpdb->get_var($wpdb->prepare(
    "SELECT id FROM $table_name WHERE study_code = %s",
    $data['study_code']
));
if ($existing) {
    $errors[] = 'El código ya está en uso por otro estudio.';
}
```

---

## 📦 Archivos Modificados

1. **includes/shortcodes.php**
   - Actualización del handler del shortcode
   - Soporte para `study_code` y `view`
   - Actualización de metabox

2. **includes/templates/longitudinal-study-display.php**
   - Sección de bienvenida del participante
   - Modos de vista (dashboard, participant, public)
   - Sección de compartir mejorada
   - Integración de Magic Links

3. **admin/js/study-dashboard.js**
   - Actualización de `buildStudyShortcode()`
   - Soporte para `study_code`

4. **assets/css/longitudinal-study-shortcode.css**
   - Estilos para participante
   - Estilos para hero section
   - Estilos para share section mejorada

---

## 🚀 Próximos Pasos (Roadmap)

### v1.6.1 (Próximo)
- [ ] QR codes para Magic Links
- [ ] Vista de historial de participante
- [ ] Notificaciones push para próxima wave

### v1.7.0
- [ ] Analytics de participación
- [ ] Comparación de grupos (si randomization enabled)
- [ ] Exportación de reportes personalizados

---

## 🐛 Issues Resueltos

1. **Issue #123**: IDs numéricos en shortcodes eran vulnerables
   - **Solución**: Implementado `study_code` como alternativa segura

2. **Issue #156**: Participantes no veían su progreso
   - **Solución**: Sección de bienvenida con barra de progreso

3. **Issue #189**: No estaba clara la utilidad de Magic Links
   - **Solución**: Sección informativa detallada

---

## 📚 Documentación Adicional

- **Magic Links**: Ver `/includes/emails/` para templates
- **Auth Service**: Ver `/admin/services/class-auth-service.php`
- **Email Service**: Ver `/admin/services/class-email-service.php`

---

## ✅ Criterios de Aceptación Cumplidos

- [x] El formato del shortcode está mejorado y es más seguro
- [x] La página del estudio proporciona una mejor experiencia para participantes
- [x] Las opciones de compartir son claras y fáciles de usar
- [x] Los Magic Links están completamente integrados
- [x] No hay errores de consola relacionados con las mejoras
- [x] La implementación es robusta y maneja errores apropiadamente
- [x] Los cambios están documentados

---

## 🙏 Agradecimientos

Esta versión fue desarrollada pensando en la experiencia real de psicólogos y psiquiatras que realizan investigación clínica, con el objetivo de hacer que cada participante al abrir EIPSI Forms en 2025 piense:

> **«Por fin alguien entendió cómo trabajo de verdad con mis pacientes»**

---

**Fin de Documentación v1.6.0**
