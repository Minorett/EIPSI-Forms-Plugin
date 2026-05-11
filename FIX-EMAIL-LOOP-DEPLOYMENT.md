# 🚨 Fix Urgente: Loop de Spam de Emails - Guía de Deployment

**Versión:** 2.6.1  
**Fecha:** Mayo 11, 2026  
**Prioridad:** CRÍTICA  
**Tiempo estimado:** 15 minutos  

---

## 📋 Resumen Ejecutivo

**Problema:** El sistema envía emails `wave_availability_T3` en loop infinito (1 por minuto) a los mismos participantes.

**Causa raíz:** El deduplicador usa un filtro obsoleto que no detecta:
- Tipos nuevos: `wave_availability_T1`, `wave_availability_T2`, etc. (v2.6.1+)
- Registros históricos con `email_type = ''` (migración ENUM→VARCHAR)

**Solución:** Query mejorada en `was_nudge_zero_already_sent()` + reparación de datos históricos.

**Impacto:** 
- ✅ Detiene el loop inmediatamente
- ✅ Previene futuros duplicados
- ✅ No requiere downtime
- ✅ Idempotente y reversible

---

## 🎯 Archivos Modificados

### 1. Código (1 archivo)
- `admin/services/class-wave-availability-email-service.php`
  - Método: `was_nudge_zero_already_sent()`
  - Líneas: ~193-266

### 2. Scripts SQL (3 archivos nuevos)
- `diagnostic-queries.sql` - Queries de diagnóstico pre-deployment
- `fix-email-loop-data-repair.sql` - Reparación de datos históricos
- `fix-email-loop-verification-tests.sql` - Tests post-deployment

---

## 📦 Checklist Pre-Deployment

- [ ] **Backup de DB** (tabla `wp_survey_email_log`)
- [ ] **Ejecutar queries de diagnóstico** (`diagnostic-queries.sql`)
- [ ] **Confirmar que el problema existe** (Query 2 debe mostrar `email_type = ''`)
- [ ] **Notificar al equipo** del deployment inminente
- [ ] **Tener acceso a error_log** para monitoreo en tiempo real

---

## 🚀 Procedimiento de Deployment

### **FASE 1: Deployment del Código (5 min)**

#### 1.1 - Subir archivo modificado
```bash
# Subir el archivo modificado al servidor
scp admin/services/class-wave-availability-email-service.php user@server:/path/to/wp-content/plugins/eipsi-forms/admin/services/
```

#### 1.2 - Verificar permisos
```bash
chmod 644 class-wave-availability-email-service.php
chown www-data:www-data class-wave-availability-email-service.php
```

#### 1.3 - Limpiar caché de PHP (si aplica)
```bash
# OPcache
php -r "opcache_reset();"

# O reiniciar PHP-FPM
systemctl restart php8.1-fpm
```

#### 1.4 - Verificar que el plugin carga sin errores
```bash
# Revisar error_log inmediatamente después del deployment
tail -f /var/log/php/error.log | grep EIPSI
```

**✅ Checkpoint 1:** No deben aparecer errores de sintaxis o fatal errors.

---

### **FASE 2: Monitoreo Inmediato (5 min)**

#### 2.1 - Observar el comportamiento del cron
```bash
# Monitorear logs en tiempo real
tail -f /var/log/php/error.log | grep "EIPSI WaveEmail"
```

**Esperado:**
- Mensajes de deduplicación: `"Nudge 0 ya enviado"`
- Mensajes de fallback: `"Deduplicación por fallback metadata"`
- **NO** deben aparecer nuevos emails enviados a `svekry@docbao7.com` o `arthurqueiroz79@wangdandan-w.cc`

