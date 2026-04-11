# Sistema de Email de Disponibilidad de Wave (Nudge 0) v2.2.0

## Flujo de Verificación Automática

```
┌─────────────────────────────────────────────────────────────┐
│  CRON JOB: eipsi_send_wave_reminders_hourly()               │
└──────────────────┬──────────────────────────────────────────┘
                   │
                   ▼
┌─────────────────────────────────────────────────────────────┐
│  Paso 1: ¿La toma está disponible?                          │
│  - Verificar: now >= last_submission_date + interval         │
└────────────┬──────────────────────────────────────────────────┘
             │
        ┌────┴────┐
        │         │
       NO        SÍ
        │         │
        ▼         ▼
   [SKIP]    ┌──────────────────────────────────────┐
             │ Paso 2: ¿Se envió Nudge 0 ya?        │
             │ - Buscar en email_log:               │
             │   participant_id + wave_id +         │
             │   email_type='reminder' + nudge=0    │
             └────────┬───────────────────────────────┘
                      │
                 ┌────┴────┐
                 │         │
                NO        SÍ (ya enviado)
                 │         │
                 ▼         ▼
            ┌────────┐  [SKIP - No duplicar]
            │ Paso 3 │  Esperar siguientes nudges
            │ ¿Max   │  (1-4) según config
            │ retries?│
            └────┬───┘
                 │
            ┌────┴────┐
            │         │
           NO        SÍ (3 intentos)
            │         │
            ▼         ▼
       ┌────────┐  [ERROR - Max retries]
       │Paso 4  │  Incrementar reminder_count
       │Enviar  │  para detener reintentos
       │email   │
       └───┬────┘
           │
      ┌────┴────┐
      │         │
   Fallo     Éxito
      │         │
      ▼         ▼
┌─────────┐ ┌──────────┐
│Registrar│ │ ✓ ÉXITO  │
│intento  │ │ Marcar   │
│+ error  │ │ como     │
│en log   │ │ enviado  │
│Reintentar│ │ (7 días) │
en próximo│ └──────────┘
cron      │
(cooldown)│
5 minutos │
└─────────┘
```

## Archivos Modificados/Creados

### NUEVO: `class-wave-availability-email-service.php`
Servicio robusto con:
- ✅ Verificación de disponibilidad de wave
- ✅ Verificación de duplicados (no enviar 2 veces)
- ✅ Sistema de reintentos (max 3 intentos)
- ✅ Cooldown entre reintentos (5 minutos)
- ✅ Logging detallado de cada paso
- ✅ Marcado automático de éxito/fracaso

### NUEVO: `wave-available.php` 
Template HTML para email de disponibilidad

### MODIFICADO: `cron-reminders-handler.php`
- Agregado soporte para Nudge 0 (reminder_count = 0)
- Integrado nuevo servicio robusto
- Mantiene lógica existente para Nudges 1-4

### MODIFICADO: `class-nudge-service.php`
- Corregido: Nudge 0 siempre retorna true
- Agregado casting a int para evitar problemas de tipo

## Logs Esperados

```
[EIPSI Cron] Starting hourly wave reminders...
[EIPSI Cron] Processing study 39...
[EIPSI Cron] Nudge 0 assignments ready for email: 2
[EIPSI Cron] Nudge 0 READY: participant=80, wave=Post-intervención...
[EIPSI WaveEmail] === INICIO VERIFICACIÓN === participant=80, wave=112
[EIPSI WaveEmail] Paso 1 - ¿Toma disponible? SÍ
[EIPSI WaveEmail] Paso 2 - ¿Nudge 0 ya enviado? NO
[EIPSI WaveEmail] Paso 3 - Estado de reintentos: intentos=0
[EIPSI WaveEmail] Paso 4 - Intentando enviar Nudge 0 (intento #1)...
[EIPSI WaveEmail] Enviando email (intento #1)...
[EIPSI WaveEmail] ✓ ÉXITO: Email enviado correctamente. Log ID: 123
[EIPSI WaveEmail] Nudge 0 marcado como enviado.
[EIPSI Cron] Wave Availability Email Service result: {"success":true,"sent":true...}
```

## Reintentos Automáticos

| Intento | Cuándo | Acción si falla |
|---------|--------|-----------------|
| 1 | Inmediato (cron actual) | Registrar error, esperar 5 min |
| 2 | Próximo cron (+5 min) | Registrar error, esperar 5 min |
| 3 | Próximo cron (+5 min) | **Max retries reached** → incrementar reminder_count |

## Prevención de Duplicados

- **Transiente**: `eipsi_wave_email_retries_{participant_id}_{wave_id}`
- **Duración**: 7 días después de envío exitoso
- **Efecto**: Bloquea reenvíos durante 7 días

## Rate Limiting

- **Transiente**: `eipsi_reminder_{participant_id}_{wave_id}_0`
- **Duración**: 24 horas
- **Efecto**: Prevenir envío duplicado en el mismo cron
