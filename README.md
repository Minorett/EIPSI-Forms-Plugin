# EIPSI Forms - Plugin de Investigación Clínica para WordPress

> Formularios profesionales de grado clínico para investigación en psicoterapia y salud mental

**Versión:** 1.2.0  
**Requisitos:** WordPress 5.8+, PHP 7.4+  
**Licencia:** GPL v2 or later

---

## 🎯 Características Principales

### 📋 Bloques de Gutenberg Personalizables

EIPSI Forms incluye **11 bloques nativos de Gutenberg** optimizados para investigación clínica:

#### **Contenedores**
- **EIPSI Form Container** - Contenedor principal con paginación y manejo de envío
- **EIPSI Form Block** - Bloque para mostrar formularios con capacidades avanzadas
- **EIPSI Página** - Contenedor de página para formularios multi-página

#### **Campos de Entrada**

**EIPSI VAS Slider** (Escala Analógica Visual)
- Slider interactivo con feedback en tiempo real
- Rangos personalizables (0-100, 0-10, etc.)
- Etiquetas de extremo configurables
- Validación de rango integrada
- Thumb optimizado para touch (32×32px + 12px padding = 44×44px)

**EIPSI Campo Likert** (Escala Likert)
- Escalas configurables (3, 5, 7, 10+ puntos)
- Etiquetas personalizables por punto
- Validación de respuesta obligatoria/opcional
- Soporte para lógica condicional
- Totalmente responsivo (44×44px touch targets)

**EIPSI Campo Radio** (Opción Única)
- Múltiples opciones dinámicas
- Validación de respuesta obligatoria
- Lógica condicional integrada
- Focus indicators accesibles (WCAG 2.1 AA)

**EIPSI Campo Multiple** (Checkboxes)
- Selección múltiple
- Validación flexible
- Lógica condicional completa

**EIPSI Campo Select** (Dropdown)
- Menú desplegable nativo
- Opciones dinámicas
- Lógica condicional

**EIPSI Campo Texto** (Input)
- Tipos: text, email, number, tel, url
- Validación de formato integrada
- Límite de caracteres opcional
- Placeholders personalizados

**EIPSI Campo Textarea** (Respuestas Largas)
- Área de texto multi-línea
- Límite de caracteres configurable
- Validación de longitud

**EIPSI Campo Descripción** (Texto Informativo)
- Texto estático sin input
- Ideal para instrucciones
- Formato rich text

---

## 🎨 Sistema de Diseño Profesional

### **5 Presets de Color Predefinidos**

#### 1. **Clinical Blue** (Defecto) ✅
Azul profesional con branding EIPSI
- **Primary:** #005a87 (EIPSI Blue - contraste 7.47:1)
- **Text:** #2c3e50 (contraste 10.98:1)
- **Background:** #ffffff
- **Características:** Sombras sutiles, border-radius moderado (8-12px), fuente system default

#### 2. **Minimal White** ✅
Esquema ultra-limpio y minimalista
- **Primary:** #475569 (Slate)
- **Text:** #0f172a
- **Background:** #ffffff
- **Características:** Sin sombras, bordes sharp (4-6px), espaciado generoso

#### 3. **Warm Neutral** ✅
Tonos cálidos y acogedores para contextos terapéuticos
- **Primary:** #8b6f47 (Warm brown)
- **Text:** #3d3935
- **Background:** #fdfcfa (warm white)
- **Características:** Fuentes serif en encabezados, bordes redondeados (10-14px), sombras suaves

#### 4. **Serene Teal** ✅
Paleta calmante para estudios de reducción de estrés
- **Primary:** #0e7490 (Teal)
- **Text:** #0c4a6e (Deep cyan)
- **Background:** #ffffff
- **Background Subtle:** #f0f9ff (Light cyan)
- **Características:** Tonos teal/cyan calmantes, bordes balanceados (10-16px), sombras teal

#### 5. **Dark EIPSI** ✅
Modo oscuro profesional con fondo EIPSI blue
- **Primary:** #22d3ee (Cyan brillante)
- **Background:** #005a87 (EIPSI Blue oscuro)
- **Background Subtle:** #003d5b
- **Text:** #ffffff (White)
- **Características:** Alto contraste para dark mode, inputs con fondo claro, sombras oscuras

