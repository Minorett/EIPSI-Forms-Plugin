# Historial del Problema de Alineación VAS (VAS Alignment Problem History)

**Fecha de compilación:** 2025 (Febrero)
**Objetivo:** Consolidar todos los intentos previos para entender por qué seguimos aquí.

## Línea de Tiempo de Intentos

### 1. El Enfoque Flexbox Original (Pre-Febrero 2025)
*   **Método:** `display: flex; justify-content: space-between;`
*   **Problema:** Dividía el espacio en zonas iguales (33% cada una). Los labels se centraban en su zona, nunca tocando los extremos.
*   **Resultado Clínico:** Ambigüedad visual. El paciente no sabía si "Nada" era 0 o 0-33.

### 2. VAS Alignment Rethink (Intento "Absolute")
*   **Documentación:** `VAS_ALIGNMENT_RETHINK.md`
*   **Cambio Arquitectónico:** Se abandonó flexbox por `position: absolute`.
*   **Fórmula:** `left: calc((1 - ratio) * 50%)`.
*   **Lógica:**
    *   Primer label: `left: 0`, `transform: translateX(-100%)`.
    *   Último label: `right: 0`, `transform: translateX(100%)`.
*   **Resultado:** Funcionalmente mejor para alineación teórica, pero introdujo complejidad en el cálculo de "dónde termina el texto realmente".

### 3. El UI "Precise Value" (El desvío)
*   **Documentación:** `VAS_RETHINK_VERIFICATION.md`
*   **Cambio UI:** Se eliminó el slider visual 0-100 del editor para permitir valores >100 (ej: 150, 200).
*   **Razón:** Para empujar los labels "más allá" de los bordes si era necesario.
*   **Resultado:** UI confusa para el psicólogo. Input numérico opaco sin feedback visual inmediato.
*   **Estado:** **REVERTIDO** en este ticket (vuelta al slider 0-100).

### 4. La Auditoría de Padding (El último hallazgo)
*   **Documentación:** `VAS_LABELS_AUDIT_FINAL_SUMMARY.md`
*   **Hipótesis:** No era solo posición, sino `padding` dentro del label lo que impedía tocar el borde.
*   **Fix Intentado:** `padding-left: 0` para el primero, `padding-right: 0` para el último.
*   **Estado Actual:** El código CSS contiene estas reglas, pero combinadas con el `transform` agresivo, pueden estar sacando el texto del viewport visible o del contenedor.

## Documentos Analizados

### Conservar (Contienen lógica técnica válida)
*   `VAS_ALIGNMENT_RETHINK.md`: Explica la matemática del absolute positioning. Útil para referencia futura.
*   `VAS_LABELS_AUDIT_FINAL_SUMMARY.md`: Buen análisis del padding.

### Descartar / Obsoletos (Basura)
*   `VAS_RETHINK_VERIFICATION.md`: Describe un UI que ya no existe (el input 0-200).
*   `VAS_LABELS_FIX_VALIDATION.html` (y similares): Tests estáticos que no reflejan la realidad dinámica.
*   `VAS_LABELS_EDGE_PADDING_FIX.md`: Integrado en la historia general.

## Conclusión Histórica
Hemos oscilado entre "control simple pero impreciso" (Flexbox) y "control matemático complejo pero UI roto" (Absolute + Input manual). El objetivo actual es **Control Simple (UI 0-100) + Matemática Correcta (Absolute)**.
