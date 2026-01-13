# 🎲 Cómo Usar el Sistema de Aleatorización RCT en EIPSI Forms

## 📋 ¿Qué es esto?

Un sistema completo para ejecutar **estudios con aleatorización** (Randomized Controlled Trials - RCTs) donde diferentes pacientes reciben diferentes formularios de forma aleatoria, pero **cada paciente siempre ve el mismo formulario asignado** sin importar cuántas veces recargue la página.

**Ejemplo:**
- Paciente A siempre ve "Evaluación de Estrés"
- Paciente B siempre ve "Evaluación de Burnout"
- Cada uno mantiene su asignación para siempre

---

## 🚀 GUÍA RÁPIDA (5 Pasos)

### PASO 1: Crear Configuración de Aleatorización

1. Ir a **Form Library** → Añadir nuevo
2. Escribir título: "Configuración RCT - Estudio Estrés"
3. Click en **[+]** para agregar bloque
4. Buscar: **"🎲 Configuración"**
5. Insertar el bloque

![Bloque de configuración](screenshot-bloque.png)

---

### PASO 2: Configurar Formularios

1. En el panel lateral derecho, activar: **"Activar Aleatorización"**
2. En el bloque, usar el dropdown para seleccionar formularios:
   - **Formulario 1:** Evaluación de Estrés → Click [➕ Añadir]
   - **Formulario 2:** Evaluación de Burnout → Click [➕ Añadir]
3. Los porcentajes se calculan automáticamente:
   - Evaluación de Estrés: **50%**
   - Evaluación de Burnout: **50%**
   - Total: **100%** ✓

![Configuración formularios](screenshot-formularios.png)

---

### PASO 3: Copiar Shortcode

1. En el bloque, aparece el shortcode generado:
   ```
   [eipsi_randomization id="rand_abc123xyz"]
   ```
2. Click en botón **"Copiar Shortcode"**
3. Aparece confirmación: ✓ Copiado
4. **Publicar** la configuración

![Shortcode generado](screenshot-shortcode.png)

---

### PASO 4: Usar en Página Real

1. Ir a **Páginas** → Añadir nueva
2. Título: "Estudio de Estrés Laboral"
3. Agregar texto introductorio (opcional):
   ```
   Bienvenido al estudio sobre estrés laboral.
   Por favor, complete el siguiente formulario.
   ```
4. Pegar el shortcode copiado:
   ```
   [eipsi_randomization id="rand_abc123xyz"]
   ```
5. **Publicar** la página

![Página con shortcode](screenshot-pagina.png)

---

### PASO 5: Compartir Link con Pacientes

1. Copiar el link de la página publicada:
   ```
   https://misite.com/estudio-estres-laboral
   ```
2. Compartir con los participantes del estudio

¡Listo! 🎉

---

## 🔄 ¿Cómo Funciona?

### Para el Paciente

1. **Primera visita:**
   - Paciente 1 accede al link
   - El sistema le asigna aleatoriamente: "Evaluación de Estrés"
   - Ve y completa ese formulario

2. **Visitas posteriores:**
   - Paciente 1 cierra el navegador
   - Vuelve al día siguiente
   - Abre el mismo link
   - **Ve el mismo formulario:** "Evaluación de Estrés"
   - ✓ Persistencia perfecta

3. **Otro paciente:**
   - Paciente 2 accede desde su dispositivo
   - El sistema le asigna: "Evaluación de Burnout"
   - Ve ese formulario
   - Siempre verá el mismo

---

## ⚙️ OPCIONES AVANZADAS

### Método de Aleatorización

En el panel lateral, podés elegir:

**1. Con seed reproducible (Recomendado)**
- Cada paciente siempre obtiene el mismo resultado
- Incluso si borrás la base de datos
- Ideal para estudios longitudinales

**2. Random puro**
- Completamente impredecible
- Ideal para estudios de una sesión

---

### Asignaciones Manuales

Si necesitás que un paciente específico reciba un formulario determinado:

1. En el bloque, sección **"Asignaciones Manuales"**
2. Ingresar email: `paciente@example.com`
3. Seleccionar formulario: "Evaluación de Estrés"
4. Click **[Añadir]**

**Uso:**
- Compartir link con query param:
  ```
  https://misite.com/estudio?email=paciente@example.com
  ```
- Ese paciente **siempre** verá "Evaluación de Estrés"
- Sobrescribe la aleatorización

**Caso de uso:** Asignar manualmente pacientes con condiciones específicas.

---

### Mostrar Instrucciones

En el panel lateral, activar **"Mostrar Instrucciones en Frontend"**

Muestra un aviso azul arriba del formulario:

> ℹ️ Este estudio utiliza aleatorización: cada participante recibe un formulario asignado aleatoriamente.
>
> Su asignación es persistente. En futuras sesiones recibirá el mismo formulario.

