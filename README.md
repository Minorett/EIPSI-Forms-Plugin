# EIPSI Forms - Professional Form Builder for Clinical Research

> Plugin multipágina diseñado para psicólogxs y psiquiatras hispanohablantes. Probado en tablets en sala, con foco en **cero miedo + cero fricción + cero pérdida de datos**.

- **Versión clínica estable:** 1.3.6 (Producción — RCT Analytics Dashboard, Consolidación de utilidades)
- **Compatibilidad probada:** WordPress 5.8+, PHP 7.4+
- **Licencia:** GPL v2 o posterior

---

## Descripción breve

EIPSI Forms convierte WordPress en una herramienta clínica de última generación para recolección de datos en psicoterapia e investigación en español. Incluye 12 bloques nativos de Gutenberg, sistema de aleatorización para ensayos clínicos (RCT), dashboard de análisis en tiempo real, navegación multipágina controlada y lógica condicional avanzada. Todo integrado en WordPress, sin dependencias externas ni SaaS.

---

## Características clínicas actuales

### 🎯 Formularios multipágina sin sorpresas
- Primera página con solo botón **"Siguiente"**
- Páginas intermedias con **"Anterior"** opcional según el ajuste `allowBackwardsNav`
- Última página exclusiva para **"Enviar"** (sin "Siguiente")
- Mensaje de finalización integrado en la misma URL, configurable desde el panel
- Botón "Comenzar de nuevo" para reutilizar la tablet en sala

### 🧪 Sistema RCT Completo (Randomized Controlled Trials)
- **Randomization Block:** Sistema de aleatorización con distribución configurable
- Configuración de brazos (control, experimental, etc.) con porcentajes personalizables
- Asignación persistente por sesión/participante
- Frontend de acceso aleatorizado con validación automática
- Exportación de datos de aleatorización con metadatos completos

### 📊 RCT Analytics Dashboard
- **Estadísticas en tiempo real:** distribución de usuarios por brazo
- Monitoreo de balanceo de asignaciones
- Visualización de asignaciones por formulario
- Exportación de datos de aleatorización (Excel/CSV)
- Auditoría de eventos de aleatorización

### 💾 Save & Continue Later
- Almacenamiento local con IndexedDB para drafts persistentes
- **Autosave cada 30 segundos** automático
- Warning `beforeunload` para evitar pérdida de datos por cierre accidental
- Recuperación de formularios incompletos entre sesiones
- Sincronización segura con servidor

### 🧱 Bloques clínicos nativos (12)
- **Contenedores:** Form Container, Form Page
- **Campos:** Texto, Textarea, Multiple (checkboxes), Radio, Select, Descripción (markdown)
- **Escalas clínicas:** Likert (configurable), VAS Slider (1-100)
- **Especiales:** Consent Block (consentimiento informado con markdown), Randomization Block (RCT)
- Todos los campos incluyen validaciones, soporte para campos obligatorios y compatibilidad total con lógica condicional

### 🎨 Diseño accesible y consistente
- 5 presets de color clínicos preconfigurados
- Toggle universal de modo oscuro (automático o manual)
- WCAG 2.1 AA validado (contrastes, focus states, navegación por teclado)
- Targets táctiles de **44×44 px** garantizados en radios, checkboxes y sliders
- Dark mode persiste entre sesiones sin afectar legibilidad

### 🔍 Lógica condicional avanzada (AND/OR)
- Mostrar/ocultar bloques según respuestas previas
- Saltos de página (`jump_to_page`) para ramificar entrevistas
- Reglas múltiples con operadores **AND** y **OR** combinables
- Compatible con todos los tipos de campo (RADIO, CHECKBOX, VAS, LIKERT, SELECT)
- Evaluación en tiempo real (sin recargar)
- Soporte para opciones con caracteres especiales (separador `;`)

### 🔐 Identificación y trazabilidad sin inventar datos
- **Participant ID** y **Session ID** automáticos (anonimizados, persistidos durante la sesión)
- **Fingerprint clínico liviano opcional:** captura de browser, OS y resolución (configurable)
- Timestamps precisos (inicio/fin, duración en milisegundos)
- Eventos clave (`view`, `start`, `page_change`, `submit`, `abandon`, `branch_jump`, `randomization`)
- Dashboard con privacidad por defecto: IP opcional, datos de navegador/OS/pantalla desactivados hasta habilitación explícita

