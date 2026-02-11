# PHASE-6-TESTING-MANUAL

Suite completa de tests manuales para validar la integración longitudinal de EIPSI Forms antes de producción.

---

## 📋 Escenarios de Prueba Manual

### Scenario 1: Flujo Completo (Registro → Login → Waves → Export → Anonimización)

**Objetivo:** Validar el flujo completo de un estudio longitudinal con 3 waves.

**Pasos:**
1. **Admin:** Configuración → Crear encuesta "Test-Longitudinal"
2. **Agregar 3 waves:** T1, T2, T3 con 7 días de intervalo
3. **Participantes:** Importar 5 emails de participantes
4. **Cron:** Ejecutar cron para enviar magic links
5. **Participante:**
   - Hacer click en magic link
   - Completar formulario T1
   - Validar que la sesión se crea en `wp_survey_sessions`
6. **Esperar cron T2:** Validar que se envía recordatorio
7. **Completar T2 y T3:** Participante completa todas las waves
8. **Dashboard:** Exportar datos y validar integridad
9. **Anonimizar:** Verificar que `participant_id` se establece a NULL
10. **Validar:** Respuestas mantienen `wave_id` y datos íntegros

**Validaciones:**
- ✅ Magic link válido solo por 30 minutos
- ✅ Sesión persiste 7 días (TTL)
- ✅ Emails enviados a participantes correctos
- ✅ Porcentajes de completitud de waves correctos
- ✅ Export contiene datos íntegros
- ✅ Anonimización limpia (sin rastros de PII)

---

### Scenario 2: Magic Links (Generación, Entrega, Expiración, Invalidación)

**Objetivo:** Validar el ciclo de vida de los magic links.

**Pasos:**
1. **Crear participante P1** en un estudio
2. **Dashboard:** "Send Magic Link" → email enviado
3. **Capturar magic link** del email
4. **Click en link:** Login exitoso y redirección a formulario
5. **Validar sesión:** `wp_survey_sessions` creada
6. **Intentar reusar link:** Debe fallar con "Invalid or expired"
7. **Generar nuevo link** para T2
8. **Esperar 30 minutos:** Link expira
9. **Intentar usar link expirado:** Debe fallar
10. **Generar link nuevo:** Debe funcionar

**Validaciones:**
- ✅ Formato del link correcto (hash base64)
- ✅ Email enviado inmediatamente
- ✅ Link usado → sesión creada
- ✅ Link reutilizado → rechazado
- ✅ Link expirado → rechazado después de 30 minutos
- ✅ Token en DB: `usado_en`, `expirado_en` guardados

---

### Scenario 3: Cron Jobs (Rate Limiting, Recordatorios, Recuperación de Dropouts)

**Objetivo:** Validar el comportamiento de los cron jobs.

**Pasos:**
1. **Setup:** 5 participantes pendientes de T2
2. **Ejecutar cron:** `wp eipsi-forms wave-reminders`
3. **Monitorear:** Tabla `wp_survey_email_log`
4. **Validar:** Máximo 2 emails/minuto (rate limiting)
5. **Esperar 60 segundos:** Otros 2 emails enviados
6. **Log:** `status='sent'`, `error_message=NULL`
7. **Dropout:** Participante 1 no responde T2
8. **Después de 7 días:** Cron `dropout-recovery` se ejecuta
9. **Email "Te extrañamos"** enviado
10. **Validar:** `metadata.recovery_attempt=1`

**Validaciones:**
- ✅ Rate limiting: máximo 2 emails/minuto respetado
- ✅ Emails en log con timestamp correcto
- ✅ Dropout recovery gatillado después de 7 días
- ✅ Intento de recuperación registrado
- ✅ Cron idempotente (sin duplicados)

---

### Scenario 4: Recuperación de Dropouts (Wave Perdida, Reintento, Exclusión Final)

**Objetivo:** Validar el flujo de recuperación y exclusión de participantes.

**Pasos:**
1. **T2 deadline:** 2025-02-15
2. **P1 no responde** hasta 2025-02-22 (7+ días)
3. **Cron ejecuta:** Email de recuperación enviado
4. **P1 responde:** Completa T2 tarde
5. **Validar:** `is_active=1` (aún en estudio)
6. **P2 no responde T3:** Deadline final 2025-02-28
7. **Después de 7 días:** Auto-exclusión (`is_active=0`)
8. **Dashboard:** Muestra "Excluded (T3 missed)"

**Validaciones:**
- ✅ Email de recuperación enviado exactamente 7 días post-deadline
- ✅ Respuestas tardías aceptadas
- ✅ Email de exclusión final enviado
- ✅ Participante marcado como inactivo
- ✅ Reporte muestra razón de exclusión

