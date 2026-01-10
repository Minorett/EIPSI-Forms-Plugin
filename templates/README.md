# JSON Templates - EIPSI Forms

Este directorio contiene plantillas de formularios en formato JSON y documentación técnica para su creación, exportación e importación.

## 📋 Tabla de contenidos
1. [Introducción](#1-introducción)
2. [Estructura JSON LITE](#2-estructura-json-lite)
3. [Guía: Crear manualmente](#3-guía-crear-manualmente)
4. [Referencia de bloques](#4-referencia-de-bloques)
5. [Ejemplos completos](#5-ejemplos-completos)
6. [Validación y errores](#6-validación-y-errores)
7. [Flujo típico](#7-flujo-típico)
8. [LITE vs FULL comparación](#8-lite-vs-full-comparación)
9. [FAQ](#9-faq)

---

## 1. Introducción

EIPSI Forms permite mover formularios entre diferentes instalaciones de WordPress mediante archivos JSON. Existen dos modalidades:

- **JSON LITE**: Un formato simplificado, limpio y legible. Ideal para que clínicos compartan plantillas, realizar control de versiones en Git o editar la estructura del formulario a mano.
- **JSON FULL**: Un volcado completo que incluye el HTML generado por Gutenberg y metadatos internos. Ideal para backups exactos o migraciones donde se quiere preservar hasta el último detalle técnico.

---

## 2. Estructura JSON LITE

Un archivo LITE sigue esta estructura jerárquica:

```json
{
  "schemaVersion": "1.0.0",
  "meta": {
    "exportedAt": "2025-02-15T10:00:00Z",
    "pluginVersion": "1.2.2",
    "formTitle": "Evaluación Clínica Inicial"
  },
  "form": {
    "title": "Evaluación Clínica Inicial",
    "formId": "eval_inicial_01",
    "blocks": [
      {
        "blockName": "eipsi/form-container",
        "attrs": { "formId": "eval_inicial_01" },
        "innerBlocks": [
           // Aquí van las páginas y campos
        ]
      }
    ]
  },
  "metadata": {
    "_eipsi_form_name": "eval_inicial_01"
  }
}
```

---

## 3. Guía: Crear manualmente

### 3.1 Estructura básica de un bloque
Cada elemento del formulario es un bloque con tres propiedades fundamentales:
- `blockName`: El identificador del componente.
- `attrs`: Objeto con la configuración (etiquetas, validaciones, etc.).
- `innerBlocks`: Un array con bloques hijos (usado en contenedores y páginas).

```json
{
  "blockName": "eipsi/campo-likert",
  "attrs": {
    "fieldName": "ansiedad_frecuencia",
    "label": "¿Con qué frecuencia se siente ansioso?",
    "required": true,
    "labels": "Nunca;Raramente;A veces;Frecuentemente;Siempre"
  },
  "innerBlocks": []
}
```

---

## 4. Referencia de bloques

### `eipsi/form-container` (Obligatorio)
Es el bloque raíz que envuelve todo el formulario.
- `formId`: String único (slug) para identificar los resultados.
- `styleConfig`: (Opcional) Configuración visual.

### `eipsi/form-page`
Divide el formulario en pasos o pantallas.
- `title`: Título de la página.
- `pageIndex`: Índice numérico (0, 1, 2...).

### `eipsi/campo-descripcion`
Texto informativo o instrucciones para el paciente.
- `label`: Título o texto principal (**REQUERIDO**).
- `helperText`: Descripción secundaria o detalles.

### `eipsi/campo-likert`
Escala de opciones cerradas (Frecuencia, Acuerdo, etc.).
- `fieldName`: ID único del dato (ej: `item_01`).
- `label`: La pregunta.
- `required`: `true` o `false`.
- `labels`: Opciones separadas por `;`. Ejemplo: `"Nunca;A veces;Siempre"`.

### `eipsi/campo-texto`
Entrada de texto libre.
- `fieldName`: ID único.
- `label`: Etiqueta del campo.
- `placeholder`: Ayuda visual dentro del cuadro.
- `required`: `true` o `false`.
- `fieldType`: `"text"`, `"email"`, `"number"`, `"tel"`.

### `eipsi/vas-slider`
Escala Visual Análoga (deslizador).
- `fieldName`: ID único.
- `label`: Pregunta.
- `labels`: Extremos separados por `;`. Ejemplo: `"Nada de dolor;Máximo dolor"`.
- `showCurrentValue`: Muestra el número seleccionado (`true`/`false`).

### `eipsi/consent-block`
Bloque legal para Consentimiento Informado.
- `titulo`: Título del documento.
- `contenido`: Texto legal completo (**REQUERIDO**, soporta HTML simple).
- `mostrarCheckbox`: `true` para requerir aceptación explícita.
- `etiquetaCheckbox`: Texto junto a la casilla (ej: "Acepto los términos").
- `isRequired`: Obliga a marcar la casilla para continuar.
- `showTimestamp`: Registra la fecha y hora de aceptación (`true`/`false`).

### `eipsi/campo-select`
Desplegable de opciones (Dropdown).
- `fieldName`: ID único.
- `label`: Etiqueta.
- `options`: Array de strings. Ejemplo: `["Opción A", "Opción B"]`.
- `required`: `true` o `false`.

### `eipsi/campo-checkbox`
Casilla de verificación simple.
- `fieldName`: ID único.
- `label`: Texto junto a la casilla.
- `required`: `true` (obligatorio marcar) o `false`.

---

## 5. Ejemplos completos

Puedes encontrar ejemplos detallados en:
- [Ejemplos paso a paso (EXAMPLES.md)](./EXAMPLES.md)
- [Ejemplo mínimo funcional (example-minimal-lite.json)](./example-minimal-lite.json)

---

## 6. Validación y errores comunes

| Error | Causa | Solución |
|-------|-------|----------|
| `schemaVersion` falta | JSON incompleto o versión muy vieja | Asegúrate de incluir `"schemaVersion": "1.0.0"` en la raíz. |
| El formulario aparece vacío | `form.blocks` no tiene el contenedor | Los campos deben estar dentro de `eipsi/form-container`. |
| Las opciones del Likert se ven mal | Separador incorrecto | Usa `;` estrictamente. Ej: `"Mal;Bien"` |
| Los saltos de línea desaparecen | Escapado incorrecto | Usa `\n` para saltos de línea dentro de strings. |
| No se puede importar | JSON mal formado | Valida tu archivo en [jsonlint.com](https://jsonlint.com). |

---

## 7. Flujo típico

1. **Crear**: Diseña tu formulario en el editor visual de WordPress.
2. **Exportar**: En la Form Library, elige "Exportar JSON" -> "✨ Formato simplificado".
3. **Compartir**: Envía el archivo `.json` resultante a otros profesionales.
4. **Importar**: El colega sube el archivo mediante el botón "Importar formulario" en su propia instancia.
5. **Personalizar**: Una vez importado, el formulario es totalmente editable.

---

## 8. LITE vs FULL comparación

| Aspecto | LITE (Simplificado) | FULL (Completo) |
|--------|---------------------|-----------------|
| **Legibilidad** | Alta (Hecho para humanos) | Baja (Hecho para máquinas) |
| **Tamaño de archivo** | Muy pequeño (~10 KB) | Mediano/Grande (~200 KB) |
| **Gutenberg HTML** | Se genera al importar | Ya viene incluido |
| **Personalización** | Muy fácil de editar en Bloc de notas | Muy difícil de editar a mano |
| **Uso ideal** | Plantillas, Demos, GitHub | Backups, Migración de servidor |

---

## 9. FAQ

**¿Puedo usar HTML en los campos de descripción?**
Sí, el sistema intentará parsear etiquetas básicas como `<b>`, `<i>` o `<p>` dentro de los atributos de texto.

**¿Qué pasa si olvido el `fieldKey`?**
No te preocupes. EIPSI Forms genera automáticamente identificadores internos únicos si no los provees en el JSON.

**¿Funciona entre diferentes versiones del plugin?**
El formato LITE es el más robusto para cambios de versión, ya que se basa en la estructura lógica y no en el HTML específico de una versión.
