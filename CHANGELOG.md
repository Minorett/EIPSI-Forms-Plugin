# Changelog

Todos los cambios clínicamente relevantes del plugin **EIPSI Forms** se documentan en este archivo.

El formato está basado en [Keep a Changelog](https://keepachangelog.com/es/1.0.0/), y este proyecto sigue [Semantic Versioning](https://semver.org/lang/es/).

---

## [Unreleased] – Próxima versión clínica

### Added
- **🎲 Bloque de Aleatorización Independiente (v1.3.0):** Nuevo bloque Gutenberg para configurar aleatorización de formularios sin depender del Form Container. Features:
  - Configuración visual simple con porcentajes automáticos (siempre suman 100%)
  - Asignaciones manuales (override ético) para participantes específicos
  - Generación automática de shortcode `[eipsi_randomization id="xyz"]` y link directo
  - Dos métodos: seeded (reproducible) y pure-random
  - Tracking de asignaciones en base de datos con persistencia
  - Query param `?eipsi_rand=xyz` para acceso directo
  - Compatible con multisite y GDPR
  - Depreca la configuración de aleatorización embebida en Form Container (mantenida por backwards compatibility)
- **Template Profesional de Burnout v2.0 (Validado):** Evaluación clínica completa del síndrome de burnout en profesionales sanitarios que incluye PHQ-9, GAD-7 y MBI-HSS con consentimiento informado profesional. Validado 100% contra block.json v1.2.2 (todos los atributos son compatibles, sin errores de importación). Incluye 5 páginas: Consentimiento, Datos Demográficos, PHQ-9 (9 ítems, escala 0-3), GAD-7 (7 ítems, escala 0-3), MBI-HSS (10 ítems, escala 0-6). Tiempo estimado: 15-20 minutos.
- **Plantillas demo EIPSI:** plantillas genéricas como "Ingreso ansiedad breve (demo)", "Seguimiento emocional (demo)" y "Satisfacción de sesión (demo)" listas para cargar desde el dropdown del EIPSI Form Container.
- **Submissions v1:** tabla paginada integrada en el panel "Results & Experience" con filtros por formulario, exportación directa a Excel/CSV y detalle completo de cada sesión (respuestas + eventos + metadatos).
- **Finalización integrada v1:** mensaje de agradecimiento configurable en la misma URL donde empezó el formulario, con botón "Comenzar de nuevo" para reutilizar tablet en sala.
- **Lógica condicional AND/OR v1.1:** combinación de reglas múltiples (AND + OR) con soporte para RADIO, CHECKBOX, VAS, LIKERT y SELECT, evaluación en tiempo real sin recargar página.
- **Fingerprint clínico liviano:** captura opcional de browser, OS y resolución de pantalla controlada desde la pestaña "Privacy & Metadata"; pensado para tablets compartidas en clínica o investigación.
- **Separador seguro `;` para opciones:** migración interna de comma-separated a semicolon-separated, evitando errores cuando las respuestas contienen comas, comillas o descripciones largas.

### Changed
- **VAS clínico v1.1:**
  - Alineación unificada entre editor y frontend: si configurás "left" en Gutenberg, aparece igual en la página real.
  - Valor 100 posicionado exactamente en el extremo derecho del slider (sin separación visual extra).
  - Soporte condicional consistente con radios, checkboxes y likert (comparaciones numéricas <= >= === para VAS).
- **UX del Form Container:**
  - Paneles reorganizados: navegación, finalización, mapa condicional y apariencia en orden lógico de edición.
  - Navegación: configuración de `allowBackwardsNav` visible de forma clara.
  - Mapa condicional: tabla con filtros y búsqueda para formularios largos.
  - Finalización: panel unificado con distinción entre finalización global (admin) y por formulario, con preview del mensaje de gracias.
  - Apariencia: presets de color + Dark Mode Toggle claramente separados.
- **Panel "Results & Experience":**
  - Pestaña "Submissions" con tabla paginada, filtros por formulario, búsqueda por participant ID y exportación directa.
  - Pestaña "Completion Message" con editor visual, logo opcional y control del botón "Comenzar de nuevo".
  - Pestaña "Privacy & Metadata" con toggles granulares para cada tipo de dato (IP, browser, OS, screen, timezone).

### Fixed
- **Guardado condicional en RADIO, CHECKBOX y LIKERT:** las reglas condicionales se guardan correctamente incluso cuando las opciones contienen comas, tildes o caracteres especiales.
- **Compatibilidad con formularios legacy:** los formularios creados antes de la migración a `;` siguen funcionando sin romper condicionales existentes (doble parser automático).
- **Reparación automática de esquema (hotfix 1.2.2):** garantía de Zero Data Loss en actualizaciones de WordPress o cambios de estructura de BD, con sincronización cada 24 h y al activar el plugin.
- **Distinción finalización global vs. por formulario:** el mensaje de agradecimiento configurado en el admin ya no sobreescribe el mensaje de un formulario individual a menos que el formulario no tenga configuración propia.
- **VAS: validación de obligatorio en página múltiple:** cuando un VAS es obligatorio, la navegación no permite avanzar hasta que el usuario interactúe con el slider.
- **VAS: compresión vertical del last-child en alignment 100:** el último label ya no se aplasta letra por letra cuando el alignment está en máximo; ahora se divide correctamente por palabra en 2 líneas legibles (ej: "Muy bien" en lugar de M-u-y-b-i-e-n). Aumentó el max-width de 26% a 30% y se cambió el transform para que el label crezca hacia la izquierda desde el borde derecho.

### Removed
- **Promesas ambiguas de plantillas clínicas oficiales:** se eliminaron frases del tipo "crear PHQ-9 / GAD-7 / PCL-5 / AUDIT / DASS-21 con un clic" sin implementación real. La documentación ahora aclara que estas escalas están planificadas pero **todavía no disponibles como templates automáticos con scoring**.

---

## [1.2.2] – 2025-01-18 (Hotfix: Zero Data Loss)

### Fixed
- **Auto-reparación de esquema de base de datos:** garantiza que las tablas `wp_vas_form_results` y `wp_vas_form_events` siempre tengan las columnas esperadas, incluso tras actualizaciones de WordPress o cambios de entorno.
- Sincronización automática cada 24 horas en background (WP-Cron).
- Validación y reparación al activar el plugin y ante errores de `submit`.

---

## [1.2.1] – 2025-01-10

### Added
- **Quality Flag v1:** indicador automático de completaciones dudosas (duración < 10 s, baja interacción) visible en la tabla de resultados.
- **Session ID persistente:** identificador único por sesión de navegación, independiente del Participant ID.

### Changed
- **Timestamps en milisegundos:** mayor precisión en duración de sesión y eventos para análisis clínicos y de investigación.

---

## [1.2.0] – 2024-12-20

### Added
- **Dark Mode Toggle universal:** activable desde el Form Container, persiste entre sesiones y respeta contraste WCAG 2.1 AA.
- **5 presets de color clínicos:** Clinical Blue, Soft Teal, Warm Amber, Fresh Green y Neutral Gray.
- **Eventos clínicos (`wp_vas_form_events`):** tabla independiente para tracking de acciones clave (`view`, `start`, `page_change`, `submit`, `abandon`, `branch_jump`).
- **Exportación Excel (XLSX):** además de CSV UTF-8, ahora podés descargar resultados en formato Excel nativo.

### Changed
- **Navegación multipágina robusta:**
  - Primera página: solo "Siguiente" (nunca "Anterior", nunca "Enviar").
  - Páginas intermedias: "Anterior" solo si `allowBackwardsNav = true`.
  - Última página: solo "Enviar" (nunca "Siguiente").
- **Panel "Results & Experience" consolidado:** las tres pestañas (Submissions, Completion Message, Privacy & Metadata) aparecen en un solo menú de WordPress.

---

## [1.1.0] – 2024-11-30

### Added
- **Lógica condicional v1:** mostrar/ocultar bloques y saltos de página (`jump_to_page`) según respuestas previas.
- **Bloques EIPSI nativos:** 11 bloques clínicos (Form Container, Form Block, Página, VAS Slider, Likert, Radio, Multiple, Select, Texto, Textarea, Campo informativo).
- **WCAG 2.1 AA:** validación completa de contraste, focus states, navegación por teclado y touch targets de 44×44 px.

### Changed
- **Migración a `@wordpress/scripts`:** build automático con Webpack y linting integrado.

---

## [1.0.0] – 2024-11-01 (Primera versión clínica estable)

### Added
- **Formularios multipágina básicos:** navegación con validación de campos obligatorios.
- **Bloques iniciales:** VAS Slider, Radio, Checkbox, Texto, Textarea.
- **Base de datos clínica (`wp_vas_form_results`):** almacenamiento seguro de respuestas y metadatos básicos.
- **Admin básico:** tabla de resultados con exportación CSV.
- **Participant ID automático:** UUID generado al inicio de sesión.

---

## Roadmap (sin fecha comprometida)

Estas features están planificadas pero **NO forman parte del plugin actual**:

- **Save & Continue Later:** autosave cada 30 s + beforeunload warning + borrador en IndexedDB.
- **Conditional required:** campos que se vuelven obligatorios según respuestas previas.
- **Plantillas clínicas oficiales con scoring automático:** PHQ-9, GAD-7, PCL-5, AUDIT, DASS-21 con botón "crear con un clic" y normas locales.
- **Dashboard gráfico de analytics:** visualización de eventos, tasas de abandono y tiempo promedio por página.
- **Integración nativa con Elementor, APIs externas, webhooks y cifrado de campos.**
- **Importar/exportar formularios en JSON.**
- **Multilingual (WPML / Polylang).**

---

Para reportar bugs o sugerir mejoras clínicas:  
📧 `support@eipsi.research`  
🐛 [GitHub Issues](https://github.com/roofkat/VAS-dinamico-mvp/issues)