### **Controles Personalizables**
- Color primario, hover y activo
- Color de texto y fondo
- Colores de error, éxito y advertencia
- **52 CSS variables** para control granular (`--eipsi-color-*`, `--eipsi-spacing-*`, etc.)

### **✅ WCAG 2.1 AA Compliant**
- Todas las combinaciones de color: **4.5:1 mínimo** (texto grande), **7:1+ óptimo** (texto pequeño)
- Touch targets: **44×44px (WCAG AAA)**
- Validación automatizada con script `wcag-contrast-validation.js` (72 tests, 100% pass rate)
- Warnings en FormStylePanel para prevenir violaciones

### **Responsividad Completa**
- Validada en 6 breakpoints: 320px, 375px, 480px, 768px, 1024px, 1280px+
- Mobile-first design
- Media queries en todos los bloques
- Optimizado para iOS y Android nativos

### **✨ WYSIWYG Instant Preset Preview (Nuevo en v1.2.1)**
- **Previsualización instantánea** de presets en el editor de Gutenberg
- **Sin necesidad de guardar** - los cambios de estilo se ven inmediatamente
- **CSS Variables completas** - 54 variables aplicadas dinámicamente a todos los bloques
- **100% consistencia** entre editor y vista publicada
- **Todos los elementos responden** - colores, tipografía, espaciado, bordes, sombras, transiciones
- **Experiencia WYSIWYG profesional** como Figma o Visual Studio Code
- **Retroalimentación visual instantánea** para decisiones de diseño informadas

Los investigadores ahora pueden:
- Probar diferentes presets y ver cambios al instante
- Comparar esquemas de color sin guardar/previsualizar
- Validar legibilidad y contraste en tiempo real
- Personalizar diseño con confianza total en el resultado final

---

## 🔐 Lógica Condicional Avanzada

### **Reglas Condicionales Inteligentes**
- **Es igual a** / **No es igual a**
- **Mayor que** / **Menor que**
- **Contiene** / **No contiene**
- Múltiples reglas con operadores **AND/OR**

### **Acciones Dinámicas**
- Mostrar/ocultar campos según respuestas
- **Saltar a página específica** (branch logic)
- Mostrar/ocultar secciones enteras
- Evaluación en tiempo real durante completación

### **Bloques Soportados**
- ✅ EIPSI VAS Slider
- ✅ EIPSI Campo Radio
- ✅ EIPSI Campo Multiple
- ✅ EIPSI Campo Select

**Ejemplo:**
```javascript
// Si pain_level > 7, saltar a página 3 (preguntas detalladas)
conditionalLogic: {
    enabled: true,
    rules: [
        { field: 'pain_level', operator: 'greater_than', value: '7' }
    ],
    action: 'jump_to_page',
    jumpToPage: 3,
    logic: 'AND'
}
```

---

## 📊 Identificación y Metadatos

### **Form ID Inteligente**
Generación automática de IDs estables y legibles:
- "Anxiety Clinical Assessment" → `ACA-a3f1b2`
- "Depression Inventory" → `DI-c7d8e9`
- "Brief Symptom Evaluation" → `BSE-f4e3d2`

**Algoritmo:**
- 3 primeras letras de cada palabra significativa
- Hash MD5 de 6 caracteres para unicidad
- Reproducible (mismo nombre → mismo ID)

### **Participant ID Universal**
- UUID v4 truncado a 12 caracteres: `p-a1b2c3d4e5f6`
- Persiste en `localStorage` durante toda la sesión
- Mismo ID en múltiples formularios (tracking longitudinal)
- **Completamente anónimo** (no contiene PII)

### **Session ID para Tracking**
- Único por cada intento de completación
- Formato: `sess-{timestamp}-{random}` (ej: `sess-1705764645000-xyz`)
- Permite rastrear múltiples intentos del mismo participante
- Análisis de abandonos y patrones de completación

### **Metadatos Completos Capturados**

#### Metadatos Automáticos:
- ✅ **Timestamps** de inicio/fin y duración (ms) - **SIEMPRE**
- ✅ **Device type** (mobile/desktop/tablet) - **ON por defecto** (configurable)
- ⚙️ **Browser** (Chrome, Firefox, Safari, Edge, etc.) - **OFF por defecto** (configurable)
- ⚙️ **OS** (Windows, macOS, Linux, iOS, Android) - **OFF por defecto** (configurable)
- ⚙️ **Screen width** (px) - **OFF por defecto** (configurable)
- ⚙️ **IP Address** - **ON por defecto** (configurable) - Auditoría clínica

