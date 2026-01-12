# 🧪 Testing Checklist: Bloque de Aleatorización

## Pre-requisitos

- [ ] WordPress 5.8+
- [ ] PHP 7.4+
- [ ] EIPSI Forms v1.3.0+ instalado y activado
- [ ] Al menos 2 formularios publicados en Form Library

---

## ✅ Editor (Gutenberg)

### Inserción del Bloque

- [ ] Al presionar `+` aparece "🎲 Aleatorización de Formularios" en búsqueda
- [ ] El bloque se inserta correctamente
- [ ] Aparece mensaje: "La aleatorización está desactivada. Actívala en el panel lateral"

### Activación

- [ ] Toggle "Activar Aleatorización" en panel lateral funciona
- [ ] Al activar, se genera un `randomizationId` único (formato: `rand_TIMESTAMP_HASH`)
- [ ] Aparecen los controles de configuración

### Configuración de Formularios

- [ ] Dropdown carga formularios desde Form Library (CPT `eipsi_form_template`)
- [ ] Al agregar 1 formulario: porcentaje = 100%
- [ ] Al agregar 2 formularios: porcentajes = 50% / 50%
- [ ] Al agregar 3 formularios: porcentajes = 33% / 33% / 34% (o similar, suma 100)
- [ ] Total siempre suma exactamente 100%
- [ ] Botón [×] remueve formulario correctamente
- [ ] Al remover, porcentajes se recalculan automáticamente
- [ ] No permite agregar el mismo formulario dos veces (alerta)

### Asignaciones Manuales

- [ ] Input de email valida formato correcto
- [ ] Dropdown de formularios funciona
- [ ] Botón "Añadir" agrega asignación
- [ ] No permite duplicados (alerta: "Ya existe asignación...")
- [ ] Email se normaliza a lowercase
- [ ] Botón [×] remueve asignación correctamente

### Generación de Shortcode/Link

- [ ] Con < 2 formularios: warning "Necesitás al menos 2 formularios"
- [ ] Con 2+ formularios: aparece card de generación
- [ ] Shortcode tiene formato correcto: `[eipsi_randomization id="rand_xyz"]`
- [ ] Link tiene formato correcto: `https://site.com/?eipsi_rand=rand_xyz`
- [ ] Botón "📋 Copiar Shortcode" copia al portapapeles
- [ ] Botón "🔗 Copiar Link" copia al portapapeles
- [ ] Feedback visual al copiar (botón cambia a "✓ Copiado!")

### Vista Previa

- [ ] Toggle "Mostrar Vista Previa" funciona
- [ ] Vista previa muestra formularios con porcentajes
- [ ] Vista previa muestra método (seeded/pure-random)
- [ ] Vista previa muestra cantidad de asignaciones manuales

### Método de Aleatorización

- [ ] Dropdown muestra "Con seed reproducible" y "Random puro"
- [ ] Cambio de método se guarda correctamente

### Panel Lateral (Inspector)

- [ ] Toggle "Activar Aleatorización" funciona
- [ ] SelectControl "Método de Aleatorización" funciona
- [ ] Toggle "Mostrar Vista Previa" funciona
- [ ] Toggle "Mostrar Instrucciones en Frontend" funciona
- [ ] Todos los controles persisten al recargar editor

---

## ✅ Persistencia

- [ ] Al guardar el post/página, configuración persiste
- [ ] Al recargar editor, todos los formularios aparecen
- [ ] Al recargar editor, todas las asignaciones manuales aparecen
- [ ] Al recargar editor, método de aleatorización persiste
- [ ] randomizationId NO cambia al recargar (es permanente)

---

## ✅ Frontend (Shortcode)

### Renderizado Básico

- [ ] Shortcode `[eipsi_randomization id="xyz"]` se procesa correctamente
- [ ] No aparece texto plano del shortcode (se procesa)
- [ ] Si showInstructions=true, aparece disclaimer azul
- [ ] Se renderiza el formulario asignado

### Asignación Aleatoria

**Test 1: Participante Nuevo (sin email)**
- [ ] Primer acceso: recibe un formulario aleatorio
- [ ] Segundo acceso (misma IP/browser): recibe el mismo formulario
- [ ] Tercer acceso (IP diferente): podría recibir otro formulario

**Test 2: Participante con Email (seeded)**
- [ ] Acceso con `?email=test@example.com`: recibe formulario
- [ ] Segundo acceso con mismo email: recibe el MISMO formulario
- [ ] Acceso desde otro browser con mismo email: recibe el MISMO formulario

**Test 3: Asignación Manual**
- [ ] Configurar asignación manual: `user@example.com → Formulario A`
- [ ] Acceder con `?email=user@example.com`: recibe Formulario A
- [ ] SIEMPRE recibe Formulario A (bypass de aleatorización)

