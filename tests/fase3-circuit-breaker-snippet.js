/**
 * FASE 3 - Fixes 7 y 8
 * Circuit Breaker y verificación IndexedDB
 */

// Fix 7: Clase CircuitBreaker (agregar antes de EIPSISaveContinue)
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
				throw new Error('Circuit OPEN — servidor no disponible');
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
			console.warn('[EIPSI] Circuit breaker abierto — demasiados errores de red');
		}
	}

	isOpen() { return this.state === 'OPEN'; }
}

// En constructor de EIPSISaveContinue agregar:
// this.circuitBreaker = new EipsiCircuitBreaker(5, 60000);

// Modificar saveToServer para usar circuit breaker:
async saveToServer(responses, pageIndex) {
	if (this.circuitBreaker.isOpen()) {
		// Guardar solo en IndexedDB mientras servidor no disponible
		await this.saveToIDB(responses, pageIndex);
		this.showSaveErrorIndicator();
		return false;
	}

	return this.circuitBreaker.execute(async () => {
		// Lógica actual de saveToServer aquí...
	});
}

// Fix 8: Verificación de integridad de IndexedDB en openDB()
async openDB() {
	try {
		const db = await this.initIndexedDB();
		// Verificar integridad haciendo una lectura simple
		await db.transaction(['responses'], 'readonly')
				.objectStore('responses')
				.get('test-key');
		return db;
	} catch (error) {
		console.warn('[EIPSI] IndexedDB corrupto, recreando...', error);
		try {
			// Eliminar DB corrupta y recrear
			await new Promise((resolve, reject) => {
				const deleteReq = indexedDB.deleteDatabase(this.dbName);
				deleteReq.onsuccess = resolve;
				deleteReq.onerror = reject;
			});
			return await this.initIndexedDB();
		} catch (resetError) {
			console.error('[EIPSI] No se pudo recuperar IndexedDB:', resetError);
			return null; // Fallback a solo servidor
		}
	}
}

// Indicador visual - métodos a agregar a EIPSISaveContinue:
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
