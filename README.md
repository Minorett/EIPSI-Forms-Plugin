# EIPSI Forms - Plugin de Investigación Clínica para WordPress

> Formularios profesionales de grado clínico para investigación en psicoterapia y salud mental

---

## 🎯 Características Principales

### 📋 Bloques de Gutenberg Personalizables

#### **Campo Likert (Escala Likert)**
- Escalas configurables (5, 7, 10+ puntos)
- Etiquetas personalizables por punto
- Validación de respuesta obligatoria/opcional
- Soporte para lógica condicional
- Totalmente responsivo (44×44px touch targets)

#### **VAS Dinámico (Visual Analogue Scale)**
- Slider interactivo con feedback en tiempo real
- Rangos personalizables (0-100, 0-10, etc.)
- Etiquetas de extremo izquierdo/derecho
- Validación de rango integrada
- Thumb optimizado para touch (32×32px)

#### **Campo Radio (Opción Única)**
- Múltiples opciones dinámicas
- Validación de respuesta obligatoria
- Lógica condicional integrada
- Focus indicators accesibles

#### **Campo Checkbox (Múltiples Opciones)**
- Selección múltiple
- Validación flexible
- Lógica condicional completa

#### **Campos de Texto (Input/Textarea)**
- Validación de formato (email, números, etc.)
- Límite de caracteres opcional
- Placeholders personalizados

#### **Secciones/Páginas Múltiples**
- Formularios largos divididos en secciones
- Navegación intuitiva con indicador de progreso
- Opción de permitir/denegar "atrás"
- Persistencia de datos entre páginas

---

## 🎨 Sistema de Diseño Profesional

### **4 Presets de Color Predefinidos**
1. **Clinical Blue** (Defecto) - Azul profesional #005a87 con branding EIPSI
2. **Minimal White** - Esquema limpio y minimalista
3. **Warm Neutral** - Tonos cálidos y acogedores
4. **High Contrast** - Máximo contraste para accesibilidad

### **Controles Personalizables**
- Color primario, hover y activo
- Color de texto y fondo
- Colores de error, éxito y advertencia
- CSS variables para control granular (52 variables disponibles)

### **✅ WCAG 2.1 AA Compliant**
- Todas las combinaciones de color: **4.5:1 mínimo**
- Touch targets: **44×44px (WCAG AAA)**
- Validación automatizada con script `wcag-contrast-validation.js`
- Warnings en FormStylePanel para evitar violaciones

### **Responsividad Completa**
- Validada en 6 breakpoints: 320px, 375px, 480px, 768px, 1024px, 1280px+
- Mobile-first design
- iOS y Android nativos

---

## 🔐 Lógica Condicional Avanzada

### **Reglas Condicionales Inteligentes**
- Es igual a / No es igual a
- Mayor que / Menor que
- Contiene / No contiene
- Múltiples reglas con AND/OR

### **Acciones Dinámicas**
- Mostrar/ocultar campos según respuestas
- Saltar a página específica
- Mostrar/ocultar secciones enteras
- Evaluación en tiempo real durante completación

---

## 📊 Identificación y Metadatos

### **Form ID Inteligente**
"Anxiety Clinical Assessment" → ACA-a3f1b2
"Depression" → DEP-b4c5d6
"Be" → BE-c7d8e9

- 3 primeras letras de cada palabra
- Hash SHA256 único
- No breaking changes con datos históricos

### **Participant ID Universal**
- UUID v4 truncado a 12 caracteres: `p-a1b2c3d4e5f6`
- Persiste en localStorage durante la sesión
- Mismo ID en múltiples formularios
- Completamente anónimo

### **Session ID para Sesiones**
- Único por cada envío
- Rastreo de múltiples intentos
- Análisis de abandonos y patrones

### **Metadatos Completos Capturados**
- Timestamps de inicio/fin y duración
- Device type (mobile/desktop/tablet)
- **IP Address** (requisito de auditoría clínica)
- Quality flag automático (HIGH/NORMAL/LOW)
- Métricas clínicas:
  - Therapeutic Engagement (tiempo, cambios, navegación)
  - Clinical Consistency (coherencia de respuestas)
  - Avoidance Patterns (saltos, retrocesos)

---

## 🛡️ Seguridad y Privacidad

### **HIPAA Ready**
- Encriptación de datos sensibles
- Audit trail de acceso
- Anonimización opcional

### **GDPR Compliant**
- Derecho al olvido
- Portabilidad de datos
- Consentimiento explícito
- Política de retención configurable (90 días para IP)

### **Validación y Sanitización**
- Validación en cliente y servidor
- Protección contra XSS
- Escapado apropiado en frontend
- Sanitización antes de guardar

---

## 💾 Gestión de Bases de Datos

### **Base de Datos Local (WordPress)**
- Tabla: `wp_vas_form_results`
- Almacena: responses, metadatos, IP, timestamps

### **Base de Datos Externa (MySQL/PostgreSQL Compatible)**
- Configuración segura con credenciales encriptadas
- Sincronización automática
- Auto-creación de tablas al cambiar BD
- Verificación periódica de esquema