**Test 4: Método Pure-Random**
- [ ] Configurar método "Random puro"
- [ ] Cada acceso puede recibir formulario diferente (probabilístico)

### Link Directo

- [ ] Link `https://site.com/?eipsi_rand=xyz` redirige a la página con el bloque
- [ ] Si no existe, muestra error 404
- [ ] Si existe, renderiza el shortcode automáticamente

### Tracking de Asignaciones

- [ ] Verificar en DB: tabla `wp_eipsi_randomization_assignments` existe
- [ ] Primera asignación: se crea registro con `assigned_at`, `access_count=1`
- [ ] Segunda asignación (mismo participante): `access_count` incrementa, `last_access` actualiza
- [ ] No se crean duplicados para mismo participante + mismo randomization_id

---

## ✅ Casos Edge

- [ ] Formulario con título en español con tildes: funciona
- [ ] Formulario con título muy largo (>50 chars): funciona
- [ ] 10+ formularios configurados: porcentajes suman 100
- [ ] Email con caracteres especiales (ñ, á, etc.): se normaliza correctamente
- [ ] Bloque en página con otros bloques: no interfiere
- [ ] Múltiples bloques de aleatorización en misma página: cada uno independiente
- [ ] Post sin publicar: no renderiza en frontend (correcto)
- [ ] Post en draft: shortcode no funciona (correcto)

---

## ✅ Seguridad

- [ ] Inputs de email sanitizados (no XSS)
- [ ] Shortcode sanitizado (no SQL injection)
- [ ] randomizationId no es secuencial (usa timestamp + random)
- [ ] IP addresses se sanitizan con `filter_var(FILTER_VALIDATE_IP)`
- [ ] Asignaciones manuales requieren email válido

---

## ✅ Compatibilidad

### Backwards Compatibility
- [ ] Formularios con aleatorización legacy (Form Container) siguen funcionando
- [ ] Shortcode `[eipsi_randomized_form]` no roto
- [ ] No hay conflictos entre legacy y nuevo sistema

### WordPress
- [ ] Compatible con Gutenberg 5.8+
- [ ] Compatible con Classic Editor (shortcode manual)
- [ ] Compatible con Multisite

### Browsers
- [ ] Chrome 90+ (desktop/mobile)
- [ ] Firefox 88+ (desktop/mobile)
- [ ] Safari 14+ (desktop/mobile)
- [ ] Edge 90+

---

## ✅ UX & Accesibilidad

- [ ] Tooltips explicativos en controles complejos
- [ ] Mensajes de error claros y en español
- [ ] Botones tienen tamaño touch-friendly (44×44px mínimo)
- [ ] Colores tienen contraste WCAG AA
- [ ] Teclado: Tab navega correctamente por controles
- [ ] Screen reader: labels descriptivos

---

## ✅ Performance

- [ ] Build time < 6 segundos
- [ ] Lint sin errores (excepto preexistente en eipsi-random.js)
- [ ] Bundle size del bloque < 50 KB
- [ ] Carga de formularios en editor < 2 segundos (con 100 forms)
- [ ] Tracking de asignación en DB < 100ms

---

## ✅ Documentación

- [ ] README.md completo
- [ ] CHANGELOG.md actualizado
- [ ] Código comentado en español
- [ ] PHPDoc en funciones críticas
- [ ] JSDoc en funciones complejas

---

## 🎯 Criterios de Éxito

Para considerar el bloque **production-ready**:

- [ ] ✅ Todos los checks de Editor pasados
- [ ] ✅ Todos los checks de Frontend pasados
- [ ] ✅ Todos los checks de Tracking pasados
- [ ] ✅ Al menos 1 psicólogo clínico lo ha testeado en condiciones reales
- [ ] ✅ Build exitoso sin errores
- [ ] ✅ Lint exitoso (max 1 error preexistente)
- [ ] ✅ Zero Data Loss garantizado

---

## 📝 Notas de Testing

### Setup Test Environment

```bash
# Crear 2 formularios de prueba
wp post create --post_type=eipsi_form_template --post_title="PHQ-9 Test" --post_status=publish
wp post create --post_type=eipsi_form_template --post_title="GAD-7 Test" --post_status=publish

# Crear página de prueba
wp post create --post_type=page --post_title="Test Randomization" --post_status=publish

# Verificar tabla
wp db query "DESCRIBE wp_eipsi_randomization_assignments;"

# Ver asignaciones
wp db query "SELECT * FROM wp_eipsi_randomization_assignments;"
```

### Debug Mode

Para ver logs detallados:

```php
// En wp-config.php
define('WP_DEBUG', true);
define('WP_DEBUG_LOG', true);
```

Logs en: `wp-content/debug.log`

---

**Versión**: 1.3.0  
**Última actualización**: 2025-01-19  
**Responsable**: Mathias N. Rojas de la Fuente