#### 2.2 - Verificar en DB que el loop se detuvo
```sql
-- Ejecutar cada minuto durante 5 minutos
SELECT 
    recipient_email,
    COUNT(*) as emails_ultimo_minuto,
    MAX(sent_at) as ultimo_email
FROM wp_survey_email_log
WHERE recipient_email IN ('svekry@docbao7.com', 'arthurqueiroz79@wangdandan-w.cc')
AND sent_at >= DATE_SUB(NOW(), INTERVAL 1 MINUTE)
GROUP BY recipient_email;
```

**Esperado:** 0 emails nuevos después del deployment.

**✅ Checkpoint 2:** El loop debe haberse detenido completamente.

---

### **FASE 3: Reparación de Datos Históricos (5 min)**

#### 3.1 - Ejecutar query de verificación (SAFE)
```sql
-- Desde fix-email-loop-data-repair.sql - PASO 1
SELECT 
    id,
    participant_id,
    email_type,
    status,
    sent_at,
    JSON_UNQUOTE(JSON_EXTRACT(metadata, '$.wave_index')) as wave_index,
    JSON_UNQUOTE(JSON_EXTRACT(metadata, '$.wave_id')) as wave_id,
    JSON_UNQUOTE(JSON_EXTRACT(metadata, '$.nudge_stage')) as nudge_stage,
    recipient_email
FROM wp_survey_email_log
WHERE email_type = ''
AND metadata LIKE '%"nudge_stage":0%'
AND metadata LIKE '%"wave_index"%'
ORDER BY sent_at DESC;
```

**Acción:** Revisar el output. Debe mostrar registros con `email_type = ''` y `wave_index` válido.

#### 3.2 - Ejecutar UPDATE de reparación (DESTRUCTIVO)
```sql
-- Desde fix-email-loop-data-repair.sql - PASO 2
UPDATE wp_survey_email_log
SET email_type = CONCAT(
    'wave_availability_T',
    JSON_UNQUOTE(JSON_EXTRACT(metadata, '$.wave_index'))
)
WHERE email_type = ''
AND metadata LIKE '%"nudge_stage":0%'
AND metadata LIKE '%"wave_index"%'
AND JSON_EXTRACT(metadata, '$.wave_index') IS NOT NULL;
```

**Acción:** Ejecutar el UPDATE. Anotar cuántas filas fueron afectadas.

#### 3.3 - Verificar resultado
```sql
-- Desde fix-email-loop-data-repair.sql - PASO 4
SELECT COUNT(*) as registros_vacios_restantes
FROM wp_survey_email_log
WHERE email_type = ''
AND metadata LIKE '%"nudge_stage":0%';
```

**Esperado:** 0 registros vacíos restantes.

**✅ Checkpoint 3:** Datos históricos reparados correctamente.

---

### **FASE 4: Tests de Verificación (5 min)**

#### 4.1 - Ejecutar tests automatizados
Seguir las instrucciones en `fix-email-loop-verification-tests.sql`:
- Test 1: Deduplicación con tipo nuevo ✅
- Test 2: Deduplicación con email_type vacío ✅
- Test 3: Deduplicación con tipo legacy ✅
- Test 4: No deduplicar waves diferentes ✅
- Test 5: Verificar que el loop se detuvo ✅

#### 4.2 - Revisar logs de error
```bash
# Buscar mensajes de deduplicación
grep "EIPSI WaveEmail" /var/log/php/error.log | tail -20

# Buscar errores
grep "ERROR\|FATAL\|WARNING" /var/log/php/error.log | grep EIPSI | tail -20
```

**Esperado:**
- Mensajes de deduplicación exitosa
- **NO** errores de SQL, JSON, o excepciones

**✅ Checkpoint 4:** Todos los tests pasan.

---

## 🎉 Criterios de Éxito

El deployment es exitoso si:

1. ✅ **El loop se detuvo:** No hay emails nuevos a participantes afectados
2. ✅ **Deduplicación funciona:** Logs muestran mensajes de "Nudge 0 ya enviado"
3. ✅ **Datos reparados:** Query de verificación retorna 0 registros vacíos
4. ✅ **Sin errores:** No hay errores en error_log
5. ✅ **Tests pasan:** Todos los tests de verificación son exitosos

