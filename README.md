# EIPSI Forms (v1.2.2) — Formularios clínicos reales para WordPress

> Plugin multipágina diseñado para psicólogxs y psiquiatras hispanohablantes. Probado en tablets en sala, con foco en **cero miedo + cero fricción + cero pérdida de datos**.

- **Versión clínica estable:** 1.2.2 (Hotfix — Reparación automática de esquema)
- **Compatibilidad probada:** WordPress 5.8+, PHP 7.4+
- **Licencia:** GPL v2 o posterior

## Descripción breve

EIPSI Forms convierte WordPress en una herramienta clínica para recolección de datos en psicoterapia e investigación en español. Incluye bloques nativos de Gutenberg, navegación multipágina controlada y lógica condicional contundente para que cada sesión pueda correrse desde la misma tablet sin sustos ni datos perdidos.

## Características clínicas actuales

### Formularios multipágina sin sorpresas
- Primera página con solo botón **“Siguiente”**.
- Páginas intermedias con **“Anterior”** opcional según el ajuste `allowBackwardsNav`.
- Última página exclusiva para **“Enviar”** (sin “Siguiente”).
- Mensaje de finalización integrado en la misma URL, configurable desde el panel, con botón “Comenzar de nuevo” para reutilizar la tablet en sala. Redirecciones externas opcionales.

### Bloques clínicos nativos (11)
- **Contenedores:** Form Container, Form Block y Página.
- **Campos:** VAS Slider, Likert, Radio, Multiple (checkboxes), Select, Texto, Textarea, Campo informativo y utilidades específicas para instrucciones/avisos.
- Todos los campos incluyen validaciones básicas, soporte para requisitos obligatorios y compatibilidad total con la lógica condicional.

### Plantillas demo EIPSI (estado actual)
- Plantillas prearmadas como **“Ingreso ansiedad breve (demo)”**, **“Seguimiento emocional (demo)”** o **“Satisfacción de sesión (demo)”** listas para cargar desde el dropdown del **EIPSI Form Container**.
- Cada plantilla demo son bloques EIPSI reales con navegación multipágina, condicionales y estilos ya configurados para que puedas partir de algo clínico.
- **Template Profesional de Burnout (v2.0 - Validado):** Evaluación clínica completa del síndrome de burnout en profesionales sanitarios que incluye PHQ-9, GAD-7 y MBI-HSS con consentimiento informado profesional. Validado 100% contra block.json v1.2.2 (todos los atributos son compatibles, sin errores de importación).
- No son escalas oficiales ni incluyen scoring automático; son ejemplos listos para personalizar mientras terminamos de liberar los botones “crear PHQ-9 / GAD-7 / PCL-5 / AUDIT / DASS-21”.

### Lógica condicional avanzada (AND/OR v1.1)
- Mostrar/ocultar bloques dentro del formulario según respuestas previas.
- Saltos de página (`jump_to_page`) para ramificar entrevistas.
- Reglas múltiples con operadores **AND** y **OR** combinables, compatibles con todos los tipos de campo (RADIO, CHECKBOX, VAS, LIKERT, SELECT) y evaluación en tiempo real (sin recargar).
- Soporte para opciones con caracteres especiales (comas, comillas, tildes) gracias a la migración interna a separador `;`.

### Diseño accesible y consistente
- 5 presets de color clínicos preconfigurados + toggle universal de modo oscuro.
- Tokens de diseño expuestos como variables CSS para personalizaciones puntuales.
- WCAG 2.1 AA validado (contrastes, focus states, navegación por teclado).
- Targets táctiles de **44×44 px** garantizados en radios, checkboxes y sliders.
- Dark mode persiste entre sesiones y no rompe la legibilidad de campos de texto.

### Identificación y trazabilidad sin inventar datos
- **Participant ID** y **Session ID** automáticos (anonimizados, persistidos durante la sesión).
- **Fingerprint clínico liviano opcional:** captura de browser, OS y resolución solo si lo activás en la pestaña Privacy & Metadata; pensado para tablets compartidas.
- Timestamps precisos (inicio/fin, duración en milisegundos) y eventos clave (`view`, `start`, `page_change`, `submit`, `abandon`, `branch_jump`).
- Dashboard con privacidad por defecto: IP opcional, datos de navegador/OS/pantalla desactivados hasta que el equipo clínico lo habilite explícitamente.
- **Validación clínica:** usamos el tiempo (objetivo, medible) y la completación de campos en lugar de indicadores algorítmicos subjetivos o "quality flags" automáticos que generan ruido.

