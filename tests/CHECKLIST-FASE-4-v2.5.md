# Checklist de Verificación - Fase 4
## Consentimiento Informado v2.5

---

## ✅ BASE DE DATOS

### Tablas Modificadas
- [ ] `wp_survey_participants` tiene campos consent:
  - [ ] `consent_decision` (VARCHAR 20)
  - [ ] `consent_decided_at` (DATETIME)
  - [ ] `consent_ip_address` (VARCHAR 45)
  - [ ] `consent_user_agent` (TEXT)
  - [ ] `consent_context` (VARCHAR 50)
  - [ ] `consent_blocked_survey_id` (VARCHAR 50)
  - [ ] `withdrawal_wave_id` (VARCHAR 50)
  - [ ] `data_deleted` (TINYINT 1, default 0)

- [ ] `wp_survey_assignments` tiene campos consent:
  - [ ] `consent_decision` (VARCHAR 20)
  - [ ] `consent_decided_at` (DATETIME)
  - [ ] `consent_ip_address` (VARCHAR 45)
  - [ ] `consent_user_agent` (TEXT)
  - [ ] `consent_context` (VARCHAR 50)

### Verificar Migration
```sql
-- Verificar columnas existentes
SHOW COLUMNS FROM wp_survey_participants LIKE 'consent%';
SHOW COLUMNS FROM wp_survey_assignments LIKE 'consent%';
```

---

## ✅ FASE 1 - FRONTEND T1 (2 Botones + Checkbox)