### 📋 Panel "Results & Experience"
- **3 pestañas consolidadas:**
  1. **Submissions:** tabla paginada con filtros por formulario, exportación directa a Excel/CSV y detalle de sesión
  2. **Completion Message:** editor rich-text para el mensaje de agradecimiento, logo opcional y control del botón "Comenzar de nuevo"
  3. **Privacy & Metadata:** toggles granulares para cada metadato y recordatorio de retenciones
- Todo ocurre en WordPress, sin paneles externos ni dependencias SaaS

### 📊 Dashboard RCT Analytics (nuevo en v1.3.2)
- **Pestaña "RCT Analytics":** estadísticas en tiempo real de aleatorización
- Distribución de usuarios por brazo y por formulario
- Monitoreo de balanceo de asignaciones
- Exportación de datos de aleatorización con metadatos
- Visualización de eventos de aleatorización

### 💾 Base de datos y exportaciones
- Tablas clínicas (`wp_vas_form_results`, `wp_vas_form_events`, `wp_vas_randomizations`, `wp_vas_rct_assignments`) con índices optimizados
- **Zero Data Loss** con auto-reparación automática del esquema
- Exportación inmediata a **Excel (XLSX)** y **CSV UTF-8** con todas las respuestas, timings y metadatos
- Índices preparados para auditoría y queries rápidas

---

## Características por versión

### v1.3.6 (Enero 2025) - Versión Actual
- ✅ **Consolidación de funciones duplicadas:** migración a utilidades centralizadas
- ✅ **Auto-registro de bloques:** sistema auto-descubrimiento para bloques Gutenberg
- ✅ **Preparación arquitectura modular:** base para futuro sistema de plugins/addons
- ✅ **Fix WordPress compatibility:** solución definitiva para errores de registro de bloques
- ✅ **Patrón de auto-descubrimiento:** cualquier nuevo bloque se registra automáticamente

### v1.3.2 (Enero 2025)
- ✅ **RCT Analytics Dashboard:** panel completo con estadísticas en tiempo real
- ✅ **Monitoreo de distribución:** visualización de balanceo de brazos por formulario
- ✅ **Exportación RCT:** datos de aleatorización en Excel/CSV con metadatos
- ✅ **API de análisis RCT:** endpoints optimizados para queries rápidas

### v1.3.1 (Enero 2025)
- ✅ **Sistema RCT completo:** Randomized Controlled Trials con distribución configurable
- ✅ **Configuración de brazos:** porcentajes personalizables por brazo
- ✅ **Frontend aleatorización:** interfaz de acceso aleatorizado persistente
- ✅ **Asignación única por participante:** validación automática para evitar re-aleatorización

### v1.3.0 (Diciembre 2024)
- ✅ **Automatización Min/Max:** escalas Likert configuran rangos automáticamente
- ✅ **Bloque aleatorización independiente:** separación de lógica RCT en bloque propio
- ✅ **Mejoras editor/frontend:** alineación exacta en VAS y escalas clínicas

### v1.2.2 (Noviembre 2024)
- ✅ **Zero Data Loss:** auto-reparación automática de esquema de base de datos
- ✅ **Hotfix de compatibilidad:** solución definitiva para actualizaciones de WordPress
- ✅ **Validación continua:** chequeo cada 24h + en cada submit fallido

---

## Requisitos técnicos

| Contexto | Requisito |
| --- | --- |
| **WordPress** | 5.8 o superior, con Gutenberg activo |
| **PHP** | 7.4+ (recomendado 8.1+) con extensiones mysqli y mbstring |
| **Base de datos** | MySQL/MariaDB 5.7+ o equivalente |
| **Servidor** | HTTPS recomendado, capacidad para ejecutar WP-Cron |
| **Desarrollo** | Node.js 14+ (recomendado 18.x LTS), npm 7+ |

---

## Instalación