### Panel “Results & Experience”
- Tres pestañas consolidadas:
  1. **Submissions:** tabla paginada con filtros por formulario, exportación directa a Excel/CSV y detalle de sesión.
  2. **Completion Message:** editor rich-text para el mensaje de agradecimiento, logo opcional y control del botón “Comenzar de nuevo”.
  3. **Privacy & Metadata:** toggles granulares para cada metadato y recordatorio de retenciones.
- Todo ocurre en WordPress, sin paneles externos ni dependencias SaaS.

### Base de datos y exportaciones
- Tablas clínicas (`wp_vas_form_results` y `wp_vas_form_events`) con índices preparados para auditoría.
- Sincronización/auto-reparación del esquema cada 24 h (Hotfix 1.2.2) para garantizar **Zero Data Loss** incluso si WordPress se actualiza.
- Exportación inmediata a **Excel (XLSX)** y **CSV UTF-8** con todas las respuestas y metadatos (el investigador es la única fuente de verdad para validar la calidad).

## Novedades clínicas post tickets 1–7
- **Submissions & Finalización v1:** página de agradecimiento integrada en la misma URL, con botón “Comenzar de nuevo” y distinción entre finalización global y por formulario.
- **VAS clínico v1.1:** alineación editor/frontend idéntica, valor 100 ubicado en el extremo derecho y soporte condicional consistente con radios y checkboxes.
- **UX del Form Container:** se reorganizaron paneles de navegación, finalización, mapa condicional y apariencia para editar formularios largos sin perderte.
- **Plantillas demo EIPSI:** demos genéricas listas para cargar desde el contenedor y personalizar en minutos.
- **Lógica AND/OR v1.1:** combinación de reglas en vivo para RADIO, CHECKBOX, VAS, LIKERT y SELECT dentro de la misma página.
- **Fingerprint liviano + Privacy & Metadata:** metadata opcional para tablets compartidas, configurable por formulario.
- **Separador seguro `;` para opciones:** evita errores cuando las respuestas contienen comas o descripciones largas, manteniendo compatibilidad con formularios legacy.

## Requisitos técnicos

| Contexto | Requisito |
| --- | --- |
| WordPress | 5.8 o superior, con Gutenberg activo |
| PHP | 7.4+ (recomendado 8.1+) con extensiones mysqli y mbstring |
| Base de datos | MySQL/MariaDB con permisos para crear/alterar tablas |
| Servidor | HTTPS recomendado, capacidad para ejecutar WP-Cron (auto-reparación) |
| Desarrollo | Node.js 18.x LTS o 20.x, npm 9/10, `@wordpress/scripts` incluido en el repo |

## Instalación y actualización segura

1. **Descargá o generá** el ZIP del plugin (`eipsi-forms.zip`).
2. En WordPress: `Plugins → Añadir nuevo → Subir plugin` y seleccioná el ZIP. Activá EIPSI Forms.
3. Tras la activación, el plugin valida y repara automáticamente las tablas necesarias.
4. Para actualizar:
   - Probalo primero en un entorno de staging con los mismos formularios clínicos.
   - En producción, desactivá la versión anterior, subí el nuevo ZIP y reactivá.
   - Borrá cachés (Hostinger, plugins de cacheo, Cloudflare) para evitar assets antiguos.
5. Si WordPress marca columnas faltantes, basta con entrar a cualquier pantalla del admin: la auto-reparación se ejecuta en menos de 1 segundo sin afectar formularios activos.

## Uso básico en consultorio/investigación

1. **Crear formulario:** abrí el editor de Gutenberg en la página deseada y añadí el bloque “EIPSI Form Container”.
2. **Agregar páginas y campos:** dentro del contenedor agregá bloques “Página” y los campos clínicos necesarios.
3. **Configurar lógica:** en cada campo activá las reglas condicionales o saltos de página que necesites.
4. **Diseño y modo oscuro:** elegí un preset y probá el toggle dark mode directamente en el editor.
5. **Publicar y probar:** abrí el formulario desde la misma tablet que usás en sala y hacé un envío completo.
6. **Revisar resultados:** WordPress → EIPSI Forms → Results & Experience → pestaña “Submissions”.
7. **Exportar o limpiar:** desde la misma pantalla descargás Excel/CSV o eliminás envíos específicos.

