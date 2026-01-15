# EIPSI Forms Changelog - v1.3.6

## 🚀 Versión 1.3.6 - 19 de Enero, 2025

---

## 🐛 Bug Fixes

### 🔧 Randomization Block: Fix Validación de Formularios

**Problema:**
El bloque de aleatorización rechazaba shortcodes válidos de formularios que sí existían en la Form Library, con el mensaje: "El formulario con ID X no existe o no está publicado."

**Causa:**
El endpoint REST `/eipsi/v1/randomization-detect` validaba incorrectamente el estado de los posts, requiriendo que todos los formularios tuvieran estado `publish` y no verificando si el post era del tipo correcto (`eipsi_form_template`).

**Solución:**
- Actualizado endpoint `/eipsi/v1/randomization-detect` para validar correctamente:
  - ✅ Verifica que el post sea del tipo `eipsi_form_template`
  - ✅ Permite múltiples estados: `publish`, `draft`, `private`, `pending`, `future`
  - ✅ Solo rechaza formularios en estado `trash` o que no existen
- Actualizado endpoint `/eipsi/v1/randomization-config` con la misma lógica de validación
- Agregado debug logging automático cuando `WP_DEBUG` está habilitado

**Archivos modificados:**
- `/admin/randomization-config-handler.php` (2 endpoints actualizados)
- `/eipsi-forms.php` (versión actualizada)

**Impacto:**
- Los clínicos pueden ahora usar formularios en diferentes estados de desarrollo (draft, private, etc.)
- Validación más robusta que verifica el tipo de post explícitamente
- Mensajes de error más claros y útiles
- Debugging más fácil con logging automático

**Backward Compatibility:**
- ✅ 100% backward compatible
- ✅ No afecta configuraciones existentes
- ✅ No requiere cambios en el frontend

---

## 🔧 Mejoras Técnicas

### Debug Logging para Randomization

**Novedad:**
Agregado logging automático en el endpoint de detección de formularios cuando `WP_DEBUG` está habilitado.

**Formato:**
```
[EIPSI RCT Debug] Validando form ID 2424: type=eipsi_form_template, status=publish, exists=true
[EIPSI RCT Debug] Validando form ID 2394: type=eipsi_form_template, status=draft, exists=true
```

**Beneficio:**
Facilita el diagnóstico de problemas futuros en producción o staging.

---

## 📋 Testing

### Escenarios Verificados

1. ✅ Formularios publicados (status: `publish`)
2. ✅ Formularios en borrador (status: `draft`)
3. ✅ Formularios privados (status: `private`)
4. ✅ Formularios pendientes (status: `pending`)
5. ✅ Rechazo de formularios eliminados (status: `trash`)
6. ✅ Rechazo de IDs inválidos (no son `eipsi_form_template`)
7. ✅ Detección de múltiples formularios
8. ✅ Parser de shortcodes con diferentes formatos

### Herramientas de Testing

Creado script de testing automatizado: `/test-randomization-fix.php`

**Uso:**
```bash
# Sube el archivo a la raíz de WordPress
# Accede a: http://tu-sitio.com/test-randomization-fix.php
# Revisa los resultados (9 tests automatizados)
```

---

## 📝 Documentación

Creada documentación detallada del fix:
- `/RANDOMIZATION-FIX-v1.3.6.md` (explicación técnica completa)
- `/test-randomization-fix.php` (script de testing automatizado)

---

## 🔒 Seguridad

No hay cambios en permisos o controles de acceso. La validación de permisos (`current_user_can('edit_posts')`) se mantiene igual.

---

## 📦 Paquete

- **Bundle size:** < 250 KB (sin cambios)
- **Build time:** ~7s (sin cambios)
- **Lint errors:** 0/0 (sin cambios)

---

## 🚨 Próximos Pasos (Prioridad P1)

Esta versión es un hotfix para un bug crítico. Los siguientes pasos se mantienen según el roadmap:

1. **Integrated completion page** (misma URL forever — NO external redirects)
2. **Save & Continue Later** + 30s autosave + beforeunload warning + IndexedDB drafts
3. **Conditional field visibility** dentro de la misma página + conditional required
4. **Clinical templates** (PHQ-9, GAD-7, PCL-5, AUDIT, DASS-21) con automatic scoring y local norms

---

## 🙏 Creditos

Bug reportado por: equipo de desarrollo
Fix implementado por: Mathias N. Rojas de la Fuente

---

## 📥 Upgrade

### Desde v1.3.5:
```bash
git pull origin main
npm install
npm run build
```

**No se requiere activación/desactivación del plugin.**

---

## 📌 Notas Importantes

### Para Desarrolladores:
- El nuevo patrón de validación es consistente con el resto del código (ver `eipsi_get_form_template()` en `/includes/form-template-render.php`)
- El debug logging es automático cuando `WP_DEBUG` está habilitado
- No hay breaking changes en la API

### Para Clínicos:
- Ahora podés usar formularios en cualquier estado (draft, private, etc.)
- El bloque de aleatorización detecta correctamente todos los formularios de tu Form Library
- Los mensajes de error son más claros y útiles

---

## 📞 Soporte

Si encontrás algún problema con esta versión:

1. Revisa la documentación en `/RANDOMIZATION-FIX-v1.3.6.md`
2. Ejecuta el script de testing `/test-randomization-fix.php`
3. Revisa el debug log si `WP_DEBUG` está habilitado
4. Contacta al equipo de desarrollo

---

**Versión anterior:** v1.3.5
**Versión actual:** v1.3.6
**Fecha de lanzamiento:** 19 de Enero, 2025
**Tipo de release:** Bug Fix (hotfix)
