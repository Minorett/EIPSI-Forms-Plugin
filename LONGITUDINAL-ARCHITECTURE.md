# EIPSI Forms - Longitudinal Architecture (v1.4.2+)

## 1. OVERVIEW

EIPSI Forms habilita un flujo longitudinal completo, pensado para psicólogxs y psiquiatras que trabajan por tomas (T1, T2, T3) y necesitan cero fricción para participantes:

**Signup → Waves → Export → Anonymize**

- **Signup:** alta de participantes (email + datos mínimos) y generación de magic link.
- **Waves:** tomas con fechas, estados y recordatorios automatizados.
- **Export:** filtros clínicos por survey, wave y estado.
- **Anonymize:** proceso auditado con hash de identifiers y borrado de PII.

---

## 2. CORE ENTITIES & SCHEMAS

Tablas principales (todas con prefijo `wp_`):

- `wp_survey_participants`
- `wp_survey_waves`
- `wp_survey_responses`
- `wp_survey_sessions`
- `wp_survey_email_log`
- `wp_survey_audit_log`

### ER Diagram (simplificado)

```
wp_survey_participants (id)
  ├─< wp_survey_sessions (participant_id)
  ├─< wp_survey_responses (participant_id)
  ├─< wp_survey_email_log (participant_id)
  └─< wp_survey_audit_log (participant_id)

wp_survey_waves (id)
  └─< wp_survey_responses (wave_id)

wp_survey_responses
  ├─ participant_id → wp_survey_participants.id
  └─ wave_id → wp_survey_waves.id
```

---

## 3. AUTHENTICATION FLOW

Magic Links + Sessions:

- **Token generation:** 30 min TTL
- **Session creation:** 7 días TTL
- **Rate limiting:** 5 intentos / 15 min
- **Security headers:** HttpOnly, Secure, SameSite

Flujo:

1. Email con magic link
2. Click del participante
3. Validación del token (TTL + one-time)
4. Creación de sesión con cookie segura

---

## 4. EMAIL SERVICE (v1.4.1+)

Templates:
- `includes/emails/welcome.php`
- `includes/emails/wave-reminder.php`
- `includes/emails/wave-confirmation.php`
- `includes/emails/dropout-recovery.php`

Methods:
- `send_welcome_email()`
- `send_wave_reminder_email()`
- `send_wave_confirmation_email()`
- `send_dropout_recovery_email()`
- `log_email()`

Rate limiting: **2 emails/min max**

---

## 5. CRON JOBS

Jobs:
- `eipsi_cron_wave_reminders`: enviá recordatorios pendientes
- `eipsi_cron_session_cleanup`: limpia sesiones expiradas
- `eipsi_cron_email_retry`: reintenta emails fallidos
- `eipsi_cron_dropout_recovery`: recupera participantes en riesgo

Schedule: **hourly**

---

## 6. EXPORT SYSTEM (v1.4.2+)

Filtros:
- `survey_id`
- `wave_index` (T1, T2, T3...)
- `date_range`
- `status` (Completed, Pending, Late)

Formatos:
- Excel (.xlsx)
- CSV (.csv)

Stats:
- `completion_rates`
- `avg_response_times`

---

## 7. MONITORING & OBSERVABILITY (v1.4.2+)

Dashboard con 5 cards:
- Email stats (sent, failed, bounce rate, pending)
- Cron jobs (status, last run)
- Sessions (active, expired, unused)
- Database (size, connection)
- Audit log (últimas 10 acciones)

Auto-refresh: **30 segundos**

---

## 8. ANONYMIZATION (v1.5.0+)

Process:
1. Check survey state (closed)
2. Hash `participant_id`
3. Clear sensitive data
4. Log action en audit
5. Mark `as_anonymized = true`

Audit trail:
- `user_id`
- `timestamp`
- `old_value` (encrypted)
- `new_value`

---

## 9. SECURITY

Implemented:
- SQL Injection: prepared statements
- XSS: output escaping
- CSRF: nonce verification
- Brute force: rate limiting
- Session hijacking: secure cookies
- SQL indices: optimized queries

---

## 10. PERFORMANCE

Optimizations:
- Database indices en `survey_participants`, `survey_responses`
- Query caching (transients)
- Lazy loading de responses
- Batch email processing

---

## 11. TESTING

Tests incluidos:
- 6 manual test scenarios
- 32+ PHPUnit unit tests
- Coverage: 100% de servicios críticos

---

## 12. DEPLOYMENT CHECKLIST

Pre-production:
- [ ] npm run lint (sin errores)
- [ ] npm run build (exitoso)
- [ ] phpunit (32/32 tests pasan)
- [ ] Monitoring tab: visible y funcional
- [ ] Cron jobs configurados
- [ ] Email templates probadas
- [ ] Export funciona (Excel + CSV)
- [ ] Database backed up

---

## DIAGRAMA DE FLUJO

```
Investigador
   ↓
Setup Survey + Waves (admin)
   ↓
Invite Participants (emails)
   ↓
Cron: envía magic links
   ↓
Participante recibe email → click en link
   ↓
Auth Service: valida token → crea session
   ↓
Frontend: show form (T1)
   ↓
Participante completa + submit
   ↓
Response guardada + session activa
   ↓
(7 días después)
   ↓
Cron: envía wave reminder (T2)
   ↓
Participante completa T2
   ↓
(7 días después)
   ↓
Cron: envía wave reminder (T3)
   ↓
Participante completa T3
   ↓
Estudio cerrado (admin action)
   ↓
Investigador exporta datos (Excel/CSV)
   ↓
Investigador anónimiza responses
   ↓
participant_id = NULL (audit log grabado)
```