## Hardening y verificación continua

- `npm run lint:js` → 0 errores / 0 warnings (estado actual de `main`).
- `npm run build` → bundle < 250 KB, build < 5 s.
- `npm audit` → sin vulnerabilidades conocidas (enero 2025); cualquier excepción queda documentada en el repo.
- Scripts de verificación (`scripts/verify-build.*`) automatizan instalación, build y chequeo de artefactos antes de cada entrega clínica.
- Auto-reparación de esquema activada por defecto (capa al activar, capa en carga diaria, capa en cada `submit` fallido).

## Code Quality - Duplicate Function Detection

EIPSI Forms incluye un sistema automático de detección de funciones duplicadas para mantener la calidad del código.

### Uso

```bash
npm run lint:duplicates
```

### Características

- Detecta funciones duplicadas en PHP y JavaScript
- Muestra ubicación exacta (archivo y línea)
- Se ejecuta en < 2 segundos
- Integración con el flujo de trabajo

### Ejemplo de Output

```
✓ Verificación de funciones duplicadas completada
  PHP: 127 funciones encontradas
  JavaScript: 89 funciones encontradas
  ✅ Sin duplicados detectados
```

O si hay duplicados:

```
✗ Funciones duplicadas detectadas:

  📍 eipsi_check_manual_assignment (PHP)
     - admin/ajax-handlers.php:45
     - admin/randomization-shortcode-handler.php:120

  📍 eipsi_get_randomizations (PHP)
     - admin/rct-analytics-api.php:10
     - includes/randomization-api.php:22

Ejecuta: npm run lint:duplicates para más detalles
```

## Alcances y límites actuales

Para evitar falsas expectativas:

- **Plantillas clínicas oficiales con un clic (PHQ-9, GAD-7, PCL-5, AUDIT, DASS-21):** el diseño de bloques y lógica condicional están listos, pero **todavía no hay botón "crear PHQ-9" ni scoring automático** en producción. Las plantillas **demo EIPSI** sí están disponibles como ejemplos genéricos desde el dropdown del Form Container.
- **Save & Continue Later / autosave de 30 s / drafts en IndexedDB:** en desarrollo.
- **Condicional required dentro de la misma página (campos que se vuelven obligatorios según respuesta):** diseño en curso.
- **Dashboard gráfico de analytics:** tracking ya guarda los eventos, pero la UI aún no está disponible.
- **Integración nativa con Elementor, APIs externas, webhooks y cifrado de campos:** planificado, sin fecha.
- **Importar/exportar formularios en JSON:** actualmente se hace duplicando páginas/bloques desde Gutenberg.

## Notas clínicas y filosofía

EIPSI Forms nace de sesiones reales con pacientes que se levantan en medio de la entrevista, tablets que se quedan sin batería y equipos de investigación que no pueden perder ni un dato. Cada decisión técnica prioriza:
- Formularios que se entienden al primer toque.
- Oscuro/claro según lo prefiera cada consultorio.
- Reparación automática ante cualquier riesgo de pérdida de datos.
- Configuraciones de privacidad que respetan el principio de mínima información necesaria.

Si algo te hace fruncir el ceño a vos o a tu paciente, es un bug para nosotros.

## Disclaimer clínico

EIPSI Forms es una herramienta para capturar datos. No provee diagnóstico, tratamiento ni reemplaza criterio clínico. El uso del plugin no constituye consejo médico y cada institución sigue siendo responsable de sus protocolos de consentimiento, almacenamiento seguro y comunicación de resultados.

## Licencia y soporte

- **Licencia:** GPL v2 o posterior.
- **Soporte y reporte de bugs:** `support@eipsi.research`
- **Issues públicos:** [GitHub Issues](https://github.com/roofkat/VAS-dinamico-mvp/issues)

¿Dudas sobre tu implementación clínica? Escribinos antes de la próxima sesión; preferimos prevenir que explicar una pérdida de datos.
