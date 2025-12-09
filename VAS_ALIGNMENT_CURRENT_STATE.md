# VAS Label Alignment - Estado Actual (Post-Reset)

**Fecha:** Febrero 2025
**Contexto:** Restauración de UI limpia y análisis de CSS vigente.

## Arquitectura del Código

### HTML Structure (Renderizado)
El bloque renderiza la siguiente estructura (simplificada):

```html
<div class="vas-slider-container" style="--vas-label-alignment: [0-1]; ...">
    <div class="vas-multi-labels">
        <span class="vas-multi-label vas-multi-label--first">Label 1</span>
        <span class="vas-multi-label">Label 2</span>
        <span class="vas-multi-label vas-multi-label--last">Label 3</span>
    </div>
    <input type="range" class="vas-slider" ... />
</div>
```

### CSS Aplicado (`assets/css/eipsi-forms.css`)

El posicionamiento depende CRÍTICAMENTE de estas reglas (Líneas ~1167+):

1.  **Contenedor (`.vas-multi-labels`)**: `position: relative`.
2.  **Labels (`.vas-multi-label`)**: `position: absolute`.
3.  **Primer Label (`--first`)**:
    *   `left: calc((1 - var(--vas-label-alignment, 0.5)) * 50%)`
    *   `transform: translateX(-100%)`
    *   `text-align: left`
    *   `padding-left: 0` (Intento de fix de padding)
4.  **Último Label (`--last`)**:
    *   `right: calc((1 - var(--vas-label-alignment, 0.5)) * 50%)`
    *   `transform: translateX(100%)`
    *   `text-align: right`
    *   `padding-right: 0` (Intento de fix de padding)

### JavaScript (`edit.js`)

*   **Atributo:** `labelAlignmentPercent` (Entero 0-100).
*   **Cálculo:** `alignmentRatio = labelAlignmentPercent / 100`.
*   **Variable CSS:** Se pasa al estilo inline como `--vas-label-alignment: alignmentRatio`.

## Comportamiento Matemático Actual

### En Alignment = 0 (Compactas)
*   **Ratio:** 0
*   **Cálculo Left:** `(1 - 0) * 50%` = `50%`.
*   **Posición:** El label empieza en el 50% del contenedor.
*   **Transform:** `translateX(-100%)` lo mueve a la izquierda su propio ancho.
*   **Resultado Visual:** Los labels están agrupados cerca del centro (no compactos hacia adentro, sino centrados).

### En Alignment = 50 (Medio)
*   **Ratio:** 0.5
*   **Cálculo Left:** `(1 - 0.5) * 50%` = `25%`.
*   **Posición:** Empieza en el 25% del ancho.
*   **Transform:** `translateX(-100%)`.
*   **Resultado Visual:** Equidistante.

### En Alignment = 100 (Bien marcadas - Extremos)
*   **Ratio:** 1
*   **Cálculo Left:** `(1 - 1) * 50%` = `0%`.
*   **Posición:** `left: 0`.
*   **Transform:** `translateX(-100%)`.
*   **Resultado Visual CRÍTICO:** El label se mueve **completamente fuera del contenedor hacia la izquierda**.
    *   Si el contenedor tiene `overflow: hidden` (o el viewport termina ahí), el texto se corta.
    *   El texto NO toca el extremo interior del slider, sino que se aleja de él hacia afuera.

## Problemas Detectados (Análisis Estático)

1.  **Desbordamiento en Alignment 100:** La combinación de `left: 0` y `translateX(-100%)` es matemáticamente incorrecta para alinear "adentro" del borde izquierdo. Mueve el elemento *antes* del borde izquierdo.
    *   *Corrección lógica futura:* Debería ser `transform: translateX(0)` si `left: 0` y queremos alineación izquierda estándar, O BIEN calcular la posición sin transform negativo en el extremo.

2.  **Inconsistencia UI Editor vs Frontend:**
    *   El editor ahora usa `RangeControl` (0-100).
    *   El CSS soporta la matemática, pero la matemática produce un resultado visual "expulsado".

## Qué FUNCIONA
*   ✅ **Editor UI:** Restaurado a slider simple 0-100.
*   ✅ **Sistema de Variables:** La tubería JS -> CSS Variables funciona.
*   ✅ **Arquitectura:** El uso de `position: absolute` es más robusto que flexbox para evitar saltos, pero la fórmula de posición necesita ajuste.

## Qué NO funciona
*   ❌ **Alineación Visual Extrema:** En 100%, los labels probablemente se ven cortados o demasiado separados del slider.

## Archivos Involucrados
*   `/src/blocks/vas-slider/edit.js`: Lógica de control (Restaurada).
*   `/assets/css/eipsi-forms.css`: Lógica visual (Requiere revisión matemática).
