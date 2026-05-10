# 🚫 Guía: Excluir Dashboard de Participantes del Cache

## ⚠️ **Problema**

WP Fastest Cache (u otros plugins de cache) pueden cachear el dashboard del participante, causando:

- ❌ Dashboard muestra información desactualizada
- ❌ Auto-refresh no funciona correctamente
- ❌ Countdown no se actualiza
- ❌ Cambios de estado no se reflejan

---

## ✅ **Solución: Excluir URLs del Cache**

### **Opción 1: WP Fastest Cache (Recomendado)**

1. **Ir a**: `WordPress Admin → WP Fastest Cache → Exclude`

2. **Agregar reglas de exclusión**:

   | Type | Content | Example |
   |------|---------|---------|
   | **Page** | `estudio-*` | Excluye todas las páginas de estudios |
   | **Page** | `dashboard-participante` | Excluye dashboard específico |
   | **Page** | `mi-estudio` | Excluye página de login/dashboard |

3. **Configuración recomendada**:
   ```
   Exclude Page:
   - estudio-*
   - dashboard-participante
   - participante-*
   
   Exclude User-Agent:
   - (dejar vacío)
   
   Exclude Cookies:
   - eipsi_participant_session
   - eipsi_auth_token
   ```

4. **Guardar cambios**

5. **Limpiar cache**: `WP Fastest Cache → Delete Cache → Delete Cache`

---

### **Opción 2: Excluir por Cookie (Más Preciso)**

Si los participantes tienen una cookie de sesión, podés excluir basándote en eso:

1. **Ir a**: `WP Fastest Cache → Exclude → Cookies`

2. **Agregar**:
   ```
   eipsi_participant_session
   eipsi_auth_token
   wordpress_logged_in_
   ```

3. **Resultado**: Cualquier usuario con estas cookies NO verá contenido cacheado

---

### **Opción 3: Código (Avanzado)**

Si querés control total, agregá esto a `functions.php` o al plugin:

```php
/**
 * Disable cache for EIPSI participant pages
 */
add_action('template_redirect', 'eipsi_disable_cache_for_participants');
function eipsi_disable_cache_for_participants() {
    // Check if current page has EIPSI shortcodes
    global $post;
    
    if (!is_a($post, 'WP_Post')) {
        return;
    }
    
    $has_eipsi_content = 
        has_shortcode($post->post_content, 'eipsi_longitudinal_study') ||
        has_shortcode($post->post_content, 'eipsi_participant_dashboard') ||
        has_shortcode($post->post_content, 'eipsi_form') ||
        has_shortcode($post->post_content, 'eipsi_survey_form');
    
    if ($has_eipsi_content) {
        // Disable WP Fastest Cache
        if (function_exists('wpfc_exclude_current_page')) {
            wpfc_exclude_current_page();
        }
        
        // Disable other cache plugins
        if (!defined('DONOTCACHEPAGE')) {
            define('DONOTCACHEPAGE', true);
        }
        
        // Set headers to prevent caching
        header('Cache-Control: no-cache, no-store, must-revalidate');
        header('Pragma: no-cache');
        header('Expires: 0');
    }
}
```

---

## 🧪 **Verificar que Funciona**

### **Test 1: Ver Headers HTTP**

1. Abrir DevTools (F12) → Network
2. Cargar página del dashboard
3. Click en la request principal (HTML)
4. Ver **Response Headers**

**Resultado esperado**:
```
Cache-Control: no-cache, no-store, must-revalidate
Pragma: no-cache
X-Cache: BYPASS (o similar)
```

**Resultado NO deseado**:
```
Cache-Control: max-age=3600
X-Cache: HIT
```

### **Test 2: Cambio de Estado**

1. Abrir dashboard de un participante
2. En phpMyAdmin, cambiar status de una wave
3. Esperar 30 segundos (polling)
4. **Resultado esperado**: Auto-refresh detecta cambio
5. **Si NO funciona**: Cache está interfiriendo

---

## 🔧 **Configuración Recomendada para WP Fastest Cache**

### **Settings → Exclude**

```
┌─────────────────────────────────────────────────────┐
│ Exclude Pages                                       │
├─────────────────────────────────────────────────────┤
│ ☑ is equal to: estudio-testing                     │
│ ☑ starts with: estudio-                            │
│ ☑ starts with: participante-                       │
│ ☑ contains: dashboard                              │
└─────────────────────────────────────────────────────┘

┌─────────────────────────────────────────────────────┐
│ Exclude Cookies                                     │
├─────────────────────────────────────────────────────┤
│ ☑ eipsi_participant_session                        │
│ ☑ eipsi_auth_token                                 │
│ ☑ wordpress_logged_in_                             │
└─────────────────────────────────────────────────────┘

┌─────────────────────────────────────────────────────┐
│ Don't Cache Pages for Logged-in Users              │
├─────────────────────────────────────────────────────┤
│ ☑ Enable (si los participantes están "logged in")  │
└─────────────────────────────────────────────────────┘
```