### UI Componentes
- [ ] Consent block muestra 2 botones:
  - [ ] "No deseo participar" (rojo outline #dc2626)
  - [ ] "Acepto participar" (verde solid #16a34a, disabled hasta checkbox)
- [ ] Checkbox "He leído y comprendo..." visible y funcional
- [ ] Botón verde se habilita SOLO cuando checkbox está checked
- [ ] Estilos aplican en light y dark mode

### JavaScript Validación
- [ ] Click en "No deseo participar" → AJAX a `eipsi_save_consent_decision`
- [ ] Click en "Acepto participar" (con checkbox) → AJAX a `eipsi_save_consent_decision`
- [ ] Sin checkbox marcado → Botón deshabilitado (visualmente gris)
- [ ] Mensajes de error visibles si falla AJAX

### Gutenberg Preview
- [ ] Editor muestra preview con botones disabled y checkbox
- [ ] Settings panel muestra campos configurables:
  - [ ] Texto botón "Acepto"
  - [ ] Texto botón "No deseo"
  - [ ] Label checkbox confirmación

---

## ✅ FASE 2 - BACKEND T1

### Endpoint AJAX: `eipsi_save_consent_decision`
- [ ] Nonce verification funciona
- [ ] Parámetros requeridos validados:
  - [ ] `participant_id`
  - [ ] `form_id`
  - [ ] `decision` ('accepted'|'declined')
  - [ ] `nonce`
- [ ] Guarda en DB longitudinal (survey_participants) cuando aplica
- [ ] Guarda en DB standalone (survey_assignments) cuando aplica
- [ ] IP address se captura correctamente
- [ ] User agent se captura correctamente
- [ ] Fecha actual se guarda en `consent_decided_at`
- [ ] Audit log se genera

### Respuesta JSON
- [ ] Éxito: `{ success: true, decision, redirect }`
- [ ] Error: `{ success: false, message }`
- [ ] Si `decision: 'declined'` → `redirect: '/consentimiento-rechazado'`

### Pantalla Bloqueo T1: `/consentimiento-rechazado`
- [ ] Template carga correctamente
- [ ] Muestra cruz roja grande ❌
- [ ] Mensaje claro de rechazo
- [ ] Info de contacto del investigador
- [ ] No permite volver al formulario
- [ ] Estilos responsive

### Funciones Helper
- [ ] `eipsi_get_client_ip()` retorna IP válida
- [ ] `eipsi_get_current_participant_id()` funciona
- [ ] `eipsi_get_study_id_for_form()` retorna study_id correcto
- [ ] `eipsi_check_consent_blocked()` detecta participantes bloqueados
- [ ] `eipsi_log_audit()` registra eventos

---

## ✅ FASE 3 - DASHBOARD T2+ (Abandonos)

### UI Dashboard
- [ ] Icono ✕ visible en footer del dashboard
- [ ] Icono tiene tooltip/title "Abandonar estudio"
- [ ] Hover: cambio de color a rojo
- [ ] Click abre modal principal

### Modal Principal (Selección B1/B2)
- [ ] Se muestra con animación suave
- [ ] 2 opciones clickeables:
  - [ ] B1: "Quiero dejar de participar"
  - [ ] B2: "Quiero eliminar todos mis datos"
- [ ] Solo una opción puede estar seleccionada
- [ ] Botón "Continuar" deshabilitado hasta seleccionar
- [ ] Botón "Cancelar" cierra modal

### Modal B1 (Abandono Estándar)
- [ ] Se abre al continuar con B1 seleccionado
- [ ] Muestra consecuencias del abandono
- [ ] Lista de qué sucede:
  - [ ] No más contactos para futuras olas
  - [ ] Datos conservados para análisis
  - [ ] Pérdida de acceso al dashboard
- [ ] Botón "Sí, quiero abandonar"
- [ ] Botón "Volver" al modal anterior

### Modal B2 (Eliminación Datos - Alta Fricción)
- [ ] Se abre al continuar con B2 seleccionado
- [ ] Warning prominente: "⚠️ Esta acción NO se puede deshacer"
- [ ] Lista consecuencias:
  - [ ] Eliminación permanente de respuestas
  - [ ] NO forman parte del análisis
  - [ ] No recuperable
- [ ] Campo de verificación visible con texto exacto:
  ```
  NO QUIERO QUE MIS RESPUESTAS FORMEN PARTE DEL ANÁLISIS
  ```
- [ ] Input valida caracter por caracter
- [ ] Botón "Eliminar" deshabilitado hasta coincidir exacto
- [ ] Error visual si texto no coincide
- [ ] Botón "Cancelar" disponible

### Endpoint AJAX: `eipsi_abandon_study`
- [ ] Nonce verification: `eipsi_abandon_study`
- [ ] Parámetros validados:
  - [ ] `participant_id`
  - [ ] `study_id`
  - [ ] `withdrawal_type` ('b1'|'b2')
  - [ ] `verification_text` (requerido para B2)
- [ ] B2: Verificación textual exacta (case-sensitive)
- [ ] Actualiza `wp_survey_participants`:
  - [ ] `consent_decision = 'withdrawn'`
  - [ ] `consent_context = 'T2A_withdrawal'` o `'T2B_data_deletion'`
  - [ ] `withdrawal_wave_id` = ola actual
  - [ ] `data_deleted = 1` (solo B2)
  - [ ] `status = 'withdrawn'`
- [ ] B2: Anonimiza submissions existentes
- [ ] B2: Borra partial responses
- [ ] Audit log con tipo de abandono

### Respuesta JSON
- [ ] Éxito B1: `{ success: true, redirect_url: '/abandono-confirmado' }`
- [ ] Éxito B2: `{ success: true, redirect_url: '/abandono-datos-eliminados' }`
- [ ] Error B2 (verificación): `{ success: false, code: 'verification_failed' }`

### Pantallas Post-Abandono

#### B1: `/abandono-confirmado`
- [ ] Template carga
- [ ] Icono 👋 (amigable)
- [ ] Mensaje confirmación abandono
- [ ] Lista consecuencias claras
- [ ] Botón "Volver al inicio"
- [ ] Link contacto investigador
- [ ] Estilos responsive

#### B2: `/abandono-datos-eliminados`
- [ ] Template carga
- [ ] Icono 🗑️ (rojo)
- [ ] Fondo gradiente rojo
- [ ] Warning prominente de irreversibilidad
- [ ] Lista qué se eliminó
- [ ] Confirmación derecho al olvido respetado
- [ ] Botón "Volver al inicio"
- [ ] Link contacto investigador
- [ ] Estilos responsive

---

## ✅ VALIDACIÓN DE ACCESO (Bloqueo)

### Lógica de Bloqueo
- [ ] Participante con `consent_decision = 'declined'` → Bloqueado
- [ ] Participante con `consent_decision = 'withdrawn'` → Bloqueado
- [ ] Participante con `status = 'consent_declined'` → Bloqueado
- [ ] Participante con `status = 'withdrawn'` → Bloqueado
- [ ] Redirección a URL correcta según tipo de bloqueo

### Test Cases
```
T1 Rechazo:  consent_decision='declined' → /consentimiento-rechazado
T2A Abandono: consent_decision='withdrawn' + context='T2A_withdrawal' → /abandono-confirmado
T2B Eliminación: consent_decision='withdrawn' + data_deleted=1 → /abandono-datos-eliminados
```

---

## ✅ SEGURIDAD Y PRIVACIDAD

### Nonces
- [ ] Todos los endpoints AJAX verifican nonce
- [ ] Nonces diferentes para cada acción:
  - [ ] `eipsi_forms_nonce` (consent T1)
  - [ ] `eipsi_abandon_study` (abandono T2)

### Sanitización
- [ ] `sanitize_text_field()` en todos los inputs
- [ ] `intval()` en IDs numéricos
- [ ] `wp_kses_post()` si hay contenido HTML

### Rate Limiting (si aplica)
- [ ] Límite de intentos en endpoints sensibles
- [ ] Logging de intentos fallidos

### GDPR / Protección Datos
- [ ] B2: Eliminación real de datos personales
- [ ] B2: Anonimización de participant_id en submissions
- [ ] B2: Borrado de partial responses
- [ ] IP address solo para audit (no identificación)

---

## ✅ ESTILOS Y UX

### Responsive
- [ ] Móvil (< 640px): Modales en slide-up
- [ ] Tablet (640-1024px): Layout adaptativo
- [ ] Desktop (> 1024px): Layout completo

### Dark Mode
- [ ] Detecta `prefers-color-scheme: dark`
- [ ] Todos los templates tienen variantes dark
- [ ] Contraste accesible (WCAG AA)

### Accesibilidad
- [ ] ARIA labels en botones icono
- [ ] Focus management en modales
- [ ] Escape key cierra modales
- [ ] Tab navigation funciona
- [ ] Screen reader compatible

---

## ✅ INTEGRACIÓN GENERAL

### WordPress
- [ ] Templates registrados correctamente
- [ ] Slugs funcionan:
  - [ ] `/consentimiento-rechazado`
  - [ ] `/abandono-confirmado`
  - [ ] `/abandono-datos-eliminados`
- [ ] No conflictos con otros plugins
- [ ] Multisite compatible (si aplica)

### Base de Datos
- [ ] Migración ejecuta sin errores
- [ ] Columnas no se duplican en re-runs
- [ ] Índices preservados

### Performance
- [ ] AJAX responses < 500ms
- [ ] No N+1 queries
- [ ] JavaScript no bloqueante

---

## 📋 COMANDOS DE TEST

```bash
# Verificar columnas DB
wp db query "SHOW COLUMNS FROM wp_survey_participants LIKE 'consent%'"

# Verificar hooks AJAX
grep -r "add_action.*wp_ajax.*eipsi" admin/

# Verificar templates existen
ls -la templates/consent-declined.php
ls -la templates/withdrawal-*.php
ls -la includes/templates/withdrawal-modals.php
```

---

## 🎯 CRITERIOS DE ACEPTACIÓN

- [ ] Todos los checks arriba están ✅
- [ ] Flujo T1 completo funciona end-to-end
- [ ] Flujo T2A completo funciona end-to-end
- [ ] Flujo T2B completo funciona end-to-end
- [ ] No hay errores en consola JS
- [ ] No hay errores en logs PHP
- [ ] Responsive testeado en móvil real
- [ ] Dark mode testeado

---

**Estado:** En progreso  
**Última actualización:** Fase 4 - Testing  
**Versión:** 2.5.0
