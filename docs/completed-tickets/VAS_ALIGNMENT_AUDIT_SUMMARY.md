# Síntesis: VAS Label Alignment Problem - Auditoría Completa

**Fecha:** Febrero 2025
**Estado:** Lista para revisión matemática final.

## Resumen Ejecutivo
Se ha restaurado la "cordura" al bloque VAS. El editor vuelve a ser limpio y comprensible para el usuario (Slider 0-100). Se ha limpiado la basura generada por intentos fallidos de automatización. Se ha identificado la causa raíz matemática del problema visual actual.

## Estado del Repositorio
*   **Archivos Inútiles:** Eliminados (`test-*.html`, `audit-*.html`).
*   **Editor UI:** Restaurado a `RangeControl` estándar (0-100).
*   **Build:** Pasa (`npm run build`).
*   **Lint:** Pasa (`npm run lint:js`).

## Hallazgo Crítico (Causa Raíz)
El problema de alineación no es de "padding" (aunque influía), sino de **lógica de transformación CSS**.

La fórmula actual:
```css
left: calc((1 - alignment) * 50%);
transform: translateX(-100%);
```
Cuando `alignment = 1` (100%):
*   `left` se vuelve `0`.
*   `transform` mueve el elemento `-100%` de su propio ancho a la izquierda.
*   **Resultado:** El label queda posicionado enteramente a la izquierda del borde 0 del contenedor (`left: -width`).

**Para la próxima implementación:**
Se debe ajustar la fórmula CSS para que en `alignment=100`, el `transform` sea 0 (o la posición compense el ancho), asegurando que el borde izquierdo del texto coincida con el borde izquierdo del slider.

## Recomendación para Próximo Ticket
**Título:** Fix CSS Mathematics for VAS Labels Absolute Positioning
**Tarea:**
1.  Mantener el UI del editor como está (0-100).
2.  Corregir SOLO las reglas CSS `.vas-multi-label--first` y `--last` en `assets/css/eipsi-forms.css`.
3.  Objetivo matemático:
    *   Alignment 0: Centrado en 50%.
    *   Alignment 100: `left: 0` (sin transform negativo) para el primero. `right: 0` (sin transform positivo) para el último.

## Documentación Relevante Preservada
*   `VAS_ALIGNMENT_PROBLEM_HISTORY.md`: Para no repetir errores.
*   `VAS_ALIGNMENT_CURRENT_STATE.md`: La foto técnica de hoy.
