# Ticket 7: Migración a punto y coma (;) como separador de opciones

## ✅ Completado

**Fecha:** 2025-01-XX  
**Versión:** v1.3 (propuesta)

---

## Objetivo clínico

Que un psicólogo pueda escribir opciones que **contienen comas** sin que el sistema se rompa:

```
Ansioso, inquieto; Tranquilo, relajado; Neutral, sin cambios
```

En lugar de:
```
Ansioso, inquieto
Tranquilo, relajado
Neutral, sin cambios
```

o el formato legacy problemático:
```
"Ansioso, inquieto","Tranquilo, relajado","Neutral, sin cambios"
```

---

## Cambios implementados

### 1. **Formato nuevo estándar: punto y coma (`;`)**

- **Separador oficial:** `;` (con o sin espacio adicional)
- **Ventajas:**
  - Permite comas dentro de cada opción
  - Más legible en una sola línea que múltiples líneas
  - Compatible con escritura rápida en textarea

### 2. **Compatibilidad total hacia atrás**

El parser detecta automáticamente el formato y lo interpreta correctamente:

**Prioridad de detección:**
1. **Si contiene `;`** → usa `;` como separador (nuevo estándar v1.3+)
2. **Si contiene `\n`** → usa newline como separador (formato v1.2)
3. **Si contiene `,`** → usa coma con parsing CSV (formato legacy v1.0–v1.1)

Esto garantiza:
- ✅ Formularios antiguos siguen funcionando sin cambios
- ✅ No hay pérdida de datos
- ✅ Lógica condicional que dependía de valores antiguos sigue activa

### 3. **Bloques actualizados**

#### **Bloques con opciones (ya usaban `parseOptions`):**
- ✅ `campo-radio` (Radio)
- ✅ `campo-multiple` (Checkbox)
- ✅ `campo-select` (Select)

**Cambio:** textos de ayuda actualizados a "Separá las opciones con punto y coma (;)"

#### **Bloques con labels (migrados a `parseOptions`):**
- ✅ `campo-likert` (Likert Scale)
- ✅ `vas-slider` (VAS Slider)

**Cambio:** ahora usan `parseOptions` en lugar de `.split(',')` manual

### 4. **Archivos modificados**

```
src/utils/optionParser.js              ← Lógica principal de parsing
src/utils/optionParser.test.js         ← Tests actualizados + nuevos tests
src/blocks/campo-radio/edit.js         ← Texto de ayuda actualizado
src/blocks/campo-multiple/edit.js      ← Texto de ayuda actualizado
src/blocks/campo-select/edit.js        ← Texto de ayuda actualizado
src/blocks/campo-likert/edit.js        ← Migrado a parseOptions + texto actualizado
src/blocks/campo-likert/save.js        ← Migrado a parseOptions
src/blocks/vas-slider/edit.js          ← Migrado a parseOptions + texto actualizado
src/blocks/vas-slider/save.js          ← Migrado a parseOptions
```

---

## Ejemplos de uso

### Radio / Checkbox / Select

**Editor (nuevo estándar):**
```
Sí, absolutamente; Sí, pero no tan frecuente; No, no ocurre a menudo; Nunca
```

**Resultado:**
- Opción 1: `Sí, absolutamente`
- Opción 2: `Sí, pero no tan frecuente`
- Opción 3: `No, no ocurre a menudo`
- Opción 4: `Nunca`

### Likert Scale

**Editor (nuevo estándar):**
```
Nada; Poco; Moderado; Bastante; Mucho
```

**Resultado:**
- 1 – Nada
- 2 – Poco
- 3 – Moderado
- 4 – Bastante
- 5 – Mucho

### VAS Slider

**Editor (nuevo estándar):**
```
Nada de dolor; Dolor leve; Dolor moderado; Dolor severo; Dolor insoportable
```

**Resultado:**
Etiquetas distribuidas uniformemente en el slider con esos textos.

---

## Tests

Se agregaron tests específicos para validar:

✅ Parsing con `;` como separador nuevo  
✅ Compatibilidad con formato newline (v1.2)  
✅ Compatibilidad con formato comma/CSV (legacy)  
✅ Redondeo completo: `parse → stringify → parse` preserva opciones  
✅ Conversión automática de formatos legacy al nuevo estándar  

---

## Build & Lint

✅ `npm run build` → sin errores  
✅ `npm run lint:js` → 0 errores, 0 warnings  
✅ Bundle size: 245 KB (dentro del límite aceptable)  

---

## Criterios de aceptación

### ✅ **Criterio 1: Nuevo estándar funciona correctamente**

Si el clínico escribe:
```
Sí; No; No estoy seguro
```
→ El sistema muestra **3 opciones** correctas, en ese orden.

### ✅ **Criterio 2: Formularios antiguos NO se rompen**

Formularios creados antes del cambio:
- Con opciones en formato newline → siguen funcionando
- Con opciones en formato comma legacy → siguen funcionando
- Lógica condicional que dependía de esas opciones → sigue activa

### ✅ **Criterio 3: Textos actualizados**

En ningún lugar de la UI se le dice al usuario que use comas como separador.  
Todos los ejemplos y placeholders usan `;`.

### ✅ **Criterio 4: Zero Data Loss**

No hay pérdida de opciones, reordenamientos inesperados ni cambios en valores guardados.

---

## Notas clínicas

**¿Por qué `;` y no newline?**

1. **Compacidad:** escribir `Opción 1; Opción 2; Opción 3` en una línea es más rápido que saltos de línea.
2. **Visibilidad:** en un textarea pequeño, una línea es más fácil de editar que scroll vertical.
3. **Consistencia:** `;` es un separador reconocible sin conflicto con puntuación natural (comas, puntos, comillas).

**Migración real en clínica:**

- Un investigador abre un formulario viejo → sigue viendo todo igual.
- Si edita y agrega una opción nueva → puede usar `;` de inmediato sin romper nada.
- El sistema convierte todo automáticamente al formato nuevo sin intervención manual.

---

## Para versiones futuras

**Consideración:**  
Si en el futuro se quiere hacer una **migración interna agresiva** (convertir todos los formularios existentes al formato `;`), se puede implementar un script que:
1. Lee todas las opciones de bloques Radio/Checkbox/Select/Likert/VAS.
2. Parsea con `parseOptions`.
3. Re-guarda con `stringifyOptions` (que ahora usa `;`).
4. Actualiza la base de datos.

**Pero por ahora NO es necesario** porque la compatibilidad es 100% transparente.

---

## Conclusión

✅ **El sistema ahora soporta opciones con comas internas** sin romper nada.  
✅ **Formularios antiguos siguen funcionando** sin modificaciones.  
✅ **Textos de ayuda actualizados** para guiar a los usuarios al nuevo estándar.  
✅ **Tests completos** que validan todos los escenarios.  
✅ **Build limpio** y sin errores de lint.

**Estado:** **Listo para merge y testing clínico real.**
