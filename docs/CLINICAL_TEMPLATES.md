# Plantillas Oficiales EIPSI - Escalas Clínicas Validadas

## Descripción

EIPSI Forms incluye 5 plantillas oficiales de escalas clínicas validadas en español, listas para usar en investigación y práctica clínica:

1. **PHQ-9** - Patient Health Questionnaire-9
2. **GAD-7** - Generalized Anxiety Disorder-7
3. **PCL-5** - PTSD Checklist for DSM-5
4. **AUDIT** - Alcohol Use Disorders Identification Test
5. **DASS-21** - Depression, Anxiety and Stress Scale - 21

---

## ¿Dónde encontrarlas?

En el panel de administración de WordPress:

```
EIPSI Forms → Form Library
```

Arriba de la lista de formularios verás la sección **"Plantillas oficiales EIPSI"** con tarjetas visuales para cada escala.

---

## Flujo de trabajo

### 1. Crear un formulario desde plantilla

1. Ir a **EIPSI Forms → Form Library**
2. En la sección de plantillas oficiales, hacer clic en **"Crear formulario"** en la escala deseada
3. El sistema creará automáticamente un nuevo formulario editable en tu librería
4. Serás redirigido al editor para revisar o ajustar el formulario si es necesario

### 2. Personalizar el formulario (opcional)

Podés editar el formulario creado desde la plantilla:

- Cambiar el título
- Ajustar textos de las preguntas (si tu población requiere adaptaciones)
- Modificar el preset de diseño o el modo oscuro
- Configurar la página de finalización
- Agregar campos adicionales (datos demográficos, contexto clínico, etc.)

**Importante**: Si modificás las preguntas o escalas de respuesta, recordá que podrías afectar la validez del instrumento. Consulta con tu equipo de investigación antes de alterar contenidos validados.

### 3. Usar el formulario en páginas/entradas

Una vez creado y (opcionalmente) personalizado:

- Copiá el **shortcode** desde la lista de Form Library
- O insertá el bloque **"Formulario EIPSI"** en cualquier página y seleccioná el formulario desde el dropdown

---

## Características de cada plantilla

### PHQ-9

- **Versión**: Adaptación hispanohablante validada
- **Ítems**: 9 preguntas
- **Escala**: 0 = Nunca, 1 = Varios días, 2 = Más de la mitad de los días, 3 = Casi todos los días
- **Ventana temporal**: Últimas 2 semanas
- **Paginación**: 1 página única
- **Botón "Anterior"**: Desactivado por defecto (allowBackwardsNav: false)

**Aplicación clínica**:  
Tamizaje de síntomas depresivos según criterios DSM-5. Ideal para screening inicial y seguimiento longitudinal de tratamiento.

---

### GAD-7

- **Versión**: Versión en español validada
- **Ítems**: 7 preguntas
- **Escala**: 0 = Nunca, 1 = Varios días, 2 = Más de la mitad de los días, 3 = Casi todos los días
- **Ventana temporal**: Últimas 2 semanas
- **Paginación**: 1 página única
- **Botón "Anterior"**: Desactivado por defecto

**Aplicación clínica**:  
Evaluación rápida de síntomas de ansiedad generalizada. Ampliamente usado en atención primaria e investigación clínica.

---

### PCL-5

- **Versión**: Versión latina autorizada
- **Ítems**: 20 preguntas
- **Escala**: 0 = Nada, 1 = Un poco, 2 = Moderadamente, 3 = Bastante, 4 = Extremadamente
- **Ventana temporal**: Último mes
- **Paginación**: 2 páginas (10 ítems por página)
- **Botón "Anterior"**: Activado por defecto (allowBackwardsNav: true)

**Aplicación clínica**:  
Checklist de trastorno de estrés postraumático según DSM-5. Útil en población expuesta a eventos traumáticos.

---

### AUDIT

- **Versión**: Traducción oficial OMS en español
- **Ítems**: 10 preguntas
- **Escala**: Variable según ítem (0-4 en la mayoría, 0/2/4 en dos ítems)
- **Ventana temporal**: Último año (mayoría de preguntas)
- **Paginación**: 1 página única
- **Botón "Anterior"**: Desactivado por defecto

**Aplicación clínica**:  
Test de identificación de trastornos por consumo de alcohol. Estándar internacional de la OMS para detección temprana de consumo riesgoso.

---

### DASS-21

- **Versión**: Versión en español (Bados, 2010)
- **Ítems**: 21 preguntas (7 por subescala: depresión, ansiedad, estrés)
- **Escala**: 0 = No me aplicó en absoluto, 1 = Me aplicó un poco o durante parte del tiempo, 2 = Me aplicó bastante o durante buena parte del tiempo, 3 = Me aplicó mucho o la mayor parte del tiempo
- **Ventana temporal**: Última semana
- **Paginación**: 2 páginas (11+10 ítems)
- **Botón "Anterior"**: Activado por defecto

**Aplicación clínica**:  
Evaluación multidimensional de estados emocionales negativos. Permite discriminar perfiles sintomáticos en población clínica y comunitaria.

---

## Tracking de respuestas

Los formularios creados desde plantillas clínicas se integran automáticamente con el sistema de tracking de EIPSI Forms:

- **Participant ID**: Generado automáticamente (anónimo)
- **Session ID**: Persistente durante la sesión del paciente
- **Quality Flag**: Evaluación automática de calidad de completación
- **Timestamps**: Inicio, fin y duración precisa en milisegundos
- **Exportación**: Excel/CSV directo desde la pestaña "Submissions"

---