> **Nota de Privacidad:**  
> - Browser, OS y Screen Width están **desactivados por defecto** para máxima privacidad.
> - IP Address está **activado por defecto** para auditoría clínica, pero es **desactivable**.
> - Todos los campos opcionales se configuran en el Dashboard de Privacidad.

#### Metadatos Clínicos (JSON en campo `metadata`):
- ✅ **Therapeutic Engagement:** Tiempo dedicado, cambios de campo, eventos de navegación - **ON por defecto** (configurable)
- ✅ **Clinical Consistency:** Coherencia de respuestas (score automático) - **ON por defecto** (configurable)
- ✅ **Avoidance Patterns:** Campos omitidos, retrocesos, tiempo excesivo - **ON por defecto** (configurable)

#### Quality Flag Automático:
- **HIGH:** Completación rápida y coherente
- **NORMAL:** Completación estándar
- **LOW:** Patrones sospechosos (muy rápido, incoherente, muchos saltos)

---

## 🛡️ Seguridad y Privacidad

### **Dashboard de Privacidad Integrado** 🆕
Configuración granular de metadatos por formulario:

**Obligatorios (siempre capturados):**
- Form ID, Participant ID, Session ID, Timestamps, Quality Flag

**Recomendados (ON por defecto):**
- ✅ Therapeutic Engagement
- ✅ Clinical Consistency  
- ✅ Avoidance Patterns
- ✅ Device Type

**Auditoría Clínica (ON por defecto, desactivable):**
- ⚙️ IP Address (retención 90 días, GDPR compliant)

**Dispositivo (OFF por defecto, opcional):**
- ⚙️ Browser
- ⚙️ OS
- ⚙️ Screen Width

> **Acceso:** WordPress Admin → EIPSI Forms → Privacy Config  
> **Filosofía:** Privacidad por defecto. Solo datos clínicos esenciales activados.

### **HIPAA Ready**
Plugin preparado para cumplimiento HIPAA (no certificado):
- ✅ Audit trail completo (IP, timestamps, device) - **configurable**
- ✅ Encriptación de credenciales de BD externa (AES-256-CBC)
- ✅ Control de acceso basado en capabilities de WordPress
- ⚠️ Datos de formularios almacenados sin encriptación (requiere configuración de servidor HTTPS + BD encriptada)

**Nota:** "HIPAA Ready" significa que el plugin está **preparado** para entornos HIPAA, pero **no garantiza certificación** sin configuración adicional del servidor y BD.

### **GDPR Compliant**
- ✅ Derecho al olvido (eliminación por Participant ID)
- ✅ Portabilidad de datos (exportación Excel/CSV)
- ✅ Consentimiento explícito (configurable por formulario)
- ✅ Política de retención configurable (90 días default para IP)
- ✅ **Privacidad por defecto:** Browser/OS/Screen Width OFF por defecto

### **Validación y Sanitización**
- **Cliente (JavaScript):** Validación en tiempo real (required, email, rangos, patterns)
- **Servidor (PHP):** Sanitización completa (`sanitize_text_field`, `sanitize_email`, `esc_sql`)
- **Protección XSS:** Escapado apropiado (`esc_html`, `esc_attr`, `wp_json_encode`)
- **Nonce verification** en todos los AJAX handlers

---

## 💾 Gestión de Bases de Datos

### **Base de Datos Local (WordPress)**
**Tabla:** `wp_vas_form_results` (27 columnas)
- Almacena respuestas completas, metadatos, IP, timestamps
- Índices optimizados: `form_id`, `participant_id`, `session_id`, `created_at`
- Campo `form_responses` en formato JSON

**Tabla:** `wp_vas_form_events` (8 columnas)
- Tracking de eventos: `view`, `start`, `page_change`, `submit`, `abandon`, `branch_jump`
- Almacena: `form_id`, `session_id`, `event_type`, `page_number`, `metadata`, `created_at`

### **Base de Datos Externa (MySQL/MariaDB)**
- ✅ Configuración segura con credenciales encriptadas (AES-256-CBC)
- ✅ **Auto-creación de tablas** al conectar
- ✅ **Sincronización automática de esquema** (Phase 14 - Database Schema Manager)
  - Verifica esquema al guardar credenciales
  - Verificación periódica cada 24 horas
  - Agrega columnas faltantes automáticamente
  - Manual: botón "Verify & Repair Schema" en admin
