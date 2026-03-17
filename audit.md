# Audit de código — EIPSI Forms (vs objetivos de `README.md`)

Fecha: 2026-03-17  
Repo: `EIPSI-Forms-Plugin`  
Versión documentada en `README.md`: **2.1.0**  
Versión en `eipsi-forms.php`: **2.0.0**  
Versión en `package.json`: **1.5.5**

> Este documento revisa el código “tal como está” y lo contrasta contra los objetivos/epics y claims del plugin en `README.md` (Longitudinal, RCT, Pools, Email, Monitoring, GDPR/Privacidad, Export, Blocks, Conditional Logic, Save & Continue).

---

## Alcance revisado

- **Entrada / bootstrap**: `eipsi-forms.php`
- **Shortcodes & frontend gates**: `includes/shortcodes.php`, `includes/class-survey-access-handler.php`
- **AJAX handlers**: `admin/ajax-handlers.php` (+ endpoints RCT en `admin/randomization-api.php`)
- **Servicios críticos**:
  - Auth/sesiones: `admin/services/class-auth-service.php`
  - Emails: `admin/services/class-email-service.php`
  - Export: `admin/services/class-export-service.php`
  - DB externa: `admin/database.php`

---

## Resumen ejecutivo (1 pantalla)

El plugin está **bien encaminado** para el objetivo clínico (longitudinal + magic links + email automation + monitoring + export) y tiene señales claras de hardening (uso de `prepared`, nonces en muchos endpoints, capas de servicio, privacy toggles).

Los principales riesgos actuales, mirando “objetivos vs implementación”, son:

- **Seguridad/CSRF + abuso de endpoints públicos**: hay endpoints `nopriv` sensibles (en particular **Save & Continue**) que no usan nonce.
- **Consistencia de sesión/auth**: se mezcla `EIPSI_Auth_Service` (cookie+DB) con `$_SESSION`/`session_start()` en lugares críticos.
- **Inconsistencia de versionado** (README vs header WP vs npm): complica soporte/auditoría, y es un riesgo operativo para “investigación clínica”.
- **Performance**: hay señales de enqueue global (carga de assets en todas las páginas) que puede impactar UX y Core Web Vitals.

---

## Mapeo rápido: objetivos del README → evidencias en código

- **Longitudinal Studies** (waves, magic links, dashboard participante, wizard):
  - `includes/shortcodes.php` implementa `[eipsi_longitudinal_study]`, `[eipsi_survey_login]`, `[eipsi_participant_dashboard]`.
  - `includes/class-survey-access-handler.php` implementa endpoint `/survey-access/?ml=TOKEN` y confirmación `/?eipsi_confirm=TOKEN`.
  - `admin/services/class-email-service.php` genera magic links y manda emails.

- **RCT / Randomization**:
  - Randomización y dashboard: `admin/ajax-handlers.php` (assign + public randomization) y `admin/randomization-api.php` (dashboard configs/stats).

- **Longitudinal Pools**:
  - Se ve bootstrap en `eipsi-forms.php` (services + APIs), pero no fue auditado en profundidad en este documento.

- **Email Service**:
  - `admin/services/class-email-service.php` (templates, placeholders, logging).

- **Monitoring Dashboard**:
  - Handlers con `manage_options` y nonce en `admin/ajax-handlers.php`.

- **Security & Privacy**:
  - Toggling de campos (IP, browser, etc.) en `admin/ajax-handlers.php` al persistir envíos.
  - Sesiones: `admin/services/class-auth-service.php` (cookie HttpOnly, SameSite, DB sessions).

- **Export System**:
  - `admin/services/class-export-service.php` export longitudinal + roster.

- **Blocks / Gutenberg**:
  - `eipsi-forms.php` registra bloques desde `build/blocks/*/block.json`.

---

## Hallazgos (priorizados)

### CRITICAL

1) **Save & Continue (nopriv) sin nonce → superficie CSRF + spam/DoS de tabla**
- Evidencia: `admin/ajax-handlers.php` declara:
  - `wp_ajax_nopriv_eipsi_save_partial_response`
  - y el handler **explicitamente** dice “No check_ajax_referer…” y no valida autenticación (`eipsi_save_partial_response_handler`).
- Impacto:
  - Un tercero puede forzar requests desde cualquier sitio (CSRF) y **escribir** drafts arbitrarios o inflar almacenamiento (DoS lógico), afectando integridad/retención de datos.
- Recomendación:
  - Requerir **nonce** siempre (aunque “haya problemas de conexión”; si falla la conexión, igual no vas a poder persistir), o usar un **token de sesión propio** (p.ej. `session_id` firmado/HMAC + exp).
  - Agregar rate limiting (por IP / session_id) y tamaño máximo de payload.

2) **Mezcla de `$_SESSION` con sesión propia (cookie+DB)**
- Evidencia:
  - `admin/ajax-handlers.php` en `eipsi_forms_submit_form_handler()` hace `session_start()` y luego lee `$_SESSION['eipsi_wave_id']`.
  - `EIPSI_Auth_Service` maneja sesiones con cookie (`EIPSI_SESSION_COOKIE_NAME`) + tabla `wp_survey_sessions`.
  - `includes/class-survey-access-handler.php` fue corregido para usar `EIPSI_Auth_Service::create_session()`, pero conserva fallback a `$_SESSION`.
- Impacto:
  - Estados divergentes: un usuario puede “estar logueado” para un flujo pero no para otro.
  - Dificulta auditoría y reproduce bugs intermitentes (especialmente con cachés/CDNs).
- Recomendación:
  - Definir **una sola fuente de verdad** de sesión (ideal: cookie+DB de `EIPSI_Auth_Service`) y migrar wave context a DB (o al token) en lugar de `$_SESSION`.

