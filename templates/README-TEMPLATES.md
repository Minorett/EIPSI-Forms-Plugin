# EIPSI Forms - Templates Clínicos

**Plugin:** EIPSI Forms v1.2.2  
**Última actualización:** 2025-01-10

---

## Templates Disponibles

### 1. Template Profesional de Burnout v2.0 ✅ VALIDADO

**Archivo:** `template-burnout-clinical-assessment.json`  
**Estado:** ✅ Validado 100% contra block.json v1.2.2  
**Categoría:** Clinical Assessment  
**Tiempo estimado:** 15-20 minutos

#### Descripción
Evaluación clínica completa del síndrome de burnout en profesionales sanitarios. Incluye escalas validadas internacionalmente (PHQ-9, GAD-7, MBI-HSS) con consentimiento informado profesional.

#### Escalas Incluidas
- **PHQ-9 (Patient Health Questionnaire-9):** Escala de depresión, 9 ítems, escala 0-3
- **GAD-7 (Generalized Anxiety Disorder-7):** Escala de ansiedad, 7 ítems, escala 0-3
- **MBI-HSS (Maslach Burnout Inventory - Human Services Survey):** Escala de burnout, 10 ítems representativos, escala 0-6

#### Estructura

**Página 1: Consentimiento Informado**
- Descripción del estudio
- Consentimiento informado completo con:
  - Objetivo del estudio
  - Procedimiento
  - Confidencialidad y protección de datos
  - Derechos y beneficios
  - Riesgos mínimos
  - Información de contacto
  - Aprobación comité de ética

**Página 2: Datos Demográficos y Profesionales**
- Profesión sanitaria (11 opciones)
- Años de experiencia (6 rangos)
- Tipo de institución (8 opciones)
- Horas semanales (5 rangos)
- Situación laboral (5 opciones)
- Edad (campo numérico)
- Género (opcional, 4 opciones)

**Página 3: PHQ-9 - Escala de Depresión**
- 9 ítems validados
- Escala 0-3 (Para nada → Casi todos los días)
- Ítem 9 con alerta de suicidalidad

**Página 4: GAD-7 - Escala de Ansiedad**
- 7 ítems validados
- Escala 0-3 (Para nada → Casi todos los días)

**Página 5: MBI-HSS - Escala de Burnout**
- 10 ítems representativos (muestreo completo disponible en template extendido)
- Escala 0-6 (Nunca → Diariamente)
- Subescalas: Agotamiento emocional, Despersonalización, Logros personales

