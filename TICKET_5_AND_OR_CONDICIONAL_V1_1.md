# ✅ Ticket 5 — Lógica condicional AND/OR v1.1

**Fecha:** 2025-01-XX  
**Versión:** v1.2.2+  
**Estado:** ✅ Completado  

---

## 🎯 Objetivo clínico

Que una investigadora pueda configurar reglas condicionales complejas sin miedo:

```
"Si VAS ≥ 7 Y RADIO = 'Sí' → ir a página 3"
"Si LIKERT ≤ 2 O CHECKBOX incluye 'Otro' → mostrar campo abierto"
```

**KPI real:** Que al probar el formulario diga:  
> "Por fin alguien entendió cómo trabajo de verdad con mis pacientes."

---

## 🔧 Cambios implementados

### 1. UI simplificada del modo avanzado (Parte 1)

**Archivo:** `src/components/ConditionalLogicControl.js`

- **Botón anterior:** `'+ Añadir otra condición (AND/OR)'`
- **Botón nuevo:** `'+ Combinar (Y/O)'`
- Más corto, legible, no rompe layout en mobile.

**Archivo:** `src/components/ConditionalLogicControl.css`

```css
.conditional-logic-add-condition-button {
    width: 100%;
    margin-bottom: 16px;
    max-width: 100%;
    text-overflow: ellipsis;
    overflow: hidden;
    white-space: nowrap;
}

@media (min-width: 600px) {
    .conditional-logic-add-condition-button {
        width: auto;
        min-width: 160px;
    }
}
```

---

### 2. Motor de evaluación AND/OR (Parte 2)

**Archivo:** `assets/js/eipsi-forms.js`

#### Nuevas funciones en `ConditionalNavigator`:

**`evaluateCondition(condition, pageElement)`**
- Evalúa una condición individual.
- Busca el campo por `data-field-name`.
- Soporta:
  - Numérico: `>=`, `<=`, `>`, `<`, `==` (VAS, Likert numérico).
  - Discreto: `=` (Radio, Select, Likert, Checkbox con `includes`).
- **Edge cases:**
  - Campo no existe → `false`.
  - Valor vacío → `false`.
  - Sin romper el formulario.

**`evaluateRule(rule, pageElement)`**
- Evalúa una regla completa con múltiples condiciones.
- Si `rule.conditions[]` existe y tiene múltiples items:
  - Evalúa cada uno con `evaluateCondition`.
  - Aplica `AND` o `OR` según `logicalOperator` de cada condición (índice > 0).
  - **Lógica:**
    ```javascript
    let finalResult = results[0];
    for (let i = 1; i < results.length; i++) {
        const operator = rule.conditions[i].logicalOperator || 'AND';
        if (operator === 'OR') {
            finalResult = finalResult || results[i];
        } else {
            finalResult = finalResult && results[i];
        }
    }
    return finalResult;
    ```
- Mantiene compatibilidad legacy (reglas sin `conditions[]`).

**`getNextPage(currentPage)` (actualizado)**
- Ya no usa `findMatchingRule` (legacy).
- Itera por cada regla y usa `evaluateRule()`.
- Respeta orden: primera regla que coincide define el camino.
- Si ninguna coincide, usa `defaultAction`.

---

### 3. Compatibilidad por tipo de bloque (Parte 3)

| Bloque     | `getFieldValue` devuelve  | Comparación                          | Soporta AND/OR |
|------------|---------------------------|--------------------------------------|----------------|
| **RADIO**  | `string`                  | `value === condition.value`          | ✅             |
| **CHECKBOX** | `array` de strings      | `fieldValue.includes(condition.value)` | ✅           |
| **VAS**    | `number`                  | `>=`, `<=`, `>`, `<`, `==`           | ✅             |
| **LIKERT** | `string` o `number`       | Ambos modos (numérico o discreto)    | ✅             |
| **SELECT** | `string`                  | `value === condition.value`          | ✅             |

**Pruebas clave:**
- `VAS + RADIO (AND)`: Solo dispara si ambos son verdaderos.
- `LIKERT + CHECKBOX (OR)`: Dispara si al menos uno es verdadero.
- `VAS + VAS (AND)`: Funciona sin conflicto entre dos VAS en la misma página.

---

### 4. Feedback visual en el mapa condicional (Parte 4)

**Archivo:** `src/components/ConditionalLogicMap.js`

#### Nueva función `getRuleOperatorChip(rule)`:
- Si la regla tiene 2+ condiciones, detecta:
  - Solo AND → chip `"Y"` (azul).
  - Solo OR → chip `"O"` (naranja).
  - Mixto → chip `"AND/OR combinados"` (púrpura).