### HIGH

3) **Nonce “admin” usado en endpoints públicos de aleatorización**
- Evidencia:
  - `admin/ajax-handlers.php` registra `wp_ajax_nopriv_eipsi_get_randomization_config` y dentro valida con `wp_verify_nonce(..., 'eipsi_admin_nonce')`.
- Problema:
  - `eipsi_admin_nonce` semánticamente es de admin. Si se expone en frontend para que funcione, se vuelve un “nonce global” poco expresivo y fácil de reutilizar indebidamente.
- Recomendación:
  - Separar nonces: `eipsi_public_randomization_nonce`, `eipsi_public_completion_nonce`, etc., con scopes claros.

4) **Versionado inconsistente (operacional/auditable)**
- Evidencia:
  - `README.md` indica versión actual 2.1.0.
  - `eipsi-forms.php` declara `Version: 2.0.0` y `EIPSI_FORMS_VERSION` 2.0.0.
  - `package.json` marca 1.5.5.
- Impacto:
  - Confusión en soporte, reproducciones y auditorías (clave en investigación clínica).
  - Assets cacheados por `EIPSI_FORMS_VERSION` pueden quedar desalineados con releases.
- Recomendación:
  - Unificar fuente de versión (p.ej. constant desde header) y sincronizar README/changelog/npm.

### MEDIUM

5) **Performance: carga de assets en todas las páginas**
- Evidencia:
  - En `eipsi-forms.php` hay un hook que llama `eipsi_forms_enqueue_frontend_assets()` en cada page load (`wp_enqueue_scripts` anónimo).
- Impacto:
  - Scripts de fingerprinting/tracking/dark mode se cargan incluso en páginas que no usan EIPSI → penaliza rendimiento y posibles conflictos JS/CSS.
- Recomendación:
  - Condicionar enqueue a presencia real de bloque/shortcode (ya existe lógica similar en varias funciones; consolidarla).

6) **Export: joins/column naming sugieren drift de schema**
- Evidencia:
  - En `admin/services/class-export-service.php` aparece `JOIN ... survey_waves sw ON sp.survey_id = sw.study_id` y `survey_responses` (que no aparece en el esquema de README, donde se menciona `wp_vas_form_results`, `wp_survey_*`, etc.).
- Impacto:
  - Riesgo de export incompleto/incorrecto según instalación/migraciones.
- Recomendación:
  - Documentar y/o consolidar el “source of truth” de tablas (vas_form_results vs survey_responses).
  - Agregar verificación de schema y fallback de queries (como ya hacen en otras partes).

7) **Datos sensibles en logs de servidor**
- Evidencia:
  - `class-email-service.php` y otros sectores usan `error_log(...)` con datos contextuales (p.ej. IDs, survey_id).
- Impacto:
  - En entornos clínicos, logs pueden terminar en sistemas externos. Aunque no se vio logging directo de PII en lo leído, la superficie está.
- Recomendación:
  - Estandarizar logging con niveles y sanitización estricta (sin emails, sin tokens, sin IP).

### LOW

8) **`SECURITY.md` es plantilla genérica**
- Evidencia:
  - `SECURITY.md` no está adaptado al proyecto.
- Impacto:
  - No hay canal/proceso claro de reporte, ni version policy real.
- Recomendación:
  - Completar `SECURITY.md` con proceso, SLAs y scope.

---

## Recomendaciones por objetivo (acciones concretas)

### Security & Privacy (README)
- **Bloquear CSRF en Save & Continue** (ver CRITICAL #1).
- **Unificar sesión/auth** (ver CRITICAL #2).
- **Scopes de nonce**: separar admin/public y por feature (ver HIGH #3).
- **Rate limiting** para endpoints públicos (submit, track, partials).
- **Límites de payload** en drafts y tracking (`max bytes`, `max keys`, `max depth`).

### Longitudinal / Magic Links
- Consolidar wave context en el sistema de sesión propio (cookie+DB), no `$_SESSION`.
- Revisar TTLs declarados en README vs implementación (README menciona 30 min magic link; hay mensajes/flows que mencionan 48h).

### RCT / Randomization
- Nonces públicos dedicados.
- Revisar si `current_user_can('edit_posts')` en asignación es intencional (define quién puede operar overrides/admin asignación).

### Export System
- Validar que las tablas y nombres (`survey_responses`, `vas_form_results`, etc.) sean consistentes con el schema real y con el README.
- Agregar “self-check” del export: si una tabla no existe, devolver error explícito en UI con guía de reparación.

### Gutenberg Blocks / UX
- Evitar enqueue global y cargar assets solo cuando corresponda.
- Unificar duplicación de lógica de enqueue entre `eipsi-forms.php` y `includes/shortcodes.php`.

---

## Checklist sugerido (para cerrar brechas rápido)

1) [CRITICAL] Poner nonce + rate limit en Save & Continue (y revisar `nopriv`).
2) [CRITICAL] Eliminar dependencia de `$_SESSION` en flujos longitudinales (migrar wave_id a DB o cookie/session table).
3) [HIGH] Crear nonces por feature (admin vs public) y reemplazar `eipsi_admin_nonce` en endpoints públicos.
4) [HIGH] Unificar versionado (README, header, `EIPSI_FORMS_VERSION`, npm).
5) [MEDIUM] Condicionar enqueue de assets (no global).
6) [MEDIUM] Auditar/exportar contra schema real (drift tables).

---

## Notas finales

- Este audit está basado en lectura de archivos clave y señales del repo (endpoints, servicios y flujos principales). Para una auditoría “completa” de seguridad clínica (IRB/GDPR), faltaría revisar: retención/anonymize, data request portal, pools assignment, y todos los handlers de export + emails + cron, con trazabilidad de datos (PII) extremo a extremo.

