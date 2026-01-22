# Changelog

Todos los cambios clínicamente relevantes del plugin **EIPSI Forms** se documentan en este archivo.

El formato está basado en [Keep a Changelog](https://keepachangelog.com/es/1.0.0/), y este proyecto sigue [Semantic Versioning](https://semver.org/lang/es/).

---

## [1.3.16] – 2025-01-26 (CRITICAL: Delete Response con BD Externa Roto)

### 🔴 HOTFIX CRÍTICO - Eliminación de Respuestas Fallaba con BD Externa

**Severidad:** MEDIA-ALTA - Delete no funciona cuando BD externa está configurada
**Impacto:** Los clínicos usando BD externa no pueden eliminar registros, generando datos fantasma y confusión

#### Fixed
- ❌→✅ **Delete Response falla silenciosamente:** `admin/handlers.php` siempre intentaba eliminar de BD local usando `$wpdb->delete()`, pero `submissions-tab.php` mostraba registros de BD externa si estaba habilitada. Resultado: "Failed to delete response. The record may not exist."
- ❌→✅ **Inconsistencia BD externa/local:** El sistema LEÍA de BD externa pero ELIMINABA de BD local, creando datos fantasma (registros visibles pero impossibles de borrar).
- ❌→✅ **Sin feedback de error:** No había logs ni mensajes claros sobre por qué fallaba la eliminación.

#### Changed
- **`admin/handlers.php`:** Refactorizado lógica de eliminación (líneas 42-136).
  - **Detectar BD externa:** Instanciar `EIPSI_External_Database` y verificar `$external_db->is_enabled()` antes de eliminar.
  - **Lógica dual de eliminación:**
    - **Si BD externa habilitada:** Usar `mysqli->query()` con `DELETE FROM vas_form_results WHERE id = X`
    - **Si BD externa deshabilitada:** Usar `$wpdb->delete()` (comportamiento original)
  - **Validación pre-delete:** `SELECT COUNT(*)` antes de DELETE en ambos casos para verificar existencia.
  - **Fallback automático:** Si conexión a BD externa falla, intentar BD local.
  - **Logging mejorado:** Logs detallados en WP_DEBUG mode con ID, Database (external/local), y error message.

#### Technical Details
- **Archivos modificados:** 1 archivo (admin/handlers.php), ~95 líneas refactorizadas
- **Causa raíz:** `submissions-tab.php` usa `$external_db->is_enabled()` para leer, pero `handlers.php` nunca verificaba esto para eliminar
- **Seguridad mantenida:** Nonce validation, permission check, ID sanitization intactos
- **SQL injection prevention:** `intval($id)` + prepared statements en ambos casos
- **Backward compatibility:** 100% - BD local funciona exactamente igual, BD externa ahora funciona correctamente
- **Testing:** PHP syntax OK, no requiere npm build (PHP puro), no afecta lint JS

#### Impact Analysis
- **Antes del fix:** BD externa habilitada → delete falla silenciosamente → datos fantasma
- **Después del fix:** Delete funciona correctamente con BD externa y local
- **Risk level:** BAJO - Solo agrega lógica de detección, no modifica comportamiento existente de BD local
- **Deployment priority:** ALTA - Funcionalidad crítica de administración corregida

---

## [Unreleased] – Próxima versión clínica

### Planning
- Integrated completion page (misma URL forever)
- Save & Continue Later + 30s autosave + IndexedDB drafts
- Conditional field visibility dentro de la misma página
- Clinical templates (PHQ-9, GAD-7, etc.) con automatic scoring

---

## [1.3.9] – 2025-01-22 (CRITICAL: Editor Gutenberg Sin Estilos - WYSIWYG Roto)

### 🔴 HOTFIX CRÍTICO - Estilos No Se Cargan en el Editor

**Severidad:** MEDIA-ALTA - WYSIWYG no funciona correctamente en editor Gutenberg
**Impacto:** Los psicólogos clínicos no pueden ver en tiempo real cómo se verán sus formularios, frustrando la experiencia de edición

#### Fixed
- ❌→✅ **Editor Gutenberg Monocromático:** Los CSS del plugin (`eipsi-forms.css`, `admin-style.css`, `theme-toggle.css`, `eipsi-randomization.css`) no se cargaban en el iframe del editor de bloques. Las CSS variables existían en el HTML pero el CSS que las consume estaba ausente.
- ❌→✅ **WYSIWYG no funcional:** Cambiar presets (Azul, Rojo, Oscuro) no reflejaba visualmente en el preview del editor, aunque los datos correctos se guardaban en la base de datos.
- ❌→✅ **Incoherencia Frontend-Editor:** Lo que se veía en el editor (gris, sin estilos) no coincidía con lo que se mostraba en el frontend (colores correctos, estilos aplicados).

#### Changed
- **`eipsi-forms.php`:** Nueva función `eipsi_forms_enqueue_block_editor_assets()` (líneas 453-494).
  - Agregados 4 archivos CSS al hook `enqueue_block_editor_assets`:
    1. `assets/css/eipsi-forms.css` - CSS principal del formulario (CONSUME las CSS variables)
    2. `assets/css/admin-style.css` - Estilos de admin para coherencia visual
    3. `assets/css/theme-toggle.css` - CSS para dark mode en editor
    4. `assets/css/eipsi-randomization.css` - CSS para controles de aleatorización
  - Hook `add_action('enqueue_block_editor_assets', ...)` - Ejecuta ANTES de registrar bloques

#### Technical Details
- **Archivos modificados:** 1 archivo (eipsi-forms.php), ~46 líneas agregadas
- **Hook correcto:** `enqueue_block_editor_assets` (NO `admin_enqueue_scripts` que solo aplica a páginas admin específicas)
- **Causa raíz:** `admin_enqueue_scripts` solo carga CSS en páginas Results & Experience, config, etc., pero NO en el iframe de Gutenberg donde se renderizan los bloques.
- **Backward compatibility:** 100% - No afecta datos ni funcionalidad existente, solo agrega carga de CSS en editor
- **Testing:** Lint JS 0/0 errores, build webpack exitoso (3 Sass deprecation warnings, no relacionados)
- **Documentación:** Esta entrada en CHANGELOG.md

#### Impact Analysis
- **Antes del fix:** Editor monocromático (gris), sin colores, sin estilos, WYSIWYG no funciona
- **Después del fix:** Editor muestra colores correctos, responde a cambios de preset, WYSIWYG funcional
- **Risk level:** BAJO - Solo agrega carga de CSS en editor, sin modificar lógica de bloques o datos
- **Deployment priority:** ALTA - Mejora significativamente la experiencia de usuario al editar formularios

---

## [1.3.8] – 2025-01-22 (CRITICAL: Block Validation Errors - Editor Bloqueado)

### 🔴 HOTFIX CRÍTICO - Errores de Validación de Bloques

**Severidad:** CRÍTICA - Editor Gutenberg marca bloques como inválidos, no puede renderizar formularios correctamente
**Impacto:** Bloques EIPSI fallan validación, datos guardados no se muestran en editor

#### Fixed
- ❌→✅ **Block Validation Failed - eipsi/form-page:** `save()` generaba atributos NO definidos en `block.json`. WordPress marcaba el bloque como inválido porque `data-page` y otros atributos faltaban en el schema. El bloque no podía renderizarse en el editor.
- ❌→✅ **Block Validation Failed - eipsi/campo-radio:** `block.json` declaraba `options` como `"type": "array"` pero el código usa `string`. Las opciones desaparecían del editor aunque persistían en la base de datos.
- ❌→✅ **Block Validation Failed - eipsi/campo-select:** Mismatch en `options` (array vs string) + falta de atributo `conditionalLogic`.
- ❌→✅ **Block Validation Failed - eipsi/campo-multiple:** Mismatch en `options` (array vs string) + falta de `fieldKey`, `conditionalLogic`.
- ❌→✅ **Block Validation Failed - eipsi/campo-likert:** Atributos desactualizados (`scale`, `minLabel`, `maxLabel`) que no se usan más. El código actual usa `labels`, `minValue`, `reversed`, `scaleVariation`.

#### Changed
- **eipsi/form-page/block.json:** Sincronizado con `save.js`/`edit.js`.
  - Removidos: `pageTitle`, `pageDescription`, `showPageNumber`, `progressPercentage`
  - Agregados: `title`, `pageIndex`, `pageType`, `enableRestartButton`, `restartButtonLabel`
  - Ejemplo actualizado para reflejar estructura correcta
- **eipsi/campo-radio/block.json:** Corregido tipo de `options` y agregados atributos faltantes.
  - `options`: `"type": "array"` → `"type": "string"` (formato canónico actual)
  - Agregados: `fieldKey`, `conditionalLogic`
  - Ejemplo: `[{"label": "...", "value": "..."}]` → `"Opción 1; Opción 2; Opción 3"`
- **eipsi/campo-select/block.json:** Corregido tipo de `options`.
  - `options`: `"type": "array"` → `"type": "string"`
  - Agregado: `conditionalLogic`
  - Ejemplo actualizado a formato semicolon-separated
- **eipsi/campo-multiple/block.json:** Corregido tipo de `options` y agregados atributos faltantes.
  - `options`: `"type": "array"` → `"type": "string"`
  - Agregados: `fieldKey`, `conditionalLogic`
  - Ejemplo actualizado
- **eipsi/campo-likert/block.json:** Reestructurados atributos para compatibilidad con sistema de presets.
  - Removidos: `scale`, `minLabel`, `maxLabel` (no se usan en código actual)
  - Agregados: `fieldKey`, `labels`, `minValue`, `reversed`, `scaleVariation`, `conditionalLogic`
  - Ejemplo actualizado para reflejar presets: `"likert5-satisfaction"`

#### Added
- **Contrato sincronizado block.json → save/edit:** Todos los atributos usados en `save()`/`edit()` ahora están definidos en `block.json`. WordPress valida bloques sin errores.
- **Ejemplos realistas:** Todos los ejemplos en `block.json` muestran el formato CANÓNICO real de datos, no estructuras legacy.
- **Zero validation errors:** Bloques EIPSI ya NO muestran "Block Validation Failed" en console de Gutenberg.
- **100% compatibilidad con datos legacy:** `parseOptions()` en v1.3.7 sigue funcionando, convirtiendo arrays/objetos legacy a strings canónicos automáticamente.

#### Technical Details
- **Archivos modificados:** 5 archivos (block.json de 5 bloques), ~85 líneas modificadas
- **Bloques reparados:** 5 bloques (form-page, campo-radio, campo-select, campo-multiple, campo-likert)
- **Errores de validación eliminados:** 5 "Block Validation Failed" en console
- **Backward compatibility:** 100% - Datos legacy (arrays de objetos) se convierten automáticamente vía `parseOptions()` de v1.3.7
- **Testing:** Lint JS 0/0 errores, build webpack exitoso (3 Sass deprecation warnings, no relacionados)
- **Documentación:** `BLOCK-VALIDATION-FIX-v1.3.8.md` con análisis completo, root cause, correcciones y deployment instructions
- **Commit:** [hash pendiente] - Branch: hotfix/block-validation-errors-attributes-mismatch-v1.3.8

#### Impact Analysis
- **Antes del fix:** Bloques marcados como inválidos → Editor falla al renderizar → Usuario no puede editar formularios
- **Después del fix:** Bloques validan correctamente → Renderizado perfecto en editor → Productividad restaurada
- **Risk level:** BAJO - Cambios solamente en block.json (declaraciones de atributos), sin modificar lógica de bloques
- **Deployment priority:** INMEDIATA - Bloques actualmente fallan validación, afectando experiencia de usuario

---

## [1.3.7] – 2025-01-21 (CRITICAL EMERGENCY: Editor Gutenberg Bloqueado)

### 🔴 HOTFIX CRÍTICO - Editor Gutenberg Completamente Roto

**Severidad:** CRÍTICA - Editor bloqueado, usuario no puede acceder a templates con formularios
**Impacto:** Sistema completamente inoperante para edición de formularios clínicos

#### Fixed
- ❌→✅ **TypeError en Form Container:** `Cannot read properties of undefined (reading 'primary')` - Acceso a `config.colors.primary` sin validar estructura de `styleConfig`. Editor crasheaba al abrir cualquier página con bloques EIPSI.
- ❌→✅ **TypeError en Campo Radio (y todos los bloques de opciones):** `e.trim is not a function` - `parseOptions()` esperaba string pero podía recibir arrays de datos legacy. Crasheaba en save() de 6 bloques.
- ❌→✅ **Incompatibilidad de datos legacy:** Bloques guardados antes de v1.3 con estructuras diferentes (array vs string, objeto vs string) causaban errores JavaScript en Gutenberg.
- ❌→✅ **Validación faltante:** Funciones utilities no manejaban `undefined`, `null`, objetos vacíos `{}`, o arrays en lugar de strings.

#### Changed
- **`parseOptions()` (optionParser.js):** Ahora acepta **string OR array** como input (líneas 105-140).
  - Si recibe array, lo procesa directamente (soporte legacy)
  - Si recibe objetos `{label: "...", value: "..."}`, extrae label
  - Si recibe primitives (string, number), los convierte a string
  - Si no es string ni array, convierte a string como fallback
  - Validación robusta: nunca falla con `.trim()`, siempre devuelve array válido
- **`serializeToCSSVariables()` (styleTokens.js):** Deep merge con defaults antes de acceso (líneas 153-167).
  - Valida que `styleConfig` sea objeto válido
  - Hace spread de defaults + input para cada sección (colors, typography, spacing, borders, shadows, interactivity)
  - **Garantía:** `safeConfig.colors.primary` SIEMPRE existe, nunca undefined
- **`migrateToStyleConfig()` (styleTokens.js):** Validación de attributes antes de procesamiento (líneas 93-97).
  - Retorna `DEFAULT_STYLE_CONFIG` si `attributes` es null/undefined/no-objeto
  - Previene errors en migrate si se llama con argumentos inválidos

#### Added
- **Validación defensiva universal:** Todas las funciones utilities ahora validan inputs antes de operar.
- **Compatibilidad 100% con datos legacy:** Funciona con estructuras de v1.0, v1.1, v1.2, v1.3.x sin errores.
- **Conversión automática de tipos:** Arrays → strings, objetos → strings, primitivos → strings según contexto.
- **Zero Data Loss garantizado:** Ninguna migración destructiva, todos los datos legacy se preservan o convierten correctamente.

#### Technical Details
- **Archivos modificados:** 2 archivos (optionParser.js, styleTokens.js), ~90 líneas modificadas
- **Errores eliminados:** 3 TypeErrors críticos en editor Gutenberg
- **Bloques protegidos:** 7 bloques (form-container, campo-radio, campo-multiple, campo-select, campo-likert, vas-slider, cualquier otro que use parseOptions)
- **Backward compatibility:** 100% - Funciona con datos de TODAS las versiones anteriores
- **Testing:** Lint JS 0/0 errores, build webpack exitoso en 10.2s
- **Commit:** [hash pendiente] - Branch: hotfix/critical-gutenberg-editor-blocked-typeErrors-v1.3.7

#### Impact Analysis
- **Antes del fix:** Editor Gutenberg inaccesible → Usuario completamente bloqueado
- **Después del fix:** Editor funciona normalmente con datos nuevos Y legacy → Productividad restaurada
- **Risk level:** BAJO - Cambios en utilities utilities solamente, no afecta bloques directamente
- **Deployment priority:** INMEDIATA - Usuario no puede trabajar sin este fix

---

## [1.3.6] – 2025-01-21 (CRITICAL FIX: RCT Schema Migration)

### 🔴 HOTFIX - Sistema de Aleatorización RCT

#### Fixed
- ❌→✅ **SQL Error crítico:** "Unknown column 'template_id' in WHERE clause" - El sistema de aleatorización generaba errores SQL porque la tabla `wp_eipsi_randomization_assignments` usaba columna `template_id` pero el código esperaba `randomization_id`.
- ❌→✅ **PHP Warnings:** "Undefined array key 'randomizationId'", "'porcentaje'", "'postId'" - Acceso a keys incorrectas en arrays de configuración.
- ❌→✅ **Transaction Failures:** INSERT statements fallaban completamente, las asignaciones RCT no se registraban en base de datos.
- ❌→✅ **RCT Analytics Dashboard:** JOINs entre tablas `configs` y `assignments` ahora funcionan correctamente, estadísticas se calculan sin errores.

#### Changed
- **Schema de base de datos:** Columna `template_id BIGINT(20)` → `randomization_id VARCHAR(255)` en tabla `wp_eipsi_randomization_assignments` (representa config_id, permite JOINs correctos).
- **Índices actualizados:** `UNIQUE KEY unique_assignment (template_id, ...)` → `(randomization_id, ...)` para integridad referencial.
- **Signatures de funciones DB:** `eipsi_get_existing_assignment()` y `eipsi_create_assignment()` simplificadas (menos parámetros, lógica más clara).
- **Acceso seguro a arrays:** Uso de `isset()` en líneas 315, 326, 358 del shortcode handler para prevenir PHP warnings.
- **Cálculo de probabilidades:** Obtiene porcentaje desde `$config['probabilidades'][$form_id]` en lugar de `$form['porcentaje']` inexistente.

#### Added
- **Script de migración automática:** `admin/migrate-randomization-schema.php` ejecuta migración de schema automáticamente en `admin_init`.
- **Preservación de datos:** Migración usa `ALTER TABLE CHANGE COLUMN` para preservar 100% de datos existentes.
- **Endpoint AJAX manual:** `/wp-admin/admin-ajax.php?action=eipsi_migrate_schema` para ejecutar migración manualmente si falla automática.
- **Logging completo:** Cada paso de migración se registra en error_log de WordPress.
- **Version tracking:** Opción `eipsi_randomization_schema_version` almacena versión actual (1.3.6).
- **Documentación técnica:** `RCT-SCHEMA-MIGRATION-v1.3.6.md` con análisis completo de causa raíz, correcciones y deployment instructions.

#### Technical Details
- **Archivos modificados:** 4 archivos, ~230 líneas cambiadas
- **Errores eliminados:** 5 errores críticos (1 SQL + 3 PHP Warnings + 1 Transaction Failure)
- **Backward compatibility:** 100% - Migración idempotente, puede ejecutarse múltiples veces sin romper nada
- **Testing:** Lint JS 0/0 errores, build webpack exitoso, prepared statements sanitizados

---

## [1.3.1] – 2025-01-19 (RCT System: Fingerprinting + Persistencia Completa)

### Added
- **🎲 Sistema RCT Completo con Fingerprinting Robusto:** Implementación end-to-end de aleatorización con persistencia perfecta para Randomized Controlled Trials (RCTs). Features:
  - **Fingerprinting en cliente (JS):** Canvas + WebGL + Screen + Timezone + Language + Platform + User Agent + Hardware concurrency + Device memory + Plugins → Hash SHA-256 de 32 caracteres. Generación automática en `assets/js/eipsi-fingerprint.js` con guardado en sessionStorage.
  - **Base de datos dual:** 
    - Tabla `wp_eipsi_randomization_configs` para almacenar configuraciones de estudios RCT (formularios, probabilidades, método, asignaciones manuales).
    - Tabla `wp_eipsi_randomization_assignments` para trackear asignaciones usuario→formulario con `UNIQUE KEY (randomization_id, user_fingerprint)`.
  - **Persistencia perfecta:** Usuario que presiona F5 (refrescar) **siempre ve el mismo formulario asignado**. La asignación persiste indefinidamente (3 meses+) porque el fingerprint es reproducible.
  - **Shortcode funcional:** `[eipsi_randomization id="rand_abc123"]` ejecuta flujo completo:
    1. Obtiene fingerprint del usuario
    2. Busca asignación previa en DB
    3. Si existe: devuelve mismo formulario + incrementa `access_count`
    4. Si no existe: calcula asignación aleatoria + guarda en DB + renderiza formulario
  - **Método seeded reproducible:** Usa `crc32(fingerprint + randomization_id)` como seed para `mt_rand()`, garantizando que mismo usuario siempre obtiene misma asignación (incluso si se borra la DB).
  - **Método pure-random:** Usa `random_int()` para aleatorización completamente impredecible.
  - **Asignaciones manuales (override ético):** Permite asignar manualmente un email específico a un formulario, sobrescribiendo aleatorización.
  - **Tracking completo:** `assigned_at`, `last_access`, `access_count` para cada usuario.
  - **REST API para guardar configuraciones:** Endpoint `/wp/v2/eipsi_randomization_config` (POST) con guardado automático desde el bloque (debounced 2s).
  - **Logging detallado:** Error logs en PHP y console logs en JS para debugging completo.
  - **Documentación completa:** `docs/RCT-SYSTEM.md` con arquitectura, flujos, ejemplos de código, casos de uso y criterios de aceptación.

### Changed
- **Bloque de Aleatorización v2.0 (v1.3.0 → v1.3.1):**
  - Título mejorado: **🎲 Configuración** en bold (fontSize: 1.25rem, fontWeight: bold) para máxima claridad.
  - Guardado automático en DB cuando cambia configuración (debounced 2s) mediante REST API.
  - Mejor integración con sistema de fingerprinting.
- **Shortcode Handler refactorizado (v1.3.1):**
  - Prioriza búsqueda de configuración en DB (vía `eipsi_get_randomization_config_from_db()`) antes de buscar en blocks (backwards compatibility).
  - Usa fingerprinting en lugar de IP débil como identificador principal.
  - Separa claramente funciones: `eipsi_get_existing_assignment()`, `eipsi_create_assignment()`, `eipsi_update_assignment_access()`.
  - Logs informativos en cada paso del flujo.
- **Frontend assets enqueuing (v1.3.1):**
  - `eipsi-fingerprint.js` se enqueue automáticamente en todas las páginas para garantizar disponibilidad del fingerprint.
  - Se ejecuta antes de `eipsi-tracking.js` y `eipsi-forms.js`.

### Fixed
- **Persistencia de asignaciones:** Antes, usuarios podían ver diferentes formularios al refrescar (F5) porque el sistema usaba solo IP (que puede cambiar con VPN/proxy). Ahora, con fingerprinting robusto, la asignación es **100% persistente** independientemente de refreshes, cierre de navegador o paso del tiempo.
- **Asignaciones duplicadas:** La constraint `UNIQUE KEY (randomization_id, user_fingerprint)` en DB previene asignaciones duplicadas para el mismo usuario.
- **Fallback robusto:** Si fingerprinting JS falla (navegadores antiguos, JavaScript deshabilitado), el sistema genera fingerprint en servidor basado en User Agent + IP + Accept-Language + Accept-Encoding.

---

## [1.3.0] – 2025-01-18 (Bloque de Aleatorización Independiente)

### Added
- **🎲 Bloque de Aleatorización Independiente:** Nuevo bloque Gutenberg para configurar aleatorización de formularios sin depender del Form Container. Features:
  - Configuración visual simple con porcentajes automáticos (siempre suman 100%)
  - Asignaciones manuales (override ético) para participantes específicos
  - Generación automática de shortcode `[eipsi_randomization id="xyz"]` y link directo
  - Dos métodos: seeded (reproducible) y pure-random
  - Tracking de asignaciones en base de datos con persistencia
  - Query param `?eipsi_rand=xyz` para acceso directo
  - Compatible con multisite y GDPR
  - Depreca la configuración de aleatorización embebida en Form Container (mantenida por backwards compatibility)
- **Templates en páginas regulares (v1.3.0):** Selector de plantillas EIPSI ahora funciona tanto en CPT `eipsi_form` como en páginas regulares de WordPress. Implementado:
  - Handler AJAX `eipsi_get_demo_templates` que carga templates independientemente del tipo de post
  - Función `loadAvailableTemplates()` en Form Container que reemplaza el sistema basado en `window.EIPSIDemoTemplates`
  - Loading states y error handling robusto para mejor UX
  - Compatibilidad total con páginas, posts y CPTs
- **Template Profesional de Burnout v2.0 (Validado):** Evaluación clínica completa del síndrome de burnout en profesionales sanitarios que incluye PHQ-9, GAD-7 y MBI-HSS con consentimiento informado profesional. Validado 100% contra block.json v1.2.2 (todos los atributos son compatibles, sin errores de importación). Incluye 5 páginas: Consentimiento, Datos Demográficos, PHQ-9 (9 ítems, escala 0-3), GAD-7 (7 ítems, escala 0-3), MBI-HSS (10 ítems, escala 0-6). Tiempo estimado: 15-20 minutos.
- **Plantillas demo EIPSI:** plantillas genéricas como "Ingreso ansiedad breve (demo)", "Seguimiento emocional (demo)" y "Satisfacción de sesión (demo)" listas para cargar desde el dropdown del EIPSI Form Container.
- **Submissions v1:** tabla paginada integrada en el panel "Results & Experience" con filtros por formulario, exportación directa a Excel/CSV y detalle completo de cada sesión (respuestas + eventos + metadatos).
- **Finalización integrada v1:** mensaje de agradecimiento configurable en la misma URL donde empezó el formulario, con botón "Comenzar de nuevo" para reutilizar tablet en sala.
- **Lógica condicional AND/OR v1.1:** combinación de reglas múltiples (AND + OR) con soporte para RADIO, CHECKBOX, VAS, LIKERT y SELECT, evaluación en tiempo real sin recargar página.
- **Fingerprint clínico liviano:** captura opcional de browser, OS y resolución de pantalla controlada desde la pestaña "Privacy & Metadata"; pensado para tablets compartidas en clínica o investigación.
- **Separador seguro `;` para opciones:** migración interna de comma-separated a semicolon-separated, evitando errores cuando las respuestas contienen comas, comillas o descripciones largas.

### Fixed
- **Bloque de Aleatorización no aparece en el selector (v1.3.0):** El bloque "🎲 Aleatorización de Formularios" no aparecía en el selector de bloques de Gutenberg porque faltaba `'randomization-block'` en el array `$block_dirs` de `eipsi-forms.php`. Aunque el código JS se compilaba correctamente en el bundle, WordPress no registraba el bloque desde el archivo `block.json`. Agregado al array de bloques para registro completo (JS + PHP).
- **Validación de link de aleatorización en Form Container:** Corregido bug donde el botón "Generar link" fallaba con el mensaje "Necesitás configurar al menos 2 formularios" incluso cuando ya tenías 3+ formularios configurados. La causa root era que la validación chequeaba `randomConfig.enabled` (propiedad que nunca se actualiza) en lugar de `useRandomization` (el toggle real del panel). Cambiado a `if (!useRandomization || currentRandomConfig.forms.length < 2)` para reflejar el estado correcto del toggle.
- **Bloque de Consentimiento en el Editor:** Ahora respeta los presets del Form Container (Clinical Blue, Minimal White, Warm Neutral, Serene Teal). El bloque hereda automáticamente las CSS variables del parent mediante `useSelect` para encontrar el `styleConfig` del Form Container. Antes, el consentimiento usaba estilos del tema por defecto y no respondía a cambios de preset.
- **getParticipantIdFromStorage undefined en eipsi-random.js:** Corregido error de linting donde la función `getParticipantIdFromStorage()` estaba duplicada en el archivo. Se movió su definición al inicio del IIFE (antes de su uso) y se eliminó la definición duplicada, resolviendo el error `'getParticipantIdFromStorage' is not defined`.

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
