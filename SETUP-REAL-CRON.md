# Configuración de Cron Real para EIPSI Forms

## Problema
WordPress Cron NO es un cron real. Solo se ejecuta cuando alguien visita el sitio.
Aunque hayas configurado `DISABLE_WP_CRON = true`, los cron jobs NO se ejecutan automáticamente.

## Solución: Configurar Cron Real del Sistema

### 1. Verificar que DISABLE_WP_CRON esté configurado

En `wp-config.php`, asegurate de tener:

```php
define('DISABLE_WP_CRON', true);
```

### 2. Configurar Cron del Sistema

#### Opción A: Linux/Mac (SSH o cPanel)

1. Abrí el crontab:
```bash
crontab -e
```

2. Agregá esta línea (ejecuta wp-cron.php cada minuto):
```bash
* * * * * curl -s https://tu-dominio.com/wp-cron.php?doing_wp_cron > /dev/null 2>&1
```

O si tenés acceso a PHP CLI:
```bash
* * * * * cd /ruta/a/wordpress && php wp-cron.php > /dev/null 2>&1
```

3. Guardá y salí (`:wq` en vim, o Ctrl+X en nano)

#### Opción B: Windows (Programador de Tareas)

1. Abrí "Programador de tareas" (Task Scheduler)
2. Crear tarea básica
3. Nombre: "WordPress Cron"
4. Desencadenador: Diariamente, repetir cada 1 minuto
5. Acción: Iniciar programa
   - Programa: `curl.exe` o `php.exe`
   - Argumentos: 
     - Para curl: `-s https://tu-dominio.com/wp-cron.php?doing_wp_cron`
     - Para PHP: `C:\ruta\a\wordpress\wp-cron.php`

#### Opción C: cPanel (Hosting compartido)

1. Ingresá a cPanel
2. Buscá "Cron Jobs" o "Tareas Cron"
3. Agregá un nuevo cron job:
   - Intervalo: Cada minuto (`* * * * *`)
   - Comando: 
     ```bash
     curl -s https://tu-dominio.com/wp-cron.php?doing_wp_cron > /dev/null 2>&1
     ```
   O:
     ```bash
     php /home/usuario/public_html/wp-cron.php > /dev/null 2>&1
     ```

#### Opción D: Servicio Externo (si no tenés acceso al servidor)

Si no tenés acceso al servidor, podés usar servicios gratuitos como:

1. **EasyCron** (https://www.easycron.com/)
   - Gratis hasta 100 ejecuciones/día
   - URL: `https://tu-dominio.com/wp-cron.php?doing_wp_cron`
   - Intervalo: Cada 1 minuto

2. **cron-job.org** (https://cron-job.org/)
   - Gratis, sin límites
   - URL: `https://tu-dominio.com/wp-cron.php?doing_wp_cron`
   - Intervalo: Cada 1 minuto

### 3. Verificar que funciona

Después de configurar el cron real:

1. Esperá 2-3 minutos
2. Ingresá al dashboard de EIPSI
3. Andá a la pestaña "Reminders" o "Monitoring"
4. Verificá que los cron jobs se estén ejecutando (deberías ver timestamps recientes)

### 4. Monitoreo de Cron Jobs

Para verificar que los cron jobs se ejecutan correctamente, podés:

1. Revisar los logs de WordPress en `wp-content/debug.log`
2. Buscar entradas como:
   ```
   [EIPSI Cron] Wave availability processor started at...
   [EIPSI Cron] Assignment expiration processor started at...
   ```

3. O usar el plugin "WP Crontrol" para ver los cron jobs programados y su última ejecución

## Cron Jobs de EIPSI Forms

Estos son los cron jobs que EIPSI Forms necesita ejecutar:

### Críticos (cada minuto):
- `eipsi_send_wave_reminders_hourly` - Envía nudges de waves
- `eipsi_process_assignment_expirations` - Marca assignments expirados
- `eipsi_process_wave_availability` - Envía emails de waves disponibles

### Importantes (cada hora):
- `eipsi_wave_skipping_cron` - Salta waves expiradas
- `eipsi_hourly_wave_expiration_check` - Verifica expiración de waves

### Mantenimiento (diario):
- `eipsi_weekly_t1_reminders_cron` - Envía recordatorios semanales T1
- `eipsi_cleanup_unconfirmed_participants_daily` - Limpia participantes no confirmados
- `eipsi_purge_access_logs_daily` - Limpia logs antiguos

## Troubleshooting

### Los emails no se envían
1. Verificá que el cron real esté ejecutándose (revisá logs)
2. Verificá que `DISABLE_WP_CRON` esté en `true`
3. Verificá que la URL del cron sea correcta (debe incluir `?doing_wp_cron`)

### El cron se ejecuta pero los emails no llegan
1. Problema de SMTP, no de cron
2. Revisá configuración de email en EIPSI Settings
3. Revisá logs de email en el dashboard

### Demasiados emails se envían
1. El cron se está ejecutando múltiples veces
2. Verificá que solo haya UNA entrada de cron en el sistema
3. Verificá que `DISABLE_WP_CRON` esté en `true`

## Notas Importantes

- **NO uses servicios de cron gratuitos para producción** - pueden ser poco confiables
- **Configurá el cron del servidor** siempre que sea posible
- **Monitoreá los logs** regularmente para detectar problemas
- **El intervalo de 1 minuto es necesario** para que los nudges funcionen correctamente