#### Características Técnicas
- ✅ Preset: "Clinical Blue" (azul profesional #1a4d6d)
- ✅ Progress bar activada
- ✅ Navegación hacia atrás permitida
- ✅ Página de completación personalizada
- ✅ Captura de timing por página
- ✅ Todos los campos con fieldKey único
- ✅ Helper text clínico relevante
- ✅ Paleta de colores médica completa

#### Cómo Usar Este Template

##### Opción 1: Importar desde el Editor (Futuro)
```
1. WordPress → Páginas → Agregar nueva
2. Agregar bloque "EIPSI Form Container"
3. Panel lateral → "Cargar Template"
4. Seleccionar "Evaluación Integral del Síndrome de Burnout"
5. Personalizar según necesidades
```

##### Opción 2: Manual (Actual)
```
1. Descargar template-burnout-clinical-assessment.json
2. Copiar estructura de bloques
3. Recrear en editor de Gutenberg
4. Ajustar textos y configuración según necesidad
```

##### Opción 3: Programático
```php
// Cargar template desde código PHP
$template_path = plugin_dir_path(__FILE__) . 
                 'templates/template-burnout-clinical-assessment.json';
$template_json = file_get_contents($template_path);
$template_data = json_decode($template_json, true);

// Acceder a bloques
$form_blocks = $template_data['form']['blocks'];
```

#### Personalización Recomendada

**Antes de usar en producción:**
1. ✏️ Actualizar información de contacto en consentimiento
2. ✏️ Reemplazar "[Nombre de la Institución]" en texto de comité de ética
3. ✏️ Ajustar logo de completación (completionLogoUrl)
4. ✏️ Personalizar mensaje de agradecimiento final
5. ✏️ Revisar opciones de profesiones según población objetivo
6. ✏️ Configurar panel "Privacy & Metadata" según regulaciones locales

**Opcional:**
- Agregar/remover ítems del MBI-HSS según necesidad
- Modificar colores en styleConfig
- Ajustar helper texts según población
- Agregar campos demográficos adicionales

#### Scoring y Análisis

⚠️ **IMPORTANTE:** Este template NO incluye scoring automático. Los datos se exportan en crudo y el análisis debe realizarse manualmente o con software estadístico externo.

**Puntos de corte sugeridos (según literatura):**

**PHQ-9:**
- 0-4: Mínima depresión
- 5-9: Depresión leve
- 10-14: Depresión moderada
- 15-19: Depresión moderadamente severa
- 20-27: Depresión severa

**GAD-7:**
- 0-4: Ansiedad mínima
- 5-9: Ansiedad leve
- 10-14: Ansiedad moderada
- 15-21: Ansiedad severa

**MBI-HSS:**
- Alto agotamiento emocional: ≥27
- Alta despersonalización: ≥10
- Baja realización personal: ≤33

---

### 2. Template de Estrés y Bienestar ⚠️ DEMO

**Archivo:** `template-stress-wellness-questionnaire.json`  
**Estado:** Demo genérico (sin validación completa)  
**Categoría:** Wellness Assessment

Plantilla demo para evaluación de estrés y bienestar. No incluye escalas clínicas validadas. Uso solo para ejemplificación.

---

## Validación de Templates

Todos los templates en este directorio deben cumplir con:

✅ **Validación JSON:** Formato JSON válido parseado sin errores  
✅ **Validación contra block.json:** Todos los atributos existen en los block.json del plugin  
✅ **Atributos únicos:** Cada fieldKey y fieldName es único dentro del formulario  
✅ **Escalas correctas:** minValue/maxValue y labels coinciden con cantidad de opciones  
✅ **Separadores:** Opciones separadas por semicolon (`;`)  
✅ **Estructura:** Jerarquía correcta de innerBlocks  

Ver `VALIDATION-BURNOUT-TEMPLATE.md` para detalles técnicos completos.

---

## Compatibilidad

| Plugin Version | Template Version | Status |
|---------------|------------------|---------|
| v1.2.2 | Burnout v2.0 | ✅ Compatible |
| v1.2.1 | Burnout v2.0 | ✅ Compatible |
| v1.2.0 | Burnout v2.0 | ✅ Compatible |
| v1.1.x | Burnout v2.0 | ⚠️ Revisar atributos |
| < v1.1 | Burnout v2.0 | ❌ No compatible |

---

## Cómo Crear Nuevos Templates

### 1. Crear formulario en Gutenberg
1. Diseñar formulario completo con todos los bloques
2. Probar en frontend
3. Verificar que todos los campos guardan correctamente

### 2. Exportar estructura
```php
// Obtener bloques del post
$post_content = get_post_field('post_content', $post_id);
$blocks = parse_blocks($post_content);

// Convertir a estructura de template
$template = [
    'schemaVersion' => '1.0.0',
    'meta' => [
        'exportedAt' => current_time('c'),
        'pluginVersion' => EIPSI_FORMS_VERSION,
        'formTitle' => 'Título del formulario',
        'formName' => 'form_slug'
    ],
    'form' => [
        'title' => 'Título del formulario',
        'formId' => 'form_slug',
        'blocks' => $blocks
    ]
];

// Guardar como JSON
file_put_contents(
    'template-nombre.json', 
    json_encode($template, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)
);
```

### 3. Validar template
```bash
# Validar JSON
python3 -m json.tool template-nombre.json > /dev/null

# Verificar atributos manualmente contra block.json
# Ver lista completa en VALIDATION-BURNOUT-TEMPLATE.md
```

### 4. Documentar
- Agregar entrada en este README
- Crear archivo VALIDATION-[nombre].md con detalles técnicos
- Actualizar CHANGELOG.md

---

## Licencia

Todos los templates incluidos están bajo licencia GPL v2 o posterior, igual que el plugin EIPSI Forms.

---

## Contacto

**Soporte técnico:** support@eipsi.research  
**Issues públicos:** [GitHub Issues](https://github.com/roofkat/VAS-dinamico-mvp/issues)

---

**Última actualización:** 2025-01-10  
**Versión del documento:** 1.0