### Instalación manual (producción)
1. Descargar el ZIP del plugin desde [GitHub Releases](https://github.com/Minorett/EIPSI-Forms-Plugin/releases)
2. En WordPress: `Plugins → Añadir nuevo → Subir plugin` y seleccionar el ZIP
3. Activar **EIPSI Forms**
4. El plugin valida y repara automáticamente las tablas necesarias tras la activación

### Instalación desde desarrollo
```bash
# Clonar el repositorio
git clone https://github.com/Minorett/EIPSI-Forms-Plugin.git
cd EIPSI-Forms-Plugin

# Instalar dependencias
npm install --legacy-peer-deps

# Build para producción
npm run build

# Activar desde WordPress Admin → Plugins
```

### Actualización segura
1. Probá primero en un entorno de staging con los mismos formularios clínicos
2. En producción:
   - Desactivá la versión anterior
   - Subí el nuevo ZIP vía `Plugins → Añadir nuevo → Subir plugin`
   - Reactivá **EIPSI Forms**
3. Borra cachés (Hostinger, plugins de cacheo, Cloudflare) para evitar assets antiguos
4. Si WordPress marca columnas faltantes, entra a cualquier pantalla del admin: la auto-reparación se ejecuta en < 1 segundo

---

## Guía de uso

### Crear un formulario básico
1. **Crear formulario:** abrí el editor de Gutenberg en la página deseada
2. **Añadir contenedor:** inserta el bloque **"EIPSI Form Container"**
3. **Agregar páginas:** dentro del contenedor, añade bloques **"Form Page"**
4. **Agregar campos:** en cada página, añade los bloques de campo necesarios (Texto, Radio, Likert, VAS, etc.)
5. **Configurar lógica:** en cada campo activa reglas condicionales o saltos de página (`jump_to_page`)
6. **Diseño:** elegí un preset de color y probá el toggle dark mode
7. **Publicar y probar:** abrí el formulario desde la tablet y hace un envío completo

### Crear un ensayo clínico (RCT)
1. **Crear formulario base:** sigue los pasos para crear un formulario multipágina
2. **Añadir Randomization Block:** inserta el bloque **"Randomization Block"** al inicio del formulario
3. **Configurar brazos:** define los brazos (control, experimental, etc.) y porcentajes
4. **Configurar lógica:** usa `jump_to_page` para redirigir a diferentes páginas según el brazo asignado
5. **Activar tracking:** ver asignaciones en **EIPSI Forms → RCT Analytics**
6. **Exportar datos:** descarga la distribución de aleatorización en Excel/CSV

### Guardar y continuar después
1. **Habilitar Save & Continue:** en el Form Container, activa la opción "Save & Continue Later"
2. **Configurar autosave:** el intervalo de 30 segundos está preconfigurado
3. **Probar:** completa parcialmente un formulario, cierra el navegador, vuelve y recupera desde el draft en IndexedDB
4. **Ver drafts:** los formularios incompletos se guardan localmente hasta completar

### Revisar resultados
1. **Abrir panel:** WordPress → EIPSI Forms → Results & Experience
2. **Pestaña Submissions:** ver todos los envíos con filtros por formulario
3. **Ver detalle:** clic en cualquier envío para ver respuestas completas
4. **Exportar:** descarga en Excel (XLSX) o CSV UTF-8 con metadatos
5. **Pestaña RCT Analytics:** ver estadísticas de aleatorización en tiempo real

---

## Arquitectura técnica

### Frontend (Gutenberg Blocks)
- **React/JSX** con WordPress Components
- **Webpack modular:** cada bloque compila independientemente
- **CSS-in-JS** con variables de tema
- **Dark mode:** detección automática vía `@media prefers-color-scheme`
- **IndexedDB:** almacenamiento local para drafts (Save & Continue Later)
- **Autosave:** sistema automático cada 30 segundos
- **beforeunload:** warning para evitar pérdida de datos

### Backend (PHP)
- **WordPress Hooks/Filters** estándar (sin alterar core)
- **AJAX nativo** para operaciones asincrónicas
- **Tablas personalizadas:**
  - `wp_vas_form_results` - respuestas de formularios
  - `wp_vas_form_events` - eventos de sesión (view, start, page_change, submit, etc.)
  - `wp_vas_randomizations` - configuraciones de aleatorización
  - `wp_vas_rct_assignments` - asignaciones por participante
- **Índices optimizados** para queries rápidas
- **Auto-reparación de esquema** cada 24h + en cada error

### Build System
- **@wordpress/scripts** (webpack wrapper oficial de WordPress)
- **ESLint + Prettier** para code quality
- **Verificación de duplicados automatizada** (`npm run lint:duplicates`)
- **Scripts de verificación:** `scripts/verify-build.*` para asegurar artefactos válidos
- **Bundle size:** < 250 KB total
- **Build time:** < 7 segundos

---

## Desarrollo y build

### Comandos esenciales
```bash
# Instalar dependencias
npm install --legacy-peer-deps

# Linting (auto-fix habilitado)
npm run lint:js
npm run lint:js -- --fix  # Auto-fix de problemas

# Formateo de código (Prettier)
npm run format

# Verificar funciones duplicadas
npm run lint:duplicates

# Build para producción
npm run build

# Build en desarrollo (watch mode)
npm run start
```

### Verificación de calidad
```bash
# Verificar build completo
npm run verify-build

# Linting debe ser 0/0 antes de commit
npm run lint:js && echo "✅ Lint OK"

# Build exitoso antes de commit
npm run build && echo "✅ Build OK"
```

### Code Quality - Duplicate Function Detection

EIPSI Forms incluye un sistema automático de detección de funciones duplicadas.

```bash
npm run lint:duplicates
```

**Características:**
- Detecta funciones duplicadas en PHP y JavaScript
- Muestra ubicación exacta (archivo y línea)
- Se ejecuta en < 2 segundos
- Integración con el flujo de trabajo

**Ejemplo de output:**
```
✓ Verificación de funciones duplicadas completada
  PHP: 127 funciones encontradas
  JavaScript: 89 funciones encontradas
  ✅ Sin duplicados detectados
```

---

## Estructura de directorios

```
EIPSI-Forms-Plugin/
├── src/
│   ├── blocks/                  # Bloques Gutenberg (12 bloques)
│   │   ├── form-container/      # Contenedor principal
│   │   ├── form-page/           # Página multipágina
│   │   ├── campo-texto/         # Input de texto
│   │   ├── campo-textarea/      # Texto largo
│   │   ├── campo-radio/         # Radio buttons
│   │   ├── campo-multiple/      # Checkboxes
│   │   ├── campo-select/       # Dropdown
│   │   ├── campo-likert/       # Escala Likert configurable
│   │   ├── vas-slider/          # Visual Analog Scale (1-100)
│   │   ├── campo-descripcion/   # Texto estático con markdown
│   │   ├── consent-block/       # Consentimiento informado
│   │   └── randomization-block/ # Sistema RCT
│   ├── components/              # Componentes React compartidos
│   ├── frontend/                # Lógica de frontend (save & continue, RCT)
│   │   ├── save-continue/       # IndexedDB + autosave
│   │   └── rct-frontend/        # Frontend aleatorización
│   ├── utils/                   # Utilidades centralizadas (v1.3.6)
│   └── index.js                 # Entry point principal
├── build/                       # Build compilado (auto-generado)
│   └── blocks/                  # 12 directorios de bloques compilados
├── admin/                       # Pages y handlers de admin
│   ├── rct-analytics-page.php   # Dashboard RCT Analytics
│   ├── ajax-handlers.php        # AJAX handlers
│   └── results-experience.php   # Panel Results & Experience
├── assets/                      # CSS, JS, imágenes
├── includes/                    # PHP compartido
│   ├── randomization-api.php    # API de aleatorización
│   └── db-install.php           # Instalación/repair DB
├── eipsi-forms.php              # Plugin main file
├── package.json                 # Dependencias npm
├── webpack.config.js            # Configuración webpack personalizada
└── README.md                    # Este archivo
```

---

## Alcances y límites actuales

### ✅ Implementado (Producción)
- Bloques Gutenberg personalizados (12 bloques)
- Sistema RCT completo con dashboard de análisis
- Save & Continue Later con IndexedDB + autosave 30s
- Lógica condicional avanzada (AND/OR, jump_to_page)
- Panel "Results & Experience" con 3 pestañas
- Dashboard "RCT Analytics" con estadísticas en tiempo real
- Escalas clínicas (Likert, VAS Slider)
- Dark mode automático
- Exportación Excel/CSV
- WCAG 2.1 AA (73 tests 100% pass)
- Zero Data Loss con auto-reparación de esquema
- Auto-registro de bloques (v1.3.6)

### 🚧 En desarrollo (Prioridad P1 - Febrero/Mayo 2025)
- **Conditional field visibility dentro de la misma página**
- **Conditional required** (campos que se vuelven obligatorios según respuesta)
- **Plantillas clínicas oficiales** (PHQ-9, GAD-7, PCL-5, AUDIT, DASS-21) con scoring automático y normas locales

### 📅 Planificado (sin fecha confirmada)
- Visual progress bar mejorado
- Matrix questions (grillas de preguntas)
- Analytics UI mejorada con gráficos
- Soporte multilingual completo (actualmente español + inglés ready)
- API REST para integraciones externas
- Cifrado de campos individual
- Importar/exportar formularios en JSON
- Integración nativa con Elementor, Divi, etc.

### ❌ Fuera de alcance
- No provee diagnóstico, tratamiento ni reemplaza criterio clínico
- No es un sistema de recolección de datos médicos HIPAA-compliant (por defecto)
- No incluye telepsiquiatría o videoconferencia

---

## Notas clínicas y filosofía

EIPSI Forms nace de sesiones reales con pacientes que se levantan en medio de la entrevista, tablets que se quedan sin batería y equipos de investigación que no pueden perder ni un dato. Cada decisión técnica prioriza:

- **Formularios que se entienden al primer toque** → interfaz intuitiva, sin barreras
- **Oscuro/claro según lo prefiera cada consultorio** → dark mode persistente
- **Reparación automática ante cualquier riesgo** → Zero Data Loss es dogma
- **Privacidad por defecto** → datos de navegador/OS desactivados hasta habilitación explícita
- **Todo en WordPress** → sin SaaS, sin panel externo, sin dependencias

Si algo te hace fruncir el ceño a vos o a tu paciente, es un bug para nosotros.

---

## Soporte y bugs

### Reportar bugs
- **Issues públicos:** [GitHub Issues](https://github.com/Minorett/EIPSI-Forms-Plugin/issues)
- **Email:** `support@eipsi.research`

### Información requerida
- Versión de WordPress
- Versión de PHP
- Pasos para reproducir el problema
- Capturas de pantalla o logs si están disponibles
- Navegador y dispositivo donde ocurre el error

### Soporte técnico
¿Dudas sobre tu implementación clínica? Escribinos antes de la próxima sesión; preferimos prevenir que explicar una pérdida de datos.

---

## Disclaimer clínico

EIPSI Forms es una herramienta para capturar datos. **No provee diagnóstico, tratamiento ni reemplaza criterio clínico.** El uso del plugin no constituye consejo médico y cada institución sigue siendo responsable de sus protocolos de consentimiento, almacenamiento seguro y comunicación de resultados.

---

## Licencia y autor

**Licencia:** GPL v2 o posterior

**Autor:** Mathias N. Rojas de la Fuente  
**Instagram:** [@enmediodel.contexto](https://www.instagram.com/enmediodel.contexto/)

**Repositorio:** [GitHub - EIPSI-Forms-Plugin](https://github.com/Minorett/EIPSI-Forms-Plugin)

---

## Contribución

¿Querés contribuir? ¡Genial! 

1. Fork el repositorio
2. Creá un branch para tu feature (`git checkout -b feature/AmazingFeature`)
3. Commit tus cambios (`npm run lint:js && npm run build && git commit -m 'Add some AmazingFeature'`)
4. Push al branch (`git push origin feature/AmazingFeature`)
5. Abrí un Pull Request

**Recordá:** Lint OK + build exitoso antes de cada commit.

---

**¿Listo para crear formularios clínicos reales en WordPress?**

Comenzá hoy mismo: [Descargar EIPSI Forms](https://github.com/Minorett/EIPSI-Forms-Plugin/releases)

*Por fin alguien entendió cómo trabajás de verdad con tus pacientes.* 🚀