- ✅ Fallback a WordPress DB si falla externa (zero data loss)

### **Gestión de Resultados en Admin**
- Vista de tabla paginada con todas las respuestas
- Filtrado por: Form ID, Participant ID, rango de fechas
- Búsqueda rápida
- Eliminación individual o en lote (con nonce verification)

---

## 📈 Análisis y Tracking

### **Sistema de Seguimiento Integrado**
**Ubicación:** `assets/js/eipsi-tracking.js` (359 líneas)

#### Eventos Registrados (6 tipos):
1. **view** - Vista del formulario
2. **start** - Inicio de formulario
3. **page_change** - Cambio de página en formularios multi-página
4. **submit** - Envío exitoso
5. **abandon** - Abandono (visibilitychange o beforeunload)
6. **branch_jump** - Salto por lógica condicional

#### Almacenamiento:
- `sessionStorage` en navegador (recuperación tras refresh)
- Tabla `wp_vas_form_events` en base de datos
- Metadatos por evento: timestamp, page_number, user_agent, metadata adicional

#### Tracking de Participantes:
- Sesiones identificadas por `Session ID`
- Duración de sesión calculada automáticamente
- Dispositivo/navegador/OS capturado
- Puntos de abandono registrados

### **Dashboard de Analytics** 
⚠️ **Status:** EN DESARROLLO

El sistema de tracking está **completamente implementado** y registra todos los eventos en la base de datos. El dashboard visual de analytics está planificado para una futura versión.

**Análisis actualmente disponible:**
- ✅ Datos raw en tabla `wp_vas_form_events`
- ✅ Queries SQL para análisis manual (tasa de abandono, tiempo promedio, etc.)
- ⏳ UI de analytics en admin (roadmap)

---

## 📥 Exportación de Datos

### **Exportación a Excel (XLSX)** ✅
**Librería:** `SimpleXLSXGen` (incluida en `/lib/`)

**Formato de exportación:**
- Headers legibles (nombres de campo)
- Columnas automáticas: ID, Form Name, Form ID, Participant ID, Session ID, Created At, Submitted At, Duration (s), Device, Browser, OS, Screen Width, IP Address, Quality Flag, Status
- **Expansión dinámica:** Una columna por cada campo del formulario (parse de JSON en `form_responses`)
- Metadatos completos incluidos

**Filtrado:**
- Por Form Name (GET parameter `form_name`)
- Rango de fechas (implementable)

**Nombre archivo:** `{form_name}_responses_{timestamp}.xlsx`

### **Exportación a CSV** ✅
- UTF-8 con BOM (compatible con Excel)
- Separador: coma (`,`)
- Mismo contenido que XLSX
- **Uso:** Análisis en SPSS, R, Python, Excel

---

## 🎯 Experiencia de Usuario

### **Interfaz de Participante**
- Formulario limpio y clínico (diseño profesional)
- Indicador de progreso visual (`Página X de Y`)
- Navegación intuitiva (botones Atrás/Siguiente/Enviar)
- Validación en tiempo real con mensajes de error
- Mensaje de éxito tras envío
- Opción de reiniciar o volver al inicio

### **Persistencia de Sesión** ⏳
**Status:** Roadmap futuro

**Planificado:**
- Save and Continue: Guardar y retomar después
- Datos persistidos en localStorage (cifrados)
- Recuperación automática de sesión

**Actualmente:**
- Datos persisten durante la sesión del navegador (antes de enviar)
- Al refrescar página: se restaura estado si no se ha enviado

### **Mensajes Personalizables**
- Mensaje de inicio (configurable por formulario)
- Helper text por campo (instrucciones contextuales)
- Mensaje de agradecimiento final (configurable)
- Mensajes de error personalizados por campo

---

## ⚙️ Configuración Avanzada

### **Panel de Privacidad y Metadatos**
**Ubicación:** Admin → EIPSI Forms → Privacy & Metadata

**Configuración granular por formulario:**
- ✅ Therapeutic Engagement (toggle)
- ✅ Clinical Consistency (toggle)
- ✅ Avoidance Patterns (toggle)
- ✅ Device tracking (toggle)
- ✅ IP Address (siempre ON - requisito de auditoría)