### **Gestión de Resultados**
- Vista admin con tabla de respuestas
- Filtrado por Form ID, Participant ID, rango de fechas
- Búsqueda rápida
- Eliminación individual o en lote

---

## 📈 Análisis y Tracking

### **Sistema de Seguimiento Integrado**
Eventos registrados:
- Inicio de formulario
- Completación de campo
- Cambio de página
- Envío exitoso
- Errores de validación
- Abandonos

### **Dashboard de Analytics**
- Tasa de respuesta en tiempo real
- Tiempo promedio de completación
- Tasa de abandono por página
- Campos con mayor tasa de error
- Patrones de respuesta

### **Tracking de Participantes**
- Sesiones identificadas por Participant ID
- Duración de sesión
- Dispositivo/navegador
- Puntos de abandono

---

## 📥 Exportación de Datos

### **Exportación a Excel (XLSX)**
- Formato compatible con Microsoft Office
- Headers legibles
- Todos los datos de respuestas
- Metadatos completos (timestamp, IP, device)
- Form ID y Participant ID

### **Exportación a CSV**
- UTF-8 compatible
- Análisis en SPSS, R, Python
- Estadísticas descriptivas por campo

### **Análisis Estadístico**
- Correlaciones básicas
- Distribuciones de respuestas
- Métricas de calidad de datos

---

## 🎯 Experiencia de Usuario

### **Interfaz de Participante**
- Formulario limpio y clínico
- Indicador de progreso visual
- Navegación intuitiva
- Mensaje de éxito tras envío
- Opción de reiniciar o volver al inicio

### **Persistencia de Sesión**
- Save and Continue: Guardar y retomar después
- Datos persistidos en localStorage (cifrados)
- Recuperación automática de sesión

### **Mensajes Personalizables**
- Mensaje de inicio
- Mensajes entre páginas
- Mensaje de agradecimiento final
- Mensajes de error personalizados

---

## ⚙️ Configuración Avanzada

### **Panel de Privacidad y Metadatos**
- Configuración granular por formulario
- Toggles para:
  - Therapeutic Engagement
  - Clinical Consistency
  - Avoidance Patterns
  - Device tracking
- **IP Address activada por defecto (NO desactivable)**

### **Privacy Dashboard**
- UI intuitiva en admin
- Indicadores de configuración
- Info sobre retención de datos
- Estado de sincronización de BD

### **Hooks y Filtros Disponibles**
```php
eipsi_form_before_render
eipsi_form_after_submit
eipsi_validate_field
eipsi_sanitize_field
eipsi_style_tokens
eipsi_tracking_event
🔧 Stack Técnico
Frontend: React (Gutenberg blocks), Vanilla JavaScript
Backend: PHP 7.4+, WordPress hooks
Estilización: SCSS compilado a CSS3, CSS variables
Build: Webpack, npm scripts
Base de Datos: MySQL/MariaDB/PostgreSQL compatible
📊 Especificaciones de Rendimiento
⚡ Load time: < 2 segundos
🎯 Uptime: Diseño para 99.9%
📈 Escalabilidad: Optimizado para 1000+ formularios
🔄 Sincronización: Instantánea entre BDs
✅ Conformidad y Certificaciones
| Estándar | Estado |
|----------|--------|
| WCAG 2.1 AA | ✅ Compliant |
| HIPAA | ✅ Ready |
| GDPR | ✅ Compliant |
| WCAG AAA Touch Targets | ✅ Implementado |
| Contraste de Color | ✅ Validado |
| Keyboard Navigation | ✅ Funcional |
| Screen Reader | ✅ Compatible |

🚀 Flujo de Uso Típico
Para Investigadores
Crear nuevo formulario en Gutenberg
Agregar bloques (Likert, VAS, Radio, etc.)
Configurar lógica condicional
Personalizar estilos (presets o custom)
Configurar URL de redirección
Obtener link del formulario
Distribuir a participantes
Para Participantes
Acceder al formulario
Responder preguntas progresivamente
Navegar entre páginas (si aplica)
Opcionalmente: guardar y continuar después
Enviar formulario
Ver mensaje de éxito
Datos guardados automáticamente
Para Análisis
Ir a panel de resultados en admin
Filtrar/buscar respuestas
Exportar a Excel/CSV
Importar en SPSS/R/Python para análisis estadístico
📚 Documentación
Guía de Instalación
Referencia de Bloques
Configuración de Privacidad
Migraciones de BD
API REST
🔄 Roadmap Futuro
[ ] Multi-idioma (i18n completo)
[ ] Versiones de formularios
[ ] A/B testing
[ ] Integración con análisis estadísticos
[ ] API REST completa
[ ] Webhooks
[ ] Encriptación end-to-end
[ ] Sincronización con EMRs (Electronic Medical Records)
📝 Licencia
Este plugin está desarrollado por EIPSI Research Team con cto.new para investigación en psicoterapia y salud mental.

🤝 Contribuciones
Las contribuciones son bienvenidas. Por favor abre un issue o pull request para reportar bugs o sugerir mejoras.

📞 Soporte
Para soporte técnico, reportar bugs o sugerencias:

📧 Email: support@eipsi.research
🐛 GitHub Issues: Abrir issue
📖 Documentación: Ver docs
