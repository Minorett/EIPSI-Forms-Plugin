# Ejemplos de JSON LITE - EIPSI Forms

En esta guía encontrarás ejemplos listos para copiar y pegar, desde lo más básico hasta estructuras clínicas reales.

## 1. Ejemplo Mínimo Funcional
Este es el "Hola Mundo" de EIPSI Forms. Contiene una sola página con un campo de texto.

```json
{
  "schemaVersion": "1.0.0",
  "form": {
    "title": "Formulario Mínimo",
    "formId": "form_minimo",
    "blocks": [
      {
        "blockName": "eipsi/form-container",
        "attrs": { "formId": "form_minimo" },
        "innerBlocks": [
          {
            "blockName": "eipsi/form-page",
            "attrs": { "title": "Inicio", "pageIndex": 0 },
            "innerBlocks": [
              {
                "blockName": "eipsi/campo-texto",
                "attrs": {
                  "fieldName": "nombre_paciente",
                  "label": "Nombre del Paciente",
                  "required": true
                }
              }
            ]
          }
        ]
      }
    ]
  }
}
```

---

## 2. Ejemplo Clínico: Tamizaje de Ansiedad (GAD-2)
Un ejemplo realista que utiliza escalas Likert y páginas separadas.

```json
{
  "schemaVersion": "1.0.0",
  "form": {
    "title": "Tamizaje Rápido GAD-2",
    "formId": "gad2_quick",
    "blocks": [
      {
        "blockName": "eipsi/form-container",
        "attrs": { "formId": "gad2_quick" },
        "innerBlocks": [
          {
            "blockName": "eipsi/form-page",
            "attrs": { "title": "Consentimiento", "pageIndex": 0 },
            "innerBlocks": [
              {
                "blockName": "eipsi/consent-block",
                "attrs": {
                  "titulo": "Consentimiento para Evaluación",
                  "contenido": "Los datos recolectados se utilizarán exclusivamente para fines clínicos.",
                  "mostrarCheckbox": true,
                  "etiquetaCheckbox": "He leído y acepto",
                  "isRequired": true
                }
              }
            ]
          },
          {
            "blockName": "eipsi/form-page",
            "attrs": { "title": "Síntomas", "pageIndex": 1 },
            "innerBlocks": [
              {
                "blockName": "eipsi/campo-descripcion",
                "attrs": {
                  "label": "Durante las últimas 2 semanas...",
                  "helperText": "Responda según su experiencia reciente."
                }
              },
              {
                "blockName": "eipsi/campo-likert",
                "attrs": {
                  "fieldName": "gad_1",
                  "label": "¿Se ha sentido nervioso, ansioso o con los nervios de punta?",
                  "labels": "Nunca;Varios días;Más de la mitad de los días;Casi todos los días",
                  "required": true
                }
              },
              {
                "blockName": "eipsi/campo-likert",
                "attrs": {
                  "fieldName": "gad_2",
                  "label": "¿No ha sido capaz de parar o controlar su preocupación?",
                  "labels": "Nunca;Varios días;Más de la mitad de los días;Casi todos los días",
                  "required": true
                }
              }
            ]
          }
        ]
      }
    ]
  }
}
```

---

## 3. Ejemplo con VAS Slider y Select
Ideal para medir intensidad de síntomas y categorías.

```json
{
  "schemaVersion": "1.0.0",
  "form": {
    "title": "Registro de Malestar",
    "formId": "vas_record",
    "blocks": [
      {
        "blockName": "eipsi/form-container",
        "attrs": { "formId": "vas_record" },
        "innerBlocks": [
          {
            "blockName": "eipsi/form-page",
            "attrs": { "title": "Evaluación", "pageIndex": 0 },
            "innerBlocks": [
              {
                "blockName": "eipsi/vas-slider",
                "attrs": {
                  "fieldName": "intensidad_malestar",
                  "label": "Indique su nivel de malestar en este momento:",
                  "labels": "Ninguno;Extremo",
                  "showCurrentValue": true
                }
              },
              {
                "blockName": "eipsi/campo-select",
                "attrs": {
                  "fieldName": "emocion_dominante",
                  "label": "Emoción predominante:",
                  "options": ["Tristeza", "Enojo", "Miedo", "Alegría", "Neutral"],
                  "required": true
                }
              }
            ]
          }
        ]
      }
    ]
  }
}
```

## Tips Pro:
1. **Atributos booleanos**: Asegúrate de que `true` y `false` vayan sin comillas.
2. **Strings multilínea**: Si quieres un salto de línea en una descripción, usa `\n`.
3. **Identificadores**: El `fieldName` debe ser único dentro de un mismo formulario para evitar colisión de datos.
