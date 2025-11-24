# QA CHECKLIST – REPARACIÓN CONTADOR Y LÓGICA CONDICIONAL

**Archivo**: `assets/js/eipsi-forms.js`  
**Cambios**: 6 modificaciones críticas para eliminar "4 de 2" y hacer thank-you un estado final  
**Fecha**: 2025-01-24

---

## ANTES DE MERGEAR A MAIN

### ✅ Verificación técnica local

- [x] `npm run lint:js` → 0 errors / 0 warnings
- [x] `npm run build` → éxito en < 5s
- [x] Bundle size: 88.5 KB (sin cambios significativos vs versión anterior)

---

## DESPUÉS DE MERGEAR Y DESPLEGAR

### 1. FORMULARIO DE PRUEBA: 3 páginas + thank-you block

Crear o usar un formulario existente con:
- 3 páginas con preguntas (sin lógica condicional)
- 1 bloque "Thank You Page" al final

#### Caso 1: Navegación lineal normal

1. **Página 1**:
   - [ ] Contador dice: "**1 de 3**"
   - [ ] Botones visibles: solo **Siguiente**
   - [ ] NO aparecen: Anterior, Enviar

2. **Página 2**:
   - [ ] Contador dice: "**2 de 3**"
   - [ ] Botones visibles: **Anterior** + **Siguiente**
   - [ ] NO aparece: Enviar

3. **Página 3**:
   - [ ] Contador dice: "**3 de 3**"
   - [ ] Botones visibles:
     - **Anterior** (si allowBackwardsNav = true)
     - **Enviar**
   - [ ] NO aparece: Siguiente

4. **Clic en Enviar**:
   - [ ] Se muestra la página de gracias **en la misma URL** (sin redirección).
   - [ ] NO se ve el contador "4 de 3" ni "3 de 3" en la thank-you.
   - [ ] El contador y los botones de navegación desaparecen completamente.

5. **Consola del navegador** (F12 → Console):
   - [ ] NO aparece `[EIPSI] CURRENT PAGE OUT OF BOUNDS`

6. **DevTools → Console → EIPSIForms**:
   - [ ] `form.dataset.formStatus === 'completed'`

---

### 2. FORMULARIO CON LÓGICA CONDICIONAL: salto a thank-you

Crear un formulario con:
- Página 1: Radio con opciones A, B, C
  - Regla condicional: si selecciona "C" → `jump_to_page` hacia la **thank-you page**

#### Caso 2: Intento de saltar a thank-you desde lógica condicional

1. **Página 1**:
   - [ ] Seleccionar opción "C" (la que tiene regla condicional a thank-you)

2. **Clic en Siguiente**:
   - [ ] NO navega a una "página 4" ni muestra error.
   - [ ] Ejecuta el **submit del formulario** automáticamente (como si hubiera llegado a la última página y clickeado Enviar).

3. **Resultado**:
   - [ ] Se muestra la **thank-you page** en la misma URL.
   - [ ] `form.dataset.formStatus === 'completed'`

---

### 3. FORMULARIO CON LÓGICA CONDICIONAL: saltos entre páginas válidas

Crear un formulario con:
- Página 1: Radio A, B
  - Si A → salta a página 3 (sin pasar por página 2)
  - Si B → página 2 normal
- Página 2: campo de texto
- Página 3: última pregunta
- Thank-you page

#### Caso 3: Salto de página 1 → página 3

1. **Página 1**:
   - [ ] Contador: "**1 de 3**"
   - [ ] Seleccionar opción "A" (salta a página 3)

2. **Clic en Siguiente**:
   - [ ] Va directamente a página 3
   - [ ] Contador: "**3 de 3**" (NO "2 de 3", NO "3 de 2")

3. **Página 3**:
   - [ ] Botones visibles: Enviar (y posiblemente Anterior si allowBackwardsNav = true)
   - [ ] NO aparece: Siguiente

4. **Clic en Enviar**:
   - [ ] Thank-you en la misma URL
   - [ ] `formStatus === 'completed'`

#### Caso 4: Navegación lineal página 1 → página 2 → página 3

1. **Página 1**:
   - [ ] Seleccionar opción "B" (no salta)

2. **Clic en Siguiente**:
   - [ ] Va a página 2
   - [ ] Contador: "**2 de 3**"

3. **Página 2**:
   - [ ] Contador: "**2 de 3**"
   - [ ] Botones: Anterior + Siguiente

4. **Clic en Siguiente**:
   - [ ] Va a página 3
   - [ ] Contador: "**3 de 3**"

5. **Clic en Enviar**:
   - [ ] Thank-you en la misma URL
   - [ ] `formStatus === 'completed'`

---

### 4. ANALÍTICA / TRACKING (si FullStory o similar está activo)

1. **Durante el llenado del formulario**:
   - [ ] Se envían eventos con `currentPage: 1`, `totalPages: 3`
   - [ ] Se envían eventos con `currentPage: 2`, `totalPages: 3`
   - [ ] Se envían eventos con `currentPage: 3`, `totalPages: 3`

2. **Al completar**:
   - [ ] Se envía evento con `currentPage: 'completed'` (o similar, según configuración)
   - [ ] NO se envía `currentPage: 4` ni ningún número fuera de rango

---

## ERRORES QUE DEBEN HABER DESAPARECIDO COMPLETAMENTE

### ❌ Nunca debe aparecer:

- [ ] "4 de 2"
- [ ] "4 de 3"
- [ ] "3 de 2"
- [ ] Cualquier numeración donde `currentPage > totalPages`
- [ ] Contador con asterisco tipo "3*"
- [ ] Tooltip "Estimado basado en tu ruta actual"

### ❌ Nunca debe ocurrir:

- [ ] Navegar a la thank-you page como si fuera una página numerada
- [ ] Ver "Anterior", "Siguiente" y "Enviar" a la vez
- [ ] Ver el contador de páginas en la thank-you page

---

## CRITERIO DE APROBACIÓN FINAL

✅ **Aprobado si**:
1. El contador siempre muestra valores coherentes (currentPage ≤ totalPages).
2. La thank-you page nunca es navegable con lógica condicional (siempre se llega vía submit).
3. `formStatus === 'completed'` al mostrar la thank-you.
4. No hay errores en consola del navegador.

❌ **Rechazado si**:
1. Aparece "X de Y" donde X > Y.
2. Aparecen contadores con asterisco tipo "3*".
3. Se puede navegar a la thank-you desde una regla condicional (sin pasar por submit).

---

## NOTAS CLÍNICAS

Este fix elimina **completamente** la posibilidad de que un psicólogo clínico vea:

> "Espera… dice '4 de 2'… ¿esto está funcionando bien?"

Y en su lugar garantiza:

> "El contador siempre tiene sentido. La página de gracias es digna, no una página numerada más."

Es **Zero fear + Zero friction + Zero excuses**.

---

**FIN DEL CHECKLIST**