---

## 📊 **Comparación de Métodos**

| Método | Pros | Contras | Recomendado |
|--------|------|---------|-------------|
| **Excluir por URL** | Simple, fácil de configurar | Debes conocer todas las URLs | ✅ Sí (para empezar) |
| **Excluir por Cookie** | Más preciso, solo afecta participantes | Requiere que tengan cookies | ✅✅ Sí (mejor opción) |
| **Código PHP** | Control total, automático | Requiere conocimiento técnico | ⚠️ Solo si necesario |
| **Desactivar cache** | Garantiza que funciona | Afecta rendimiento de todo el sitio | ❌ No recomendado |

---

## ⚡ **Impacto en Rendimiento**

### **Sin Cache en Dashboard**

- ✅ **Positivo**: Dashboard siempre actualizado
- ✅ **Positivo**: Auto-refresh funciona correctamente
- ⚠️ **Neutral**: Carga del servidor aumenta ligeramente
- ✅ **Aceptable**: Solo afecta páginas de participantes (bajo tráfico)

### **Con Cache en Dashboard**

- ❌ **Negativo**: Dashboard puede estar desactualizado
- ❌ **Negativo**: Auto-refresh no funciona
- ❌ **Negativo**: Participantes confundidos
- ✅ **Positivo**: Menor carga del servidor

**Conclusión**: Vale la pena desactivar cache en dashboards de participantes.

---

## 🎯 **Configuración Específica para tu Sitio**

Basándome en tu URL de ejemplo: `https://palevioletred-goat-519138.hostingersite.com/estudio-testing_2026/`

### **Reglas de Exclusión**

```
Exclude Page (starts with):
- estudio-
- participante-

Exclude Page (contains):
- _2026
- dashboard

Exclude Page (is equal to):
- estudio-testing_2026
```

Esto excluirá:
- ✅ `estudio-testing_2026`
- ✅ `estudio-cualquier-nombre`
- ✅ `participante-dashboard`
- ✅ Cualquier página con "dashboard" en la URL

---

## 📝 **Checklist de Implementación**

- [ ] Ir a WP Fastest Cache → Exclude
- [ ] Agregar reglas para excluir páginas de estudios
- [ ] Agregar reglas para excluir por cookies (si aplica)
- [ ] Guardar configuración
- [ ] Limpiar cache completo
- [ ] Probar: Abrir dashboard, cambiar estado en DB, verificar auto-refresh
- [ ] Verificar headers HTTP (no-cache)
- [ ] Confirmar que auto-refresh funciona

---

## 🐛 **Troubleshooting**

### **Problema: Auto-refresh sigue sin funcionar**

1. **Verificar que cache está excluido**:
   - Abrir DevTools → Network
   - Ver headers: `Cache-Control: no-cache`

2. **Limpiar cache del navegador**:
   - Ctrl+Shift+Delete
   - Borrar cache del navegador

3. **Verificar que JavaScript se carga**:
   - DevTools → Console
   - Buscar: `[EIPSI Auto-Refresh] Initializing...`

4. **Verificar que polling funciona**:
   - DevTools → Network → XHR
   - Buscar requests a: `admin-ajax.php?action=eipsi_check_wave_state`

### **Problema: Cache se sigue aplicando**

1. **Verificar orden de plugins**:
   - Algunos cache plugins ignoran exclusiones de otros
   - Desactivar temporalmente otros cache plugins

2. **Verificar cache del servidor**:
   - Hostinger puede tener cache a nivel servidor
   - Ir a Hostinger panel → Cache → Limpiar

3. **Verificar CDN**:
   - Si usás Cloudflare u otro CDN
   - Configurar Page Rules para excluir URLs

---

## ✅ **Resultado Final Esperado**

Después de configurar correctamente:

1. ✅ Dashboard del participante NO se cachea
2. ✅ Auto-refresh funciona cada 30 segundos
3. ✅ Countdown se actualiza en tiempo real
4. ✅ Cambios de estado se reflejan inmediatamente
5. ✅ Resto del sitio sigue cacheado (buen rendimiento)

---

## 📞 **Soporte**

Si después de seguir esta guía el auto-refresh sigue sin funcionar:

1. Verificar logs en DevTools Console
2. Verificar logs en `wp-content/debug.log`
3. Probar desactivando WP Fastest Cache temporalmente
4. Verificar que el JavaScript se está cargando correctamente
