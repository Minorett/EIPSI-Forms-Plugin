# 🔄 Actualización de Cron Jobs a Every Minute

## ⚠️ IMPORTANTE: Debes reactivar el plugin

Los cron jobs han sido actualizados de **every_5_minutes** → **every_minute** para soportar estudios con intervalos cortos (10, 20, 30 minutos).

---

## 📋 Pasos para aplicar el cambio:

### **Opción 1: Desde WordPress Admin (Recomendado)**

1. **Ve a:** `Plugins` → `Installed Plugins`
2. **Desactiva** el plugin "EIPSI Forms"
3. **Activa** nuevamente el plugin "EIPSI Forms"

✅ Los cron jobs se reprogramarán automáticamente.

---

### **Opción 2: Desde WP-CLI (Avanzado)**

```bash
wp plugin deactivate eipsi-forms
wp plugin activate eipsi-forms
```

---

### **Opción 3: Verificar manualmente los cron jobs**

```bash
wp cron event list
```

Deberías ver:
```
eipsi_send_wave_reminders_hourly       every_minute
eipsi_send_dropout_recovery_hourly     every_minute
eipsi_process_assignment_expirations   every_minute
eipsi_process_wave_availability        every_minute
```

Si todavía aparecen como `every_5_minutes`, ejecuta:

```bash
wp cron event delete eipsi_send_wave_reminders_hourly
wp cron event delete eipsi_send_dropout_recovery_hourly
wp cron event delete eipsi_process_assignment_expirations
wp cron event delete eipsi_process_wave_availability
```

Luego reactiva el plugin.

---

## 🎯 Beneficios del cambio:

### **Antes (every_5_minutes):**
- Nudge programado: `00:49:17`
- Ejecutado: `00:50:03` ❌ (46 segundos de retraso)
- Nudge programado: `00:53:29`
- Ejecutado: `00:55:02` ❌ (1.5 minutos de retraso)

**Retraso máximo:** ~2.5 minutos

### **Ahora (every_minute):**
- Nudge programado: `00:49:17`
- Ejecutado: `00:50:00` ✅ (43 segundos de retraso)
- Nudge programado: `00:53:29`
- Ejecutado: `00:54:00` ✅ (31 segundos de retraso)

**Retraso máximo:** ~60 segundos

---

## 📊 Impacto en estudios cortos:

| Intervalo | Retraso con 5 min | Retraso con 1 min | Mejora |
|-----------|-------------------|-------------------|--------|
| 10 min    | 15% del tiempo    | 5% del tiempo     | 3x mejor |
| 20 min    | 7.5% del tiempo   | 2.5% del tiempo   | 3x mejor |
| 30 min    | 5% del tiempo     | 1.7% del tiempo   | 3x mejor |

---

## ⚙️ Cron jobs afectados:

1. ✅ `eipsi_send_wave_reminders_hourly` → Emails de waves disponibles
2. ✅ `eipsi_send_dropout_recovery_hourly` → Emails de recuperación
3. ✅ `eipsi_process_assignment_expirations` → Expiración de assignments
4. ✅ `eipsi_process_wave_availability` → Notificaciones de disponibilidad

---

## 🔍 Verificar que funciona:

Después de reactivar, revisa los logs:

```bash
tail -f wp-content/debug.log | grep "EIPSI"
```

Deberías ver ejecuciones **cada minuto** en lugar de cada 5 minutos.

---

## 💡 Notas:

- El cambio **NO afecta** cron jobs diarios/semanales/mensuales
- Compatible con código legacy (mantiene `every_5_minutes` para otros usos)
- Optimizado para estudios longitudinales con intervalos cortos
