# Guía Manual - Fases 2 y 3 del Save & Continue

## Archivo a editar: `src/frontend/eipsi-save-continue.js`

---

## PASO 1: Agregar Circuit Breaker (Fix 7)

**Ubicación:** Después de la línea 36 (después del `EXCLUDED_FIELDS` y antes de `class EIPSISaveContinue`)

**Copiar y pegar este código:**

```javascript
/**
 * Fix 7: Circuit Breaker Pattern
 * Protege contra fallos de red consecutivos
 */
class EipsiCircuitBreaker {
	constructor(threshold = 5, timeout = 60000) {
		this.failures = 0;
		this.threshold = threshold;
		this.timeout = timeout;
		this.state = 'CLOSED';
		this.nextAttempt = null;
	}

	async execute(fn) {
		if (this.state === 'OPEN') {
			if (Date.now() < this.nextAttempt) {
				throw new Error('Circuit OPEN');
			}
			this.state = 'HALF_OPEN';
		}
		try {
			const result = await fn();
			this.onSuccess();
			return result;
		} catch (error) {
			this.onFailure();
			throw error;
		}
	}

	onSuccess() {
		this.failures = 0;
		this.state = 'CLOSED';
	}

	onFailure() {
		this.failures++;
		if (this.failures >= this.threshold) {
			this.state = 'OPEN';
			this.nextAttempt = Date.now() + this.timeout;
			console.warn('[EIPSI] Circuit breaker ABIERTO');
		}
	}

	isOpen() {
		return this.state === 'OPEN';
	}
}
```

---

## PASO 2: Agregar propiedades al constructor

**Ubicación:** Dentro del `constructor()` de `EIPSISaveContinue`, después de la línea 56 (después de `this.lastSavedChecksum = null;`)

**Agregar estas 2 líneas:**

```javascript
		// Fix 7: Circuit breaker
		this.circuitBreaker = new EipsiCircuitBreaker(5, 60000);
		// Fix 9: Indicador visual
		this.saveIndicator = null;
```

---

## PASO 3: Modificar collectResponses() para dirty tracking

**Ubicación:** Buscar el método `collectResponses()` (debería estar cerca de la línea 1100-1150)

**Reemplazar TODO el método por:**

```javascript
	collectResponses(fullScan = false) {
		const responses = {};

		if (fullScan || this.dirtyFields.size === 0) {
			// Scan completo — para beforeunload y primer guardado
			const formData = new FormData(this.form);
			formData.forEach((value, key) => {
				if (EXCLUDED_FIELDS.has(key)) return;
				if (value instanceof File) return;
				responses[key] = value;
			});
		} else {
			// Solo campos modificados (Fix 5: dirty tracking)
			this.dirtyFields.forEach((fieldName) => {
				const field = this.form.querySelector(`[name="${fieldName}"]`);
				if (field) {
					responses[fieldName] = field.value;
				}
			});
		}

		return responses;
	}
```

---

## PASO 4: Modificar saveToServer() para usar circuit breaker

**Ubicación:** Buscar el método `saveToServer()` 

**Al inicio del método, agregar:**

```javascript
async saveToServer(responses, pageIndex) {
	// Fix 7: Circuit breaker check
	if (this.circuitBreaker.isOpen()) {
		console.warn('[EIPSI] Circuit breaker abierto — solo guardando local');
		this.showSaveErrorIndicator();
		return false;
	}

	return this.circuitBreaker.execute(async () => {
		// ... resto del código actual del método ...
		// (dejar todo lo que ya está dentro de este método)
	});
}
```

---

## PASO 5: Agregar métodos de indicador visual

**Ubicación:** Al final de la clase `EIPSISaveContinue`, antes del cierre `}`

**Agregar estos métodos:**

```javascript
	// Fix 9: Indicadores visuales de guardado
	showSavingIndicator() {
		if (!this.saveIndicator) {
			this.createSaveIndicator();
		}
		this.saveIndicator.textContent = '💾 Guardando...';
		this.saveIndicator.className = 'eipsi-save-indicator saving';
	}

	showSavedIndicator() {
		if (!this.saveIndicator) return;
		this.saveIndicator.textContent = '✓ Guardado';
		this.saveIndicator.className = 'eipsi-save-indicator saved';
		setTimeout(() => {
			if (this.saveIndicator) {
				this.saveIndicator.textContent = '';
				this.saveIndicator.className = 'eipsi-save-indicator';
			}
		}, 3000);
	}

	showSaveErrorIndicator() {
		if (!this.saveIndicator) return;
		this.saveIndicator.textContent = '⚠️ Sin conexión — guardado local';
		this.saveIndicator.className = 'eipsi-save-indicator error';
	}

	createSaveIndicator() {
		this.saveIndicator = document.createElement('span');
		this.saveIndicator.className = 'eipsi-save-indicator';
		this.saveIndicator.setAttribute('aria-live', 'polite');
		// Insertar antes del formulario o en un lugar visible
		if (this.form && this.form.parentNode) {
			this.form.parentNode.insertBefore(this.saveIndicator, this.form);
		}
	}
```

---

## PASO 6: Llamar a los indicadores en executeSave()

**Ubicación:** Dentro de `executeSave()`, antes de `await this.saveToIDB()`

**Agregar:**
```javascript
this.showSavingIndicator();
```

**Y después de `await this.saveToServer()`, agregar:**
```javascript
this.showSavedIndicator();
```

---

## PASO 7: Agregar CSS (archivo separado)

**Archivo:** `assets/css/eipsi-forms.css` (o crear si no existe)

**Agregar al final:**

```css
.eipsi-save-indicator {
	display: inline-block;
	font-size: 12px;
	color: #64748b;
	padding: 4px 8px;
	border-radius: 4px;
	transition: all 0.3s ease;
	min-height: 20px;
	margin-bottom: 8px;
}

.eipsi-save-indicator.saving {
	color: #3b82f6;
	background: #eff6ff;
}

.eipsi-save-indicator.saved {
	color: #22c55e;
	background: #f0fdf4;
}

.eipsi-save-indicator.error {
	color: #f59e0b;
	background: #fffbeb;
}
```

---

## VERIFICACIÓN RÁPIDA

Después de hacer los cambios, verificar:

1. **No hay errores de sintaxis** en consola del navegador
2. **Dirty tracking:** Modificar un campo y ver en Network que solo se envía ese campo
3. **Circuit breaker:** Desconectar internet, intentar guardar 6 veces, verificar que se muestra "Sin conexión"
4. **Indicador:** Debería verse 💾 → ✓ cuando se guarda

---

## RESUMEN DE CAMBIOS

| Fix | Qué se hizo | Líneas aprox |
|-----|-------------|--------------|
| 4 | Participant ID seguro | ✅ Ya está |
| 5 | Dirty tracking en collectResponses | ~1100 |
| 6 | Merge paralelo | ✅ Ya está |
| 7 | Circuit Breaker clase + uso | ~37, ~57, saveToServer |
| 9 | Indicadores visuales | Final de clase |

---

**Nota:** Si algo no funciona, revisar la consola del navegador (F12) para ver errores específicos.
