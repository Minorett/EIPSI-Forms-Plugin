# EIPSI Forms - Professional Form Builder for Clinical Research

> **Plugin multipágina diseñado para psicólogxs y psiquiatras hispanohablantes.**
> 
> «Por fin alguien entendió cómo trabajo de verdad con mis pacientes»

[![WordPress](https://img.shields.io/badge/WordPress-5.8%2B-blue.svg)](https://wordpress.org/)
[![PHP](https://img.shields.io/badge/PHP-7.4%2B-purple.svg)](https://php.net/)
[![License](https://img.shields.io/badge/License-GPL%20v2%2B-green.svg)](https://www.gnu.org/licenses/gpl-2.0.html)
[![WCAG](https://img.shields.io/badge/WCAG-2.1%20AA-brightgreen.svg)](https://www.w3.org/WAI/WCAG21/quickref/)

---

## 📋 Versión Actual: 2.1.0

**Compatibilidad:** WordPress 5.8+ | PHP 7.4+ | Tested up to WP 6.7

**Instalación:** [Download ZIP](https://github.com/eipsi/eipsi-forms/archive/refs/heads/main.zip) → Upload to `/wp-content/plugins/` → Activate

---

## 🚀 Quick Start

### Instalación Rápida

```bash
# Clonar o descargar el plugin
cd /wp-content/plugins/
git clone https://github.com/eipsi/eipsi-forms.git
cd eipsi-forms

# Instalar dependencias y compilar
npm install
npm run build
```

### Crear tu primer formulario

1. **Ir a WordPress Admin** → Posts/Pages → Add New
2. **Agregar bloque "EIPSI Form Container"** desde el editor Gutenberg
3. **Dentro del container**, agregar bloques de campos:
   - `Campo Texto`, `Campo Textarea`, `Campo Radio`, `Campo Select`
   - `Campo Likert`, `VAS Slider` (escalas visuales analógicas)
   - `Consent Block` (consentimiento informado)
4. **Publicar** y el formulario está listo para recibir respuestas

### Ver resultados

- **Admin** → EIPSI Forms → Results & Experience → Submissions
- Exportar a Excel (.xlsx) o CSV con un click

---

## ✨ Features Principales

### 📊 Longitudinal Studies (Estudios Longitudinales)

Sistema completo para estudios con múltiples tomas (T1, T2, T3...) con seguimiento de participantes.

**Características:**
- **Multiple Waves:** Configura hasta N tomas con intervalos personalizables
- **Magic Links:** Links seguros con TTL 30 min, one-time use, auto-login
- **Participant Management:** CRUD completo, importación CSV, estados activo/inactivo
- **Email Automation:** Bienvenida, recordatorios por wave, confirmaciones, recuperación de dropouts
- **Session Management:** Sesiones de 7 días, cookies seguras (HttpOnly, Secure, SameSite)
- **Wave Completion Tracking:** Seguimiento de progreso por participante y wave
- **Setup Wizard:** Crea estudios en 3 pasos con templates pre-configurados

**Shortcodes:**
```
[eipsi_survey_login]           // Portal de login para participantes
[eipsi_participant_dashboard]  // Dashboard personal del participante
[eipsi_longitudinal_study id="123"]  // Formulario de estudio específico
```

---

### 🎲 Randomized Controlled Trials (RCT)

Sistema de aleatorización completo para ensayos clínicos controlados.

**Características:**
- **Configuración de brazos:** Múltiples brazos con probabilidades custom
- **Fingerprinting robusto:** Canvas, WebGL, Screen, Timezone, Audio, Fonts
- **Métodos de aleatorización:**
  - `seeded` - Reproducible para auditoría
  - `pure-random` - Aleatoriedad criptográfica
- **Dashboard en tiempo real:** Estadísticas de asignación por brazo
- **Manual overrides:** Asignación manual para casos especiales
- **Prevención de duplicados:** Un participante = una asignación

**Shortcodes:**
```
[eipsi_randomized_form configuration_id="456"]
[eipsi_randomized_form_page configuration_id="456"]
```

---

### 🏥 Longitudinal Pools

Asignación automática de participantes a estudios con probabilidades configurables.

**Características:**
- **Pools de estudios:** Agrupa múltiples estudios longitudinales
- **Probabilidades custom:** Define peso de cada estudio en el pool
- **Asignación automática:** Weighted random al unirse
- **Dashboard de monitoreo:** Visualización de asignaciones y balances

**Shortcodes:**
```
[eipsi_pool_join pool_id="789"]  // Página de unión al pool
```

**Estado:** Parte 1-4 implementadas (lógica de asignación + dashboard)

---

### 📧 Email Service

Sistema de emails transaccionales con templates HTML y logging completo.

**Templates disponibles:**
- `welcome.php` - Bienvenida con Magic Link inicial
- `wave-reminder.php` - Recordatorio de toma pendiente
- `wave-confirmation.php` - Confirmación de recepción
- `dropout-recovery.php` - Mensaje "Te extrañamos"
- `magic-link.php` - Links seguros de acceso
- `gentle-reminder.php` - Recordatorios empáticos

**Características técnicas:**
- Logging en `wp_survey_email_log`
- Retry mechanism para envíos fallidos
- SMTP integration configurable
- Placeholders dinámicos: `{name}`, `{study_name}`, `{wave_date}`, `{magic_link}`

---

### 📈 Monitoring Dashboard

Panel de monitoreo integral para administradores.

**Métricas disponibles:**
- **Email Stats:** Enviados, fallidos, bounce rate
- **Cron Jobs:** Estado, última ejecución, health indicator
- **Sessions:** Activas, expiradas, unused
- **Database Health:** Integridad de tablas
- **Audit Log:** Historial de acciones administrativas

---

### 🔐 Security & Privacy

Diseñado para cumplir con estándares de investigación clínica.

**Seguridad:**
- Rate limiting: 5 intentos de login / 15 minutos
- Session TTL: 7 días
- Magic link TTL: 30 minutos
- Cookie flags: HttpOnly, Secure, SameSite=Lax
- Nonce verification en todos los AJAX handlers
- Prepared statements en todas las queries SQL

**Privacidad (GDPR compliant):**
- IP configurable (OFF por defecto)
- Browser/OS/Screen OFF por defecto
- Data Request Portal para participantes
- Admin-initiated anonymization
- Audit logging completo
- Retention policy enforcement

---

### 💾 Export System

Exportación de datos en múltiples formatos.

**Formatos:**
- Excel (.xlsx) con estilos
- CSV (UTF-8 compatible)

**Filtros disponibles:**
- Por estudio/survey
- Por wave
- Por rango de fechas
- Por estado (pending/submitted)

**Stats incluidos:**
- Tasa de finalización
- Tiempos de respuesta
- Distribución de respuestas

---

### 🧱 12 Gutenberg Blocks

Bloques diseñados específicamente para investigación clínica.

| Bloque | Descripción |
|--------|-------------|
| `Form Container` | Contenedor principal del formulario |
| `Form Page` | Página/sección dentro del formulario |
| `Campo Texto` | Input de texto simple |
| `Campo Textarea` | Área de texto largo |
| `Campo Radio` | Opción única (radio buttons) |
| `Campo Multiple` | Opción múltiple (checkboxes) |
| `Campo Select` | Dropdown/selector |
| `Campo Descripción` | Texto informativo (markdown) |
| `Campo Likert` | Escala Likert configurable |
| `VAS Slider` | Escala visual analógica 1-100 |
| `Consent Block` | Consentimiento informado |
| `Randomization Block` | Configuración de aleatorización |

---

### 🔀 Conditional Logic

Lógica condicional potente para formularios dinámicos.

**Operadores:** AND / OR
**Acciones:**
- `jump_to_page` - Saltar a página específica
- `show/hide` - Mostrar/ocultar campos
- `conditional required` - Campos obligatorios condicionales

**Compatibilidad:** Funciona con todos los tipos de campo

---

### 💾 Save & Continue Later

Sistema de guardado automático para formularios largos.

**Características:**
- IndexedDB para drafts persistentes
- Autosave cada 30 segundos
- beforeunload warning (previene pérdida accidental)
- Modal de recuperación de sesión
- Cross-session persistence

---

## 📋 Changelog Reciente

### v2.1.0 (2025-02-24) - Phase 3: Researcher Data Confidence

**Epic:** Researcher Data Confidence

#### 3A - Export Hardening
- ✅ Participant Access Log Export (IRB compliance)
- ✅ Completion Rate Verification
- ✅ Wave-Level Timestamps (`wave_started_at`, `wave_completed_at`, `time_to_complete`)

#### 3B - Monitoring Upgrades
- ✅ Per-Participant Progress View (Timeline)
- ✅ Failed Email Alerts Dashboard
- ✅ Cron Health Indicator

#### 3C - GDPR Deletion Foundation
- ✅ Participant Data Request Portal
- ✅ Admin-Initiated Anonymization
- ✅ Retention Policy Enforcement

---

### v2.0.0 (2025-02-XX)

- 🎉 Longitudinal Pools Parts 1-4 completadas
- ✅ Pool Assignment Service con weighted random
- ✅ Pool Dashboard con monitoreo de asignaciones
- ✅ Pool Join Shortcode
- ✅ Múltiples fixes de UI/UX

---

### v1.4.2 (2025-02-06) - Security Hardening

- 🔒 12 vulnerabilidades corregidas
- ✅ SQL Injection fixes (prepared statements)
- ✅ Input validation con whitelists
- ✅ Race condition fix (atomic delete)
- ✅ Database indices optimizados

---

### v1.4.1 (2025-02-05) - Email Service

- 📧 4 templates HTML creados
- ✅ Email logging en base de datos
- ✅ SMTP integration

---

### v1.4.0 (2025-02-04) - Longitudinal Studies

- 📊 Sistema longitudinal completo
- ✅ Waves, Magic Links, Participants
- ✅ Email automation
- ✅ Session management

---

## 🗄️ Database Schema

El plugin crea las siguientes tablas en la base de datos:

### Tablas principales

| Tabla | Descripción |
|-------|-------------|
| `wp_vas_form_results` | Respuestas de formularios |
| `wp_vas_form_events` | Eventos de sesión (page views, etc.) |
| `wp_vas_randomizations` | Configuraciones RCT |
| `wp_vas_rct_assignments` | Asignaciones de brazos RCT |

### Tablas longitudinales

| Tabla | Descripción |
|-------|-------------|
| `wp_survey_studies` | Definición de estudios longitudinales |
| `wp_survey_waves` | Waves/tomas de cada estudio |
| `wp_survey_participants` | Participantes registrados |
| `wp_survey_sessions` | Sesiones activas |
| `wp_survey_email_log` | Log de emails enviados |
| `wp_survey_audit_log` | Auditoría de acciones |
| `wp_survey_cron_log` | Historial de cron jobs |
| `wp_survey_data_requests` | Solicitudes GDPR |

### Tablas de pools

| Tabla | Descripción |
|-------|-------------|
| `wp_eipsi_longitudinal_pools` | Definición de pools |
| `wp_eipsi_longitudinal_pool_assignments` | Asignaciones a pools |

---

## 🏗️ Architecture

El plugin sigue una arquitectura orientada a servicios (Service Layer Pattern).

### Services (20 clases)

```php
// Servicios principales
EIPSI_Email_Service          // Envío de emails
EIPSI_Participant_Service    // Gestión de participantes
EIPSI_Wave_Service           // Gestión de waves
EIPSI_MagicLinksService      // Generación de magic links
EIPSI_Auth_Service           // Autenticación
EIPSI_Anonymize_Service      // Anonimización GDPR
EIPSI_Export_Service         // Exportación de datos
EIPSI_SMTP_Service           // Configuración SMTP
EIPSI_Assignment_Service     // Asignación de participantes

// Phase 3 - Researcher Data Confidence
EIPSI_Access_Log_Export_Service       // Export de logs de acceso
EIPSI_Completion_Verification_Service // Verificación de completitud
EIPSI_Participant_Timeline_Service    // Timeline por participante
EIPSI_Failed_Email_Alerts_Service     // Alertas de emails fallidos
EIPSI_Cron_Health_Service             // Monitoreo de cron
EIPSI_Participant_Data_Request_Service // Portal GDPR
EIPSI_Participant_Access_Log_Service  // Logs de acceso
EIPSI_Participant_Auth_Handler        // Handler de auth
EIPSI_Device_Data_Service             // Datos de dispositivo

// Pools
EIPSI_Pool_Assignment_Service  // Lógica de asignación
EIPSI_Pool_Dashboard_Service   // Dashboard de monitoreo
```

---

## 📁 Directory Structure

```
eipsi-forms/
├── admin/                          # Panel de administración
│   ├── services/                   # Clases de servicio (20 archivos)
│   ├── tabs/                       # Tabs del panel admin
│   ├── ajax-*.php                  # Handlers AJAX
│   ├── cron-*.php                  # Handlers de cron
│   ├── database-schema-manager.php # Gestión de schema
│   ├── export.php                  # Sistema de export
│   ├── monitoring.php              # Dashboard de monitoreo
│   └── ...
├── assets/                         # Assets estáticos
│   ├── css/                        # Estilos (15+ archivos)
│   ├── js/                         # JavaScript (15+ archivos)
│   └── images/                     # Iconos e imágenes
├── build/                          # Build output (generado)
├── includes/                       # Includes principales
│   ├── emails/                     # Templates de email (9 archivos)
│   ├── shortcodes/                 # Clases de shortcodes
│   └── class-*.php                 # Clases helper
├── src/                            # Código fuente Gutenberg
│   ├── blocks/                     # 13 bloques Gutenberg
│   └── utils/                      # Utilidades JS
├── languages/                      # Traducciones (.pot, .po, .mo)
├── eipsi-forms.php                 # Archivo principal del plugin
├── package.json                    # Dependencias npm
├── webpack.config.js               # Configuración de build
└── README.md                       # Este archivo
```

---

## 🔧 Development

### Requisitos

- Node.js 16+
- npm 8+
- PHP 7.4+
- WordPress 5.8+

### Comandos de desarrollo

```bash
# Instalar dependencias
npm install

# Build de producción
npm run build

# Build de desarrollo (watch)
npm run start

# Linting JavaScript
npm run lint:js

# Linting con auto-fix
npm run lint:js -- --fix

# Linting CSS
npm run lint:css
```

### Build Output

- Bundle size: < 250 KB (gzipped)
- Build time: < 5 segundos
- Lint: 0 errors / 0 warnings

---

## 🛣️ Roadmap

### ✅ Completado (v1.0 - v2.1)

- [x] 12 Gutenberg Blocks clínicos
- [x] Sistema longitudinal completo
- [x] Randomized Controlled Trials (RCT)
- [x] Email Service con templates
- [x] Monitoring Dashboard
- [x] Export System (Excel/CSV)
- [x] Conditional Logic (AND/OR, jump_to_page)
- [x] Save & Continue Later
- [x] GDPR Compliance Portal
- [x] Security Hardening (12 vulnerabilidades)
- [x] Longitudinal Pools (Parts 1-4)

### 🚧 En Progreso / Próximos

- [ ] **Integrated completion page** (misma URL forever, sin redirects externos)
- [ ] **Conditional field visibility** (dentro de la misma página)
- [ ] **Clinical templates** (PHQ-9, GAD-7, PCL-5, AUDIT, DASS-21) con scoring automático
- [ ] **Fingerprint ID → Datos RAW** (opcional en export para análisis forense)

### 📅 Futuro (Nice-to-have)

- [ ] Visual progress bar
- [ ] Matrix questions (grid)
- [ ] Analytics UI avanzada
- [ ] Multilingual (i18n completo)
- [ ] REST API pública
- [ ] Field encryption

---

## 🤝 Contributing

Las contribuciones son bienvenidas. Por favor:

1. Fork el repositorio
2. Crear branch feature (`git checkout -b feature/nueva-funcionalidad`)
3. Commit cambios (`git commit -m 'feat: descripción del cambio'`)
4. Push al branch (`git push origin feature/nueva-funcionalidad`)
5. Abrir Pull Request

### Código de Conducta

Este proyecto sigue el principio de **Zero fear + Zero friction + Zero excuses**. Todo comentario, issue o PR debe ser respetuoso y constructivo.

---

## 📜 License

GPL v2 or later. Ver [LICENSE](https://www.gnu.org/licenses/gpl-2.0.html).

---

## 👤 Author

**Mathias N. Rojas de la Fuente**
- Instagram: [@enmediodel.contexto](https://www.instagram.com/enmediodel.contexto/)
- Web: [enmediodelcontexto.com.ar](https://enmediodelcontexto.com.ar)

---

## 🙏 Acknowledgments

EIPSI Forms existe porque **ningún plugin de forms entendió cómo trabajan realmente lxs psicólogxs y psiquiatras** con sus pacientes. Este plugin es la respuesta a esa frustración compartida por miles de clínicos en el mundo hispanohablante.

**"Por fin alguien entendió cómo trabajo de verdad con mis pacientes"**

---

<p align="center">
  <strong>EIPSI Forms</strong> - El form builder que habla tu idioma clínico
</p>