---

## 📊 TRACKING Y ANÁLISIS

### Ver Asignaciones

Las asignaciones se guardan en la base de datos:

**Tabla:** `wp_eipsi_randomization_assignments`

**Datos almacenados:**
- Cuándo se asignó cada paciente
- Qué formulario recibió
- Cuántas veces accedió
- Última vez que accedió

### Exportar Datos

*(Próximamente: Panel de Analytics en el Admin)*

Por ahora, podés acceder a la base de datos directamente:

```sql
SELECT * FROM wp_eipsi_randomization_assignments 
WHERE randomization_id = 'rand_abc123xyz'
ORDER BY assigned_at DESC;
```

---

## ❓ PREGUNTAS FRECUENTES

### ¿Qué pasa si el paciente borra las cookies?
✓ **No afecta.** El sistema usa fingerprinting del dispositivo, no cookies.

### ¿Qué pasa si el paciente cambia de navegador?
⚠️ **Cambia el fingerprint.** Se asignará como nuevo paciente.

### ¿Qué pasa si el paciente usa VPN?
✓ **No afecta.** El fingerprinting no depende solo de IP.

### ¿Qué pasa si presiono F5 (refrescar)?
✓ **Mismo formulario siempre.** Persistencia perfecta.

### ¿Puedo usar más de 2 formularios?
✓ **Sí.** Podés agregar 3, 4, 5, etc. Los porcentajes se calculan automáticamente.

### ¿Es anónimo?
✓ **Sí.** No se almacenan datos identificables a menos que uses asignaciones manuales por email.

### ¿Es compatible con GDPR?
✓ **Sí.** El fingerprint es un hash SHA-256 no reversible.

---

## 🐛 SOLUCIÓN DE PROBLEMAS

### El shortcode no funciona

**Problema:** Aparece el shortcode literal `[eipsi_randomization id="..."]` en la página.

**Solución:**
1. Verificar que el plugin EIPSI Forms esté activo
2. Verificar que hayas **publicado** la configuración
3. Recargar la página

---

### No aparece ningún formulario

**Problema:** La página está en blanco o muestra error.

**Solución:**
1. Verificar que los formularios existan en Form Library
2. Verificar que tengas al menos 2 formularios configurados
3. Ver logs en WordPress (Settings → Debugging)

---

### Los pacientes ven formularios diferentes cada vez

**Problema:** No hay persistencia.

**Solución:**
1. Verificar que las tablas de base de datos existan:
   - `wp_eipsi_randomization_configs`
   - `wp_eipsi_randomization_assignments`
2. Reactivar el plugin para crear las tablas
3. Verificar logs en error_log de PHP

---

## 📞 SOPORTE

Si necesitás ayuda:

- **Documentación técnica:** `docs/RCT-SYSTEM.md`
- **Instagram:** [@enmediodel.contexto](https://www.instagram.com/enmediodel.contexto/)
- **Sitio:** https://enmediodelcontexto.com.ar

---

## ✅ CHECKLIST DE VERIFICACIÓN

Antes de compartir con pacientes, verificar:

- [ ] Configuración creada y publicada
- [ ] Al menos 2 formularios agregados
- [ ] Porcentajes suman 100%
- [ ] Shortcode copiado correctamente
- [ ] Página con shortcode publicada
- [ ] Probaste el link tú mismo
- [ ] Refrescaste (F5) y viste el mismo formulario
- [ ] Probaste desde otro dispositivo/navegador

---

## 🎯 CASOS DE USO REALES

### Caso 1: Estudio de Intervención

**Objetivo:** Comparar eficacia de dos terapias.

**Configuración:**
- Formulario A: Terapia Cognitivo-Conductual
- Formulario B: Terapia de Aceptación y Compromiso
- Porcentajes: 50-50
- Método: Seeded

**Resultado:** 100 pacientes, 50 reciben TCC, 50 reciben ACT.

---

### Caso 2: Evaluación con Grupos Control

**Objetivo:** Evaluar efecto de un nuevo cuestionario.

**Configuración:**
- Formulario A: Cuestionario Nuevo
- Formulario B: Cuestionario Estándar (control)
- Porcentajes: 50-50

**Resultado:** Mitad completa nuevo, mitad completa estándar.

---

### Caso 3: Estudio con Grupo Placebo

**Objetivo:** Evaluar percepción de bienestar.

**Configuración:**
- Formulario A: Evaluación con video motivacional
- Formulario B: Evaluación sin video (placebo)
- Porcentajes: 60-40

**Resultado:** 60% ve video, 40% no ve.

---

**EIPSI Forms v1.3.1** - Sistema RCT Completo ✓

*«Por fin alguien entendió cómo trabajo de verdad con mis pacientes.»*