### **Privacy Dashboard**
- UI intuitiva en panel de administración
- Indicadores de estado de configuración
- Info sobre retención de datos (90 días default)
- Estado de sincronización de BD externa

### **Hooks y Filtros Disponibles**
```php
// Hooks de acción
do_action('eipsi_form_before_render', $form_id, $attributes);
do_action('eipsi_form_after_submit', $form_id, $participant_id, $responses);
do_action('eipsi_tracking_event', $event_type, $form_id, $session_id, $metadata);

// Filtros
apply_filters('eipsi_validate_field', $is_valid, $field_name, $value, $field_config);
apply_filters('eipsi_sanitize_field', $sanitized_value, $field_name, $raw_value);
apply_filters('eipsi_style_tokens', $style_config, $form_id);
```

---

## 🔧 Stack Técnico

### **Frontend**
- **React** (Gutenberg blocks)
- **Vanilla JavaScript** (eipsi-forms.js, eipsi-tracking.js)
- **SCSS** → CSS3 (compilado con Webpack)
- **CSS Variables** (52 tokens customizables)

### **Backend**
- **PHP 7.4+** (WordPress hooks y filters)
- **MySQL/MariaDB** (BD local y externa)
- **SimpleXLSXGen** (exportación Excel)

### **Build**
- **Webpack 5** (build de bloques)
- **npm scripts** (build, lint, format)
- Tiempo de build: ~4.1s

### **Validación**
- `npm run lint:js` (0 errors, 0 warnings - 100% compliance)
- `node accessibility-audit.js` (73 tests, 100% pass rate)
- `node wcag-contrast-validation.js` (72 tests, 100% pass rate)
- `node performance-validation.js` (28 tests, 100% pass rate)
- `node edge-case-validation.js` (82 tests, 100% pass rate)

---

## 📊 Especificaciones de Rendimiento

⚡ **Load time:** < 2 segundos  
🎯 **Uptime:** Diseño para 99.9%  
📈 **Escalabilidad:** Optimizado para 1000+ formularios  
🔄 **Sincronización:** Instantánea entre BDs (fallback a WordPress DB)  
📦 **Bundle size:** Optimizado (code splitting por bloque)

---

## ✅ Conformidad y Certificaciones

| Estándar | Estado | Validación |
|----------|--------|------------|
| **WCAG 2.1 AA** | ✅ Compliant | 73 automated tests (100% pass) |
| **HIPAA** | ⚠️ Ready | Audit trail + encryption preparada |
| **GDPR** | ✅ Compliant | Retention policies + right to erasure |
| **WCAG AAA Touch Targets** | ✅ Implementado | 44×44px mínimo |
| **Contraste de Color** | ✅ Validado | 72 tests (5 presets, 100% pass) |
| **Keyboard Navigation** | ✅ Funcional | Tab order lógico + focus indicators |
| **Screen Reader** | ✅ Compatible | ARIA labels completos |

---

## 🚀 Flujo de Uso Típico

### Para Investigadores

1. **Crear formulario** en Gutenberg editor
2. **Agregar bloques** (VAS Slider, Likert, Radio, etc.)
3. **Configurar lógica condicional** (opcional)
4. **Personalizar estilos** (presets o custom)
5. **Configurar privacidad** (metadatos a capturar)
6. **Publicar** y obtener link del formulario
7. **Distribuir** a participantes (email, web, QR code)

### Para Participantes

1. **Acceder** al formulario vía link
2. **Responder preguntas** progresivamente
3. **Navegar** entre páginas (si formulario multi-página)
4. **Enviar** formulario
5. **Ver mensaje de éxito** (customizable)
6. Datos guardados automáticamente (no resubmission)

### Para Análisis

1. **Acceder** a panel de resultados (Admin → EIPSI Forms → Results)
2. **Filtrar/buscar** respuestas (por Form ID, Participant ID, fecha)
3. **Exportar** a Excel/CSV (botón "Export to Excel")
4. **Importar** en SPSS/R/Python para análisis estadístico
5. **Analizar eventos** (queries SQL en `wp_vas_form_events` para tracking)

---

## 📚 Documentación

