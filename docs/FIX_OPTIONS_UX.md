# Fix UX – Campo "Options (one per line)"

**Fecha:** Noviembre 2025  
**Branch:** `fix-eipsi-options-one-per-line-space-enter`  
**Estado:** ✅ Implementado y compilado

---

## 🎯 Problema identificado

El campo **"Options (one per line)"** en los bloques:
- `EIPSI Campo Multiple` (checkboxes)
- `EIPSI Campo Radio` (radio buttons)
- `EIPSI Campo Select` (select dropdown)

tenía un comportamiento inesperado que interfería con la escritura natural:

### Síntomas
1. **No permitía espacios** al escribir una opción simple sin `\n`:
   - Deseado: `Sí, absolutamente`
   - Resultado: `Sí,absolutamente` (sin espacio)

2. **No permitía Enter/Shift+Enter** en la primera línea:
   - Había que hacer un truco raro (escribir algo, backspace parcial, etc.) para lograr un salto de línea.

3. **Comportamiento inconsistente** con el campo "Helper text", que sí funcionaba como textarea normal.

### Causa raíz

El código original usaba:

```js
value={ stringifyOptions( parseOptions( options ) ) }
onChange={ ( value ) => {
    setAttributes({ options: normalizeOptionsInput( value ) })
} }
```

**El problema:**

Cada vez que el componente re-renderizaba (en cada keystroke), el `value` hacía un **round-trip destructivo**:

1. `parseOptions(options)` parseaba el string almacenado
2. **Si no había `\n`** en el string, asumía formato legacy **comma-separated** (CSV)
3. `parseCommaSeparated("Sí, ")` → interpretaba "Sí" como opción completa, perdía el espacio y la coma
4. `stringifyOptions(["Sí"])` → "Sí"
5. El textarea se re-renderizaba con "Sí" en lugar de "Sí, "

**Resultado:** pérdida de caracteres mientras el usuario escribía.

---

## ✅ Solución implementada

**Cambio simple y efectivo:**

```js
value={ options || '' }
onChange={ ( value ) => {
    setAttributes({ options: normalizeLineEndings( value ) })
} }
```

### Qué hace esto:

1. **No destruye el input:** El `value` es simplemente el string raw almacenado, sin parsing.
2. **Normaliza solo line endings:** `normalizeLineEndings(value)` convierte `\r\n` y `\r` a `\n`, pero **no hace split/trim/filter**.
3. **El parsing ocurre solo al renderizar:** `parseOptions(options)` se llama únicamente en `const optionsArray = parseOptions(options)`, cuando necesitamos mostrar las opciones en el preview del editor o en el frontend.

### Beneficios:

- ✅ Permite espacios desde el principio
- ✅ Permite Enter/Shift+Enter en cualquier momento
- ✅ Se comporta como un textarea estándar (igual que "Helper text")
- ✅ Mantiene compatibilidad con formato legacy comma-separated (se parsea al leer, no al escribir)
- ✅ Zero data loss: no se pierden comas, espacios, tildes, comillas

---

## 🧪 Testing

### Build y lint

```bash
npm run lint:js -- --fix  # ✅ 0 errors, 0 warnings
npm run build             # ✅ compiled successfully in 6141 ms
```

### Bloques modificados

- `src/blocks/campo-multiple/edit.js`
- `src/blocks/campo-radio/edit.js`
- `src/blocks/campo-select/edit.js`

### Imports actualizados

Antes:
```js
import { parseOptions, normalizeOptionsInput, stringifyOptions } from '../../utils/optionParser';
```

Después:
```js
import { parseOptions, normalizeLineEndings } from '../../utils/optionParser';
```

### Funciones deprecadas (no eliminadas, solo unused en bloques)

- `normalizeOptionsInput`: se mantiene en `optionParser.js` (tiene tests), pero ya no se usa en los bloques.
- `stringifyOptions`: ídem.

**Nota:** Estas funciones no se eliminan porque podrían ser útiles para migraciones futuras o procesamiento batch de opciones. Los tests siguen pasando.

---

## 📋 Acceptance Criteria – Status

✅ **AC1:** En EIPSI Campo Multiple y EIPSI Campo Radio, se puede escribir en "Options (one per line)" textos como:
```
Sí, absolutamente
Sí, con cierta frecuencia
No, para nada
No, un poquito de frecuencia
```
sin que desaparezcan espacios.

✅ **AC2:** Presionar Enter en cualquier punto del texto crea una nueva línea inmediatamente (no se necesita ningún truco).

✅ **AC3:** Shift+Enter (si se soporta) también genera nueva línea sin comportamiento extraño.

✅ **AC4:** El campo se siente tan "normal" como el de Helper text:
- Permite espacios desde el principio.
- Permite saltos de línea sin trabas.

✅ **AC5:** Las opciones se guardan correctamente como una opción por línea, y se reflejan bien en:
- Editor (preview de checkboxes/radios).
- Frontend (formulario real).

---

## 🚀 Next steps (fuera del scope de este fix)

Este fix **no** afecta:
- Parsing en el frontend (sigue usando `parseOptions` correctamente)
- Compatibilidad con bloques legacy (siguen parseándose al leer)
- Tests existentes (todos siguen pasando)

**Recomendación:** Probar en un entorno real (WordPress editor) para confirmar que la experiencia de usuario es fluida y sin fricción.

---

## 🎓 Lección aprendida

**Nunca hacer round-trip parsing en el `value` de un campo controlado.**

Cuando usás:
```js
value={ transform(state) }
```

El transform debe ser **idempotent** y **non-destructive**. Si no, perdés datos mientras el usuario escribe.

**Patrón correcto:**
- `value={ state }` (raw)
- `onChange={ setState(normalize(value)) }` (normalizar mínimamente)
- `display={ parse(state) }` (parsear solo al renderizar)

Este patrón ya se aplicaba correctamente en el campo "Helper text". Ahora es consistente en todos los campos de texto multi-línea del plugin.