## Consideraciones éticas y clínicas

### Validez de contenido

Las plantillas oficiales respetan las versiones validadas en español de cada instrumento. Si vas a modificar textos o escalas:

1. Consultá con tu equipo de investigación
2. Documentá los cambios realizados
3. Considerá que podrías afectar las propiedades psicométricas del instrumento

### Consentimiento informado

Estos formularios **no incluyen** cláusulas de consentimiento informado. Es responsabilidad del equipo clínico:

- Informar al participante sobre el uso de sus datos
- Obtener consentimiento explícito antes de la aplicación
- Garantizar anonimato o confidencialidad según lo prometido

### Interpretación de resultados

EIPSI Forms captura las respuestas pero **no provee scoring automático ni interpretación clínica** en esta versión (1.3.0).

Para interpretar resultados:

- Exportá las respuestas a Excel/CSV
- Aplicá el scoring correspondiente según el manual de cada escala
- Interpretá los puntajes en el contexto clínico apropiado con un profesional habilitado

---

## Notas técnicas

### Nomenclatura de campos

Los campos de cada plantilla siguen este formato:

- PHQ-9: `phq9_q1`, `phq9_q2`, ..., `phq9_q9`
- GAD-7: `gad7_q1`, `gad7_q2`, ..., `gad7_q7`
- PCL-5: `pcl5_q1`, `pcl5_q2`, ..., `pcl5_q20`
- AUDIT: `audit_q1`, `audit_q2`, ..., `audit_q10`
- DASS-21: `dass21_q1`, `dass21_q2`, ..., `dass21_q21`

### Form IDs únicos

Cada formulario creado desde plantilla genera un `formId` único automáticamente (ej: `phq9-a1b2c3d4`), evitando colisiones entre múltiples instancias de la misma escala.

### Metadatos de plantilla

Cada formulario creado desde plantilla almacena dos metadatos en WordPress:

- `_eipsi_form_name`: El `formId` del form-container (para tracking de respuestas)
- `_eipsi_clinical_template`: El ID de la plantilla original (ej: `phq9`, `gad7`)

---

## Preguntas frecuentes

### ¿Puedo crear múltiples formularios de la misma escala?

Sí, podés crear tantos formularios como necesites. Por ejemplo:

- PHQ-9 para intake inicial
- PHQ-9 para seguimiento mensual
- PHQ-9 para alta terapéutica

Cada uno tendrá su propio `formId` único y rastreará respuestas por separado.

### ¿Puedo duplicar un formulario creado desde plantilla?

Sí, usá la opción **"Duplicar"** en la lista de Form Library para crear copias de cualquier formulario (incluidos los generados desde plantillas).

### ¿Las plantillas se actualizan automáticamente?

No. Cuando creás un formulario desde una plantilla, generás una **copia editable e independiente**. Futuras actualizaciones de la plantilla oficial no afectarán formularios ya creados.

### ¿Cómo exporto los resultados de una escala?

1. Ir a **EIPSI Forms → Results & Experience → Submissions**
2. Filtrar por el formulario específico usando el dropdown
3. Hacer clic en **"Export to Excel"** o **"Export to CSV"**
4. Analizar los datos en tu software estadístico preferido

### ¿Dónde encuentro las normas de interpretación para cada escala?

EIPSI Forms provee la estructura de las escalas pero no incluye interpretación clínica. Consultá:

- Manuales originales de cada instrumento
- Publicaciones de validación en tu población de interés
- Guías clínicas de tu institución o sociedad profesional

---

## Soporte y actualizaciones

Si encontrás errores en los textos de las escalas, opciones mal codificadas o problemas técnicos:

**Reportar issues:**  
support@eipsi.research  
GitHub Issues: https://github.com/roofkat/VAS-dinamico-mvp/issues

**Solicitar escalas adicionales:**  
Si necesitás otras escalas clínicas validadas (ej: BDI-II, BAI, STAI, CAGE), escribinos con:

- Nombre del instrumento
- Versión validada en español que usás
- Contexto clínico de aplicación

---

## Referencias clínicas

### PHQ-9
Kroenke, K., Spitzer, R. L., & Williams, J. B. (2001). The PHQ-9: Validity of a brief depression severity measure. *Journal of General Internal Medicine*, 16(9), 606-613.

### GAD-7
Spitzer, R. L., Kroenke, K., Williams, J. B., & Löwe, B. (2006). A brief measure for assessing generalized anxiety disorder: The GAD-7. *Archives of Internal Medicine*, 166(10), 1092-1097.

### PCL-5
Weathers, F. W., Litz, B. T., Keane, T. M., Palmieri, P. A., Marx, B. P., & Schnurr, P. P. (2013). *The PTSD Checklist for DSM-5 (PCL-5)*. National Center for PTSD.

### AUDIT
Babor, T. F., Higgins-Biddle, J. C., Saunders, J. B., & Monteiro, M. G. (1992). *AUDIT: The Alcohol Use Disorders Identification Test*. Organización Mundial de la Salud.

### DASS-21
Lovibond, P. F., & Lovibond, S. H. (1995). The structure of negative emotional states: Comparison of the Depression Anxiety Stress Scales (DASS) with the Beck Depression and Anxiety Inventories. *Behaviour Research and Therapy*, 33(3), 335-343.  
Adaptación española: Bados, A., Solanas, A., & Andrés, R. (2005). Psicothema, 17(4), 679-683.

---

**Versión de la documentación**: 1.3.0  
**Última actualización**: Febrero 2025  
**Plugin**: EIPSI Forms v1.2.2+
