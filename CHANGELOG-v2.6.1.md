# Changelog v2.6.1 - Email Type & Timezone Improvements

## 📧 Email Type Improvements

### Problema Anterior
- Los emails de `wave_availability` y nudges no indicaban a qué wave (T1, T2, T3, etc.) correspondían
- Difícil distinguir si un email duplicado era un error o correspondía a diferentes waves
- Algunos emails se guardaban sin `email_type` en la base de datos

### Solución Implementada
Los `email_type` ahora incluyen el índice de la wave:

**Formato Nuevo:**
- `wave_availability_T1` - Email de disponibilidad para T1
- `wave_availability_T2` - Email de disponibilidad para T2
- `nudge_1_T1` - Primer recordatorio de T1
- `nudge_2_T3` - Segundo recordatorio de T3
- etc.

**Beneficios:**
- ✅ Identificación clara de la wave en logs de email
- ✅ Mejor tracking de emails por wave específica
- ✅ Facilita debugging de emails duplicados
- ✅ Metadata enriquecida con `wave_id`, `wave_index`, `wave_name`, `nudge_stage`

### Archivos Modificados
1. **`admin/services/class-email-service.php`**
   - Líneas 561-576: Modificado `send_wave_reminder_email()` para incluir wave_index en email_type
   - Agrega metadata completa con info de la wave

2. **`admin/services/class-wave-availability-email-service.php`**
   - Líneas 437-448: Modificado para incluir wave_index en email_type
   - Agrega metadata con wave info

3. **`admin/database-schema-manager.php`**
   - Línea 597: Cambió `email_type` de ENUM a VARCHAR(100) para soportar tipos dinámicos

4. **`admin/js/email-log.js`**
   - Líneas 302-327: Actualizado `getTypeLabel()` para parsear tipos con wave (ej: "Disponibilidad (T1)")

5. **`admin/js/study-dashboard.js`**
   - Líneas 2805-2820: Agregada función `getEmailTypeLabel()` para parsear tipos con wave

---

## 🕐 Timezone Improvements

### Problema Anterior
- Los timestamps se mostraban en UTC en lugar del timezone configurado en WordPress
- Confusión para usuarios en diferentes timezones (ej: Argentina UTC-3)
- Inconsistencia entre horarios mostrados en admin y participante

### Solución Implementada
Todos los timestamps ahora respetan el timezone configurado en WordPress:

**Backend (PHP):**
- Usa `wp_date()` en lugar de `date()` o `date_i18n()`
- Formatea según `get_option('date_format')` y `get_option('time_format')`
- Envía timestamps pre-formateados al frontend

**Frontend (JavaScript):**
- Usa timestamps formateados del servidor (`sent_at_formatted`)
- Fallback a formateo local solo si no hay timestamp del servidor
- Consistencia en todos los tabs de admin y dashboard del participante

### Archivos Modificados
1. **`admin/services/class-email-service.php`**
   - Líneas 1144-1155: Ya usaba `wp_date()` correctamente (sin cambios)

2. **`admin/study-dashboard-api.php`**
   - Líneas 1173-1181: Actualizado para usar `wp_date()` con formato de WordPress

3. **`admin/js/email-log.js`**
   - Líneas 225-237: Usa `sent_at_formatted` del servidor en lugar de formateo local

4. **`admin/js/study-dashboard.js`**
   - Líneas 2287-2306: Actualizado `formatDateTime()` para priorizar timestamp del servidor
   - Línea 2835: Pasa `sent_at_formatted` a la función

5. **`includes/templates/dashboard/timeline-history.php`**
   - Ya usaba `date_i18n()` correctamente (sin cambios)

6. **`includes/templates/longitudinal-study-display.php`**
   - Ya usaba `date_i18n()` correctamente (sin cambios)

---

## 🔄 Migración de Base de Datos

### Cambio de Schema
```sql
-- Antes:
email_type ENUM('reminder', 'wave_availability', 'nudge_1', ...) DEFAULT 'custom'

-- Después:
email_type VARCHAR(100) DEFAULT 'custom'
```

### Instrucciones de Migración
1. El cambio de schema se aplicará automáticamente al activar el plugin
2. Los emails existentes mantendrán sus tipos originales (ej: `wave_availability`)
3. Los nuevos emails usarán el formato mejorado (ej: `wave_availability_T1`)
4. Ambos formatos son compatibles y se muestran correctamente en el frontend

**No se requiere acción manual del administrador.**

---

## 📊 Ejemplos de Visualización

### Email Log (Admin)
**Antes:**
```
Disponibilidad | 10/05/2026, 14:30:00 UTC
Disponibilidad | 10/05/2026, 14:35:00 UTC  ← ¿Duplicado o wave diferente?
```

**Después:**
```
Disponibilidad (T1) | 10 de mayo de 2026, 11:30 (hora local)
Disponibilidad (T2) | 10 de mayo de 2026, 11:35 (hora local)  ← Claramente waves diferentes
```

### Study Dashboard
**Antes:**
```
Recordatorio 1 | 2026-05-10 14:30:00  ← UTC, confuso
```

**Después:**
```
Recordatorio 1 (T2) | 10 de mayo de 2026, 11:30  ← Hora local, clara
```

---

## ✅ Testing Checklist

- [ ] Verificar que nuevos emails se guarden con formato `wave_availability_T1`
- [ ] Confirmar que emails antiguos se muestran correctamente
- [ ] Validar que timestamps se muestran en timezone de WordPress
- [ ] Revisar Email Log tab en admin
- [ ] Revisar Study Dashboard email logs
- [ ] Verificar timeline del participante
- [ ] Confirmar que no hay emails sin `email_type`

---

## 🐛 Fixes Adicionales

### Email Type Vacío
- **Causa:** Algunos emails se enviaban sin especificar `email_type`
- **Fix:** Todos los métodos de envío ahora especifican `email_type` obligatorio
- **Metadata:** Se agrega metadata completa para mejor tracking

---

## 📝 Notas para Desarrolladores

### Formato de Email Type
```php
// Formato: {base_type}_T{wave_index}
$email_type = 'wave_availability_T' . $wave_index;  // wave_availability_T1
$email_type = 'nudge_1_T' . $wave_index;            // nudge_1_T2
```

### Metadata Requerida
```php
$metadata = array(
    'wave_id' => $wave->id,
    'wave_index' => $wave_index,
    'wave_name' => $wave->name,
    'nudge_stage' => $stage,
    'base_type' => $base_type  // 'wave_availability' o 'nudge_1', etc.
);
```

### Timezone Handling
```php
// ✅ Correcto - Respeta timezone de WordPress
$formatted = wp_date(
    get_option('date_format') . ', ' . get_option('time_format'),
    $timestamp
);

// ❌ Incorrecto - Usa timezone del servidor
$formatted = date('Y-m-d H:i:s', $timestamp);
```

---

## 🔗 Referencias

- WordPress Timezone: https://developer.wordpress.org/reference/functions/wp_date/
- Email Service: `admin/services/class-email-service.php`
- Wave Availability Service: `admin/services/class-wave-availability-email-service.php`
- Database Schema: `admin/database-schema-manager.php`