---

## 🔄 Rollback (Si algo sale mal)

### Opción 1: Rollback del código
```bash
# Restaurar versión anterior del archivo
git checkout HEAD~1 admin/services/class-wave-availability-email-service.php
scp admin/services/class-wave-availability-email-service.php user@server:/path/to/plugin/
systemctl restart php8.1-fpm
```

### Opción 2: Rollback de datos (si el UPDATE causó problemas)
```sql
-- Revertir el UPDATE de reparación
UPDATE wp_survey_email_log
SET email_type = ''
WHERE email_type LIKE 'wave_availability_T%'
AND sent_at < 'YYYY-MM-DD HH:MM:SS'; -- Timestamp antes del UPDATE
```

### Opción 3: Restaurar backup de DB
```bash
# Restaurar tabla desde backup
mysql -u user -p database_name < backup_survey_email_log.sql
```

---

## 📊 Monitoreo Post-Deployment (24 horas)

### Métricas a observar:

1. **Tasa de emails enviados**
   ```sql
   SELECT 
       DATE_FORMAT(sent_at, '%Y-%m-%d %H:00') as hora,
       COUNT(*) as emails_enviados
   FROM wp_survey_email_log
   WHERE email_type LIKE 'wave_availability%'
   AND sent_at >= DATE_SUB(NOW(), INTERVAL 24 HOUR)
   GROUP BY hora
   ORDER BY hora DESC;
   ```
   **Esperado:** Tasa estable, sin picos anormales

2. **Duplicados por participante**
   ```sql
   SELECT 
       participant_id,
       JSON_EXTRACT(metadata, '$.wave_id') as wave_id,
       COUNT(*) as total_emails
   FROM wp_survey_email_log
   WHERE email_type LIKE 'wave_availability%'
   AND sent_at >= DATE_SUB(NOW(), INTERVAL 24 HOUR)
   GROUP BY participant_id, wave_id
   HAVING total_emails > 1
   ORDER BY total_emails DESC;
   ```
   **Esperado:** 0 duplicados (o muy pocos por race conditions)

3. **Logs de deduplicación**
   ```bash
   grep "Deduplicación por fallback metadata" /var/log/php/error.log | wc -l
   ```
   **Esperado:** Número decreciente a medida que los datos históricos se limpian

---

## 📞 Contacto y Escalamiento

**Si el deployment falla:**
1. Ejecutar rollback inmediatamente
2. Capturar logs de error completos
3. Notificar al equipo técnico
4. Documentar el problema para análisis

**Si el loop continúa después del deployment:**
1. Verificar que el archivo fue subido correctamente
2. Verificar que el caché de PHP fue limpiado
3. Revisar error_log para excepciones
4. Ejecutar queries de diagnóstico nuevamente

---

## 📝 Notas Adicionales

- **Idempotencia:** El UPDATE de reparación es idempotente, se puede ejecutar múltiples veces
- **Reversibilidad:** Todos los cambios son reversibles con rollback
- **Sin downtime:** El deployment no requiere detener el sitio
- **Compatibilidad:** El fix es compatible con versiones anteriores
- **Performance:** La query mejorada es más eficiente (LIKE con índice)

---

## ✅ Checklist Final

- [ ] Código deployado correctamente
- [ ] Caché de PHP limpiado
- [ ] Loop detenido (verificado en DB)
- [ ] Datos históricos reparados
- [ ] Tests de verificación pasados
- [ ] Logs revisados (sin errores)
- [ ] Monitoreo configurado para 24h
- [ ] Equipo notificado del deployment exitoso
- [ ] Documentación actualizada

---

**Deployment completado por:** _______________  
**Fecha y hora:** _______________  
**Resultado:** ✅ Exitoso / ❌ Fallido / 🔄 Rollback ejecutado  
**Notas:** _______________________________________________
