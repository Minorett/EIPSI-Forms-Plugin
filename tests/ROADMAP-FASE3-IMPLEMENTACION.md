# FASE 3 - Implementación Manual
## Fixes 7, 8 + Indicador Visual

---

## Fix 7: Circuit Breaker

### Paso 1: Agregar clase antes de EIPSISaveContinue (línea ~37)

```javascript
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

	onSuccess() { this.failures = 0; this.state = 'CLOSED'; }
	onFailure() {
		this.failures++;
		if (this.failures >= this.threshold) {
			this.state = 'OPEN';
			this.nextAttempt = Date.now() + this.timeout;
		}
	}
	isOpen() { return this.state === 'OPEN'; }
}
```

### Paso 2: En constructor agregar (línea ~55):
```javascript
this.circuitBreaker = new EipsiCircuitBreaker(5, 60000);
```

### Paso 3: Modificar saveToServer() (buscar el método):
```javascript
async saveToServer(responses, pageIndex) {
	if (this.circuitBreaker.isOpen()) {
		await this.saveToIDB(responses, pageIndex);
		this.showSaveErrorIndicator();
		return false;
	}
	return this.circuitBreaker.execute(async () => {
		// ... lógica actual de AJAX ...
	});
}
```

---

## Fix 8: Verificación IndexedDB

### Modificar openDB() (buscar el método existente):
```javascript
async openDB() {
	try {
		const db = await this.initIndexedDB();
		// Verificar integridad
		await db.transaction(['responses'], 'readonly')
				.objectStore('responses').get('test-key');
		return db;
	} catch (error) {
		console.warn('[EIPSI] IndexedDB corrupto, recreando...');
		try {
			await new Promise((resolve, reject) => {
				const deleteReq = indexedDB.deleteDatabase(this.dbName);
				deleteReq.onsuccess = resolve;
				deleteReq.onerror = reject;
			});
			return await this.initIndexedDB();
		} catch (resetError) {
			console.error('[EIPSI] No se pudo recuperar IndexedDB');
			return null;
		}
	}
}
```

---

## Indicador Visual

### Paso 1: Agregar métodos a la clase (al final, antes del cierre):
```javascript
showSavingIndicator() {
	const indicator = document.querySelector('.eipsi-save-indicator');
	if (indicator) {
		indicator.textContent = '💾 Guardando...';
		indicator.className = 'eipsi-save-indicator saving';
	}
}

showSavedIndicator() {
	const indicator = document.querySelector('.eipsi-save-indicator');
	if (indicator) {
		indicator.textContent = '✓ Guardado';
		indicator.className = 'eipsi-save-indicator saved';
		setTimeout(() => {
			indicator.textContent = '';
			indicator.className = 'eipsi-save-indicator';
		}, 3000);
	}
}

showSaveErrorIndicator() {
	const indicator = document.querySelector('.eipsi-save-indicator');
	if (indicator) {
		indicator.textContent = '⚠️ Sin conexión — guardado local';
		indicator.className = 'eipsi-save-indicator error';
	}
}
```

### Paso 2: CSS en assets/css/eipsi-forms.css:
```css
.eipsi-save-indicator {
	font-size: 12px;
	color: #64748b;
	transition: all 0.3s ease;
	min-height: 20px;
}
.eipsi-save-indicator.saving { color: #3b82f6; }
.eipsi-save-indicator.saved { color: #22c55e; }
.eipsi-save-indicator.error { color: #f59e0b; }
```

### Paso 3: HTML en template del formulario:
```html
<span class="eipsi-save-indicator" aria-live="polite"></span>
```

---

## Testing Fase 3

- [ ] 6 errores consecutivos de red → circuit breaker abre
- [ ] IndexedDB corrupto → se recrea automáticamente
- [ ] 💾 → ✓ → (vacío) en guardado exitoso
- [ ] ⚠️ en modo offline

---

**Estado:** Listo para implementación manual  
**Archivo objetivo:** `src/frontend/eipsi-save-continue.js`