---

### Scenario 5: Exportación (Filtros, Integridad de Datos, Formatos)

**Objetivo:** Validar la exportación de datos en diferentes formatos.

**Pasos:**
1. **Dashboard:** Results → Export tab
2. **Seleccionar estudio:** "Test-Longitudinal"
3. **Filtros:**
   - Waves: T1, T2
   - Fecha: 1-15 Febrero 2025
   - Estado: "Completed"
4. **Descargar Excel:** Validar estructura
5. **Validar:**
   - Encabezados correctos
   - Datos corresponden a filtros
   - Formato de tiempos (ej: `00:15:30`)
   - Encoding UTF-8
6. **Descargar CSV:** Validar mismo contenido
7. **Abrir en Calc + Excel:** Datos idénticos

**Validaciones:**
- ✅ Filtros funcionales (lógica AND)
- ✅ Excel: headers, datos y formatos correctos
- ✅ CSV: encoding UTF-8, delimitadores correctos
- ✅ Datos no corruptos (checksum validado)
- ✅ Performance: 500+ filas en <5 segundos

---

### Scenario 6: Gestión de Sesiones (Persistencia, Expiración, Limpieza)

**Objetivo:** Validar el manejo de sesiones de participantes.

**Pasos:**
1. **Login con magic link:** `session_token` guardado en cookie
2. **Validar DB:** `wp_survey_sessions.token` existe, `expires_at = now + 7 días`
3. **Cerrar navegador:** Reabrir → cookie aún presente
4. **Recargar formulario:** Sesión válida
5. **Modificar cookie:** Token inválido → "Invalid session"
6. **Crear nueva sesión:** Funciona correctamente
7. **Esperar 7 días:** Intentar submit → "Session expired"
8. **Cron cleanup:** `wp eipsi-forms session-cleanup`
9. **Validar:** Sesiones antiguas removidas de DB

**Validaciones:**
- ✅ Sesión persiste en DB (no solo en memoria)
- ✅ TTL=7 días respetado
- ✅ Sesiones expiradas rechazadas
- ✅ Cookie flags: `HttpOnly=true`, `Secure=true` (HTTPS)
- ✅ Cron cleanup remueve sesiones expiradas

---

## 📊 Criterios de Aceptación

### Tests Manuales:
- [ ] 6 escenarios completamente documentados
- [ ] Cada escenario: pasos, validaciones, resultados esperados
- [ ] Todos los escenarios ejecutados sin errores
- [ ] Evidencia (screenshots/logs) opcional pero recomendada

### Tests Automatizados (PHPUnit):
- [ ] Suite creada (4 archivos de test)
- [ ] 32+ tests unitarios implementados
- [ ] 100% coverage de métodos críticos
- [ ] Todos los tests pasan: `phpunit` → 32/32 ✅
- [ ] `npm run lint` → sin errores

### Integración:
- [ ] Tests corren en CI/CD (si aplica)
- [ ] README.md actualizado con instrucciones de testing

---

## 🔄 Flujo de Implementación

1. **Crear documento** `PHASE-6-TESTING-MANUAL.md` con 6 escenarios detallados
2. **Configurar PHPUnit:** `phpunit.xml`
3. **Crear tests automatizados:**
   - `tests/test-participant-service.php` (6 tests)
   - `tests/test-auth-service.php` (7 tests)
   - `tests/test-wave-service.php` (6 tests)
   - `tests/test-email-service.php` (7 tests)
4. **Ejecutar PHPUnit:** Todos los tests deben pasar
5. **Validar linting:** `npm run lint` sin errores
6. **Documentar:** Actualizar README.md con "Running Tests"

---

## 📝 Notas Técnicas

### Testing Manual:
- Ejecutar en staging o entorno local con datos de prueba
- Validar compatibilidad cross-browser (Chrome, Firefox, Safari)
- Documentar cualquier issue encontrado

### PHPUnit Setup:
- Basado en WordPress Test Suite
- Usar mocks donde sea necesario
- Tests transaccionales (rollback automático)
- Aislamiento entre tests

### Coverage Target:
- `EIPSI_Participant_Service`: 100%
- `EIPSI_Auth_Service`: 100%
- `EIPSI_Wave_Service`: 100%
- `EIPSI_Email_Service`: 95% (mock de `wp_mail`)

---

## 🎯 Resultado Final

✅ **Confianza total en:**
- Flujo longitudinal completo (registro → T1 → T2 → T3 → anonimización)
- Magic links y gestión de sesiones
- Cron jobs y recuperación de dropouts
- Exportación con integridad de datos
- Performance bajo carga

**Listo para PRODUCCIÓN** 🚀