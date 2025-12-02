# JSON Export/Import – EIPSI Forms

## Formato simplificado para demos clínicas (v1.3.0)

Desde la versión 1.3.0, EIPSI Forms soporta **dos formatos de exportación JSON**:

### 1. **Formato simplificado** (recomendado) 

**Ideal para:**
- Editar plantillas a mano
- Crear demos rápidas para investigadores
- Compartir plantillas entre equipos
- Control de versiones (Git/GitHub)

**Estructura:**
```json
{
  "schemaVersion": "1.0.0",
  "meta": {
    "exportedAt": "2025-02-05T10:30:00+00:00",
    "exportedBy": "Usuario",
    "pluginVersion": "1.3.0",
    "formTitle": "Cuestionario PHQ-9",
    "formName": "phq9_demo"
  },
  "form": {
    "title": "Cuestionario PHQ-9",
    "formId": "phq9_demo",
    "blocks": [
      {
        "blockName": "vas-dinamico/form-container",
        "attrs": {
          "formId": "phq9_demo"
        },
        "innerBlocks": [
          {
            "blockName": "vas-dinamico/form-page",
            "attrs": {
              "pageIndex": 1
            },
            "innerBlocks": [
              {
                "blockName": "vas-dinamico/campo-informativo",
                "attrs": {
                  "fieldKey": "intro",
                  "title": "Introducción",
                  "content": "Este cuestionario evalúa síntomas depresivos."
                },
                "innerBlocks": []
              }
            ]
          }
        ]
      }
    ]
  },
  "metadata": {
    "_eipsi_form_name": "phq9_demo"
  }
}
```

**Ventajas:**
- ✅ Editable a mano sin errores
- ✅ Solo incluye configuración clínicamente relevante
- ✅ Archivos ~60% más pequeños
- ✅ Se regenera automáticamente al importar

### 2. **Formato completo**

**Ideal para:**
- Backups completos con HTML generado
- Migración exacta entre sitios
- Máxima compatibilidad con versiones antiguas

**Incluye:**
- `postContent`: HTML Gutenberg crudo con comentarios `<!-- wp:... -->`
- `innerHTML` / `innerContent`: Fragmentos HTML renderizados
- `formContainerAttrs`: Atributos del contenedor

**Desventaja:** Difícil de editar a mano (cualquier cambio puede romper el HTML).

---

## Cómo exportar

1. Ve a **EIPSI Forms → Form Library**
2. Hacé clic en **"Exportar JSON"** debajo del formulario
3. Seleccioná:
   - **✨ Formato simplificado** (recomendado para demos/plantillas)
   - **Formato completo** (para backups exactos)
4. El archivo se descarga automáticamente

---

## Cómo importar

1. Ve a **EIPSI Forms → Form Library**
2. Hacé clic en **"⬆ Importar formulario"**
3. Seleccioná tu archivo `.json`
4. El sistema detecta automáticamente el formato y lo importa

**Comportamiento:**
- **Formato simplificado**: Regenera el HTML automáticamente
- **Formato completo**: Restaura el HTML tal cual

---

## Edición manual (formato simplificado)

### Ejemplo: Cambiar el título de un campo

**Antes:**
```json
{
  "blockName": "vas-dinamico/campo-texto",
  "attrs": {
    "fieldName": "nombre",
    "label": "Nombre completo",
    "required": true
  },
  "innerBlocks": []
}
```

**Después:**
```json
{
  "blockName": "vas-dinamico/campo-texto",
  "attrs": {
    "fieldName": "nombre_paciente",
    "label": "Nombre del paciente",
    "required": false
  },
  "innerBlocks": []
}
```

### Ejemplo: Agregar un campo nuevo

Insertá un bloque nuevo dentro de `innerBlocks` de una página:

```json
{
  "blockName": "vas-dinamico/campo-radio",
  "attrs": {
    "fieldName": "genero",
    "label": "Género",
    "options": "Masculino; Femenino; Otro; Prefiero no decir",
    "required": true
  },
  "innerBlocks": []
}
```

---

## Tipos de bloques disponibles

| **Bloque** | **blockName** | **Atributos clave** |
|------------|---------------|---------------------|
| Form Container | `vas-dinamico/form-container` | `formId` |
| Página | `vas-dinamico/form-page` | `pageIndex` |
| Texto | `vas-dinamico/campo-texto` | `fieldName`, `label`, `required` |
| Textarea | `vas-dinamico/campo-textarea` | `fieldName`, `label`, `required` |
| Select | `vas-dinamico/campo-select` | `fieldName`, `label`, `options`, `required` |
| Radio | `vas-dinamico/campo-radio` | `fieldName`, `label`, `options`, `required` |
| Checkbox | `vas-dinamico/campo-multiple` | `fieldName`, `label`, `options`, `required` |
| Likert | `vas-dinamico/campo-likert` | `fieldName`, `label`, `options`, `required` |
| VAS Slider | `vas-dinamico/vas-slider` | `fieldName`, `label`, `minValue`, `maxValue`, `step` |
| Info | `vas-dinamico/campo-informativo` | `title`, `content` |

---

## Solución de problemas

### Error: "El archivo JSON no tiene un esquema válido"

**Causa:** Falta `schemaVersion` o `form.title`.

**Solución:**
```json
{
  "schemaVersion": "1.0.0",
  "form": {
    "title": "Mi formulario",
    "formId": "mi_form",
    "blocks": [...]
  }
}
```

### Error: "El archivo JSON no tiene bloques válidos"

**Causa:** Falta `form.blocks` o está vacío.

**Solución:**
```json
{
  "form": {
    "blocks": [
      {
        "blockName": "vas-dinamico/form-container",
        "attrs": { ... },
        "innerBlocks": [...]
      }
    ]
  }
}
```

### El formulario se importa vacío

**Causa:** Formato completo sin `postContent`.

**Solución:** Usá formato simplificado (sin `postContent`, solo `blocks`).

---

## Compatibilidad

- ✅ **Formato simplificado** importa en v1.3.0+
- ✅ **Formato completo** importa en todas las versiones
- ✅ Formularios exportados en versiones anteriores siguen funcionando

---

## Preguntas frecuentes

### ¿Puedo mezclar ambos formatos?

No. Si incluís `postContent`, el importador ignora los cambios manuales en `blocks` y usa directamente el HTML.

### ¿El formato simplificado pierde información?

No. Toda la configuración clínica (campos, validación, conditional logic, etc.) se preserva perfectamente.

Solo se omite el HTML generado (comentarios `<!-- wp:... -->` y fragmentos renderizados), que se regenera automáticamente al importar.

### ¿Qué pasa con los formularios VAS ya existentes?

Nada cambia. El `labelAlignmentPercent` del VAS Slider siempre fue independiente del valor del paciente. Ahora está mejor documentado y comentado en el código.

---

**¿Más dudas?** Consultá el código en `admin/form-library-tools.php` o abrí un issue en GitHub.