### Guías Disponibles
- ✅ **Instalación:** `/docs/INSTALLATION.md` (incluida en plugin)
- ✅ **Referencia de Bloques:** `/docs/BLOCKS_REFERENCE.md`
- ✅ **Configuración de Privacidad:** `/docs/PRIVACY_CONFIGURATION.md`
- ✅ **Sincronización de BD:** `/docs/DATABASE_SCHEMA_SYNC.md` (500+ líneas, español)
- ✅ **Lógica Condicional:** `CONDITIONAL_LOGIC_GUIDE.md`
- ✅ **Presets de Tema:** `THEME_PRESETS_DOCUMENTATION.md`

### Reportes QA
- ✅ **Phase 5 (Accessibility):** `/docs/qa/QA_PHASE5_RESULTS.md` (50+ páginas)
- ✅ **Phase 6 (Analytics):** `/docs/qa/QA_PHASE6_RESULTS.md` (500+ líneas)
- ✅ **Phase 7 (Admin Workflows):** `/docs/qa/QA_PHASE7_RESULTS.md` (600+ líneas)
- ✅ **Phase 8 (Edge Cases):** `/docs/qa/QA_PHASE8_RESULTS.md` (1,200+ líneas)
- ✅ **Phase 9 (Performance):** `/docs/qa/QA_PHASE9_RESULTS.md` (900+ líneas)

---

## 🔄 Roadmap Futuro

### En Desarrollo
- ⏳ **Dashboard de Analytics** (tracking implementado, UI en desarrollo)
- ⏳ **Save and Continue** (guardar progreso y retomar después)

### Planificado
- 🔮 Multi-idioma (i18n completo con archivos .po/.mo)
- 🔮 Versiones de formularios (versionado de cambios)
- 🔮 A/B testing (variantes de formularios)
- 🔮 Análisis estadístico integrado (correlaciones, distribuciones)
- 🔮 API REST completa (CRUD de formularios)
- 🔮 Webhooks (notificaciones en tiempo real)
- 🔮 Encriptación end-to-end (respuestas en BD)
- 🔮 Sincronización con EMRs (Electronic Medical Records)

### Completado Recientemente
- ✅ **Dark EIPSI Preset** (Phase 13 - November 2025)
- ✅ **Database Schema Synchronization** (Phase 14 - January 2025)
- ✅ **WCAG 2.1 AA Compliance** (Phase 5 - validado 100%)
- ✅ **Code Quality & Linting** (0 errors, 0 warnings)

---

## 📝 Licencia

Este plugin está licenciado bajo **GPL v2 or later**.

Desarrollado por **EIPSI Research Team** con **cto.new** para investigación en psicoterapia y salud mental.

---

## 🤝 Contribuciones

Las contribuciones son bienvenidas. Por favor:

1. Abre un **issue** para reportar bugs o sugerir mejoras
2. Envía **pull requests** con:
   - Descripción clara del cambio
   - Tests automatizados (si aplica)
   - Código que pase linting (`npm run lint:js`)
3. Sigue los estándares de código existentes
4. Actualiza documentación si es necesario

---

## 📞 Soporte

Para soporte técnico, reportar bugs o sugerencias:

- 📧 **Email:** support@eipsi.research
- 🐛 **GitHub Issues:** [Abrir issue](https://github.com/roofkat/VAS-dinamico-mvp/issues)
- 📖 **Documentación:** Ver carpeta `/docs/` en el plugin

---

## 🔍 Audit & Changelog

Este README fue actualizado el **Enero 2025** basándose en un **escaneo completo del código** (ver `PLUGIN_AUDIT_REPORT.md` para detalles).

### Cambios vs. Versión Anterior
- ✅ **Agregado:** Preset "Serene Teal" (no documentado anteriormente)
- ✅ **Agregado:** Preset "Dark EIPSI" (implementado Phase 13)
- ❌ **Removido:** Preset "High Contrast" (no existe en código)
- ✅ **Actualizado:** Número de presets (4 → 5)
- ✅ **Clarificado:** HIPAA "Ready" vs. "Compliant"
- ✅ **Marcado:** Dashboard Analytics como "En desarrollo"
- ✅ **Expandido:** Detalles de 11 bloques individuales
- ✅ **Agregado:** Información de sincronización de esquema BD

---

**¿Preguntas?** Ver `PLUGIN_AUDIT_REPORT.md` para verificación detallada de features vs. código real.