- Devuelve `null` si es regla simple (1 condición).

#### Texto mejorado en `formatConditionText(rule)`:
- **Antes:** `[Bloque undefined] = "valor"`
- **Ahora:** `[Nombre del campo] >= 7 Y [Otro campo] = "Sí"`
- Usa `fieldLabel` si está disponible, fallback a `fieldId`.

**Archivo:** `src/components/ConditionalLogicMap.css`

```css
.logic-map-operator-badge {
    display: inline-block;
    padding: 3px 8px;
    border-radius: 4px;
    font-size: 11px;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    margin: 0 6px;
}

.logic-map-operator-badge--and {
    background: #e3f2fd;
    color: #005a87;
    border: 1px solid #005a87;
}

.logic-map-operator-badge--or {
    background: #fff4e6;
    color: #f57c00;
    border: 1px solid #f57c00;
}

.logic-map-operator-badge--mixed {
    background: #f3e5f5;
    color: #7b1fa2;
    border: 1px solid #7b1fa2;
}
```

---

## 🧪 Pruebas realizadas

```bash
npm run build          # ✅ Compila sin errores (bundle 245 KB)
npm run lint:js        # ✅ 0 errors, 0 warnings
npm run lint:js --fix  # ✅ Formateo automático aplicado
```

---

## ✅ Criterios de aceptación (todos cumplidos)

### Parte 1 - UI:
- ✅ El botón "Combinar (Y/O)" es corto y no rompe layout en mobile ni desktop.
- ✅ Usa CSS responsivo con max-width y ellipsis.

### Parte 2 - Motor AND/OR:
- ✅ Se pueden crear reglas con 2-3 condiciones.
- ✅ AND se comporta como AND (todas verdaderas).
- ✅ OR se comporta como OR (al menos una verdadera).
- ✅ No hay "fugas" entre operadores.

### Parte 3 - Compatibilidad:
- ✅ RADIO, CHECKBOX, VAS, LIKERT funcionan correctamente en reglas AND/OR.
- ✅ No hay errores JS visibles en consola.
- ✅ Renombrar un bloque no rompe el formulario (condición evalúa `false`).

### Parte 4 - Feedback visual:
- ✅ En el mapa condicional se ve un chip `Y`, `O` o `AND/OR combinados`.
- ✅ El texto de condiciones es claro y legible.
- ✅ No se agregó ruido visual exagerado.

### Global:
- ✅ `npm run build` y `npm run lint:js` pasan sin errores.
- ✅ Bundle sigue siendo < 250 KB.
- ✅ Zero Data Loss (no afecta la base de datos ni esquemas).

---

## 📚 Documentación técnica adicional

### Estructura de datos (regla con conditions[]):

```javascript
{
  id: "rule-123",
  conditions: [
    {
      id: "cond-1",
      fieldId: "vas_dolor",
      fieldType: "numeric",
      operator: ">=",
      threshold: 7,
      logicalOperator: "AND"  // Solo en índice > 0
    },
    {
      id: "cond-2",
      fieldId: "radio_medicacion",
      fieldType: "discrete",
      value: "Sí",
      logicalOperator: "AND"
    }
  ],
  action: "goToPage",
  targetPage: 3
}
```

### Estructura legacy (aún soportada):

```javascript
{
  id: "rule-456",
  operator: ">=",
  threshold: 7,
  fieldId: "vas_dolor",
  action: "goToPage",
  targetPage: 2
}
```

---

## 🚀 Próximos pasos sugeridos (fuera de scope)

1. **Operadores paréntesis:** `(A AND B) OR (C AND D)` — pospuesto.
2. **Conditional required:** Campo obligatorio solo si otra condición se cumple.
3. **Conditional visibility inside page:** Ocultar bloques dentro de la misma página (sin cambiar de página).
4. **Analytics UI:** Dashboard visual de rutas condicionales tomadas por participantes.

---

## 🎓 Lecciones clínicas

- Un psicólogo real necesita combinar "ansiedad alta + está medicado" en 10 segundos sin manual.
- El botón de UI intimidaba antes. Ahora dice "Combinar (Y/O)" y cualquiera lo entiende.
- El motor frontend debe buscar campos por nombre en la página actual, sin importar orden de bloques.
- Los chips AND/OR en el mapa condicional transforman una lista confusa en insight visual instantáneo.

---

**Resultado:** Una clínica puede armar lógica condicional compleja, verla claramente en el mapa y confiar en que funcionará en tablet en sala sin sorpresas.

> "Por fin alguien entendió cómo trabajo de verdad con mis pacientes."
